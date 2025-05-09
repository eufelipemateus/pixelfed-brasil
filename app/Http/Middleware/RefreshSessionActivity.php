<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use App\Services\SessionService;

class RefreshSessionActivity
{
    public function handle($request, Closure $next)
    {
        if (config('instance.limit_users_active.enabled')) {

            if (auth()->check()) {
                $sessionId = Session::getId();
                $activeSessions = SessionService::getActiveSessions();

                if (!isset($activeSessions[$sessionId]) && count($activeSessions) >= config('instance.limit_users_active.max_users_active')) {
                    if ($request->routeIs('waiting-room')) {
                        return $next($request);
                    }
                    return redirect()->route('waiting-room');
                }

                SessionService::setActiveSession($sessionId, auth()->id());

                if ($request->routeIs('waiting-room') ) {
                    return redirect()->to(route('home'));
                }
            }
        } else {
            if ($request->routeIs('waiting-room')) {
                return redirect()->to(route('home'));
            }
        }

        return $next($request);
    }
}
