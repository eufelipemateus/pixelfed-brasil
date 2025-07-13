<?php

namespace App\Providers;

use App\Avatar;
use App\Follower;
use App\HashtagFollow;
use App\Like;
use App\ModLog;
use App\Notification;
use App\Observers\AvatarObserver;
use App\Observers\FollowerObserver;
use App\Observers\HashtagFollowObserver;
use App\Observers\LikeObserver;
use App\Observers\ModLogObserver;
use App\Observers\NotificationObserver;
use App\Observers\ProfileObserver;
use App\Observers\StatusHashtagObserver;
use App\Observers\StatusObserver;
use App\Observers\UserFilterObserver;
use App\Observers\UserObserver;
use App\Profile;
use App\Services\AccountService;
use App\Services\UserOidcService;
use App\Status;
use App\StatusHashtag;
use App\User;
use App\UserFilter;
use Auth;
use Horizon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;
use Illuminate\Http\Request;
use URL;
use App\Util\ActivityPub\Inbox;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('instance.force_https_urls', true)) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
        Avatar::observe(AvatarObserver::class);
        Follower::observe(FollowerObserver::class);
        HashtagFollow::observe(HashtagFollowObserver::class);
        Like::observe(LikeObserver::class);
        Notification::observe(NotificationObserver::class);
        ModLog::observe(ModLogObserver::class);
        Profile::observe(ProfileObserver::class);
        StatusHashtag::observe(StatusHashtagObserver::class);
        User::observe(UserObserver::class);
        Status::observe(StatusObserver::class);
        UserFilter::observe(UserFilterObserver::class);
        Horizon::auth(function ($request) {
            return Auth::check() && $request->user()->is_admin;
        });
        Validator::includeUnvalidatedArrayKeys();

        Gate::define('viewPulse', function (User $user) {
            return $user->is_admin === 1;
        });

        if (config('pulse.enabled', false)) {
            Pulse::user(function ($user) {
                $acct = AccountService::get($user->profile_id, true);

                return $acct ? [
                    'name' => $acct['username'],
                    'extra' => $user->email,
                    'avatar' => $acct['avatar'],
                ] : [
                    'name' => $user->username,
                    'extra' => 'DELETED',
                    'avatar' => '/storage/avatars/default.jpg',
                ];
            });
        }

        RateLimiter::for('app-signup', function (Request $request) {
            return Limit::perDay(100)->by($request->ip());
        });

        RateLimiter::for('app-code-verify', function (Request $request) {
            return Limit::perHour(20)->by($request->ip());
        });

        RateLimiter::for('app-code-resend', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        Inbox::registerHandlers([
            'Add' => \App\Util\ActivityPub\Handlers\AddHandler::class,
            'Create' => \App\Util\ActivityPub\Handlers\CreateHandler::class,
            'Follow' => \App\Util\ActivityPub\Handlers\FollowHandler::class,
            'Announce' => \App\Util\ActivityPub\Handlers\AnnounceHandler::class,
            'Accept' => \App\Util\ActivityPub\Handlers\AcceptHandler::class,
            'Delete' => \App\Util\ActivityPub\Handlers\DeleteHandler::class,
            'Like' => \App\Util\ActivityPub\Handlers\LikeHandler::class,
            'Reject' => \App\Util\ActivityPub\Handlers\RejectHandler::class,
            'Story:Reaction' => \App\Util\ActivityPub\Handlers\StoryReactionHandler::class,
            'Story:Reply' => \App\Util\ActivityPub\Handlers\StoryReplyHandler::class,
            'Flag' => \App\Util\ActivityPub\Handlers\FlagHandler::class,
            'Update' => \App\Util\ActivityPub\Handlers\UpdateHandler::class,
            'Move' => \App\Util\ActivityPub\Handlers\MoveHandler::class,
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserOidcService::class, function () {
            return UserOidcService::build();
        });
    }
}
