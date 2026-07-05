<?php

namespace App\Http\Middleware;

use App\Services\LoginLockdown;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceLoginLockdown
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!LoginLockdown::isActive()) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user && !LoginLockdown::canBypass($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('lockdown', 'Login is temporarily disabled for system maintenance. Please try again later.');
        }

        return $next($request);
    }
}
