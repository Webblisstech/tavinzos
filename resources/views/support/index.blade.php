@extends('layouts.app')

@section('title', __('Contact support'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Contact support') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ $message ?: __('We\'re here to help. Reach us any of these ways.') }}</p>
    </div>
@endsection

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- Hours banner --}}
    @if ($hours)
        <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-600/20 bg-emerald-50 p-4 dark:border-emerald-400/20 dark:bg-emerald-400/10">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-500 text-white"><svg width="18" height="18" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg></span>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Support hours') }}</p>
                <p class="text-[14px] font-semibold text-emerald-800 dark:text-emerald-200">{{ $hours }}</p>
            </div>
        </div>
    @endif

    {{-- Contact channels --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

        @if ($whatsapp)
            @php $wa = preg_replace('/\D+/', '', $whatsapp); @endphp
            <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
               class="group flex items-center gap-3 rounded-2xl border border-ink-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-ink-900/5 dark:border-ink-800 dark:bg-ink-900 dark:hover:border-emerald-500/40">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-500 text-white"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.5L3 21l2-5.7A8.4 8.4 0 1 1 21 11.5Z"/></svg></span>
                <div class="min-w-0">
                    <p class="text-[14px] font-bold">{{ __('WhatsApp') }}</p>
                    <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ __('Chat with us now') }}</p>
                </div>
            </a>
        @endif

        @if ($telegram)
            @php $tg = \Illuminate\Support\Str::startsWith($telegram, 'http') ? $telegram : 'https://t.me/' . ltrim($telegram, '@'); @endphp
            <a href="{{ $tg }}" target="_blank" rel="noopener"
               class="group flex items-center gap-3 rounded-2xl border border-ink-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-lg hover:shadow-ink-900/5 dark:border-ink-800 dark:bg-ink-900 dark:hover:border-sky-500/40">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-sky-500 text-white"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 4 3 11l6 2 2 6 3-4 4 3 3-14Z"/></svg></span>
                <div class="min-w-0">
                    <p class="text-[14px] font-bold">{{ __('Telegram') }}</p>
                    <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ $telegram }}</p>
                </div>
            </a>
        @endif

        @if ($email)
            <a href="mailto:{{ $email }}"
               class="group flex items-center gap-3 rounded-2xl border border-ink-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg hover:shadow-ink-900/5 dark:border-ink-800 dark:bg-ink-900 dark:hover:border-brand-500/40 {{ (!$whatsapp || !$telegram) ? 'sm:col-span-2' : '' }}">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-600 text-white"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/></svg></span>
                <div class="min-w-0">
                    <p class="text-[14px] font-bold">{{ __('Email') }}</p>
                    <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ $email }}</p>
                </div>
            </a>
        @endif
    </div>

    @unless ($whatsapp || $telegram || $email)
        <div class="card px-6 py-16 text-center">
            <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="26" height="26" class="h-[26px] w-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.5L3 21l2-5.7A8.4 8.4 0 1 1 21 11.5Z"/></svg></span>
            <p class="text-[14px] font-semibold">{{ __('Support details coming soon') }}</p>
            <p class="mt-1 text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Please check back shortly.') }}</p>
        </div>
    @endunless
</div>
@endsection