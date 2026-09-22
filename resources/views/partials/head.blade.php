{{-- Shared <head> assets: fonts, Tailwind config, design tokens, the
     motion system and component classes. Included by every layout so
     the two shells can never drift apart. --}}
@php $theme = \App\Support\Theme::tokens(); @endphp
    {{-- Fonts (admin-chosen) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $theme['font_url'] }}">

    {{-- Theme tokens --}}
    <style>:root {
        --app-radius: {{ $theme['radius'] }};
        --accent: {{ $theme['accent'] }};
        --accent-hover: {{ $theme['accent_hover'] }};
        --accent-active: {{ $theme['accent_active'] }};
        --accent-text: {{ $theme['accent_text'] }};
    } html { font-size: {{ $theme['scale'] }}; }</style>

    {{-- Tailwind (CDN) --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: [{!! collect(explode(',', $theme['font_stack']))->map(fn($f) => "'".trim($f, " '")."'")->implode(', ') !!}],
                        mono: ['DM Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace']
                    },
                    borderRadius: { DEFAULT: 'var(--app-radius)' },
                    colors: {
                        brand: {
                            50:  '{{ $theme['brand'][50] }}',
                            100: '{{ $theme['brand'][100] }}',
                            200: '{{ $theme['brand'][200] }}',
                            300: '{{ $theme['brand'][300] }}',
                            400: '{{ $theme['brand'][400] }}',
                            500: '{{ $theme['brand'][500] }}',
                            600: '{{ $theme['brand'][600] }}',
                            700: '{{ $theme['brand'][700] }}',
                            800: '{{ $theme['brand'][800] }}',
                            900: '{{ $theme['brand'][900] }}'
                        },
                        accent: '{{ $theme['accent'] }}',
                        ink: {
                            50:  '#faf7f7',
                            100: '#f4efef',
                            200: '#e9e1e1',
                            300: '#d9cdcd',
                            400: '#a89898',
                            500: '#3f3535',
                            600: '#2b2323',
                            700: '#3a3131',
                            800: '#2e2525',
                            900: '#14100f',
                            950: '#100c0c'
                        }
                    }
                }
            }
        };
    </script>

    @verbatim
    <style type="text/tailwindcss">
        @layer base {
            html { -webkit-font-smoothing: antialiased; }
            h1, h2, h3 { letter-spacing: -0.03em; }
            ::selection { background: #d91f2c; color: #fff; }

            /* iOS zooms into any focused input under 16px. The viewport tag
               blocks pinch-zoom; this blocks the focus auto-zoom too, without
               forcing every field's visible size up to 16px. */
            @supports (-webkit-touch-callout: none) {
                input, select, textarea { font-size: 16px; }
            }
            :focus-visible { outline: 2px solid #d91f2c; outline-offset: 2px; }
            [data-cloak] { display: none !important; }
        }

        /* ───────────────────────── Motion ─────────────────────────
           Entrances are short and directional; loops are reserved for
           things that are genuinely still happening (waiting for an SMS).
           Everything here is switched off under reduced-motion.          */

        @keyframes rise     { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
        @keyframes fade     { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pop      { 0% { opacity: 0; transform: scale(.9); } 60% { transform: scale(1.03); } 100% { opacity: 1; transform: scale(1); } }
        @keyframes slideUp  { from { opacity: 0; transform: translate(-50%, 14px); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes breathe  { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
        @keyframes halo     { 0% { box-shadow: 0 0 0 0 rgba(217,31,44,.4); } 70% { box-shadow: 0 0 0 9px rgba(217,31,44,0); } 100% { box-shadow: 0 0 0 0 rgba(217,31,44,0); } }
        @keyframes sweep    { from { background-position: -180% 0; } to { background-position: 180% 0; } }
        @keyframes spin     { to { transform: rotate(360deg); } }
        @keyframes flash    { 0% { background-color: rgba(217,31,44,.28); } 100% { background-color: transparent; } }

        .anim-rise { animation: rise .45s cubic-bezier(.22,1,.36,1) both; }
        .anim-fade { animation: fade .35s ease-out both; }
        .anim-pop  { animation: pop  .4s cubic-bezier(.34,1.4,.64,1) both; }

        /* List rows drift up as they enter — used with a per-row delay so a
           freshly-shown panel cascades in rather than snapping. */
        @keyframes rowIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .row-in { animation: rowIn .4s cubic-bezier(.22,1,.36,1) both; }

        /* A crisp emphasis pulse for the count badge when a tab is chosen. */
        @keyframes tick { 0% { transform: scale(1); } 40% { transform: scale(1.18); } 100% { transform: scale(1); } }
        .tick { animation: tick .3s ease-out; }

        /* Stagger: put .d-N on successive children of a group */
        .d-1 { animation-delay: .04s; } .d-2 { animation-delay: .08s; }
        .d-3 { animation-delay: .12s; } .d-4 { animation-delay: .16s; }
        .d-5 { animation-delay: .20s; } .d-6 { animation-delay: .24s; }

        /* A row that is still doing something */
        .dot-live  { animation: breathe 1.7s ease-in-out infinite; }
        .halo-live { animation: halo 2s ease-out infinite; }
        .flash-in  { animation: flash .9s ease-out both; }

        /* Hover affordance for selectable rows */
        .lift { transition: transform .18s cubic-bezier(.22,1,.36,1), border-color .18s, background-color .18s; will-change: transform; }
        .lift:hover { transform: translateY(-2px); }
        .lift:active { transform: translateY(0); }

        /* Loading skeleton */
        .sweep {
            background-image: linear-gradient(90deg, transparent, rgba(120,100,100,.14), transparent);
            background-size: 180% 100%;
            animation: sweep 1.3s linear infinite;
        }

        /* Button busy state: text hides, spinner takes its place */
        .is-busy { position: relative; color: transparent !important; pointer-events: none; }
        .is-busy::after {
            content: ""; position: absolute; inset: 0; margin: auto;
            width: 15px; height: 15px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,.4); border-top-color: #fff;
            animation: spin .6s linear infinite;
        }
        .is-busy.btn-ghost::after { border-color: rgba(120,100,100,.3); border-top-color: #d91f2c; }

        .toast-in { animation: slideUp .32s cubic-bezier(.22,1,.36,1) both; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
            .lift:hover { transform: none; }
        }
        @layer components {
            /* All buttons use the accent (secondary) color. This targets only
               button/submit elements carrying bg-brand-600, so the logo tile,
               badges and hero panels that also use brand-600 keep the brand
               color — only clickable buttons switch to accent. */
            button.bg-brand-600,
            a.bg-brand-600,
            input[type="submit"].bg-brand-600,
            [type="button"].bg-brand-600 {
                background-color: var(--accent) !important;
                color: var(--accent-text) !important;
            }
            button.bg-brand-600:hover,
            a.bg-brand-600:hover,
            input[type="submit"].bg-brand-600:hover,
            [type="button"].bg-brand-600:hover {
                background-color: var(--accent-hover) !important;
            }
            .card {
                @apply rounded-2xl border border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-950;
            }
            .btn-primary {
                @apply inline-flex h-11 items-center justify-center gap-2 rounded-xl px-4
                       text-[13px] font-semibold transition;
                background-color: var(--accent);
                color: var(--accent-text);
            }
            .btn-primary:hover { background-color: var(--accent-hover); }
            .btn-primary:active { background-color: var(--accent-active); }
            /* Alias so either class works */
            .btn-accent {
                @apply inline-flex h-11 items-center justify-center gap-2 rounded-xl px-4
                       text-[13px] font-semibold transition;
                background-color: var(--accent);
                color: var(--accent-text);
            }
            .btn-accent:hover { background-color: var(--accent-hover); }
            .btn-accent:active { background-color: var(--accent-active); }
            .btn-ghost {
                @apply inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-ink-300 px-4
                       text-[13px] font-semibold transition hover:bg-ink-100
                       dark:border-ink-700 dark:hover:bg-ink-900;
            }
            .stat { @apply font-mono text-2xl font-medium tracking-[-0.03em]; }

            /* A list that scrolls itself instead of stretching its card */
            .scroll-y {
                @apply overflow-y-auto overscroll-contain;
                scrollbar-width: thin;
            }
            .scroll-y::-webkit-scrollbar { width: 6px; }
            .scroll-y::-webkit-scrollbar-thumb {
                @apply rounded-full bg-ink-300 dark:bg-ink-700;
            }
            .scroll-y::-webkit-scrollbar-track { background: transparent; }

            /* Reserves the flag's box before the image loads, so rows never jump */
            .flag-slot {
                @apply grid h-[15px] w-5 shrink-0 place-items-center overflow-hidden
                       rounded-[2px] bg-ink-100 dark:bg-ink-800;
            }
            .nav-link {
                @apply flex h-11 items-center gap-3 rounded-xl px-3 text-[13px] font-medium
                       text-ink-600 transition hover:bg-ink-100 hover:text-ink-900
                       dark:text-ink-300 dark:hover:bg-ink-900 dark:hover:text-ink-50;
            }
            .nav-link-active {
                @apply bg-brand-50 font-semibold text-brand-700
                       hover:bg-brand-50 hover:text-brand-700
                       dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/10 dark:hover:text-brand-400;
            }
        }
    </style>
    @endverbatim