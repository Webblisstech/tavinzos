@extends('layouts.admin')

@section('title', __('Customers'))

@section('header')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Customers') }}</h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Manage accounts, wallets and access.') }}</p>
        </div>
        <div class="flex items-center gap-2 text-[12px] font-semibold text-ink-500 dark:text-ink-400">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ number_format($stats['active']) }} {{ __('active') }}</span>
            @if ($stats['suspended'])<span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-500"></span>{{ number_format($stats['suspended']) }} {{ __('suspended') }}</span>@endif
        </div>
    </div>
@endsection

@section('content')

{{-- ═══════════ Hero stat band ═══════════ --}}
<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Primary: money held --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-600 to-brand-800 p-6 text-white shadow-xl shadow-brand-600/20 lg:col-span-2">
        <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute bottom-0 right-16 h-32 w-32 rounded-full bg-white/5"></div>
        <div class="relative">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/70">{{ __('Total held in wallets') }}</p>
            <p class="mt-2 font-mono text-[38px] font-bold leading-none tracking-[-0.03em] sm:text-[46px]">{{ $stats['held']['formatted'] }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-x-8 gap-y-3">
                <div>
                    <p class="font-mono text-[20px] font-bold leading-none">{{ number_format($stats['total']) }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-white/70">{{ __('Total customers') }}</p>
                </div>
                <div class="h-8 w-px bg-white/20"></div>
                <div>
                    <p class="font-mono text-[20px] font-bold leading-none">{{ number_format($stats['active']) }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-white/70">{{ __('Active') }}</p>
                </div>
                <div class="h-8 w-px bg-white/20"></div>
                <div>
                    <p class="font-mono text-[20px] font-bold leading-none">{{ number_format($stats['suspended']) }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-white/70">{{ __('Suspended') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary: quick ratio --}}
    <div class="card flex flex-col justify-between p-6">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-ink-400">{{ __('Account health') }}</p>
            @php $activePct = $stats['total'] ? round($stats['active'] / $stats['total'] * 100) : 100; @endphp
            <p class="mt-2 font-mono text-[38px] font-bold leading-none tracking-[-0.03em] text-emerald-600 dark:text-emerald-400">{{ $activePct }}%</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('of accounts in good standing') }}</p>
        </div>
        <div class="mt-5 h-2.5 w-full overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all" style="width: {{ $activePct }}%"></div>
        </div>
    </div>
</div>

{{-- ═══════════ Search + segmented filter ═══════════ --}}
<form method="GET" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="flex h-12 flex-1 items-center gap-2.5 rounded-2xl border border-ink-200 bg-white px-4 shadow-sm shadow-ink-900/5 transition focus-within:border-brand-500 focus-within:shadow-md focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="18" height="18" class="h-[18px] w-[18px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="{{ __('Search by name, email, phone or referral code…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
        @if ($filters['q'])<a href="{{ route('admin.customers.index', array_filter(['status' => $filters['status']])) }}" class="shrink-0 text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200"><svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></a>@endif
    </div>

    {{-- Segmented status control --}}
    <div class="flex h-12 items-center gap-1 rounded-2xl border border-ink-200 bg-ink-50 p-1 dark:border-ink-800 dark:bg-ink-900/60">
        @foreach (['' => __('All'), 'active' => __('Active'), 'suspended' => __('Suspended')] as $val => $label)
            <a href="{{ route('admin.customers.index', array_filter(['q' => $filters['q'], 'status' => $val])) }}"
               class="flex h-10 items-center rounded-xl px-4 text-[12.5px] font-bold transition {{ ($filters['status'] ?? '') === $val ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-ink-50' : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100' }}">{{ $label }}</a>
        @endforeach
    </div>
    <button type="submit" class="btn-primary h-12 rounded-2xl px-6 text-[13px]">{{ __('Search') }}</button>
</form>

{{-- ═══════════ Customer list ═══════════ --}}
@if ($rows->isEmpty())
    <div class="card flex flex-col items-center justify-center px-6 py-20 text-center">
        <span class="mb-4 grid h-16 w-16 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800">
            <svg width="30" height="30" class="h-[30px] w-[30px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
        </span>
        <p class="text-[15px] font-bold">{{ __('No customers found') }}</p>
        <p class="mt-1 text-[13px] text-ink-500 dark:text-ink-400">{{ __('Try a different search or filter.') }}</p>
        @if ($filters['q'] || $filters['status'])
            <a href="{{ route('admin.customers.index') }}" class="btn-ghost mt-5 h-10 px-5 text-[13px]">{{ __('Clear filters') }}</a>
        @endif
    </div>
@else
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($rows as $u)
            @php
                $initials = \Illuminate\Support\Str::of($u['name'] ?: 'U')->explode(' ')->take(2)
                    ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))->implode('');
                $hue = ['bg-brand-600','bg-emerald-600','bg-sky-600','bg-violet-600','bg-amber-600','bg-rose-600'][$u['id'] % 6];
            @endphp
            <a href="{{ route('admin.customers.show', $u['id']) }}"
               class="group relative flex flex-col overflow-hidden rounded-2xl border border-ink-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-xl hover:shadow-ink-900/5 dark:border-ink-800 dark:bg-ink-900 dark:hover:border-brand-500/40">

                {{-- top: avatar + status --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $hue }} text-[16px] font-bold text-white">{{ $initials }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-[14.5px] font-bold text-ink-900 dark:text-ink-50">{{ $u['name'] }}</p>
                            <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ $u['email'] }}</p>
                        </div>
                    </div>
                    @if ($u['suspended'])
                        <span class="shrink-0 rounded-lg bg-brand-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ __('Suspended') }}</span>
                    @else
                        <span class="flex shrink-0 items-center gap-1 rounded-lg bg-emerald-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('Active') }}</span>
                    @endif
                </div>

                {{-- balance + spent --}}
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-ink-50 p-3 dark:bg-ink-950/50">
                        <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Balance') }}</p>
                        <p class="mt-0.5 font-mono text-[16px] font-bold text-brand-600 dark:text-brand-400">{{ $u['balance']['formatted'] }}</p>
                    </div>
                    <div class="rounded-xl bg-ink-50 p-3 dark:bg-ink-950/50">
                        <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Spent') }}</p>
                        <p class="mt-0.5 font-mono text-[16px] font-bold text-ink-900 dark:text-ink-50">{{ $u['spent']['formatted'] }}</p>
                    </div>
                </div>

                {{-- footer: phone + joined + arrow --}}
                <div class="mt-4 flex items-center justify-between border-t border-ink-100 pt-3 dark:border-ink-800/60">
                    <div class="min-w-0 text-[11.5px] text-ink-500 dark:text-ink-400">
                        <span class="truncate">{{ $u['phone'] ?: '—' }}</span>
                        <span class="mx-1.5 text-ink-300 dark:text-ink-700">·</span>
                        <span>{{ \Illuminate\Support\Carbon::parse($u['joined'])->format('M Y') }}</span>
                    </div>
                    <span class="flex items-center gap-1 text-[12px] font-bold text-brand-600 transition group-hover:gap-2 dark:text-brand-400">
                        {{ __('View') }}
                        <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    @if ($rows->count() >= 300)
        <p class="mt-6 text-center text-[12px] text-ink-500 dark:text-ink-400">{{ __('Showing the most recent 300 customers. Refine your search to find older accounts.') }}</p>
    @endif
@endif
@endsection