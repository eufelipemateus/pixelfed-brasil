<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DangerZone
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only OIDC-registered users have a random unknown password and cannot complete sudo-mode password confirmation.
        if (config('remote-auth.oidc.enabled') && $request->user() && $request->user()->register_source === 'oidc') {
            return $next($request);
        }

        if ($request->session()->get('sudoModeAttempts') > 3) {
            // Invalidate the whole session
            Auth::logout();
            $request->session()->invalidate();

            return redirect(route('login'));
        }
        if (! $request->user()) {
            return redirect(route('login'));
        }
        $passwordTimeout = (int) config('auth.password_timeout', 1800);
        $confirmedAt = max(
            (int) $request->session()->get('sudoMode', 0),
            (int) $request->session()->get('auth.password_confirmed_at', 0),
        );
        $passwordConfirmed = $request->session()->get('sudoTrustDevice') == 1
            || $confirmedAt >= now()->subSeconds($passwordTimeout)->timestamp;

        if (! $request->is('i/auth/sudo') && ! $passwordConfirmed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Password confirmation required.'], 423);
            }

            $request->session()->put('redirectNext', $request->url());

            return redirect('/i/auth/sudo');
        }

        return $next($request);
    }
}
