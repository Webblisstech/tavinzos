@props([
    'heading',
    'subtitle' => null,
    'title' => null,
    'panelTitle' => null,
    'panelText' => null,
])
{{--
    Shared brand shell for the simple auth pages (forgot/reset/confirm/verify).
    Slots: heading, subtitle props, then the form as the component body ($slot).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Account') }} · {{ config('app.name', 'Tavinzos') }}</title>

    <script>
        (function () {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @if (view()->exists('partials.head'))
        @include('partials.head')
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
        <script>
            tailwind.config = { darkMode: 'class', theme: { extend: {
                fontFamily: { sans: ['Bricolage Grotesque','system-ui','sans-serif'], mono: ['DM Mono','monospace'] },
                colors: {
                    brand: {50:'#fef2f2',100:'#fde3e4',200:'#fbccce',300:'#f7a3a7',400:'#f16b72',500:'#e63e46',600:'#D91F2C',700:'#b41822',800:'#951820',900:'#7c1a20'},
                    ink: {50:'#faf7f7',100:'#f4efef',200:'#e9e1e1',300:'#d9cdcd',400:'#a89898',500:'#776a6a',600:'#5e5252',700:'#3a3131',800:'#2e2525',900:'#14100f',950:'#100c0c'},
                },
            } } };
        </script>
    @endif
</head>

<body class="min-h-full bg-ink-50 font-sans text-ink-900 antialiased dark:bg-black dark:text-ink-50">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Brand panel --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 lg:block">
        <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-10 h-80 w-80 rounded-full bg-white/5"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <a href="/" class="flex items-center gap-2.5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15"><svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16.5v-2M9 16.5v-5M13 16.5v-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M5 12.5c3-4 6-5.5 9.5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" opacity="0.55"/><circle cx="18" cy="6.5" r="3.4" fill="currentColor"/><path d="m16.7 6.5 1 1 1.6-1.9" stroke="#D91F2C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="text-[19px] font-bold tracking-[-0.03em]">{{ config('app.name', 'Tavinzos') }}</span>
            </a>
            <div>
                <h1 class="text-[32px] font-bold leading-[1.1] tracking-[-0.03em]">{{ $panelTitle ?? __('Secure account access.') }}</h1>
                <p class="mt-4 max-w-md text-[14px] leading-relaxed text-white/80">{{ $panelText ?? __('Your numbers, accounts and wallet — protected and always within reach.') }}</p>
            </div>
            <div class="flex items-center gap-2 text-[12px] text-white/70">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                {{ __('Bank-grade security') }}
            </div>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white"><svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16.5v-2M9 16.5v-5M13 16.5v-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M5 12.5c3-4 6-5.5 9.5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" opacity="0.55"/><circle cx="18" cy="6.5" r="3.4" fill="currentColor"/><path d="m16.7 6.5 1 1 1.6-1.9" stroke="#D91F2C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span class="text-[18px] font-bold tracking-[-0.03em]">{{ config('app.name', 'Tavinzos') }}</span>
            </div>

            <h2 class="text-[24px] font-bold tracking-[-0.03em]">{{ $heading }}</h2>
            @isset($subtitle)<p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-500 dark:text-ink-400">{{ $subtitle }}</p>@endisset

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-emerald-600/25 bg-emerald-50 p-3 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            <div class="mt-6">{{ $slot }}</div>
        </div>
    </div>
</div>
</body>
</html>