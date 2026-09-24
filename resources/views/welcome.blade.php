<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $brand = config('app.name', 'Tavinzos'); @endphp
    <title>{{ $brand }} — {{ __('SMS verification & accounts, made simple') }}</title>
    <meta name="description" content="{{ __('Buy virtual numbers for OTPs and ready-made accounts for the apps you use. One wallet, one dashboard, instant delivery.') }}">

    <script>
        (function () {
            var t = localStorage.getItem('theme');
            var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @include('partials.head')
</head>
<body class="min-h-full bg-white font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-ink-50">

{{-- ═══════════════ Nav ═══════════════ --}}
<header class="absolute inset-x-0 top-0 z-30">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-8 sm:py-5">
        @include('partials.logo', ['size' => 'md'])
        <div class="hidden items-center gap-8 text-[13.5px] font-semibold text-white/80 lg:flex">
            <a href="#services" class="transition hover:text-white">{{ __('Services') }}</a>
            <a href="#how" class="transition hover:text-white">{{ __('How it works') }}</a>
            <a href="#why" class="transition hover:text-white">{{ __('Why us') }}</a>
            <a href="#faq" class="transition hover:text-white">{{ __('FAQ') }}</a>
        </div>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-600 px-3.5 py-2.5 text-[13px] font-bold text-white transition hover:bg-brand-700 sm:px-4">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-xl border border-white/20 px-4 py-2.5 text-[13px] font-bold text-white transition hover:bg-white/10 sm:block">{{ __('Log in') }}</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-3.5 py-2.5 text-[13px] font-bold text-white transition hover:bg-brand-700 sm:px-4">{{ __('Sign up') }}</a>
            @endauth
        </div>
    </nav>
</header>

{{-- ═══════════════ Hero ═══════════════ --}}
<section class="relative overflow-hidden bg-gradient-to-br from-ink-950 via-ink-900 to-brand-900">
    <div class="pointer-events-none absolute -right-40 top-20 h-[400px] w-[400px] rounded-full bg-brand-500/20 blur-[120px] sm:h-[500px] sm:w-[500px]"></div>
    <div class="pointer-events-none absolute -left-20 bottom-0 h-72 w-72 rounded-full bg-brand-600/10 blur-[100px]"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 pb-14 pt-28 sm:px-8 sm:pb-20 sm:pt-32 lg:grid-cols-2 lg:gap-8 lg:pb-24 lg:pt-36">
        {{-- Left: copy --}}
        <div class="flex flex-col justify-center text-center lg:text-left">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-brand-400 sm:text-[12px] sm:tracking-[0.18em]">{{ __('Virtual numbers & ready-made accounts') }}</p>
            <h1 class="mt-4 text-[38px] font-extrabold leading-[1.02] tracking-[-0.035em] text-white sm:mt-5 sm:text-[54px] lg:text-[62px] lg:leading-[0.98]">
                {{ __('SMS verification,') }}<br>
                <span class="text-brand-400">{{ __('made simple.') }}</span>
            </h1>
            <p class="mx-auto mt-5 max-w-md text-[14.5px] leading-relaxed text-white/70 sm:mt-6 sm:text-[15px] lg:mx-0">
                {{ __('Rent virtual numbers to receive OTP codes for any app, and buy ready-made accounts delivered the instant you pay. One wallet, one dashboard.') }}
            </p>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-center lg:justify-start">
                <a href="{{ route('register') }}" class="flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 text-[14px] font-bold text-white transition hover:bg-brand-700">
                    {{ __('Get started') }}
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a href="{{ route('login') }}" class="flex h-12 items-center justify-center rounded-xl border border-white/15 px-6 text-[14px] font-bold text-white transition hover:bg-white/10">{{ __('I have an account') }}</a>
            </div>

            {{-- Feature strip --}}
            <div class="mt-9 grid grid-cols-1 gap-4 text-left sm:grid-cols-3">
                @foreach ([
                    ['t' => __('Instant delivery'), 's' => __('Codes & accounts in seconds'), 'i' => 'bolt'],
                    ['t' => __('Safe & trusted'), 's' => __('Your wallet is protected'), 'i' => 'shield'],
                    ['t' => __('Always available'), 's' => __('200+ countries, 24/7'), 'i' => 'globe'],
                ] as $f)
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-500/15 text-brand-400">
                            @switch($f['i'])
                                @case('bolt') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.5H11l-1 8.5L19.5 10H13l1-8Z"/></svg> @break
                                @case('shield') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3 20 7v6c0 4.5-3.3 7-8 8-4.7-1-8-3.5-8-8V7l8-4Z"/><path d="m9 12 2 2 4-4"/></svg> @break
                                @default <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg>
                            @endswitch
                        </span>
                        <div>
                            <p class="text-[13px] font-bold text-white">{{ $f['t'] }}</p>
                            <p class="text-[11.5px] text-white/50">{{ $f['s'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Right: phone mockup (hidden on very small, shown from sm) --}}
        <div class="relative hidden items-center justify-center sm:flex lg:justify-end">
            <div class="relative w-[280px] rounded-[36px] border-[6px] border-ink-800 bg-white shadow-2xl sm:w-[300px] lg:w-[320px]">
                <div class="rounded-[30px] bg-white p-5 dark:bg-ink-900">
                    <div class="flex items-center justify-between">
                        @include('partials.logo', ['size' => 'sm'])
                        <span class="text-[10px] text-ink-400">9:41</span>
                    </div>
                    <p class="mt-5 text-[13px] text-ink-500">{{ __('Welcome back,') }}</p>
                    <p class="text-[17px] font-bold">{{ __('User') }} 👋</p>
                    <div class="mt-4 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-700 p-4 text-white">
                        <p class="text-[10.5px] font-semibold text-white/70">{{ __('Wallet balance') }}</p>
                        <p class="mt-1 font-mono text-[24px] font-bold">₦12,450</p>
                        <span class="mt-3 inline-flex rounded-lg bg-white/20 px-3 py-1.5 text-[11px] font-bold">{{ __('Fund wallet') }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                        @foreach (['Buy a number','Buy accounts','Orders','History'] as $q)
                            <div class="rounded-xl border border-ink-100 p-3 text-center dark:border-ink-800">
                                <div class="mx-auto mb-1.5 h-6 w-6 rounded-lg bg-brand-50 dark:bg-brand-500/10"></div>
                                <p class="text-[10.5px] font-semibold">{{ __($q) }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[11px] font-bold text-ink-500">{{ __('Popular services') }}</p>
                    <div class="mt-2 space-y-2">
                        @foreach (['WhatsApp','Telegram','Instagram'] as $svc)
                            <div class="flex items-center justify-between rounded-lg border border-ink-100 px-3 py-2 dark:border-ink-800">
                                <span class="text-[11.5px] font-semibold">{{ $svc }}</span>
                                <span class="font-mono text-[10.5px] text-brand-600 dark:text-brand-400">{{ __('From ₦150') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Service logos strip --}}
    <div class="relative border-t border-white/10 bg-ink-950/50">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-6 gap-y-3 px-4 py-5 text-[12.5px] font-semibold text-white/60 sm:gap-x-8 sm:px-8 sm:py-6 sm:text-[13px]">
            @foreach (['WhatsApp','Telegram','Instagram','Facebook','TikTok','Google','X'] as $svc)
                <span>{{ $svc }}</span>
            @endforeach
            <span class="text-white/30">{{ __('… and more') }}</span>
        </div>
    </div>
</section>

{{-- ═══════════════ Stats ═══════════════ --}}
<section class="border-b border-ink-200 py-12 dark:border-ink-800 sm:py-14">
    <div class="mx-auto grid max-w-5xl grid-cols-2 gap-6 px-4 sm:px-8 md:grid-cols-4">
        @foreach ([
            ['10,000+', __('Happy users')],
            ['200+', __('Countries')],
            ['500K+', __('Codes delivered')],
            ['24/7', __('Always online')],
        ] as $stat)
            <div class="text-center">
                <p class="font-mono text-[28px] font-extrabold tracking-[-0.02em] text-brand-600 dark:text-brand-400 sm:text-[34px]">{{ $stat[0] }}</p>
                <p class="mt-1 text-[12.5px] font-semibold text-ink-500 dark:text-ink-400">{{ $stat[1] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════ Services ═══════════════ --}}
<section id="services" class="mx-auto max-w-7xl px-4 py-16 sm:px-8 sm:py-20">
    <h2 class="text-center text-[28px] font-extrabold tracking-[-0.03em] sm:text-[40px]">{{ __('Everything you need') }}</h2>
    <p class="mx-auto mt-3 max-w-md text-center text-[14px] text-ink-500 dark:text-ink-400 sm:text-[15px]">{{ __('Two core services, one balance. Buy, receive, done.') }}</p>

    <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-12 sm:gap-5 md:grid-cols-2">
        <div class="rounded-3xl border border-ink-200 p-6 dark:border-ink-800 sm:p-8">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
            </span>
            <h3 class="mt-5 text-[19px] font-bold sm:text-[20px]">{{ __('Virtual numbers') }}</h3>
            <p class="mt-2 text-[13.5px] leading-relaxed text-ink-500 dark:text-ink-400 sm:text-[14px]">{{ __('Rent a number, receive the OTP code for WhatsApp, Telegram, Instagram and 200+ services. USA numbers deliver fastest.') }}</p>
            <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-1.5 text-[13.5px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('Buy a number') }} <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
        <div class="rounded-3xl border border-ink-200 p-6 dark:border-ink-800 sm:p-8">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
            </span>
            <h3 class="mt-5 text-[19px] font-bold sm:text-[20px]">{{ __('Ready-made accounts') }}</h3>
            <p class="mt-2 text-[13.5px] leading-relaxed text-ink-500 dark:text-ink-400 sm:text-[14px]">{{ __('Buy verified social and email accounts, delivered to your dashboard the instant you pay. Login details, ready to use.') }}</p>
            <a href="{{ route('register') }}" class="mt-4 inline-flex items-center gap-1.5 text-[13.5px] font-bold text-brand-600 hover:underline dark:text-brand-400">{{ __('Browse accounts') }} <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
    </div>
</section>

{{-- ═══════════════ Why us ═══════════════ --}}
<section id="why" class="bg-ink-50 py-16 dark:bg-ink-900/40 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-8">
        <h2 class="text-center text-[28px] font-extrabold tracking-[-0.03em] sm:text-[40px]">{{ __('Why choose us') }}</h2>
        <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['bolt', __('Lightning fast'), __('Codes and accounts arrive in seconds, not minutes. USA numbers are the fastest.')],
                ['wallet', __('Fair pricing'), __('Transparent prices with no hidden fees. Pay only for what delivers.')],
                ['refund', __('Full refunds'), __('No code? Cancel the number and get every naira back, instantly.')],
                ['lock', __('Secure wallet'), __('Bank-grade protection. Fund by card or transfer to your dedicated account.')],
                ['clock', __('24/7 service'), __('Buy anytime, day or night. Our system never sleeps.')],
                ['chat', __('Real support'), __('Stuck? Reach our team on WhatsApp and get help fast.')],
            ] as $w)
                <div class="rounded-2xl bg-white p-6 dark:bg-ink-950">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        @switch($w[0])
                            @case('bolt') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 4.5 13.5H11l-1 8.5L19.5 10H13l1-8Z"/></svg> @break
                            @case('wallet') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5.5" width="19" height="13" rx="2.5"/><path d="M2.5 10h19"/></svg> @break
                            @case('refund') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg> @break
                            @case('lock') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg> @break
                            @case('clock') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> @break
                            @default <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/></svg>
                        @endswitch
                    </span>
                    <h3 class="mt-4 text-[16px] font-bold">{{ $w[1] }}</h3>
                    <p class="mt-1.5 text-[13px] leading-relaxed text-ink-500 dark:text-ink-400">{{ $w[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════ How it works ═══════════════ --}}
<section id="how" class="mx-auto max-w-7xl px-4 py-16 sm:px-8 sm:py-20">
    <h2 class="text-center text-[28px] font-extrabold tracking-[-0.03em] sm:text-[40px]">{{ __('How it works') }}</h2>
    <div class="mt-10 grid grid-cols-1 gap-5 sm:mt-12 md:grid-cols-3 md:gap-6">
        @foreach ([
            ['1', __('Fund your wallet'), __('Add money with a card or a bank transfer to your dedicated account.')],
            ['2', __('Pick what you need'), __('Choose a number and service, or browse ready-made accounts.')],
            ['3', __('Get it instantly'), __('Your code or account arrives on your dashboard right away.')],
        ] as $step)
            <div class="rounded-3xl border border-ink-200 p-6 dark:border-ink-800 sm:p-7">
                <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-600 font-mono text-[15px] font-bold text-white">{{ $step[0] }}</span>
                <h3 class="mt-5 text-[17px] font-bold">{{ $step[1] }}</h3>
                <p class="mt-2 text-[13.5px] leading-relaxed text-ink-500 dark:text-ink-400">{{ $step[2] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════ Testimonials ═══════════════ --}}
<section class="bg-ink-50 py-16 dark:bg-ink-900/40 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-8">
        <h2 class="text-center text-[28px] font-extrabold tracking-[-0.03em] sm:text-[40px]">{{ __('Loved by users') }}</h2>
        <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-12 sm:gap-5 md:grid-cols-3">
            @foreach ([
                [__('Codes come through in seconds every time. Best verification service I have used.'), 'T.A.', __('Lagos')],
                [__('Bought a Facebook account and got the login instantly. No stress at all.'), 'C.S.', __('Abuja')],
                [__('Funded with a transfer and it reflected right away. Support is fast too.'), 'E.R.', __('Port Harcourt')],
            ] as $t)
                <div class="rounded-2xl bg-white p-6 dark:bg-ink-950">
                    <div class="flex gap-0.5 text-amber-400">
                        @for ($i = 0; $i < 5; $i++)<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.6 6.7L12 17.3 5.8 20.8l1.6-6.7L2.2 8.9l6.9-.6L12 2Z"/></svg>@endfor
                    </div>
                    <p class="mt-3 text-[13.5px] leading-relaxed text-ink-700 dark:text-ink-200">"{{ $t[0] }}"</p>
                    <div class="mt-4 flex items-center gap-2.5">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $t[1] }}</span>
                        <span class="text-[12px] text-ink-400">{{ $t[2] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════ CTA ═══════════════ --}}
<section class="relative overflow-hidden bg-gradient-to-br from-brand-600 to-brand-800 py-16 sm:py-20">
    <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-2xl"></div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-8">
        <h2 class="text-[28px] font-extrabold tracking-[-0.03em] text-white sm:text-[40px]">{{ __('Ready to get started?') }}</h2>
        <p class="mx-auto mt-3 max-w-md text-[14.5px] text-white/80 sm:text-[15px]">{{ __('Create a free account and get your first number or account in minutes.') }}</p>
        <a href="{{ route('register') }}" class="mt-7 inline-flex h-12 items-center justify-center rounded-xl bg-white px-8 text-[14px] font-bold text-brand-700 transition hover:bg-white/90 sm:mt-8">{{ __('Create your free account') }}</a>
    </div>
</section>

{{-- ═══════════════ FAQ ═══════════════ --}}
<section id="faq" class="mx-auto max-w-3xl px-4 py-16 sm:px-8 sm:py-20">
    <h2 class="text-center text-[28px] font-extrabold tracking-[-0.03em] sm:text-[40px]">{{ __('Common questions') }}</h2>
    <div class="mt-8 space-y-3 sm:mt-10">
        @foreach ([
            [__('How fast do codes arrive?'), __('USA numbers usually deliver in seconds. Enter the number in the app and request the code — it appears on your dashboard.')],
            [__('What if I don\'t get a code?'), __('If no code arrives, cancel the number for a full refund. You only keep numbers that deliver.')],
            [__('How are accounts delivered?'), __('Instantly. The login details appear on your orders page the moment your payment is confirmed.')],
            [__('How do I fund my wallet?'), __('Pay with a card, or transfer to your dedicated bank account. Your balance updates automatically.')],
            [__('Is my money safe?'), __('Yes. Your wallet is protected and every transaction is recorded. Refunds are automatic when a number does not deliver.')],
        ] as $qa)
            <details class="group rounded-2xl border border-ink-200 p-5 dark:border-ink-800">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-[14.5px] font-bold sm:text-[15px]">
                    {{ $qa[0] }}
                    <svg class="h-5 w-5 shrink-0 text-ink-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <p class="mt-3 text-[13.5px] leading-relaxed text-ink-500 dark:text-ink-400 sm:text-[14px]">{{ $qa[1] }}</p>
            </details>
        @endforeach
    </div>
</section>

{{-- ═══════════════ Footer ═══════════════ --}}
<footer class="border-t border-ink-200 py-10 dark:border-ink-800">
    <div class="mx-auto flex max-w-7xl flex-col items-center gap-5 px-4 sm:px-8 md:flex-row md:justify-between">
        @include('partials.logo', ['size' => 'sm'])
        <p class="text-[12px] text-ink-400 sm:text-[12.5px]">&copy; {{ date('Y') }} {{ $brand }}. {{ __('All rights reserved.') }}</p>
        <div class="flex gap-5 text-[12.5px] font-semibold text-ink-500 dark:text-ink-400">
            <a href="{{ route('login') }}" class="transition hover:text-brand-600">{{ __('Log in') }}</a>
            <a href="{{ route('register') }}" class="transition hover:text-brand-600">{{ __('Sign up') }}</a>
            <a href="{{ Route::has('support.index') ? route('support.index') : '#' }}" class="transition hover:text-brand-600">{{ __('Support') }}</a>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>