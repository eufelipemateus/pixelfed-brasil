<?php

namespace App\Models;

use App\Casts\StatusEnumCast;
use App\Enums\StatusEnums;
use App\Services\AvatarService;
use App\Util\RateLimit\User as UserRateLimit;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes, UserRateLimit;

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'deleted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            '2fa_setup_at' => 'datetime',
            'last_active_at' => 'datetime',
            'storage_used_updated_at' => 'datetime',
            'status' => StatusEnumCast::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->refer_code) {
                return;
            }

            do {
                $code = strtoupper(Str::random(6));
            } while (User::where('refer_code', $code)->exists());

            $user->refer_code = $code;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email',
        'password',
        'is_admin',
        'remember_token',
        'email_verified_at',
        '2fa_enabled',
        '2fa_secret',
        '2fa_backup_codes',
        '2fa_setup_at',
        'deleted_at',
        'updated_at',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function url()
    {
        return url(config('app.url').'/'.$this->username);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function statuses()
    {
        return $this->hasManyThrough(
            Status::class,
            Profile::class
        );
    }

    public function filters()
    {
        return $this->hasMany(UserFilter::class, 'user_id', 'profile_id');
    }

    public function receivesBroadcastNotificationsOn()
    {
        return 'App.User.'.$this->id;
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function storageUsedKey()
    {
        return 'profile:storage:used:'.$this->id;
    }

    public function accountLog()
    {
        return $this->hasMany(AccountLog::class);
    }

    public function interstitials()
    {
        return $this->hasMany(AccountInterstitial::class);
    }

    public function avatarUrl()
    {
        if (! $this->profile_id || $this->status !== StatusEnums::ACTIVE) {
            return config('app.url').'/storage/avatars/default.jpg';
        }

        return AvatarService::get($this->profile_id);
    }

    public function routeNotificationForExpo()
    {
        return $this->expo_token;
    }

    #[Scope]
    protected function whereActive(Builder $query): void
    {
        $query->whereNull('status');
    }

    public function enable(): void
    {
        if ($this->status === StatusEnums::DISABLED) {
            $this->status = StatusEnums::ACTIVE;
            $this->save();
        }
    }

    public function disable(): void
    {
        if ($this->status === StatusEnums::ACTIVE) {
            $this->status = StatusEnums::DISABLED;
            $this->save();
        }
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function inviteLink()
    {
        return route('register', ['ref' => $this->refer_code]);
    }
}
