<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#100c0c" media="(prefers-color-scheme: dark)">

    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name', 'Numera') }}</title>

    {{-- Theme is applied before first paint so dark mode never flashes white --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @include('partials.head')

    @stack('head')
</head>

<body class="h-full bg-white font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-ink-50">

@if (session()->has('impersonator_admin_id'))
    <div class="fixed inset-x-0 top-0 z-[60] flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-[12.5px] font-bold text-amber-950">
        <span class="flex items-center gap-1.5">
            <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            {{ __('Viewing as :name', ['name' => auth()->user()->name ?? __('customer')]) }}
        </span>
        <form method="POST" action="{{ route('impersonate.stop') }}">
            @csrf
            <button type="submit" class="rounded-md bg-amber-950/15 px-2.5 py-1 text-[11.5px] font-bold transition hover:bg-amber-950/25">{{ __('Return to admin') }}</button>
        </form>
    </div>
    <div class="h-9"></div>
@endif

@php
    $navGroups = [
        __('Menu') => [
            ['label' => __('Overview'),        'route' => 'dashboard',     'icon' => 'grid'],
            ['label' => __('Virtual numbers'), 'route' => 'numbers.index', 'icon' => 'hash'],
            ['label' => __('Logs store'),      'route' => 'logs.index',    'icon' => 'layers'],
            ['label' => __('Add funds'),       'route' => 'wallet.index',  'icon' => 'wallet'],
            ['label' => __('Transactions'),    'route' => 'transactions.index', 'icon' => 'list'],
            ['label' => __('Orders'),          'route' => 'orders.index',  'icon' => 'list'],
            ['label' => __('Affiliates'),      'route' => 'affiliates.index', 'icon' => 'users'],
            ['label' => __('Support'),         'route' => 'support.index', 'icon' => 'help'],
            ['label' => __('Settings'),        'route' => 'settings.index', 'icon' => 'cog'],
        ],
    ];

    $userName = auth()->user()->name ?? __('Guest');
    $initials = Str::of($userName)->explode(' ')->take(2)
        ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');
@endphp

<div class="flex h-screen overflow-hidden">

    {{-- Mobile scrim --}}
    <div id="scrim" data-cloak
         class="fixed inset-0 z-30 bg-ink-950/50 backdrop-blur-[2px] lg:hidden"></div>

    {{-- ───────────────── Sidebar ───────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex h-full w-[264px] shrink-0 -translate-x-full flex-col border-r border-ink-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0 dark:border-ink-800 dark:bg-ink-950">

        <div class="flex h-16 shrink-0 items-center justify-between px-4 lg:h-[72px] lg:px-5">
            @include('partials.logo', ['size' => 'md'])

            <button type="button" data-close-sidebar
                    class="grid h-9 w-9 place-items-center rounded-lg text-ink-600 transition hover:bg-ink-100 lg:hidden dark:text-ink-300 dark:hover:bg-ink-900"
                    aria-label="{{ __('Close navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        {{-- Balance first: it decides whether anything else on the page is
             possible, so it should not be buried in a nav row. --}}
        <div class="mx-3 mb-5 rounded-2xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-500/25 dark:bg-brand-500/10">
            <div class="flex items-start justify-between gap-2">
                <span class="text-[11px] font-bold tracking-[0.08em] text-brand-800 dark:text-brand-300">{{ __('CURRENT BALANCE') }}</span>
                <a href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}"
                   class="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-brand-300 text-brand-700 transition hover:bg-brand-100 dark:border-brand-400/40 dark:text-brand-300 dark:hover:bg-brand-500/20"
                   aria-label="{{ __('Add funds') }}">
                    <svg width="12" height="12" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </a>
            </div>
            <p data-wallet-balance class="mt-2 font-mono text-[24px] font-medium tracking-[-0.03em] text-brand-700 dark:text-brand-400">
                {{ config('services.numbers.currency.symbol', '₦') }}{{ number_format((float) (auth()->user()->wallet_balance ?? 0), 2) }}
            </p>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2">
            @foreach ($navGroups as $heading => $items)
                <div class="space-y-0.5">
                    <p class="px-3 pb-2 text-[9.5px] font-bold tracking-[0.13em] text-ink-500 dark:text-ink-400">
                        {{ Str::upper($heading) }}
                    </p>

                    @foreach ($items as $item)
                        @php
                            $exists = Route::has($item['route']);
                            $url    = $exists ? route($item['route']) : '#';
                            $active = $exists && request()->routeIs($item['route'] . '*');
                        @endphp

                        <a href="{{ $url }}" @if ($active) aria-current="page" @endif
                           class="nav-link group {{ $active ? 'nav-link-active' : '' }}">
                            <span class="shrink-0">
                                @switch($item['icon'])
                                    @case('grid')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="11" width="7" height="10" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/></svg>
                                        @break
                                    @case('hash')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                                        @break
                                    @case('layers')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                                        @break
                                    @case('wallet')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18"/><circle cx="17" cy="14.5" r="1.2"/></svg>
                                        @break
                                    @case('list')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M4 12h16M4 17h7"/></svg>
                                        @break
                                    @case('code')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-12"/><path d="m6 9-4 3 4 3"/><path d="m18 9 4 3-4 3"/></svg>
                                        @break
                                    @case('users')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M18.5 20a5.5 5.5 0 0 0-3-4.9"/></svg>
                                        @break
                                    @case('help')
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.5 1.5c0 1.5-2 2-2 3M12 17h.01"/></svg>
                                        @break
                                    @default
                                        <svg class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.2M12 18.8V21M21 12h-2.2M5.2 12H3M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6M18.4 18.4l-1.6-1.6M7.2 7.2 5.6 5.6"/></svg>
                                @endswitch
                            </span>

                            <span class="flex-1 truncate">{{ $item['label'] }}</span>

                            @isset($item['badge'])
                                <span data-wallet-balance class="shrink-0 rounded-md bg-ink-100 px-1.5 py-0.5 font-mono text-[10px] font-medium text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ $item['badge'] }}</span>
                            @endisset
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="m-3 mt-0 rounded-2xl border border-ink-200 p-3.5 dark:border-ink-800">
            <p class="text-[12px] leading-snug text-ink-600 dark:text-ink-300">
                {{ __('Need help with an order?') }}
            </p>
            <a href="{{ config('app.support_url') ?: '#' }}"
               class="mt-2 flex h-9 w-full items-center justify-center rounded-lg border border-ink-300 text-[12px] font-semibold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">
                {{ __('Contact support') }}
            </a>
        </div>

    </aside>

    {{-- ───────────────── Main column ───────────────── --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

        <header class="app-header flex h-16 shrink-0 items-center gap-3 border-b border-ink-200 bg-white/85 px-4 backdrop-blur-md sm:px-6 lg:h-[72px] lg:px-8 dark:border-ink-800 dark:bg-ink-950/85">

            <button type="button" data-open-sidebar
                    class="-ml-1 grid h-10 w-10 shrink-0 place-items-center rounded-xl text-ink-600 transition hover:bg-ink-100 lg:hidden dark:text-ink-300 dark:hover:bg-ink-900"
                    aria-label="{{ __('Open navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
            </button>

            {{-- Logo — mobile only (desktop shows it in the sidebar) --}}
            <div class="lg:hidden">
                @include('partials.logo', ['size' => 'sm'])
            </div>

            <form action="#" method="GET" class="hidden min-w-0 flex-1 md:flex md:max-w-md">
                <label for="global-search" class="sr-only">{{ __('Search') }}</label>
                <div class="flex h-11 w-full items-center gap-2.5 rounded-xl border border-ink-200 bg-ink-50 px-3.5 transition focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-900">
                    <svg class="h-[17px] w-[17px] shrink-0 text-ink-500 dark:text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
                    <input id="global-search" name="q" type="search"
                           placeholder="{{ __('Search service, country or order ID') }}"
                           class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13px] text-ink-900 placeholder:text-ink-500 focus:ring-0 dark:text-ink-50 dark:placeholder:text-ink-400">
                    <kbd class="hidden shrink-0 rounded-md border border-ink-200 px-1.5 py-0.5 font-mono text-[10px] text-ink-500 lg:block dark:border-ink-700 dark:text-ink-400">⌘K</kbd>
                </div>
            </form>

            <div class="flex-1 md:flex-none"></div>

            <span class="hidden h-10 items-center gap-2 rounded-xl border border-ink-200 bg-ink-50 px-3.5 text-[12px] font-semibold text-ink-600 xl:inline-flex dark:border-ink-800 dark:bg-ink-900 dark:text-ink-300">
                <span class="dot-live h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                {{ __('All gateways online') }}
            </span>

            <button type="button" data-theme-toggle
                    class="grid h-10 w-10 place-items-center rounded-xl border border-ink-200 text-ink-700 transition hover:bg-ink-100 dark:border-ink-800 dark:text-ink-200 dark:hover:bg-ink-900"
                    aria-label="{{ __('Toggle dark mode') }}">
                <svg class="h-[18px] w-[18px] dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z"/></svg>
                <svg class="hidden h-[18px] w-[18px] dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M21.5 12h-2M4.5 12h-2M18.4 5.6l-1.4 1.4M7 17l-1.4 1.4M18.4 18.4 17 17M7 7 5.6 5.6"/></svg>
            </button>

            <a href="#" class="relative grid h-10 w-10 place-items-center rounded-xl border border-ink-200 text-ink-700 transition hover:bg-ink-100 dark:border-ink-800 dark:text-ink-200 dark:hover:bg-ink-900"
               aria-label="{{ __('Notifications') }}">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9a6 6 0 1 0-12 0c0 5-2 6-2 6h16s-2-1-2-6Z"/><path d="M10.5 20a2 2 0 0 0 3 0"/></svg>
                <span class="halo-live absolute right-2 top-2 h-2 w-2 rounded-full bg-brand-600 ring-2 ring-white dark:ring-ink-950"></span>
            </a>

            {{-- Account menu --}}
            <div class="relative ml-1" id="account-menu">
                <button type="button" data-menu-button aria-expanded="false" aria-haspopup="true"
                        class="flex items-center gap-2.5 rounded-xl p-1 pr-2 transition hover:bg-ink-100 dark:hover:bg-ink-900">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[11px] bg-ink-900 text-[12px] font-bold text-white dark:bg-ink-100 dark:text-ink-900">{{ $initials }}</span>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block text-[12.5px] font-semibold">{{ $userName }}</span>
                        <span class="block text-[11px] text-ink-500 dark:text-ink-400">{{ __('Member') }}</span>
                    </span>
                    <svg class="hidden h-4 w-4 text-ink-500 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div data-menu-panel data-cloak
                     class="anim-pop absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-ink-200 bg-white p-1.5 shadow-xl shadow-ink-900/5 dark:border-ink-800 dark:bg-ink-900 dark:shadow-black/40">
                    <a href="{{ Route::has('profile.edit') ? route('profile.edit') : '#' }}" class="flex h-10 items-center rounded-xl px-3 text-[13px] font-medium text-ink-700 transition hover:bg-ink-100 dark:text-ink-200 dark:hover:bg-ink-800">{{ __('Profile') }}</a>
                    <div class="my-1.5 h-px bg-ink-200 dark:bg-ink-800"></div>
                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                        @csrf
                        <button type="submit" class="flex h-10 w-full items-center rounded-xl px-3 text-[13px] font-medium text-brand-700 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Log out') }}</button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Page header. On desktop it sits above the scroll (pinned); on
             mobile it scrolls away with the content, so long pages aren't
             stuck behind the title + summary. --}}
        @hasSection('header')
            <div class="hidden border-b border-ink-200 px-4 py-6 sm:px-6 lg:block lg:px-8 dark:border-ink-800">
                <div class="mx-auto max-w-[1400px]">@yield('header')</div>
            </div>
        @endif

        <main class="flex-1 overflow-y-auto scroll-y px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-6">
            @hasSection('header')
                <div class="mx-auto mb-5 max-w-[1400px] lg:hidden">@yield('header')</div>
            @endif
            <div class="mx-auto max-w-[1400px]">
                @if (session('status'))
                    <div class="anim-fade mb-5 flex items-start gap-3 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
                        <svg class="mt-px h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="border-t border-ink-200 px-4 py-5 sm:px-6 lg:px-8 dark:border-ink-800">
            <div class="mx-auto flex max-w-[1400px] flex-col gap-2 text-[12px] text-ink-500 sm:flex-row sm:items-center sm:justify-between dark:text-ink-400">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Numera') }}. {{ __('All rights reserved.') }}</span>
                <span class="flex gap-5">
                    <a href="#" class="transition hover:text-ink-900 dark:hover:text-ink-100">{{ __('Status') }}</a>
                    <a href="#" class="transition hover:text-ink-900 dark:hover:text-ink-100">{{ __('Support') }}</a>
                </span>
            </div>
        </footer>

        {{-- ═══════════ Mobile bottom nav ═══════════ --}}
        @php
            $bottomNav = [
                ['label' => __('Home'),    'route' => 'dashboard',     'icon' => 'grid'],
                ['label' => __('Numbers'), 'route' => 'numbers.index', 'icon' => 'hash'],
                ['label' => __('Accounts'),'route' => 'logs.index',    'icon' => 'layers'],
                ['label' => __('Orders'),  'route' => 'orders.index',  'icon' => 'list'],
                ['label' => __('Wallet'),  'route' => 'wallet.index',  'icon' => 'wallet'],
            ];
        @endphp
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-ink-200 bg-white/95 backdrop-blur-md lg:hidden dark:border-ink-800 dark:bg-ink-950/95"
             style="padding-bottom: env(safe-area-inset-bottom, 0px);"
             aria-label="{{ __('Primary') }}">
            <div class="grid grid-cols-5">
                @foreach ($bottomNav as $item)
                    @php
                        $exists = Route::has($item['route']);
                        $url    = $exists ? route($item['route']) : '#';
                        $active = $exists && request()->routeIs($item['route'] . '*');
                    @endphp
                    <a href="{{ $url }}" @if ($active) aria-current="page" @endif
                       class="flex flex-col items-center justify-center gap-0.5 py-2.5 text-[10px] font-semibold transition
                              {{ $active ? 'text-brand-600 dark:text-brand-400' : 'text-ink-500 dark:text-ink-400' }}">
                        <span class="grid h-6 w-6 place-items-center">
                            @switch($item['icon'])
                                @case('grid')   <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="11" width="7" height="10" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/></svg> @break
                                @case('hash')   <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg> @break
                                @case('layers') <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg> @break
                                @case('list')   <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M4 12h16M4 17h7"/></svg> @break
                                @default        <svg class="h-[21px] w-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                            @endswitch
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>
</div>

@verbatim
<script>
    (function () {
        var sidebar = document.getElementById('sidebar');
        var scrim   = document.getElementById('scrim');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            scrim.removeAttribute('data-cloak');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            scrim.setAttribute('data-cloak', '');
        }

        document.querySelectorAll('[data-open-sidebar]').forEach(function (el) {
            el.addEventListener('click', openSidebar);
        });
        document.querySelectorAll('[data-close-sidebar]').forEach(function (el) {
            el.addEventListener('click', closeSidebar);
        });
        scrim.addEventListener('click', closeSidebar);

        // Theme
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            });
        });

        // Account dropdown
        var menu   = document.getElementById('account-menu');
        var button = menu.querySelector('[data-menu-button]');
        var panel  = menu.querySelector('[data-menu-panel]');

        button.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = panel.hasAttribute('data-cloak');
            panel.toggleAttribute('data-cloak', !open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target)) {
                panel.setAttribute('data-cloak', '');
                button.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                panel.setAttribute('data-cloak', '');
                button.setAttribute('aria-expanded', 'false');
                closeSidebar();
            }
        });
    })();
</script>
@endverbatim

@stack('scripts')
</body>
</html>