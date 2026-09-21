<?php

namespace App\Http\Controllers\Settings;

use App\Mail\PasswordChange;
use App\Models\AccountLog;
use App\Models\EmailVerification;
use App\Models\Media;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\EmailService;
use App\Services\EmailVerificationService;
use App\Services\PronounService;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\PrettyNumber;
use App\Util\Localization\Localization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Purify;

trait HomeSettings
{
    public function home(Request $request)
    {
        $id = $request->user()->profile_id;
        $storage = [];
        $used = Media::whereProfileId($id)->sum('size');
        $storage['limit'] = config_cache('pixelfed.max_account_size') * 1024;
        $storage['used'] = $used;
        $storage['percentUsed'] = ceil($storage['used'] / $storage['limit'] * 100);
        $storage['limitPretty'] = PrettyNumber::size($storage['limit']);
        $storage['usedPretty'] = PrettyNumber::size($storage['used']);
        $pronouns = PronounService::get($id);

        return view('settings.home', ['storage' => $storage, 'pronouns' => $pronouns]);
    }

    public function homeUpdate(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
            'bio' => 'nullable|string|max:'.config('pixelfed.max_bio_length'),
            'website' => 'nullable|url',
            'language' => 'nullable|string|min:2|max:12',
            'pronouns' => 'nullable|array|max:4',
        ]);

        $changes = false;
        $name = strip_tags(Purify::clean($request->input('name')));
        $bio = $request->filled('bio') ? strip_tags(Purify::clean($request->input('bio'))) : null;
        $website = $request->input('website');
        $language = $request->input('language');
        $user = $request->user();
        $profile = $user->profile;
        $pronouns = $request->input('pronouns');
        $existingPronouns = PronounService::get($profile->id);
        $layout = $request->input('profile_layout');
        if ($layout) {
            $layout = ! in_array($layout, ['metro', 'moment']) ? 'metro' : $layout;
        }

        $enforceEmailVerification = config_cache('pixelfed.enforce_email_verification');

        // Only allow email to be updated if not yet verified
        if (! $enforceEmailVerification || $user->email_verified_at) {
            if ($profile->name != $name) {
                $changes = true;
                $user->name = $name;
                $profile->name = $name;
            }

            if ($profile->website != $website) {
                $changes = true;
                $profile->website = $website;
            }

            if (strip_tags($profile->bio) != $bio) {
                $changes = true;
                $profile->bio = Autolink::create()->autolink($bio);
            }

            if ($user->language != $language &&
                in_array($language, Localization::languages())
            ) {
                $changes = true;
                $user->language = $language;
                session()->put('locale', $language);
            }

            if ($existingPronouns != $pronouns) {
                if ($pronouns && in_array('Select Pronoun(s)', $pronouns)) {
                    PronounService::clear($profile->id);
                } else {
                    PronounService::put($profile->id, $pronouns);
                }
            }
        } else {
            return redirect('/settings/home')->with('status', 'Verify your email address before you can update your profile!');
        }

        if ($changes === true) {
            $user->save();
            $profile->save();
            Cache::forget('user:account:id:'.$user->id);
            AccountService::forgetAccountSettings($profile->id);
            AccountService::del($profile->id);

            return redirect('/settings/home')->with('status', 'Profile successfully updated!');
        }

        return redirect('/settings/home');
    }

    public function password()
    {
        return view('settings.password');
    }

    public function passwordUpdate(Request $request)
    {
        $this->validate($request, [
            'current' => 'required|string',
            'password' => 'required|string|confirmed|min:8|different:current',
            'revoke_sessions' => 'nullable|boolean',
        ]);

        $current = $request->input('current');
        $new = $request->input('password');
        $revokeSessions = $request->boolean('revoke_sessions');

        $user = $request->user();

        if (! password_verify($current, $user->password)) {
            return redirect()->back()->with('error', 'There was an error with your request! Please try again.');
        }

        $user->password = bcrypt($new);
        $user->save();

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = User::class;
        $log->action = 'account.edit.password';
        $log->message = $revokeSessions
            ? 'Password changed and all sessions revoked'
            : 'Password changed';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();

        Mail::to($request->user())->send(new PasswordChange($user));

        if ($revokeSessions) {
            $user->tokens->each(function ($token) {
                $token->revoke();
                $token->refreshToken?->revoke();
            });

            Auth::logoutOtherDevices($new);
        }

        return redirect('/settings/home')->with('status', 'Password successfully updated!');
    }

    public function email()
    {
        $profileId = Auth::user()->profile_id;
        $settings = AccountService::getAccountSettings($profileId) ?? [];

        return view('settings.email', [
            'settings' => [
                'send_email_new_follower' => (bool) ($settings['send_email_new_follower'] ?? false),
                'send_email_new_follower_request' => (bool) ($settings['send_email_new_follower_request'] ?? false),
                'send_email_on_share' => (bool) ($settings['send_email_on_share'] ?? false),
                'send_email_on_like' => (bool) ($settings['send_email_on_like'] ?? false),
                'send_email_on_mention' => (bool) ($settings['send_email_on_mention'] ?? false),
                'send_weekly_email' => (bool) ($settings['send_weekly_email'] ?? false),
                'felipemateus_wants_updates' => (bool) ($settings['felipemateus_wants_updates'] ?? false),
            ],
        ]);
    }

    public function emailUpdate(Request $request)
    {
        $this->validate($request, [
            // Ignore the user's own row so an unchanged (pre-filled) submission
            // is a no-op; collisions with other accounts still fail.
            'email' => [
                'required',
                // Do not reject existing accounts whose legacy address no
                // longer has DNS. Delivery verification and the banned-domain
                // check below remain the authoritative safety controls.
                'email:rfc',
                'unique:users,email,'.$request->user()->id,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (EmailService::isBanned($value)) {
                        $fail('Email is invalid.');
                    }
                },
            ],
        ]);
        $changes = false;
        $email = $request->input('email');
        $user = $request->user();
        $profile = $user->profile;

        $validate = config_cache('pixelfed.enforce_email_verification');

        if ($user->email != $email) {
            $changes = true;
            $user->email = $email;

            if ($validate) {
                // auto verify admin email addresses
                $user->email_verified_at = $user->is_admin == true ? now() : null;
                // Prevent old verifications from working
                EmailVerification::whereUserId($user->id)->delete();
            }

            $log = new AccountLog;
            $log->user_id = $user->id;
            $log->item_id = $user->id;
            $log->item_type = User::class;
            $log->action = 'account.edit.email';
            $log->message = 'Email changed';
            $log->link = null;
            $log->ip_address = $request->ip();
            $log->user_agent = $request->userAgent();
            $log->save();
        }

        if ($changes === true) {
            Cache::forget('user:account:id:'.$user->id);
            $user->save();
            $profile->save();

            if ($validate && is_null($user->email_verified_at)) {
                EmailVerificationService::send($user);
            }

            return redirect('/settings/email')->with('status', 'Email successfully updated!');
        }

        return redirect('/settings/email');

    }

    public function emailConfigUpdate(Request $request)
    {
        $fields = [
            'send_email_new_follower',
            'send_email_new_follower_request',
            'send_email_on_share',
            'send_email_on_like',
            'send_email_on_mention',
            'send_weekly_email',
            'felipemateus_wants_updates',
        ];

        $request->validate(collect($fields)->mapWithKeys(fn (string $field) => [$field => ['sometimes', 'boolean']])->all());

        $user = $request->user();
        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
        foreach ($fields as $field) {
            $settings->{$field} = $request->boolean($field);
        }
        $settings->save();
        Cache::forget(AccountService::CACHE_PF_ACCT_SETTINGS_KEY.$user->profile_id);

        return redirect('/settings/email')->with('status', 'Email preferences updated.');
    }

    public function emailVerificationResend(Request $request)
    {
        $user = $request->user();

        if (! is_null($user->email_verified_at)) {
            return redirect('/settings/email');
        }

        if (! EmailVerificationService::send($user)) {
            return redirect('/settings/email')->withErrors([
                'email' => __('A verification email was sent a moment ago. Check your inbox, then try again in a minute.'),
            ]);
        }

        return redirect('/settings/email')->with('status', __('Verification email sent to').' '.$user->email);
    }

    public function avatar()
    {
        return view('settings.avatar');
    }
}
