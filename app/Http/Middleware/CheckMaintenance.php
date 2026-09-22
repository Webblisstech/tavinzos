<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site-wide maintenance mode. When site.maintenance is on, customers see a
 * maintenance page. Admins pass through so they can keep working, and the
 * admin panel, login and logout routes are never blocked.
 */
class CheckMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->on()) {
            return $next($request);
        }

        // Never gate the admin area or auth routes — staff must get in to
        // turn it back off.
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'up')) {
            return $next($request);
        }

        // Signed-in admins bypass maintenance entirely.
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        // Everyone else gets the notice (503 so crawlers know it's temporary).
        return response()->view('maintenance', [], 503);
    }

    private function on(): bool
    {
        $all = Cache::remember('settings', 300, function () {
            try {
                return DB::table('settings')->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                        'int' => (int) $r->value, 'float' => (float) $r->value,
                        'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                    }])->all();
            } catch (\Throwable) { return []; }
        });

        return (bool) ($all['site.maintenance'] ?? false);
    }
}