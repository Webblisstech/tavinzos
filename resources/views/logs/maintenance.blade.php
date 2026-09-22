@extends('layouts.app')

@section('title', __('Under maintenance'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ __('Accounts & logs') }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Ready-made accounts, delivered the instant you buy.') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-3 rounded-xl border border-ink-200 px-3.5 py-2 dark:border-ink-800">
            <div>
                <p class="text-[9.5px] font-bold tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('BALANCE') }}</p>
                <p class="font-mono text-[15px] font-medium">{{ $balance['formatted'] }}</p>
            </div>
            <a href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}"
               class="rounded-lg bg-brand-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-brand-700">{{ __('Top up') }}</a>
        </div>
    </div>
@endsection

@section('content')
<div class="card mx-auto max-w-lg px-6 py-16 text-center sm:py-20">
    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400">
        <svg width="30" height="30" class="h-[30px] w-[30px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a1.4 1.4 0 0 0 2 2l6-6a4 4 0 0 0 5.4-5.4l-2.3 2.3-2-2 2.3-2.3Z"/>
        </svg>
    </span>

    <h3 class="mt-6 text-[20px] font-bold tracking-[-0.02em]">{{ __('The store is under maintenance') }}</h3>
    <p class="mx-auto mt-2 max-w-sm text-[14px] leading-relaxed text-ink-600 dark:text-ink-300">
        {{ __("We're making some improvements to the account store. It'll be back shortly — thanks for your patience.") }}
    </p>

    <div class="mt-8 flex flex-col justify-center gap-2 sm:flex-row">
        <a href="{{ route('numbers.index') }}" class="btn-primary h-11 px-6 text-[13px]">{{ __('Buy a number instead') }}</a>
        <a href="{{ route('dashboard') }}" class="btn-ghost h-11 px-6 text-[13px]">{{ __('Back to dashboard') }}</a>
    </div>
</div>
@endsection