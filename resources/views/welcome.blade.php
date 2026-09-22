<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'IBSolutions') }} — {{ __('Numbers & accounts, instantly') }}</title>
    <meta name="description" content="{{ __('Virtual numbers, ready-made accounts and a fast wallet. Delivered the instant you buy.') }}">

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

    <style>
        @keyframes fadeUp { from { opacity:0; transform: translateY(24px); } to { opacity:1; transform:none; } }
        .reveal { opacity:0; }
        .reveal.in { animation: fadeUp .7s cubic-bezier(.22,1,.36,1) both; }
        @media (prefers-reduced-motion: reduce) { .reveal, .reveal.in { animation:none !important; opacity:1 !important; } }

        /* iOS auto-zooms into focused inputs under 16px; the viewport tag blocks
           pinch-zoom, this blocks the focus zoom on the embedded sign-in form. */
        @supports (-webkit-touch-callout: none) {
            input, select, textarea { font-size: 16px; }
        }
    </style>
</head>

<body class="min-h-full bg-white font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-ink-50">

{{-- ═══════════ Nav ═══════════ --}}
<header class="sticky top-0 z-40 border-b border-ink-100 bg-white/85 backdrop-blur-md dark:border-ink-800 dark:bg-ink-950/85">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 lg:h-[70px]">
        <a href="/" class="flex items-center gap-2.5" aria-label="IBSolutions">
            <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-brand-600"><span class="text-[17px] font-bold leading-none tracking-[-0.04em] text-white">IB</span></span>
            <span class="text-[18px] font-bold tracking-[-0.03em]">{{ config('app.name', 'IBSolutions') }}</span>
        </a>
        <nav class="hidden items-center gap-8 text-[14px] font-semibold text-ink-600 md:flex dark:text-ink-300">
            <a href="#features" class="transition hover:text-ink-900 dark:hover:text-ink-50">{{ __('Features') }}</a>
            <a href="#how" class="transition hover:text-ink-900 dark:hover:text-ink-50">{{ __('How it works') }}</a>
            <a href="#faq" class="transition hover:text-ink-900 dark:hover:text-ink-50">{{ __('FAQ') }}</a>
        </nav>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-[13.5px] font-bold text-white transition hover:bg-brand-700">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-xl border border-ink-200 px-4 py-2 text-[13.5px] font-bold transition hover:bg-ink-100 sm:block dark:border-ink-700 dark:hover:bg-ink-900">{{ __('Login') }}</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-[13.5px] font-bold text-white transition hover:bg-brand-700">{{ __('Sign up') }}</a>
            @endauth
        </div>
    </div>
</header>

{{-- ═══════════ Hero with embedded sign-in ═══════════ --}}
<section class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-brand-50/70 to-transparent dark:from-brand-500/5"></div>
    <div class="pointer-events-none absolute -right-40 -top-40 h-96 w-96 rounded-full bg-brand-200/40 blur-3xl dark:bg-brand-500/10"></div>

    <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-5 py-12 lg:grid-cols-2 lg:gap-16 lg:py-20">

        {{-- Left: pitch --}}
        <div class="reveal">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-[12px] font-bold text-brand-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span> {{ __('Trusted by 50,000+ users') }}
            </span>
            <h1 class="mt-5 text-[36px] font-bold leading-[1.05] tracking-[-0.04em] sm:text-[46px] lg:text-[52px]">
                {{ __('Biggest account') }}<br><span class="text-brand-600 dark:text-brand-500">{{ __('portal') }}</span> {{ __('in Africa.') }}
            </h1>
            <p class="mt-5 max-w-md text-[15px] leading-relaxed text-ink-600 dark:text-ink-300">
                {{ __('Hand-picked accounts, virtual numbers for verification, and a wallet built for speed — for any use-case.') }}
            </p>
            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="group flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 text-[14px] font-bold text-white transition hover:bg-brand-700">
                    {{ __('Create account') }}
                    <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <a href="#features" class="flex h-12 items-center justify-center rounded-xl border border-ink-200 px-6 text-[14px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-900">{{ __('View our services') }}</a>
            </div>
            <p class="mt-8 text-[13px] font-medium text-ink-500 dark:text-ink-400">
                <span class="font-bold text-brand-600 dark:text-brand-500">50,000</span> {{ __('individuals and companies trust us') }}
            </p>
        </div>

        {{-- Right: sign-in card --}}
        <div class="reveal">
            <div class="mx-auto w-full max-w-md rounded-3xl border border-ink-100 bg-white p-6 shadow-2xl shadow-ink-900/10 sm:p-8 dark:border-ink-800 dark:bg-ink-900 dark:shadow-black/40">
                @auth
                    <div class="py-8 text-center">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
                        <h2 class="mt-4 text-[20px] font-bold tracking-[-0.02em]">{{ __('Welcome back') }}</h2>
                        <p class="mt-1 text-[13.5px] text-ink-500 dark:text-ink-400">{{ __('You are signed in.') }}</p>
                        <a href="{{ route('dashboard') }}" class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Go to dashboard') }}</a>
                    </div>
                @else
                    <h2 class="text-center text-[22px] font-bold tracking-[-0.02em]">{{ __('Sign in') }}</h2>

                    @if ($errors->any())
                        <p class="mt-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $errors->first() }}</p>
                    @endif
                    @if (session('status'))
                        <p class="mt-4 rounded-xl border border-emerald-600/25 bg-emerald-50 p-3 text-center text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</p>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label for="login" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Username or Email') }}</label>
                            <input id="login" name="login" type="text" value="{{ old('login') }}" required autocomplete="username" placeholder="{{ __('username or you@example.com') }}"
                                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-950">
                        </div>
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Password') }}</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-[12px] font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Forgot password?') }}</a>
                                @endif
                            </div>
                            <div class="relative mt-1.5">
                                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••"
                                       class="h-12 w-full rounded-xl border-ink-300 pr-11 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-950">
                                <button type="button" id="toggle-pw" tabindex="-1" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-400 transition hover:text-ink-700 dark:hover:text-ink-200" aria-label="{{ __('Show password') }}">
                                    <svg id="eye" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-[13px] font-medium text-ink-600 dark:text-ink-300">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"> {{ __('Remember me') }}
                        </label>
                        <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700 active:bg-brand-800">{{ __('Sign in') }}</button>
                    </form>

                    <a href="{{ route('register') }}" class="mt-3 flex h-12 w-full items-center justify-center rounded-xl border border-brand-600/30 text-[14px] font-bold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Create an account') }}</a>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ Features ═══════════ --}}
<section id="features" class="border-t border-ink-100 dark:border-ink-800">
    <div class="mx-auto max-w-6xl px-5 py-16 lg:py-20">
        <div class="reveal grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['hash','Crafted','Hand-picked accounts and numbers for your exact use-case.'],
                ['sliders','Customizable','Filter by country, service and quality in seconds.'],
                ['book','Documented','Every account comes with a clear format and details.'],
                ['chat','Active support','Our team is on hand 24/7 for quick help.'],
            ] as $i => [$icon,$title,$desc])
                <div class="reveal" style="animation-delay: {{ $i * 80 }}ms">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        @switch($icon)
                            @case('hash')   <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg> @break
                            @case('sliders') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M4 6h10M18 6h2M4 12h2M10 12h10M4 18h8M16 18h4"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="14" cy="18" r="2"/></svg> @break
                            @case('book')   <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2Z"/><path d="M8 3v18"/></svg> @break
                            @default        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L3 21l1.8-6.5A8 8 0 1 1 21 12Z"/></svg>
                        @endswitch
                    </span>
                    <h3 class="mt-4 text-[16px] font-bold">{{ __($title) }}</h3>
                    <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-600 dark:text-ink-300">{{ __($desc) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ How it works ═══════════ --}}
<section id="how" class="border-t border-ink-100 bg-ink-50/50 dark:border-ink-800 dark:bg-ink-900/40">
    <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 py-16 lg:grid-cols-2 lg:py-20">
        <div class="reveal">
            <h2 class="text-[30px] font-bold tracking-[-0.03em] sm:text-[36px]">{{ __('How it works') }}</h2>
            <ol class="mt-8 space-y-5">
                @foreach ([
                    'Sign up in under a minute — it\'s free.',
                    'Fund your wallet, easily.',
                    'Choose the service you want to purchase.',
                    'Enter the quantity and place your order.',
                    'Get it instantly on your dashboard.',
                ] as $i => $step)
                    <li class="reveal flex items-center gap-4" style="animation-delay: {{ $i * 70 }}ms">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-brand-600 text-[13px] font-bold text-brand-600 dark:text-brand-500">{{ $i + 1 }}</span>
                        <p class="text-[15px] font-medium">{{ __($step) }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
        <div class="reveal">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-100 to-brand-50 p-10 dark:from-brand-500/15 dark:to-brand-500/5">
                <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full border-[20px] border-white/50 dark:border-white/5"></div>
                <div class="pointer-events-none absolute bottom-6 right-16 h-28 w-28 rounded-full border-[14px] border-white/40 dark:border-white/5"></div>
                <p class="relative text-[30px] font-bold leading-tight tracking-[-0.03em] text-brand-700 dark:text-brand-400">{{ __('Get curated premium accounts within seconds.') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ Testimonial ═══════════ --}}
<section class="border-t border-ink-100 dark:border-ink-800">
    <div class="mx-auto max-w-3xl px-5 py-16 text-center lg:py-20">
        <div class="reveal">
            <svg class="mx-auto h-10 w-10 text-brand-200 dark:text-brand-500/30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 7H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v2a2 2 0 0 1-2 2H5v2h2a4 4 0 0 0 4-4V9a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v2a2 2 0 0 1-2 2h-2v2h2a4 4 0 0 0 4-4V9a2 2 0 0 0-2-2Z"/></svg>
            <p class="mt-6 text-[22px] font-medium leading-snug tracking-[-0.02em] text-ink-800 sm:text-[26px] dark:text-ink-100">
                {{ __('“Very happy with my purchases so far. The service and quality are outstanding — clear, fast and reliable.”') }}
            </p>
            <p class="mt-6 text-[15px] font-bold">Philip</p>
            <p class="text-[13px] text-ink-500 dark:text-ink-400">Lagos, Nigeria</p>
        </div>
    </div>
</section>

{{-- ═══════════ Stats ═══════════ --}}
<section class="border-t border-ink-100 bg-ink-50/50 dark:border-ink-800 dark:bg-ink-900/40">
    <div class="mx-auto max-w-6xl px-5 py-16 lg:py-20">
        <h2 class="reveal text-center text-[30px] font-bold tracking-[-0.03em] sm:text-[36px]">{{ config('app.name', 'IBSolutions') }} {{ __('in numbers') }}</h2>
        <div class="mt-10 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                ['350,000+','Users empowered','Serving a thriving community.','users'],
                ['480,000+','Orders completed','And we\'re just getting started.','rocket'],
                ['2024','Operating since','Revolutionising the logs industry.','calendar'],
                ['1,500+','Partners','The best partners, ready to deliver.','award'],
            ] as $i => [$num,$label,$sub,$icon])
                <div class="reveal flex flex-col rounded-2xl border border-ink-100 p-5 {{ $i === 1 ? 'bg-gradient-to-br from-brand-600 to-brand-700 text-white' : 'dark:border-ink-800' }}" style="animation-delay: {{ $i * 70 }}ms">
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $i === 1 ? 'bg-white/15 text-white' : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' }}">
                        @switch($icon)
                            @case('users')  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5a3 3 0 0 1 0 6"/></svg> @break
                            @case('rocket') <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13c-1.5 1.5-2 5-2 5s3.5-.5 5-2M12 7l5 5M15 4l5 5-9 9-5-1-1-5 9-9Z"/></svg> @break
                            @case('calendar')<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg> @break
                            @default        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M8.5 12 7 21l5-3 5 3-1.5-9"/></svg>
                        @endswitch
                    </span>
                    <p class="mt-4 font-mono text-[26px] font-bold tracking-[-0.03em] sm:text-[30px]">{{ $num }}</p>
                    <p class="text-[13px] font-semibold {{ $i === 1 ? 'text-white/90' : '' }}">{{ __($label) }}</p>
                    <p class="mt-auto pt-3 text-[11.5px] {{ $i === 1 ? 'text-white/70' : 'text-ink-500 dark:text-ink-400' }}">{{ __($sub) }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FAQ ═══════════ --}}
<section id="faq" class="border-t border-ink-100 dark:border-ink-800">
    <div class="mx-auto max-w-3xl px-5 py-16 lg:py-20">
        <div class="reveal text-center">
            <h2 class="text-[30px] font-bold tracking-[-0.03em] sm:text-[36px]">{{ __('FAQ') }}</h2>
            <p class="mt-3 text-[15px] text-ink-600 dark:text-ink-300">{{ __('Answers to the most commonly asked questions.') }}</p>
        </div>
        <div class="mt-10 space-y-3">
            @foreach ([
                ['What is '.config('app.name', 'IBSolutions').'?', 'A platform to buy virtual numbers for verification and ready-made accounts, funded by a fast in-app wallet.'],
                ['What payment methods do you accept?', 'Card, bank transfer, USSD and QR — through our secure payment partner. Funds reflect instantly.'],
                ['Why should I use '.config('app.name', 'IBSolutions').'?', 'Instant delivery, hand-picked quality, fair pricing and 24/7 human support.'],
                ['How do I leave a review?', 'Once you have made a purchase, you can leave a review from your orders page.'],
                ['Do you offer discounts?', 'Yes — keep an eye on announcements and the affiliate programme for savings.'],
            ] as $q)
                <details class="reveal group rounded-2xl border border-ink-100 p-5 dark:border-ink-800">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-[15px] font-bold">
                        {{ __($q[0]) }}
                        <svg class="h-5 w-5 shrink-0 text-brand-500 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <p class="mt-3 text-[14px] leading-relaxed text-ink-600 dark:text-ink-300">{{ __($q[1]) }}</p>
                </details>
            @endforeach
        </div>
        <p class="reveal mt-8 text-center text-[14px] text-ink-600 dark:text-ink-300">
            {{ __('More questions?') }} <a href="#" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Contact us') }}</a>
        </p>
    </div>
</section>

{{-- ═══════════ CTA ═══════════ --}}
<section class="mx-auto max-w-6xl px-5 py-16 lg:py-20">
    <div class="reveal overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 px-8 py-14 text-center text-white sm:px-16">
        <h2 class="text-[30px] font-bold tracking-[-0.03em] sm:text-[38px]">{{ __('Ready to get started?') }}</h2>
        <p class="mx-auto mt-3 max-w-md text-[15px] text-white/85">{{ __('Join thousands who buy numbers and accounts the fast way. It only takes a minute.') }}</p>
        <a href="{{ route('register') }}" class="mt-8 inline-flex h-12 items-center justify-center rounded-xl bg-white px-7 text-[14px] font-bold text-brand-700 transition hover:bg-white/90">{{ __('Create your free account') }}</a>
    </div>
</section>

{{-- ═══════════ Footer ═══════════ --}}
<footer class="border-t border-ink-100 dark:border-ink-800">
    <div class="mx-auto max-w-6xl px-5 py-12">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
            <div class="col-span-2 sm:col-span-1">
                <a href="/" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-brand-600"><span class="text-[17px] font-bold leading-none tracking-[-0.04em] text-white">IB</span></span>
                    <span class="text-[17px] font-bold tracking-[-0.03em]">{{ config('app.name', 'IBSolutions') }}</span>
                </a>
                <p class="mt-3 text-[13px] text-ink-500 dark:text-ink-400">{{ __('The all-in-one platform for numbers and accounts.') }}</p>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Company') }}</p>
                <ul class="mt-3 space-y-2 text-[13.5px] font-medium text-ink-600 dark:text-ink-300">
                    <li><a href="#features" class="transition hover:text-brand-600">{{ __('Features') }}</a></li>
                    <li><a href="#how" class="transition hover:text-brand-600">{{ __('How it works') }}</a></li>
                    <li><a href="#faq" class="transition hover:text-brand-600">{{ __('FAQ') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Account') }}</p>
                <ul class="mt-3 space-y-2 text-[13.5px] font-medium text-ink-600 dark:text-ink-300">
                    <li><a href="{{ route('login') }}" class="transition hover:text-brand-600">{{ __('Login') }}</a></li>
                    <li><a href="{{ route('register') }}" class="transition hover:text-brand-600">{{ __('Sign up') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Support') }}</p>
                <ul class="mt-3 space-y-2 text-[13.5px] font-medium text-ink-600 dark:text-ink-300">
                    <li>{{ __('24/7 human support') }}</li>
                    <li>{{ __('Card · Transfer · USSD · QR') }}</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-ink-100 pt-6 text-[12.5px] text-ink-500 sm:flex-row dark:border-ink-800 dark:text-ink-400">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'IBSolutions') }}. {{ __('All rights reserved.') }}</span>
            <span class="flex gap-5">
                <a href="#" class="transition hover:text-ink-900 dark:hover:text-ink-100">{{ __('Terms') }}</a>
                <a href="#" class="transition hover:text-ink-900 dark:hover:text-ink-100">{{ __('Privacy') }}</a>
            </span>
        </div>
    </div>
</footer>

<script>
    // Password toggle
    (function () {
        var btn = document.getElementById('toggle-pw'), pw = document.getElementById('password'), eye = document.getElementById('eye');
        btn && btn.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            eye.innerHTML = show
                ? '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 4.6A9.6 9.6 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.4 4M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 3-.5"/>'
                : '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
        });
    })();
    // Scroll reveal
    (function () {
        var els = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) { els.forEach(function (e){ e.classList.add('in'); }); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.1 });
        els.forEach(function (e){ io.observe(e); });
    })();
</script>
</body>
</html>