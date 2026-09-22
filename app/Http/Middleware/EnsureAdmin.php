<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate every admin route on the 'admin' guard.
 *
 * A signed-in customer has no session on this guard, so they are bounced to
 * the admin login — never shown the panel. An inactive admin is logged out.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        if (! $admin->is_active) {
            auth('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'This admin account is disabled.']);
        }

        return $next($request);
    }
}