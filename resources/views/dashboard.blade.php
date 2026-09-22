@extends('layouts.app')

@section('title', __('Overview'))

@section('content')

{{-- ═══════════ Hero: greeting + balance + quick actions ═══════════ --}}
<section class="card relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-600 to-brand-700 p-5 text-white sm:p-8">
    <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute -bottom-16 right-16 h-32 w-32 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div>
            <p class="text-[13px] font-medium text-white/80">
                {{ __('Welcome back') }}@if ($name), {{ \Illuminate\Support\Str::of($name)->before(' ') }}@endif 👋
            </p>
            <p class="mt-3 text-[10.5px] font-bold uppercase tracking-[0.12em] text-white/60 sm:mt-4 sm:text-[11px]">{{ __('Wallet balance') }}</p>
            <p data-wallet-balance class="mt-1 font-mono text-[30px] font-bold leading-none tracking-[-0.03em] sm:text-[40px] lg:text-[46px]">{{ $balance['formatted'] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
            <a href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}" class="flex h-10 items-center justify-center gap-1.5 rounded-xl bg-white px-4 sm:h-11 text-[13px] font-bold text-brand-700 transition hover:bg-white/90">
                <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('Add funds') }}
            </a>
            <a href="{{ route('numbers.index') }}" class="flex h-10 items-center justify-center rounded-xl bg-white/15 px-4 sm:h-11 text-[13px] font-bold text-white transition hover:bg-white/25">{{ __('Buy number') }}</a>
            <a href="{{ Route::has('logs.index') ? route('logs.index') : '#' }}" class="flex h-10 items-center justify-center rounded-xl bg-white/15 px-4 sm:h-11 text-[13px] font-bold text-white transition hover:bg-white/25">{{ __('Buy accounts') }}</a>
            <a href="{{ Route::has('orders.index') ? route('orders.index') : '#' }}" class="flex h-10 items-center justify-center rounded-xl bg-white/15 px-4 sm:h-11 text-[13px] font-bold text-white transition hover:bg-white/25">{{ __('Orders') }}</a>
        </div>
    </div>
</section>

{{-- ═══════════ Deposit rank ═══════════ --}}
<section class="card mt-4 overflow-hidden p-5 sm:p-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Your rank') }}</h3>
            <p class="text-[11.5px] text-ink-500 dark:text-ink-400">{{ __('Based on total deposits') }}</p>
        </div>
        <div class="text-right">
            @if ($rank['position'])
                <p class="font-mono text-[26px] font-bold leading-none tracking-[-0.03em] text-brand-600 dark:text-brand-400">#{{ $rank['position'] }}</p>
                <p class="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{{ __('of :n', ['n' => $rank['total']]) }}</p>
            @else
                <p class="font-mono text-[26px] font-bold leading-none tracking-[-0.03em] text-ink-300 dark:text-ink-700">#—</p>
            @endif
        </div>
    </div>

    @if ($rank['podium']->isNotEmpty())
        {{-- Podium: 2nd · 1st · 3rd, like a real leaderboard --}}
        @php
            $byPos = $rank['podium']->keyBy('position');
            $order = [2, 1, 3];
        @endphp
        <div class="mt-5 grid grid-cols-3 items-end gap-2">
            @foreach ($order as $pos)
                @php $p = $byPos[$pos] ?? null; @endphp
                <div class="flex flex-col items-center">
                    @if ($p)
                        <span class="grid h-9 w-9 place-items-center rounded-full text-[13px] font-bold text-white
                            {{ $pos === 1 ? 'bg-amber-400' : ($pos === 2 ? 'bg-ink-400' : 'bg-amber-700/70') }}">{{ $pos }}</span>
                        <p class="mt-1.5 max-w-full truncate text-[12px] font-bold {{ $p['is_you'] ? 'text-brand-600 dark:text-brand-400' : '' }}">{{ $p['is_you'] ? __('You') : $p['name'] }}</p>
                        <div class="mt-1.5 flex w-full items-center justify-center rounded-t-xl bg-ink-50 dark:bg-ink-800/60
                            {{ $pos === 1 ? 'h-16' : ($pos === 2 ? 'h-12' : 'h-9') }}">
                            <span class="font-mono text-[13px] font-bold">{{ $p['deposited']['formatted'] }}</span>
                        </div>
                    @else
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-ink-100 text-[13px] font-bold text-ink-400 dark:bg-ink-800">{{ $pos }}</span>
                        <p class="mt-1.5 text-[12px] text-ink-400">—</p>
                        <div class="mt-1.5 flex w-full items-center justify-center rounded-t-xl bg-ink-50 dark:bg-ink-800/40 {{ $pos === 1 ? 'h-16' : ($pos === 2 ? 'h-12' : 'h-9') }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @unless ($rank['position'])
        <div class="mt-4 rounded-xl border border-ink-100 bg-ink-50/60 p-3 text-center dark:border-ink-800 dark:bg-ink-900/40">
            <p class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Make a deposit to join the leaderboard.') }}</p>
        </div>
    @endunless
</section>
<div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @php
        $tiles = [
            ['label' => __('Active numbers'), 'value' => $active . ' / ' . $ceiling, 'hint' => __('waiting for codes'), 'icon' => 'clock', 'tone' => 'amber'],
            ['label' => __('Delivery rate'),  'value' => $rate === null ? '—' : $rate . '%', 'hint' => $rate === null ? __('no orders yet') : __('last 24 hours'), 'icon' => 'check', 'tone' => 'emerald'],
            ['label' => __('Orders this week'), 'value' => number_format($orders), 'hint' => $spend['formatted'] . ' ' . __('spent'), 'icon' => 'list', 'tone' => 'sky'],
            ['label' => __('Added this week'), 'value' => $deposited['formatted'], 'hint' => __('total top-ups'), 'icon' => 'cash', 'tone' => 'violet'],
        ];
        $tones = [
            'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400',
            'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400',
            'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-400',
            'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-400/15 dark:text-violet-400',
        ];
    @endphp
    @foreach ($tiles as $t)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <p class="text-[11px] font-bold uppercase tracking-[0.07em] text-ink-500 dark:text-ink-400">{{ $t['label'] }}</p>
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $tones[$t['tone']] }}">
                    @switch($t['icon'])
                        @case('clock') <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg> @break
                        @case('check') <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5 9.5 17 19 7"/></svg> @break
                        @case('list')  <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M4 12h16M4 17h7"/></svg> @break
                        @default       <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                    @endswitch
                </span>
            </div>
            <p class="mt-3 font-mono text-[23px] font-bold tracking-[-0.02em] text-ink-900 dark:text-ink-50">{{ $t['value'] }}</p>
            <p class="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{{ $t['hint'] }}</p>
        </div>
    @endforeach
</div>

{{-- ═══════════ Trend chart + top services ═══════════ --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Delivery rate') }}</h3>
                <p class="text-[11.5px] text-ink-500 dark:text-ink-400">{{ __('Codes returned, last 7 days') }}</p>
            </div>
            @if ($rate !== null)
                <span class="rounded-lg bg-emerald-100 px-2.5 py-1 text-[12px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ $rate }}%</span>
            @endif
        </div>
        <div class="mt-5 flex h-36 items-end gap-2.5">
            @php $labels = ['6d','5d','4d','3d','2d','Yd','Td']; @endphp
            @foreach ($trend as $i => $v)
                <div class="group flex flex-1 flex-col items-center gap-1.5">
                    <span class="text-[9px] font-bold text-ink-400 opacity-0 transition group-hover:opacity-100">{{ $v }}%</span>
                    <div class="flex w-full flex-1 items-end">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-brand-600 to-brand-500 transition-all hover:from-brand-700 hover:to-brand-600" style="height: {{ max($v, 3) }}%"></div>
                    </div>
                    <span class="text-[10px] font-semibold text-ink-400">{{ $labels[$i] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card p-5">
        <h3 class="mb-4 text-[14px] font-bold tracking-[-0.02em]">{{ __('Top services') }}</h3>
        @forelse ($top as $svc)
            @php $max = $top->max('count') ?: 1; @endphp
            <div class="mb-3 last:mb-0">
                <div class="mb-1 flex items-center justify-between">
                    <span class="truncate text-[12.5px] font-semibold">{{ $svc['service'] }}</span>
                    <span class="shrink-0 font-mono text-[11px] font-bold text-ink-500 dark:text-ink-400">{{ $svc['count'] }}</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ round($svc['count'] / $max * 100) }}%"></div>
                </div>
            </div>
        @empty
            <div class="py-10 text-center">
                <p class="text-[13px] font-semibold">{{ __('No services yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your most-used services will rank here.') }}</p>
            </div>
        @endforelse
    </section>
</div>

{{-- ═══════════ Recent numbers + wallet activity ═══════════ --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <section class="card p-5 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent numbers') }}</h3>
            <a href="{{ route('numbers.index') }}" class="text-[12px] font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Buy more') }}</a>
        </div>
        @forelse ($live as $o)
            @php
                $waiting = $o['status'] === 'Waiting';
                $pill = $waiting ? 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300';
                $hasCode = ($o['code'] ?? '') !== '';
            @endphp
            <div class="flex items-center gap-3 border-t border-ink-100 py-3 first:border-t-0 dark:border-ink-800/60">
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-0.5 text-[10.5px] font-bold {{ $pill }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $waiting ? 'bg-amber-500 dot-live' : 'bg-emerald-500' }}"></span>{{ $o['status'] }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold">{{ $o['service'] ?: __('Number') }}@if ($o['country']) <span class="font-normal text-ink-500 dark:text-ink-400">· {{ $o['country'] }}</span>@endif</p>
                    <p class="truncate font-mono text-[11px] text-ink-500 dark:text-ink-400">{{ $o['number'] ?: '—' }}</p>
                </div>
                @if ($hasCode)
                    <span class="shrink-0 rounded-lg border-2 border-brand-500 px-2.5 py-1 font-mono text-[14px] font-bold tracking-[0.1em] text-brand-700 dark:text-brand-300">{{ $o['code'] }}</span>
                @endif
            </div>
        @empty
            <div class="py-12 text-center">
                <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg></span>
                <p class="text-[13px] font-semibold">{{ __('No active numbers') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Buy one and its code shows up here.') }}</p>
            </div>
        @endforelse
    </section>

    <section class="card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Wallet activity') }}</h3>
            <a href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}" class="text-[12px] font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('All') }}</a>
        </div>
        @forelse ($ledger as $t)
            @php $credit = $t['amount'] >= 0; @endphp
            <div class="flex items-center gap-3 border-t border-ink-100 py-3 first:border-t-0 dark:border-ink-800/60">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $credit ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400' : 'bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400' }}">
                    @if ($credit)
                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
                    @else
                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"/></svg>
                    @endif
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[12.5px] font-semibold capitalize">{{ str_replace('_', ' ', $t['type']) }}</p>
                    <p class="truncate text-[10.5px] text-ink-400">{{ \Illuminate\Support\Carbon::parse($t['at'])->diffForHumans() }}</p>
                </div>
                <span class="shrink-0 font-mono text-[13px] font-bold {{ $credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-800 dark:text-ink-200' }}">{{ $t['formatted'] }}</span>
            </div>
        @empty
            <div class="py-12 text-center">
                <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></span>
                <p class="text-[13px] font-semibold">{{ __('No activity yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Top-ups and purchases appear here.') }}</p>
            </div>
        @endforelse
    </section>
</div>
@endsection