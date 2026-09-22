<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Create account') }} · {{ config('app.name', 'Numera') }}</title>

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
                <h1 class="text-[34px] font-bold leading-[1.1] tracking-[-0.03em]">{{ __('Create your') }}<br>{{ __('account today.') }}</h1>
                <p class="mt-4 max-w-md text-[14px] leading-relaxed text-white/80">{{ __('Get instant access to virtual numbers, ready-made accounts and a wallet built for speed. It only takes a minute.') }}</p>
            </div>

            <ul class="space-y-2.5 text-[13px] text-white/80">
                <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg> {{ __('Numbers from 200+ countries') }}</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg> {{ __('Accounts delivered instantly') }}</li>
                <li class="flex items-center gap-2"><svg class="h-4 w-4 shrink-0 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg> {{ __('Secure card & transfer top-ups') }}</li>
            </ul>
        </div>
    </div>

    {{-- ═══════════ Form panel ═══════════ --}}
    <div class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-sm">

            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 text-white">
                    <span class="font-sans text-[18px] font-bold leading-none tracking-[-0.04em] text-white">IB</span>
                </span>
                <span class="text-[18px] font-bold tracking-[-0.03em]">IBSolutions</span>
            </div>

            <h2 class="text-[26px] font-bold tracking-[-0.03em]">{{ __('Create account') }}</h2>
            <p class="mt-1 text-[13.5px] text-ink-500 dark:text-ink-400">{{ __('Start buying numbers and accounts in minutes.') }}</p>

            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="{{ __('Your name') }}">
                    @error('name')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Username --}}
                <div>
                    <label for="username" class="flex items-center justify-between text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">
                        {{ __('Username') }}
                        <span class="text-[11px] font-normal text-ink-400">{{ __('optional') }}</span>
                    </label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" maxlength="40" autocomplete="username"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="{{ __('choose a username') }}">
                    @error('username')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="you@example.com">
                    @error('email')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label for="phone" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Phone number') }}</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="08012345678">
                    @error('phone')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Password') }}</label>
                    <div class="relative mt-1.5">
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="h-12 w-full rounded-xl border-ink-300 pr-11 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                               placeholder="••••••••">
                        <button type="button" data-toggle="password" tabindex="-1" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200" aria-label="{{ __('Show password') }}">
                            <svg class="eye h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Confirm password --}}
                <div>
                    <label for="password_confirmation" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Confirm password') }}</label>
                    <div class="relative mt-1.5">
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="h-12 w-full rounded-xl border-ink-300 pr-11 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                               placeholder="••••••••">
                        <button type="button" data-toggle="password_confirmation" tabindex="-1" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200" aria-label="{{ __('Show password') }}">
                            <svg class="eye h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password_confirmation')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                {{-- Referral code (optional) — pre-filled from a ?ref= link/cookie if present --}}
                @php $prefRef = old('ref', request('ref', request()->cookie('ref') ?? '')); @endphp
                <div>
                    <label for="ref" class="flex items-center justify-between text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">
                        {{ __('Referral code') }}
                        <span class="text-[11px] font-normal text-ink-400">{{ __('optional') }}</span>
                    </label>
                    <input id="ref" name="ref" type="text" value="{{ $prefRef }}" maxlength="12" autocapitalize="characters"
                           class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] uppercase tracking-wide transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900"
                           placeholder="{{ __('e.g. AB12CD') }}">
                    @error('ref')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700 active:bg-brand-800">
                    {{ __('Create account') }}
                </button>
            </form>

            <p class="mt-6 text-center text-[13px] text-ink-500 dark:text-ink-400">
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Sign in') }}</a>
            </p>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.toggle);
            var eye = btn.querySelector('.eye');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.innerHTML = show
                ? '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.6A9.6 9.6 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.4 4M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 3-.5"/>'
                : '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
        });
    });
</script>
</body>
</html>