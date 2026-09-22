<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin dashboard: a platform-wide snapshot — revenue, customers, orders,
 * a 14-day trend, top services, low stock, and recent activity.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $today = today();

        // ── Revenue: kept spend across both product lines ────────────
        $numRevenue = (float) DB::table('verifications')->where('status', '!=', 'cancelled')->sum('price');
        $logRevenue = (float) DB::table('log_orders')->where('status', 'delivered')->sum('total');
        $revenueTotal = $numRevenue + $logRevenue;

        $todayRevenue = (float) DB::table('verifications')->where('status', '!=', 'cancelled')->whereDate('created_at', $today)->sum('price')
            + (float) DB::table('log_orders')->where('status', 'delivered')->whereDate('created_at', $today)->sum('total');

        $monthRevenue = (float) DB::table('verifications')->where('status', '!=', 'cancelled')->where('created_at', '>=', $today->copy()->startOfMonth())->sum('price')
            + (float) DB::table('log_orders')->where('status', 'delivered')->where('created_at', '>=', $today->copy()->startOfMonth())->sum('total');

        // ── Headline tiles ───────────────────────────────────────────
        $stats = [
            'revenue'       => $this->money($revenueTotal),
            'revenue_today' => $this->money($todayRevenue),
            'revenue_month' => $this->money($monthRevenue),
            'held'          => $this->money((float) DB::table('users')->sum('wallet_balance')),
            'customers'     => DB::table('users')->count(),
            'customers_new' => DB::table('users')->whereDate('created_at', $today)->count(),
            'orders'        => DB::table('verifications')->count() + DB::table('log_orders')->count(),
            'orders_today'  => DB::table('verifications')->whereDate('created_at', $today)->count()
                              + DB::table('log_orders')->whereDate('created_at', $today)->count(),
            'topups_today'  => $this->money((float) DB::table('wallet_transactions')
                                ->where('type', 'topup')
                                ->when($this->hasStatus(), fn ($q) => $q->where('status', 'settled'))
                                ->whereDate('created_at', $today)->sum('amount')),
            'waiting'       => DB::table('verifications')->where('status', 'waiting')->count(),
            'suspended'     => DB::table('users')->whereNotNull('suspended_at')->count(),
        ];

        // ── 14-day revenue trend ─────────────────────────────────────
        $days = collect(range(13, 0))->map(function ($d) use ($today) {
            $date = $today->copy()->subDays($d);
            $rev = (float) DB::table('verifications')->where('status', '!=', 'cancelled')->whereDate('created_at', $date)->sum('price')
                 + (float) DB::table('log_orders')->where('status', 'delivered')->whereDate('created_at', $date)->sum('total');
            return ['label' => $date->format('j M'), 'value' => round($rev, 2)];
        });
        $trendMax = max(1, (float) $days->max('value'));

        // ── Top services (numbers) ───────────────────────────────────
        $topServices = DB::table('verifications')
            ->where('status', '!=', 'cancelled')
            ->select('service', DB::raw('COUNT(*) as orders'), DB::raw('SUM(price) as revenue'))
            ->groupBy('service')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'name'    => $s->service ?: 'Unknown',
                'orders'  => (int) $s->orders,
                'revenue' => $this->money((float) $s->revenue),
            ]);

        // ── Low stock (account products) ─────────────────────────────
        $lowStock = DB::table('log_products')
            ->leftJoin('log_items', function ($j) {
                $j->on('log_items.log_product_id', '=', 'log_products.id')->where('log_items.status', 'available');
            })
            ->select('log_products.name', 'log_products.slug', DB::raw('COUNT(log_items.id) as stock'))
            ->groupBy('log_products.id', 'log_products.name', 'log_products.slug')
            ->having('stock', '<=', 3)
            ->orderBy('stock')
            ->limit(5)
            ->get();

        // ── Recent orders (both lines, newest first) ─────────────────
        $recentNumbers = DB::table('verifications')
            ->leftJoin('users', 'users.id', '=', 'verifications.user_id')
            ->orderByDesc('verifications.id')->limit(6)
            ->get(['verifications.reference', 'verifications.service', 'verifications.price', 'verifications.status', 'verifications.created_at', 'users.name as user'])
            ->map(fn ($r) => ['kind' => 'number', 'title' => $r->service ?: 'Number', 'user' => $r->user ?: '—', 'amount' => $this->money((float) $r->price), 'status' => ucfirst($r->status), 'at' => $r->created_at]);

        $recentAccounts = DB::table('log_orders')
            ->leftJoin('users', 'users.id', '=', 'log_orders.user_id')
            ->orderByDesc('log_orders.id')->limit(6)
            ->get(['log_orders.reference', 'log_orders.product_name', 'log_orders.total', 'log_orders.status', 'log_orders.created_at', 'users.name as user'])
            ->map(fn ($o) => ['kind' => 'account', 'title' => $o->product_name, 'user' => $o->user ?: '—', 'amount' => $this->money((float) $o->total), 'status' => ucfirst($o->status), 'at' => $o->created_at]);

        $recentOrders = $recentNumbers->concat($recentAccounts)->sortByDesc('at')->take(7)->values();

        // ── Recent signups ───────────────────────────────────────────
        $recentUsers = DB::table('users')->orderByDesc('id')->limit(5)
            ->get(['id', 'name', 'email', 'created_at', 'suspended_at'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'at' => $u->created_at, 'suspended' => (bool) $u->suspended_at]);

        return view('admin.dashboard', compact('stats', 'days', 'trendMax', 'topServices', 'lowStock', 'recentOrders', 'recentUsers'));
    }

    private function hasStatus(): bool
    {
        return DB::getSchemaBuilder()->hasColumn('wallet_transactions', 'status');
    }

    // ── money helper ─────────────────────────────────────────────────
    private ?array $settingsCache = null;
    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                            'int' => (int) $r->value, 'float' => (float) $r->value,
                            'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                        }])->all();
                } catch (\Throwable) { return []; }
            });
        }
        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }
    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol')
            . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
    }
}