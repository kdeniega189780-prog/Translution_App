<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user->active || $request->session()->get('session_version') !== $user->session_version) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->with('status', 'Please sign in again.');
        }
        if ($user->must_change_password && ! $request->routeIs('profile.*', 'logout')) {
            return to_route('profile.edit')->with('status', 'Please change your temporary password before continuing.');
        }

        return $next($request);
    }
}
