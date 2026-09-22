<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ __('Under maintenance') }} · {{ config('app.name', 'IBSolutions') }}</title>
    <meta name="robots" content="noindex">

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
        @keyframes rise { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform:none; } }
        .rise { opacity:0; animation: rise .8s cubic-bezier(.22,1,.36,1) forwards; }
        .rise-1 { animation-delay:.05s } .rise-2 { animation-delay:.15s } .rise-3 { animation-delay:.25s } .rise-4 { animation-delay:.35s } .rise-5 { animation-delay:.45s }

        @keyframes float { 0%,100%{ transform: translateY(0) rotate(0deg) } 50%{ transform: translateY(-14px) rotate(3deg) } }
        .float { animation: float 6s ease-in-out infinite; }

        @keyframes spinslow { to { transform: rotate(360deg) } }
        .cog { animation: spinslow 14s linear infinite; transform-origin: center; }
        .cog-rev { animation: spinslow 10s linear infinite reverse; transform-origin: center; }

        @keyframes pulse-ring { 0%{ transform: scale(.9); opacity:.6 } 70%{ transform: scale(1.5); opacity:0 } 100%{ opacity:0 } }
        .ring { animation: pulse-ring 3s cubic-bezier(0,0,.2,1) infinite; }

        @keyframes drift { from { background-position: 0 0 } to { background-position: 60px 60px } }
        .grid-bg { animation: drift 8s linear infinite; }

        @keyframes shimmer { 0%{ background-position: -200% 0 } 100%{ background-position: 200% 0 } }
        .bar-fill { background-size: 200% 100%; animation: shimmer 2.2s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .rise,.float,.cog,.cog-rev,.ring,.grid-bg,.bar-fill { animation: none !important; opacity:1 !important; }
        }
    </style>
</head>

<body class="relative min-h-full overflow-hidden bg-white font-sans text-ink-900 antialiased dark:bg-ink-950 dark:text-ink-50">

    {{-- Ambient background --}}
    <div class="pointer-events-none absolute inset-0 grid-bg opacity-[0.4] dark:opacity-[0.25]"
         style="background-image: radial-gradient(circle at 1px 1px, rgba(217,31,44,0.18) 1px, transparent 0); background-size: 30px 30px;"></div>
    <div class="pointer-events-none absolute -left-40 -top-40 h-[28rem] w-[28rem] rounded-full bg-brand-200/40 blur-3xl dark:bg-brand-500/10"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-40 h-[28rem] w-[28rem] rounded-full bg-brand-100/50 blur-3xl dark:bg-brand-500/[0.07]"></div>

    <main class="relative grid min-h-screen place-items-center px-6 py-12">
        <div class="w-full max-w-lg text-center">

            {{-- Logo --}}
            <div class="rise rise-1 mb-10 flex items-center justify-center gap-2.5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-600 shadow-lg shadow-brand-600/25"><span class="text-[18px] font-bold leading-none tracking-[-0.04em] text-white">IB</span></span>
                <span class="text-[19px] font-bold tracking-[-0.03em]">{{ config('app.name', 'IBSolutions') }}</span>
            </div>

            {{-- Animated cog emblem --}}
            <div class="rise rise-2 relative mx-auto mb-9 h-32 w-32">
                <span class="ring absolute inset-0 rounded-full border-2 border-brand-400/40"></span>
                <span class="ring absolute inset-0 rounded-full border-2 border-brand-400/40" style="animation-delay:1.5s"></span>

                <div class="float relative grid h-32 w-32 place-items-center">
                    <div class="grid h-24 w-24 place-items-center rounded-3xl bg-gradient-to-br from-brand-500 to-brand-700 shadow-2xl shadow-brand-600/30">
                        <svg class="h-12 w-12 text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <g class="cog" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Copy --}}
            <span class="rise rise-3 inline-flex items-center gap-1.5 rounded-full border border-amber-300/60 bg-amber-50 px-3 py-1 text-[11.5px] font-bold uppercase tracking-[0.1em] text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-400">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span> {{ __('Scheduled maintenance') }}
            </span>

            <h1 class="rise rise-3 mt-6 text-[32px] font-bold leading-[1.1] tracking-[-0.03em] sm:text-[40px]">
                {{ __("We'll be back") }}<br><span class="text-brand-600 dark:text-brand-500">{{ __('very soon.') }}</span>
            </h1>

            <p class="rise rise-4 mx-auto mt-4 max-w-md text-[15px] leading-relaxed text-ink-600 dark:text-ink-300">
                {{ __("We're polishing things up to make your experience faster and smoother. Your account and balance are completely safe — hang tight, we won't be long.") }}
            </p>

            {{-- Indeterminate progress --}}
            <div class="rise rise-4 mx-auto mt-8 h-1.5 w-56 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                <div class="bar-fill h-full w-2/3 rounded-full" style="background-image: linear-gradient(90deg, transparent, #D91F2C, transparent);"></div>
            </div>

            {{-- Actions --}}
            <div class="rise rise-5 mt-8 flex flex-col items-center justify-center gap-2.5 sm:flex-row">
                <button onclick="location.reload()" class="flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 text-[14px] font-bold text-white transition hover:bg-brand-700 active:scale-[0.98]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                    {{ __('Check again') }}
                </button>
                <a href="https://wa.me/" target="_blank" rel="noopener"
                   class="flex h-12 items-center justify-center gap-2 rounded-xl border border-ink-200 px-6 text-[14px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-900">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.5L3 21l2-5.7A8.4 8.4 0 1 1 21 11.5Z"/></svg>
                    {{ __('Contact support') }}
                </a>
            </div>

            {{-- Live clock, so the page feels alive --}}
            <p class="rise rise-5 mt-9 font-mono text-[12px] text-ink-400" id="clock"></p>
        </div>
    </main>

    <script>
        // Live clock — small touch that shows the page is live, not a static error.
        (function () {
            var el = document.getElementById('clock');
            function tick() {
                var d = new Date();
                el.textContent = '{{ __('Last checked') }} ' + d.toLocaleTimeString();
            }
            tick(); setInterval(tick, 1000);
        })();

        // Auto-retry: quietly reload every 60s to catch the moment it's back.
        setTimeout(function () { location.reload(); }, 60000);
    </script>
</body>
</html>