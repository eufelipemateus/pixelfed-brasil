<?php

namespace App\Services;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class SessionService
{
    const CACHE_KEY = 'pf:services:session:active_sessions';

    public static function getActiveSessions()
    {
        $activeSessions = Cache::get(self::CACHE_KEY, []);
        $activeSessions = array_filter(
            $activeSessions, function ($session) {
                return $session['last_seen'] > now()->subMinutes(
                    config('instance.limit_users_active.user_session_timeout')
                )->timestamp;
            }
        );
        Cache::put(self::CACHE_KEY, $activeSessions, now()->addMinutes(config('instance.limit_users_active.user_session_timeout')));
        return $activeSessions;
    }

    public static function setActiveSession($sessionId, $userId)
    {
        $activeSessions = self::getActiveSessions();
        $activeSessions[$sessionId] = [
            'user_id' => $userId,
            'last_seen' => now()->timestamp,
        ];
        Cache::put(self::CACHE_KEY, $activeSessions, now()->addMinutes(config('instance.limit_users_active.user_session_timeout')));
    }

    public static function removeActiveSession($sessionId)
    {
        $activeSessions = self::getActiveSessions();
        unset($activeSessions[$sessionId]);
        Cache::put(self::CACHE_KEY, $activeSessions, now()->addMinutes(config('instance.limit_users_active.user_session_timeout')));
    }
    public static function getTotalActiveSessions()
    {
        $activeSessions = self::getActiveSessions();
        return count($activeSessions);
    }

}
