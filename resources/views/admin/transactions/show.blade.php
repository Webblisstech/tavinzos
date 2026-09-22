@extends('layouts.admin')

@section('title', __('Transaction :ref', ['ref' => $tx['ref']]))

@section('header')
    <div>
        <a href="{{ route('admin.transactions.index') }}" class="mb-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink-500 transition hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">
            <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            {{ __('All transactions') }}
        </a>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50 capitalize">{{ $tx['type'] }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400"><span class="font-mono">{{ $tx['ref'] }}</span></p>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Amount + balance flow --}}
    <section class="card p-6 lg:col-span-2">
        <div class="flex items-center justify-between">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Transaction') }}</h3>
            @php
                $tone = match ($tx['status']) {
                    'settled' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                    'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                    default   => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
                };
            @endphp
            <span class="rounded-lg px-2.5 py-1 text-[12px] font-bold capitalize {{ $tone }}">{{ $tx['status'] }}</span>
        </div>

        {{-- The amount, big --}}
        <p class="mt-5 font-mono text-[34px] font-bold tracking-[-0.03em] {{ $tx['credit'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-900 dark:text-ink-50' }}">
            {{ $tx['credit'] ? '+' : '' }}{{ $tx['amount']['formatted'] }}
        </p>

        {{-- Balance before → after --}}
        <div class="mt-6 flex items-center gap-3 rounded-2xl border border-ink-200 p-4 dark:border-ink-800">
            <div class="flex-1 text-center">
                <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('Balance before') }}</p>
                <p class="mt-1 font-mono text-[17px] font-bold">{{ $tx['before']['formatted'] }}</p>
            </div>
            <svg class="h-5 w-5 shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            <div class="flex-1 text-center">
                <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('Balance after') }}</p>
                <p class="mt-1 font-mono text-[17px] font-bold {{ $tx['credit'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-brand-600 dark:text-brand-400' }}">{{ $tx['after']['formatted'] }}</p>
            </div>
        </div>

        <dl class="mt-5 divide-y divide-ink-100 dark:divide-ink-800/60">
            @if ($tx['note'])
                <div class="flex items-start justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Note') }}</dt>
                    <dd class="text-right text-[13px] font-medium">{{ $tx['note'] }}</dd>
                </div>
            @endif
            @if ($tx['gateway'])
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Gateway ref') }}</dt>
                    <dd class="truncate text-right font-mono text-[12px] font-semibold">{{ $tx['gateway'] }}</dd>
                </div>
            @endif
            <div class="flex items-center justify-between py-3">
                <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Date') }}</dt>
                <dd class="text-[13px] font-semibold">{{ \Illuminate\Support\Carbon::parse($tx['at'])->format('j M Y, H:i:s') }}</dd>
            </div>
        </dl>
    </section>

    {{-- Customer --}}
    <section class="card h-fit p-5">
        <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Customer') }}</h3>
        <p class="mt-3 text-[14px] font-semibold">{{ $tx['user'] }}</p>
        <p class="truncate text-[12.5px] text-ink-500 dark:text-ink-400">{{ $tx['email'] }}</p>

        <div class="mt-4 border-t border-ink-100 pt-4 dark:border-ink-800/60">
            <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('Current balance') }}</p>
            <p class="mt-1 font-mono text-[20px] font-bold text-brand-600 dark:text-brand-400">{{ $tx['current']['formatted'] }}</p>
        </div>
    </section>
</div>
@endsection