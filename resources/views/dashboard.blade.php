@extends('layouts.app')

@section('title', __('Dashboard'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Overview') }}</h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Your wallet and activity at a glance.') }}</p>
        </div>
    </div>
@endsection

@section('content')

{{-- ═══════════ Balance hero + delivery donut ═══════════ --}}
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Balance --}}
    <section class="card relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-600 to-brand-700 p-5 text-white sm:p-6 lg:col-span-2">
        <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute bottom-0 right-16 h-32 w-32 rounded-full bg-white/5"></div>
        <div class="relative flex h-full flex-col justify-between gap-4">
            <div>
                <p class="text-[10.5px] font-bold uppercase tracking-[0.12em] text-white/60 sm:text-[11px]">{{ __('Wallet balance') }}</p>
                <p data-wallet-balance class="mt-1 font-mono text-[30px] font-bold leading-none tracking-[-0.03em] sm:text-[40px] lg:text-[46px]">{{ $balance['formatted'] }}</p>
                <p class="mt-2 text-[12px] text-white/70">{{ __('Spendable across numbers and accounts.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('wallet.index') }}" class="flex h-10 items-center justify-center gap-1.5 rounded-xl bg-white px-4 text-[12.5px] font-bold text-brand-700 transition hover:bg-white/90 sm:h-11">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('Add funds') }}
                </a>
                <a href="{{ route('numbers.index') }}" class="flex h-10 items-center justify-center rounded-xl bg-white/15 px-4 text-[12.5px] font-bold text-white transition hover:bg-white/25 sm:h-11">{{ __('Buy a number') }}</a>
                <a href="{{ route('logs.index') }}" class="flex h-10 items-center justify-center rounded-xl bg-white/15 px-4 text-[12.5px] font-bold text-white transition hover:bg-white/25 sm:h-11">{{ __('Accounts') }}</a>
            </div>
        </div>
    </section>

    {{-- Delivery rate donut --}}
    <section class="card flex flex-col items-center justify-center p-6">
        <p class="text-[11.5px] font-semibold text-ink-500 dark:text-ink-400">{{ __('Delivery rate') }}</p>
        @php
            $pct = $rate !== null ? max(0, min(100, (float) $rate)) : 0;
            $circ = 2 * pi() * 42;                       // r = 42
            $dash = $circ * $pct / 100;
        @endphp
        <div class="relative mt-3 h-32 w-32">
            <svg class="h-32 w-32 -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="10" class="text-ink-100 dark:text-ink-800"/>
                <circle cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                        class="text-brand-500 transition-all duration-700"
                        stroke-dasharray="{{ $dash }} {{ $circ }}"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="font-mono text-[24px] font-bold leading-none tracking-[-0.02em]">{{ $rate !== null ? round($rate) . '%' : '—' }}</span>
                <span class="mt-0.5 text-[10px] font-medium text-ink-400">{{ __('7 days') }}</span>
            </div>
        </div>
        <p class="mt-3 text-center text-[11px] text-ink-400">{{ __('Codes delivered vs cancelled.') }}</p>
    </section>
</div>

{{-- ═══════════ Stat tiles ═══════════ --}}
<div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @php
        $tiles = [
            ['label' => __('Total spent'),  'value' => $spend['formatted'],   'sub' => $orders . ' ' . trans_choice('order|orders', $orders), 'icon' => 'cart',   'tone' => 'brand'],
            ['label' => __('Avg. order'),   'value' => $average['formatted'], 'sub' => __('per purchase'), 'icon' => 'chart',  'tone' => 'sky'],
            ['label' => __('Accounts'),     'value' => number_format($accounts), 'sub' => __('bought'),    'icon' => 'layers', 'tone' => 'violet'],
            ['label' => __('Live numbers'), 'value' => $active . ' / ' . $ceiling, 'sub' => __('active now'), 'icon' => 'hash', 'tone' => 'emerald'],
        ];
        $tones = [
            'brand'=>'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
            'sky'=>'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400',
            'violet'=>'bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400',
            'emerald'=>'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400',
        ];
    @endphp
    @foreach ($tiles as $t)
        <div class="card p-4">
            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $tones[$t['tone']] }}">
                @switch($t['icon'])
                    @case('cart')   <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg> @break
                    @case('chart')  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg> @break
                    @case('layers') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg> @break
                    @default        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                @endswitch
            </span>
            <p class="mt-3 font-mono text-[20px] font-bold tracking-[-0.02em] text-ink-900 dark:text-ink-50">{{ $t['value'] }}</p>
            <p class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ $t['label'] }}</p>
            <p class="mt-0.5 text-[11px] text-ink-400">{{ $t['sub'] }}</p>
        </div>
    @endforeach
</div>

{{-- ═══════════ Live numbers ═══════════ --}}
@if ($live->isNotEmpty())
    <section class="card mt-4 p-5 sm:p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Waiting for a code') }}</h3>
            <a href="{{ route('orders.index') }}" class="text-[12px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('All orders') }}</a>
        </div>
        <div class="space-y-2">
            @foreach ($live as $n)
                <a href="{{ route('numbers.index') }}" class="flex items-center gap-3 rounded-xl border border-ink-100 p-3 transition hover:border-brand-200 dark:border-ink-800 dark:hover:border-brand-500/30">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold">{{ $n['service'] ?? __('Number') }}</p>
                        <p class="truncate font-mono text-[11.5px] text-ink-500 dark:text-ink-400">{{ $n['number'] ?? '—' }}</p>
                    </div>
                    <span class="shrink-0 rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ __('Waiting') }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- ═══════════ Activity + services ═══════════ --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="card p-5 sm:p-6 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent activity') }}</h3>
            <a href="{{ route('wallet.index') }}" class="text-[12px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('View wallet') }}</a>
        </div>
        <div class="space-y-1.5">
            @forelse ($ledger as $t)
                @php
                    $credit = $t['amount'] >= 0;
                    $meta = match ($t['type']) {
                        'topup'    => [__('Wallet funded'), 'plus', 'emerald'],
                        'purchase' => [__('Purchase'), 'cart', 'ink'],
                        'refund'   => [__('Refund'), 'undo', 'sky'],
                        'reversal' => [__('Reversal'), 'undo', 'violet'],
                        default    => [__('Adjustment'), 'dot', 'amber'],
                    };
                    $tone = ['emerald'=>'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400','ink'=>'bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400','sky'=>'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400','violet'=>'bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400','amber'=>'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400'][$meta[2]];
                @endphp
                <div class="flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-ink-50 dark:hover:bg-ink-900/50">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $tone }}">
                        @switch($meta[1])
                            @case('plus') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> @break
                            @case('cart') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg> @break
                            @case('undo') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg> @break
                            @default <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                        @endswitch
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[12.5px] font-semibold">{{ $meta[0] }}</p>
                        <p class="text-[11px] text-ink-400">{{ \Illuminate\Support\Carbon::parse($t['at'])->diffForHumans() }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-[13px] font-bold {{ $credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-800 dark:text-ink-200' }}">{{ $t['formatted'] }}</span>
                </div>
            @empty
                <div class="py-10 text-center">
                    <p class="text-[13px] font-semibold">{{ __('Nothing here yet') }}</p>
                    <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Add funds to get started.') }}</p>
                    <a href="{{ route('wallet.index') }}" class="btn-primary mt-4 inline-flex h-10 px-5 text-[13px]">{{ __('Add funds') }}</a>
                </div>
            @endforelse
        </div>
    </section>

    <div class="space-y-4">
        <section class="card p-5 sm:p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Your top services') }}</h3>
            <p class="mt-0.5 text-[11.5px] text-ink-500 dark:text-ink-400">{{ __('This past week') }}</p>
            <div class="mt-4 space-y-3">
                @forelse ($top as $s)
                    @php $ttop = max(1, $top->max('count')); @endphp
                    <div>
                        <div class="flex items-center justify-between text-[12.5px]">
                            <span class="truncate font-semibold">{{ $s['service'] ?? __('Service') }}</span>
                            <span class="ml-2 shrink-0 font-mono text-ink-500 dark:text-ink-400">{{ $s['count'] }}×</span>
                        </div>
                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ round($s['count'] / $ttop * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Buy a number to see your favourites here.') }}</p>
                @endforelse
            </div>
        </section>

        @if ($rank['podium']->isNotEmpty())
            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Depositor rank') }}</h3>
                    @if ($rank['position'])<span class="font-mono text-[15px] font-bold text-brand-600 dark:text-brand-400">#{{ $rank['position'] }}</span>@endif
                </div>
                <div class="mt-4 space-y-2">
                    @foreach ($rank['podium'] as $r)
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-bold text-white {{ $r['position'] === 1 ? 'bg-amber-400' : ($r['position'] === 2 ? 'bg-ink-400' : 'bg-amber-700/70') }}">{{ $r['position'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-[12.5px] font-semibold {{ $r['is_you'] ? 'text-brand-600 dark:text-brand-400' : '' }}">{{ $r['is_you'] ? __('You') : $r['name'] }}</span>
                            <span class="shrink-0 font-mono text-[12px] font-bold">{{ $r['deposited']['formatted'] }}</span>
                        </div>
                    @endforeach
                </div>
                @unless ($rank['position'])<p class="mt-3 text-center text-[11.5px] text-ink-400">{{ __('Deposit to join the board.') }}</p>@endunless
            </section>
        @endif
    </div>
</div>
@endsection