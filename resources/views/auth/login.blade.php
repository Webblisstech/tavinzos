<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Sign in') }} · {{ config('app.name', 'Numera') }}</title>

    {{-- Before-paint theme guard --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    {{-- If you have the shared head partial, use it; otherwise Tailwind CDN + tokens --}}
    @if (view()->exists('partials.head'))
        @include('partials.head')
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: { extend: {
                    fontFamily: { sans: ['Bricolage Grotesque', 'system-ui', 'sans-serif'], mono: ['DM Mono', 'monospace'] },
                    colors: {
                        brand: { 50:'#fef2f2',100:'#fde3e4',200:'#fbccce',300:'#f7a3a7',400:'#f16b72',500:'#e63e46',600:'#D91F2C',700:'#b41822',800:'#951820',900:'#7c1a20' },
                        ink: { 50:'#faf7f7',100:'#f4efef',200:'#e9e1e1',300:'#d9cdcd',400:'#a89898',500:'#776a6a',600:'#5e5252',700:'#3a3131',800:'#2e2525',900:'#14100f',950:'#100c0c' },
                    },
                } },
            };
        </script>
    @endif
</head>

<body class="min-h-full bg-ink-50 font-sans text-ink-900 antialiased dark:bg-black dark:text-ink-50">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- ═══════════ Brand panel (desktop) ═══════════ --}}
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 lg:block">
        <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-10 h-80 w-80 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute right-24 top-1/3 h-40 w-40 rounded-full bg-white/5"></div>

        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2.5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15">
                    <span class="font-sans text-[18px] font-bold leading-none tracking-[-0.04em] text-white">IB</span>
                </span>
                <span class="text-[19px] font-bold tracking-[-0.03em]">IBSolutions</span>
            </div>

            <div>
                <h1 class="text-[34px] font-bold leading-[1.1] tracking-[-0.03em]">{{ __('Numbers & accounts,') }}<br>{{ __('delivered instantly.') }}</h1>
                <p class="mt-4 max-w-md text-[14px] leading-relaxed text-white/80">{{ __('Virtual numbers for verification, ready-made accounts, and a wallet that just works. Sign in to pick up where you left off.') }}</p>
            </div>

            <div class="flex items-center gap-6 text-[12px] text-white/70">
                <span class="flex items-center gap-1.5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg> {{ __('Secure payments') }}</span>
                <span class="flex items-center gap-1.5"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/></svg> {{ __('Instant delivery') }}</span>
            </div>
        </div>
    </div>

    {{-- ═══════════ Form panel ═══════════ --}}
    <div class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-sm">

            {{-- Mobile logo --}}
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white">
                    <span class="font-sans text-[18px] font-bold leading-none tracking-[-0.04em] text-white">IB</span>
                </span>
                <span class="text-[18px] font-bold tracking-[-0.03em]">IBSolutions</span>
            </div>

            <h2 class="text-[26px] font-bold tracking-[-0.03em]">{{ __('Welcome back') }}</h2>
            <p class="mt-1 text-[13.5px] text-ink-500 dark:text-ink-400">{{ __('Sign in to your account to continue.') }}</p>

            {{-- Session status --}}
            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-600/25 bg-emerald-50 p-3 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                @csrf

                {{-- Username or email --}}
                <div>
                    <label for="login" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Name, username or email') }}</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="{{ __('name, username or email') }}">
                    @error('login')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Password') }}</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-[12px] font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Forgot?') }}</a>
                        @endif
                    </div>
                    <div class="relative mt-1.5">
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="h-12 w-full rounded-xl border-ink-300 pr-11 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                               placeholder="••••••••">
                        <button type="button" id="toggle-pw" tabindex="-1"
                                class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200" aria-label="{{ __('Show password') }}">
                            <svg id="eye" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Remember --}}
                <label class="flex items-center gap-2 text-[13px] font-medium text-ink-600 dark:text-ink-300">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Keep me signed in') }}
                </label>

                <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700 active:bg-brand-800">
                    {{ __('Sign in') }}
                </button>
            </form>

            @if (Route::has('register'))
                <p class="mt-6 text-center text-[13px] text-ink-500 dark:text-ink-400">
                    {{ __("New here?") }}
                    <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Create an account') }}</a>
                </p>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        var btn = document.getElementById('toggle-pw');
        var pw = document.getElementById('password');
        var eye = document.getElementById('eye');
        btn && btn.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            eye.innerHTML = show
                ? '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.6A9.6 9.6 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.4 4M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 3-.5"/>'
                : '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
        });
    })();
</script>
</body>
</html>