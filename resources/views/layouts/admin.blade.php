<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@hasSection('title')@yield('title') · @endif{{ __('Admin') }} · {{ config('app.name', 'Numera') }}</title>

    {{-- Same before-paint theme guard as the main app --}}
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

{{-- A faint slate wash instead of the store's white ground, so there is never
     any doubt which side of the app you are on. --}}
<body class="h-full bg-ink-100 font-sans text-ink-900 antialiased dark:bg-black dark:text-ink-50">

@php
    $adminNav = [
        __('Operations') => [
            ['label' => __('Dashboard'),  'route' => 'admin.dashboard',   'icon' => 'grid'],
            ['label' => __('Accounts'),   'route' => 'admin.logs.index',  'icon' => 'layers'],
            ['label' => __('Categories'), 'route' => 'admin.categories.index', 'icon' => 'tag'],
            ['label' => __('Orders'),     'route' => 'admin.orders.index', 'icon' => 'list'],
            ['label' => __('Numbers catalog'), 'route' => 'admin.numbers.index', 'icon' => 'hash'],
            ['label' => __('Transactions'), 'route' => 'admin.transactions.index', 'icon' => 'wallet'],
            ['label' => __('Customers'), 'route' => 'admin.customers.index',       'icon' => 'users'],
        ],
        __('System') => [
            ['label' => __('Settings'),   'route' => 'admin.settings.index', 'icon' => 'cog'],
        ],
    ];

    $adminName = auth('admin')->user()->name ?? __('Admin');
    $initials  = \Illuminate\Support\Str::of($adminName)->explode(' ')->take(2)
        ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))->implode('');
@endphp

<div class="flex h-screen overflow-hidden" x-data="{ open: false }">

    {{-- Mobile scrim --}}
    <div id="admin-scrim" data-cloak class="fixed inset-0 z-30 bg-ink-950/50 backdrop-blur-[2px] lg:hidden"></div>

    {{-- ───────── Sidebar ───────── --}}
    <aside id="admin-sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-[248px] shrink-0 -translate-x-full flex-col border-r border-ink-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0 dark:border-ink-800 dark:bg-ink-950">

        <div class="flex h-16 shrink-0 items-center justify-between px-4 lg:h-[68px]">
            <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : route('admin.logs.index') }}" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-brand-600">
                    <span class="font-sans text-[17px] font-bold leading-none tracking-[-0.04em] text-white">IB</span>
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-[15px] font-bold tracking-[-0.03em] text-ink-900 dark:text-ink-50">IBSolutions</span>
                    <span class="text-[10px] font-bold tracking-[0.14em] text-brand-600 dark:text-brand-400">ADMIN</span>
                </span>
            </a>

            <button type="button" data-close-admin
                    class="grid h-9 w-9 place-items-center rounded-lg text-ink-600 transition hover:bg-ink-100 lg:hidden dark:text-ink-300 dark:hover:bg-ink-900"
                    aria-label="{{ __('Close navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2">
            @foreach ($adminNav as $heading => $items)
                <div class="space-y-0.5">
                    <p class="px-3 pb-2 text-[10.5px] font-bold tracking-[0.13em] text-ink-500 dark:text-ink-400">{{ \Illuminate\Support\Str::upper($heading) }}</p>

                    @foreach ($items as $item)
                        @php
                            $exists = Route::has($item['route']);
                            $url    = $exists ? route($item['route']) : '#';
                            $active = $exists && request()->routeIs($item['route'] . '*');
                        @endphp

                        <a href="{{ $url }}" @if ($active) aria-current="page" @endif
                           class="nav-link {{ $active ? 'nav-link-active' : '' }}">
                            <span class="shrink-0">
                                @switch($item['icon'])
                                    @case('grid')   <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="8" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="11" width="7" height="10" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/></svg> @break
                                    @case('layers') <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg> @break
                                    @case('tag')    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 13 13 20.5a2 2 0 0 1-2.8 0l-6.7-6.7a2 2 0 0 1-.5-1.9L4.6 5A2 2 0 0 1 6 3.6l6-1.6a2 2 0 0 1 1.9.5l6.6 6.6a2 2 0 0 1 0 2.9Z"/><circle cx="8.5" cy="8.5" r="1.3"/></svg> @break
                                    @case('list')   <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M4 12h16M4 17h7"/></svg> @break
                                    @case('users')  <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M18.5 20a5.5 5.5 0 0 0-3-4.9"/></svg> @break
                                    @case('wallet') <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5.5" width="19" height="13" rx="2.5"/><path d="M2.5 10h19"/></svg> @break
                                    @case('hash') <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg> @break
                                    @default        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path d="M12 3v2.2M12 18.8V21M21 12h-2.2M5.2 12H3M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6M18.4 18.4l-1.6-1.6M7.2 7.2 5.6 5.6"/></svg>
                                @endswitch
                            </span>
                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Back to the customer-facing app --}}
        <div class="m-3 mt-0">
            <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"
               class="flex h-11 items-center justify-center gap-2 rounded-xl border border-ink-300 text-[12.5px] font-semibold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-900">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                {{ __('Exit to store') }}
            </a>
        </div>
    </aside>

    {{-- ───────── Main column ───────── --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-ink-200 bg-white/85 px-4 backdrop-blur-md sm:px-6 lg:h-[68px] dark:border-ink-800 dark:bg-ink-950/85">

            <button type="button" data-open-admin
                    class="-ml-1 grid h-10 w-10 shrink-0 place-items-center rounded-xl text-ink-600 transition hover:bg-ink-100 lg:hidden dark:text-ink-300 dark:hover:bg-ink-900"
                    aria-label="{{ __('Open navigation') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
            </button>

            {{-- A standing reminder this is production admin --}}
            <span class="hidden items-center gap-2 rounded-lg bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700 sm:inline-flex dark:bg-brand-500/10 dark:text-brand-400">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                {{ __('ADMIN MODE') }}
            </span>

            <div class="flex-1"></div>

            <button type="button" data-theme-toggle
                    class="grid h-10 w-10 place-items-center rounded-xl border border-ink-200 text-ink-700 transition hover:bg-ink-100 dark:border-ink-800 dark:text-ink-200 dark:hover:bg-ink-900"
                    aria-label="{{ __('Toggle dark mode') }}">
                <svg class="h-[18px] w-[18px] dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z"/></svg>
                <svg class="hidden h-[18px] w-[18px] dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M21.5 12h-2M4.5 12h-2M18.4 5.6l-1.4 1.4M7 17l-1.4 1.4M18.4 18.4 17 17M7 7 5.6 5.6"/></svg>
            </button>

            <div class="relative" x-data>
                <div class="flex items-center gap-2.5 pl-1">
                    <a href="{{ route('admin.account.index') }}" class="flex items-center gap-2.5 rounded-xl px-1.5 py-1 transition hover:bg-ink-100 dark:hover:bg-ink-900" aria-label="{{ __('My account') }}">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[11px] bg-ink-900 text-[13px] font-bold text-white dark:bg-ink-100 dark:text-ink-900">{{ $initials }}</span>
                        <span class="hidden text-left leading-tight sm:block">
                            <span class="block text-[13px] font-semibold">{{ $adminName }}</span>
                            <span class="block text-[11px] text-ink-500 dark:text-ink-400">{{ ucfirst(auth('admin')->user()->role ?? 'Administrator') }}</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" class="ml-1">
                        @csrf
                        <button type="submit"
                                class="grid h-9 w-9 place-items-center rounded-xl border border-ink-200 text-ink-600 transition hover:bg-ink-100 hover:text-brand-700 dark:border-ink-800 dark:text-ink-300 dark:hover:bg-ink-900 dark:hover:text-brand-400"
                                aria-label="{{ __('Sign out') }}">
                            <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 12H4M11 8l-4 4 4 4M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        @hasSection('header')
            <div class="border-b border-ink-200 bg-white px-4 py-6 sm:px-6 lg:px-8 dark:border-ink-800 dark:bg-ink-950">
                <div class="mx-auto max-w-[1400px]">@yield('header')</div>
            </div>
        @endif

        <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="anim-fade mx-auto max-w-[1400px]">
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
                        <svg class="mt-px h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>

<script>
    (function () {
        var sidebar = document.getElementById('admin-sidebar');
        var scrim   = document.getElementById('admin-scrim');

        function openNav()  { sidebar.classList.remove('-translate-x-full'); scrim.removeAttribute('data-cloak'); }
        function closeNav() { sidebar.classList.add('-translate-x-full'); scrim.setAttribute('data-cloak', ''); }

        document.querySelectorAll('[data-open-admin]').forEach((el) => el.addEventListener('click', openNav));
        document.querySelectorAll('[data-close-admin]').forEach((el) => el.addEventListener('click', closeNav));
        scrim.addEventListener('click', closeNav);

        document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
            btn.addEventListener('click', () => {
                var dark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            });
        });

        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeNav(); });
    })();
</script>

@stack('scripts')
</body>
</html>