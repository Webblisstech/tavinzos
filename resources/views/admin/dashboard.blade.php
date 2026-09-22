@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Dashboard') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Your platform at a glance.') }}</p>
    </div>
@endsection

@section('content')

{{-- ═══════════ Hero revenue + trend ═══════════ --}}
<div class="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Revenue hero --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-600 to-brand-800 p-6 text-white shadow-xl shadow-brand-600/20">
        <div class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full bg-white/10"></div>
        <div class="relative">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">{{ __('Total revenue') }}</p>
            <p class="mt-2 font-mono text-[36px] font-bold leading-none tracking-[-0.03em]">{{ $stats['revenue']['formatted'] }}</p>
            <div class="mt-6 flex items-center gap-6">
                <div>
                    <p class="font-mono text-[17px] font-bold leading-none">{{ $stats['revenue_today']['formatted'] }}</p>
                    <p class="mt-1 text-[10.5px] font-semibold text-white/70">{{ __('Today') }}</p>
                </div>
                <div class="h-8 w-px bg-white/20"></div>
                <div>
                    <p class="font-mono text-[17px] font-bold leading-none">{{ $stats['revenue_month']['formatted'] }}</p>
                    <p class="mt-1 text-[10.5px] font-semibold text-white/70">{{ __('This month') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- 14-day trend --}}
    <div class="card p-6 lg:col-span-2">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400">{{ __('Revenue') }}</p>
                <p class="text-[13px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Last 14 days') }}</p>
            </div>
            <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('Kept spend') }}</span>
        </div>
        <div class="mt-5 flex h-36 items-end gap-1.5">
            @foreach ($days as $d)
                @php $h = $trendMax > 0 ? max(4, round($d['value'] / $trendMax * 100)) : 4; @endphp
                <div class="group relative flex flex-1 flex-col items-center justify-end">
                    <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition hover:from-brand-700 hover:to-brand-500" style="height: {{ $h }}%"></div>
                    <div class="pointer-events-none absolute -top-8 hidden whitespace-nowrap rounded-md bg-ink-900 px-2 py-1 text-[10px] font-bold text-white group-hover:block dark:bg-ink-700">{{ $d['label'] }}: {{ config('services.numbers.currency.symbol', '₦') }}{{ number_format($d['value']) }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-[9.5px] font-medium text-ink-400">
            <span>{{ $days->first()['label'] }}</span>
            <span>{{ $days->last()['label'] }}</span>
        </div>
    </div>
</div>

{{-- ═══════════ Stat tiles ═══════════ --}}
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @php
        $tiles = [
            ['label' => __('Held in wallets'), 'value' => $stats['held']['formatted'], 'sub' => __('customer liability'), 'icon' => 'wallet', 'tone' => 'brand'],
            ['label' => __('Customers'),       'value' => number_format($stats['customers']), 'sub' => '+' . $stats['customers_new'] . ' ' . __('today'), 'icon' => 'users', 'tone' => 'sky'],
            ['label' => __('Orders'),          'value' => number_format($stats['orders']), 'sub' => '+' . $stats['orders_today'] . ' ' . __('today'), 'icon' => 'bag', 'tone' => 'violet'],
            ['label' => __('Top-ups today'),   'value' => $stats['topups_today']['formatted'], 'sub' => __('funds in'), 'icon' => 'plus', 'tone' => 'emerald'],
        ];
        $tones = [
            'brand'   => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
            'sky'     => 'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400',
            'violet'  => 'bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400',
            'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
        ];
    @endphp
    @foreach ($tiles as $t)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <span class="grid h-10 w-10 place-items-center rounded-xl {{ $tones[$t['tone']] }}">
                    @switch($t['icon'])
                        @case('wallet') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg> @break
                        @case('users')  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5a3 3 0 0 1 0 6"/></svg> @break
                        @case('bag')    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg> @break
                        @default        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    @endswitch
                </span>
            </div>
            <p class="mt-4 font-mono text-[22px] font-bold tracking-[-0.02em] text-ink-900 dark:text-ink-50">{{ $t['value'] }}</p>
            <p class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ $t['label'] }}</p>
            <p class="mt-0.5 text-[11px] text-ink-400">{{ $t['sub'] }}</p>
        </div>
    @endforeach
</div>

{{-- ═══════════ Alerts row ═══════════ --}}
@if ($stats['waiting'] || $stats['suspended'] || $lowStock->isNotEmpty())
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="{{ route('admin.orders.index', ['type' => 'numbers', 'status' => 'waiting']) }}" class="flex items-center gap-3 rounded-2xl border border-amber-300/50 bg-amber-50 p-4 transition hover:bg-amber-100 dark:border-amber-400/25 dark:bg-amber-400/10 dark:hover:bg-amber-400/15">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-500 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg></span>
            <div><p class="font-mono text-[18px] font-bold text-amber-700 dark:text-amber-300">{{ $stats['waiting'] }}</p><p class="text-[11.5px] font-semibold text-amber-700/80 dark:text-amber-300/80">{{ __('numbers waiting') }}</p></div>
        </a>
        <a href="{{ route('admin.customers.index', ['status' => 'suspended']) }}" class="flex items-center gap-3 rounded-2xl border border-brand-300/50 bg-brand-50 p-4 transition hover:bg-brand-100 dark:border-brand-500/25 dark:bg-brand-500/10 dark:hover:bg-brand-500/15">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg></span>
            <div><p class="font-mono text-[18px] font-bold text-brand-700 dark:text-brand-300">{{ $stats['suspended'] }}</p><p class="text-[11.5px] font-semibold text-brand-700/80 dark:text-brand-300/80">{{ __('suspended users') }}</p></div>
        </a>
        <a href="{{ route('admin.logs.index') }}" class="flex items-center gap-3 rounded-2xl border border-ink-200 bg-white p-4 transition hover:bg-ink-50 dark:border-ink-800 dark:bg-ink-900 dark:hover:bg-ink-800/50">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-ink-800 text-white dark:bg-ink-700"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg></span>
            <div><p class="font-mono text-[18px] font-bold">{{ $lowStock->count() }}</p><p class="text-[11.5px] font-semibold text-ink-500 dark:text-ink-400">{{ __('products low on stock') }}</p></div>
        </a>
    </div>
@endif

{{-- ═══════════ Recent activity + top services ═══════════ --}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Recent orders --}}
    <section class="card p-6 lg:col-span-2">
        <div class="flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent orders') }}</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-[12px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('View all') }}</a>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($recentOrders as $o)
                <div class="flex items-center gap-3 rounded-xl border border-ink-100 p-3 dark:border-ink-800">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                        @if ($o['kind'] === 'account')
                            <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                        @else
                            <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold">{{ $o['title'] }}</p>
                        <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ $o['user'] }} · {{ \Illuminate\Support\Carbon::parse($o['at'])->diffForHumans() }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-[13px] font-bold text-brand-600 dark:text-brand-400">{{ $o['amount']['formatted'] }}</span>
                </div>
            @empty
                <p class="py-8 text-center text-[13px] text-ink-500 dark:text-ink-400">{{ __('No orders yet.') }}</p>
            @endforelse
        </div>
    </section>

    {{-- Side column --}}
    <div class="space-y-4">

        {{-- Top services --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Top services') }}</h3>
            <div class="mt-4 space-y-3">
                @forelse ($topServices as $i => $s)
                    <div class="flex items-center gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-brand-50 text-[11px] font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[12.5px] font-semibold">{{ $s['name'] }}</p>
                            <p class="text-[10.5px] text-ink-400">{{ $s['orders'] }} {{ __('orders') }}</p>
                        </div>
                        <span class="shrink-0 font-mono text-[12px] font-bold">{{ $s['revenue']['formatted'] }}</span>
                    </div>
                @empty
                    <p class="py-4 text-center text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('No sales yet.') }}</p>
                @endforelse
            </div>
        </section>

        {{-- Recent signups --}}
        <section class="card p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('New customers') }}</h3>
                <a href="{{ route('admin.customers.index') }}" class="text-[12px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('All') }}</a>
            </div>
            <div class="mt-4 space-y-2.5">
                @forelse ($recentUsers as $u)
                    <a href="{{ route('admin.customers.show', $u['id']) }}" class="flex items-center gap-2.5 transition hover:opacity-75">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-600 text-[11px] font-bold text-white">{{ strtoupper(substr($u['name'], 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[12.5px] font-semibold">{{ $u['name'] }}</p>
                            <p class="truncate text-[10.5px] text-ink-400">{{ \Illuminate\Support\Carbon::parse($u['at'])->diffForHumans() }}</p>
                        </div>
                        @if ($u['suspended'])<span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>@else<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>@endif
                    </a>
                @empty
                    <p class="py-4 text-center text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('No customers yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection