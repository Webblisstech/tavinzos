<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends signed-in but unverified customers to the code-verification page.
 * The verify + resend + logout routes stay reachable so they can complete it.
 */
class EnsureEmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->email_verified_at === null
            && ! $request->routeIs('verification.*')
            && ! $request->routeIs('logout')) {
            return redirect()->route('verification.code');
        }

        return $next($request);
    }
}