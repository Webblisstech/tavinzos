<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Admin sign in') }} · {{ config('app.name', 'Numera') }}</title>

    <script>
        (function () {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @include('partials.head')
</head>

<body class="grid min-h-full place-items-center bg-ink-100 px-4 font-sans text-ink-900 antialiased dark:bg-black dark:text-ink-50">

    <div class="anim-rise w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center text-center">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-ink-900 dark:bg-ink-100">
                <svg class="h-6 w-6 text-white dark:text-ink-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 7v6c0 5 3.5 8 9 9 5.5-1 9-4 9-9V7l-9-5Z"/></svg>
            </span>
            <h1 class="mt-4 text-[19px] font-bold tracking-[-0.03em]">{{ config('app.name', 'Numera') }} {{ __('Admin') }}</h1>
            <p class="mt-1 text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Staff access only.') }}</p>
        </div>

        <div class="card p-6">
            @if ($errors->any())
                <p class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">
                    {{ $errors->first() }}
                </p>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-3.5">
                @csrf

                <div>
                    <label for="email" class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
                </div>

                <div>
                    <label for="password" class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
                </div>

                <label class="flex items-center gap-2 text-[12.5px] font-medium text-ink-600 dark:text-ink-300">
                    <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Keep me signed in') }}
                </label>

                <button type="submit" class="btn-primary h-12 w-full text-[14px]">{{ __('Sign in') }}</button>
            </form>
        </div>

        <p class="mt-5 text-center text-[12px] text-ink-500 dark:text-ink-400">
            <a href="{{ url('/') }}" class="hover:text-ink-900 dark:hover:text-ink-100">&larr; {{ __('Back to store') }}</a>
        </p>
    </div>
</body>
</html>