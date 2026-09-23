@extends('layouts.app')

@section('title', __('Buy a number'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ __('Buy a number') }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
                {{ __('Pick a country, pick a service, pick a price. The code arrives on the right.') }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-3 rounded-xl border border-ink-200 px-3.5 py-2 dark:border-ink-800">
            <div>
                <p class="text-[9.5px] font-bold tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('BALANCE') }}</p>
                <p id="wallet-balance" class="font-mono text-[15px] font-medium">{{ $balance['formatted'] }}</p>
            </div>
            <a href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}"
               class="rounded-lg bg-brand-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-brand-700">
                {{ __('Top up') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-4 lg:grid-cols-5">

    {{-- ═══════════════ The order form ═══════════════ --}}
    <div class="lg:col-span-3">
        {{-- No overflow-hidden here: it clips the step dropdowns, which are
             absolutely positioned and must escape the card. --}}
        <section class="card">

            {{-- Source tabs --}}
            <div role="tablist" aria-label="{{ __('Number pool') }}"
                 class="flex overflow-hidden rounded-t-2xl border-b border-ink-200 dark:border-ink-800">
                @if ($usaEnabled)
                <button type="button" role="tab" data-tab="usa" aria-controls="step-panel"
                        class="tab-btn relative flex flex-1 items-center justify-center gap-2 px-4 py-3.5 text-[13px] font-semibold transition">
                    {{ __('USA pool') }}
                    <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[9.5px] font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('BEST') }}</span>
                </button>
                @endif
                @if ($globalEnabled)
                <button type="button" role="tab" data-tab="global" aria-controls="step-panel"
                        class="tab-btn relative flex flex-1 items-center justify-center gap-2 px-4 py-3.5 text-[13px] font-semibold transition">
                    {{ __('All countries') }}
                    <span class="rounded-md bg-ink-100 px-1.5 py-0.5 text-[9.5px] font-bold text-ink-600 dark:bg-ink-800 dark:text-ink-300">200+</span>
                </button>
                @endif
            </div>

            <div id="step-panel" class="p-5">

                <p id="tip" class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-600/20 bg-emerald-50 p-3 text-[12px] text-emerald-900 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200">
                    <svg width="15" height="15" class="mt-px h-[15px] w-[15px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    <span id="tip-text"></span>
                </p>

                {{-- One box per pool. A USA outage must not put a red banner
                     on the All-countries tab, which may be working fine. --}}
                @if ($usaError)
                    <p id="err-usa" data-cloak class="mb-5 rounded-xl border border-brand-600/25 bg-brand-50 p-3.5 text-[12px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">
                        {{ $usaError }}
                    </p>
                @endif

                @if ($countryError)
                    <p id="err-global" data-cloak class="mb-5 rounded-xl border border-brand-600/25 bg-brand-50 p-3.5 text-[12px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">
                        {{ $countryError }}
                    </p>
                @endif

                {{-- ── Step 1 · Country ───────────────────────────── --}}
                <div class="flex gap-3.5">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-[11px] font-bold text-white">1</span>
                    <div class="min-w-0 flex-1">
                        <p id="country-label-text" class="text-[13px] font-semibold">{{ __('Choose country') }}</p>

                        <div class="relative mt-2" id="country-box">
                            <input type="hidden" id="g-country" value="">

                            <button type="button" id="country-trigger"
                                    role="combobox" aria-expanded="false" aria-haspopup="listbox"
                                    aria-controls="country-list" aria-labelledby="country-label-text"
                                    class="flex h-12 w-full items-center gap-3 rounded-xl border border-ink-300 bg-white px-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-70 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
                                <span id="country-flag" class="flag-slot"></span>
                                <span id="country-label" class="flex-1 truncate text-[13.5px] font-semibold text-ink-500 dark:text-ink-400">{{ __('Select a country…') }}</span>
                                <svg width="16" height="16" class="h-4 w-4 shrink-0 text-ink-500 transition-transform" id="country-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div id="country-panel" data-cloak
                                 class="anim-pop absolute left-0 right-0 top-[calc(100%+6px)] z-30 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-xl shadow-ink-900/10 dark:border-ink-700 dark:bg-ink-900 dark:shadow-black/50">
                                <div class="border-b border-ink-200 p-2 dark:border-ink-800">
                                    <label for="country-search" class="sr-only">{{ __('Search countries') }}</label>
                                    <div class="flex h-9 items-center gap-2 rounded-lg bg-ink-50 px-2.5 dark:bg-ink-950">
                                        <svg width="14" height="14" class="h-3.5 w-3.5 shrink-0 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
                                        <input id="country-search" type="text" autocomplete="off"
                                               placeholder="{{ __('Search 200+ countries…') }}"
                                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[12px] text-ink-900 placeholder:text-ink-500 focus:ring-0 dark:text-ink-50">
                                        <span id="country-count" class="shrink-0 font-mono text-[10px] text-ink-500 dark:text-ink-400"></span>
                                    </div>
                                </div>
                                <ul id="country-list" role="listbox" class="scroll-y max-h-72 p-1.5"></ul>
                                <p id="country-empty" data-cloak class="px-3 py-6 text-center text-[12px] text-ink-500 dark:text-ink-400">{{ __('No country matches that.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 2 · Service ───────────────────────────── --}}
                <div class="mt-5 flex gap-3.5">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-[11px] font-bold text-white">2</span>
                    <div class="min-w-0 flex-1">
                        <p id="service-label-text" class="text-[13px] font-semibold">{{ __('Choose service') }}</p>

                        <div class="relative mt-2" id="service-box">
                            <input type="hidden" id="g-service" value="">

                            <button type="button" id="service-trigger" disabled
                                    role="combobox" aria-expanded="false" aria-haspopup="listbox"
                                    aria-controls="service-list" aria-labelledby="service-label-text"
                                    class="flex h-12 w-full items-center gap-3 rounded-xl border border-ink-300 bg-white px-3.5 text-left transition disabled:cursor-not-allowed disabled:opacity-50 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
                                <span id="service-label" class="flex-1 truncate text-[13.5px] font-semibold text-ink-500 dark:text-ink-400">{{ __('Pick a country first') }}</span>
                                <svg width="16" height="16" class="h-4 w-4 shrink-0 text-ink-500 transition-transform" id="service-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div id="service-panel" data-cloak
                                 class="anim-pop absolute left-0 right-0 top-[calc(100%+6px)] z-20 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-xl shadow-ink-900/10 dark:border-ink-700 dark:bg-ink-900 dark:shadow-black/50">
                                <div class="border-b border-ink-200 p-2 dark:border-ink-800">
                                    <label for="service-search" class="sr-only">{{ __('Search services') }}</label>
                                    <div class="flex h-9 items-center gap-2 rounded-lg bg-ink-50 px-2.5 dark:bg-ink-950">
                                        <svg width="14" height="14" class="h-3.5 w-3.5 shrink-0 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
                                        <input id="service-search" type="text" autocomplete="off"
                                               placeholder="{{ __('Search services…') }}"
                                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[12px] text-ink-900 placeholder:text-ink-500 focus:ring-0 dark:text-ink-50">
                                        <span id="service-count" class="shrink-0 font-mono text-[10px] text-ink-500 dark:text-ink-400"></span>
                                    </div>
                                </div>
                                <ul id="service-list" role="listbox" class="scroll-y max-h-72 p-1.5"></ul>
                                <p id="service-empty" data-cloak class="px-3 py-6 text-center text-[12px] text-ink-500 dark:text-ink-400">{{ __('Nothing matches that.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 3 · Price ─────────────────────────────── --}}
                <div class="mt-5 flex gap-3.5">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-[11px] font-bold text-white">3</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold">{{ __('Choose price') }}</p>

                        <div id="tiers" class="scroll-y mt-2 flex max-h-[168px] flex-wrap gap-2" data-cloak></div>

                        <div id="tiers-skeleton" data-cloak class="mt-2 flex gap-2">
                            <div class="sweep h-[52px] w-32 rounded-xl bg-ink-100 dark:bg-ink-800"></div>
                            <div class="sweep h-[52px] w-32 rounded-xl bg-ink-100 dark:bg-ink-800"></div>
                        </div>

                        <p id="tiers-hint" class="mt-2 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Pick a service to see prices.') }}</p>
                    </div>
                </div>

                <div id="buy-error" data-cloak
                     class="mt-5 flex flex-col items-center gap-2 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">
                    <span id="buy-error-text"></span>
                    <a id="buy-error-action" data-cloak
                       href="{{ Route::has('wallet.index') ? route('wallet.index') : '#' }}"
                       class="rounded-lg bg-brand-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-brand-700">
                        {{ __('Top up wallet') }}
                    </a>
                </div>

                <button type="button" id="buy" disabled
                        class="mt-5 h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700 active:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-40">
                    {{ __('Buy') }}
                </button>
            </div>
        </section>
    </div>

    {{-- ═══════════ Purchase confirmation ═══════════ --}}
    <div id="confirm-buy" style="display:none" class="fixed inset-0 z-[60] items-center justify-center p-5">
        <div class="absolute inset-0 bg-ink-950/55 backdrop-blur-sm" data-confirm-close></div>
        <div class="relative w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-ink-900">
            <div class="p-6">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                </div>
                <h3 class="text-[17px] font-bold tracking-[-0.02em]">{{ __('Confirm purchase') }}</h3>
                <p class="mt-1 text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Review before we charge your wallet.') }}</p>

                <div class="mt-4 space-y-2.5 rounded-2xl border border-ink-100 p-4 dark:border-ink-800">
                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-ink-500 dark:text-ink-400">{{ __('Service') }}</span>
                        <span id="cb-service" class="font-semibold"></span>
                    </div>
                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-ink-500 dark:text-ink-400">{{ __('Pool') }}</span>
                        <span id="cb-pool" class="font-semibold"></span>
                    </div>
                    <div class="flex items-center justify-between border-t border-ink-100 pt-2.5 text-[14px] dark:border-ink-800">
                        <span class="font-semibold">{{ __('Total') }}</span>
                        <span id="cb-price" class="font-mono text-[16px] font-bold text-brand-600 dark:text-brand-400"></span>
                    </div>
                </div>

                <p class="mt-3 text-[11.5px] leading-relaxed text-ink-400">{{ __('The number is rented — enter it in the app and request your code. No code? Cancel for a full refund.') }}</p>

                <div class="mt-5 flex gap-2.5">
                    <button type="button" data-confirm-close class="btn-ghost h-11 flex-1 text-[13.5px]">{{ __('Cancel') }}</button>
                    <button type="button" id="cb-confirm" class="btn-primary h-11 flex-1 text-[13.5px]">{{ __('Confirm & buy') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ Orders + how-to ═══════════════ --}}
    <div class="space-y-4 lg:col-span-2 lg:self-start">

        <section class="card flex max-h-[62vh] flex-col p-5">
            <div class="flex shrink-0 items-center justify-between gap-3">
                <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Your orders') }}</h3>
                <span id="orders-count" class="rounded-lg bg-ink-100 px-2 py-0.5 font-mono text-[11px] font-medium text-ink-600 dark:bg-ink-800 dark:text-ink-300">0</span>
            </div>

            <div id="orders-empty" class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                <span class="anim-pop grid h-11 w-11 place-items-center rounded-2xl bg-ink-100 dark:bg-ink-800">
                    <svg width="20" height="20" class="h-5 w-5 text-ink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="3"/><path d="M10.5 18.5h3"/></svg>
                </span>
                <p class="mt-3 text-[13px] font-semibold">{{ __('No orders yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your number and its code appear here.') }}</p>
            </div>

            {{-- Only the newest few: the rest live on the orders page. --}}
            <div id="orders-stack" class="scroll-y mt-4 flex-1 space-y-3 pr-1" data-cloak></div>

            <a id="orders-more" data-cloak
               href="{{ Route::has('orders.index') ? route('orders.index') : '#' }}"
               class="mt-3 flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-ink-200 text-[12px] font-semibold text-ink-600 transition hover:bg-ink-100 dark:border-ink-800 dark:text-ink-300 dark:hover:bg-ink-900">
                <span id="orders-more-text">{{ __('View all') }}</span>
                <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </section>

        {{-- ── How to place an order ───────────────────────────────── --}}
        <section class="card overflow-hidden">
            <h3 class="px-5 pt-5 text-[14px] font-bold tracking-[-0.02em]">{{ __('How to place an order') }}</h3>

            @if ($tutorial)
                {{-- 16:9 box reserved up front so the card never jumps when the
                     player loads. --}}
                <div class="mt-4 aspect-video w-full bg-ink-100 dark:bg-ink-900">
                    <iframe src="{{ $tutorial }}"
                            title="{{ __('How to place an order') }}"
                            class="h-full w-full"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @else
                <div class="mt-4 grid aspect-video w-full place-items-center border-y border-ink-200 bg-ink-50 text-center dark:border-ink-800 dark:bg-ink-900">
                    <div class="px-6">
                        <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-brand-600 text-white">
                            <svg width="18" height="18" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg>
                        </span>
                        <p class="mt-3 text-[12.5px] font-semibold">{{ __('Walkthrough coming soon') }}</p>
                        <p class="mt-1 text-[11.5px] text-ink-500 dark:text-ink-400">
                            {{ __('Add a video link in settings under site.tutorial_url.') }}
                        </p>
                    </div>
                </div>
            @endif

            <ol class="space-y-2.5 p-5">
                @foreach ([
                    __('Pick the country you need the number in.'),
                    __('Search for the service you are verifying.'),
                    __('Choose a price, then press Buy.'),
                    __('Wait on this page — the code appears above.'),
                ] as $i => $step)
                    <li class="flex gap-2.5 text-[12px] text-ink-600 dark:text-ink-300">
                        <span class="grid h-[18px] w-[18px] shrink-0 place-items-center rounded-full bg-ink-100 text-[9.5px] font-bold text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ $i + 1 }}</span>
                        <span>{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</div>

<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl px-4 py-2.5 text-[13px] font-semibold shadow-lg" data-cloak></div>
@endsection

@push('scripts')
<script>
    window.NUMBERS = {
        tab: @json($tab),
        pollUsa: @json($pollUsa),
        pollGlobal: @json($pollGlobal),
        cancelLock: @json($cancelLock),
        countries: @json($countries),
        usaServices: @json($usaServices),
        orders: @json($orders),
        routes: {
            usaServices:    @json(route('numbers.usa.services')),
            globalServices: @json(url('/numbers/services')),
            globalPrices:   @json(route('numbers.prices')),
            purchase:       @json(route('numbers.store')),
            order:          @json(url('/numbers/order')),
        },
    };
</script>
@verbatim
<script>
(function () {
    const cfg  = window.NUMBERS;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const $    = (id) => document.getElementById(id);
    const show = (el, on) => el && el.toggleAttribute('data-cloak', !on);

    async function api(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            ...options,
        });

        let body = {};
        try { body = await res.json(); } catch (e) { /* empty or HTML body */ }

        const ok = res.ok && body.success !== false;

        if (!ok) {
            // A 419 (expired session) or 500 returns no JSON message, which
            // is exactly how a failure ends up looking like nothing happened.
            console.error('API', res.status, url, body);

            // Laravel's own throttle and auth responses carry terse strings
            // like "Too Many Attempts." — rewrite anything that is not ours.
            const framework = !body.message ||
                /^too many attempts/i.test(body.message) ||
                /^unauthenticated/i.test(body.message);

            if (framework) {
                body.message = ({
                    401: 'Please sign in again.',
                    403: 'You do not have access to that.',
                    419: 'Your session expired. Reload the page and try again.',
                    429: 'Going a bit fast — wait a few seconds and try again.',
                    500: 'Something broke on our side. Try again in a moment.',
                    503: 'This service is temporarily unavailable. Please try again shortly.',
                })[res.status] || ('Request failed (HTTP ' + res.status + ').');
            }
        }

        return { ok, status: res.status, body };
    }

    // Nothing should ever fail without saying so.
    window.addEventListener('unhandledrejection', (e) => {
        console.error('Unhandled', e.reason);
        toast('Something broke — check the console', 'error');
    });

    let toastTimer;
    function toast(message, tone = 'ok') {
        const el = $('toast');
        el.textContent = message;
        el.className = 'toast-in pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl px-4 py-2.5 text-[13px] font-semibold shadow-lg ' +
            (tone === 'error' ? 'bg-brand-600 text-white' : 'bg-ink-900 text-white dark:bg-ink-100 dark:text-ink-900');
        show(el, true);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => show(el, false), 3600);
    }

    function setBalance(money) {
        if (!money) return;
        document.querySelectorAll('#wallet-balance, [data-wallet-balance]')
            .forEach((node) => { node.textContent = money.formatted; });
    }

    function flag(iso, name) {
        if (iso) {
            const img = document.createElement('img');
            img.src = 'https://flagcdn.com/w40/' + iso + '.png';
            img.srcset = 'https://flagcdn.com/w80/' + iso + '.png 2x';
            img.width = 20; img.height = 15; img.alt = '';
            img.loading = 'lazy';
            img.className = 'h-[15px] w-5 shrink-0 rounded-[2px] object-cover ring-1 ring-ink-900/10';
            return img;
        }
        const chip = document.createElement('span');
        chip.className = 'grid h-[15px] w-5 shrink-0 place-items-center rounded-[2px] bg-ink-100 text-[8px] font-bold text-ink-500 dark:bg-ink-800 dark:text-ink-400';
        chip.textContent = (name || '?').slice(0, 2).toUpperCase();
        return chip;
    }

    function setFlag(slot, iso, name) {
        if (slot) slot.replaceChildren(flag(iso, name));
    }

    function el(tag, cls, text) {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text != null) node.textContent = text;
        return node;
    }

    /* ═══════════════ Combobox ═══════════════
       One control, two uses. Type to filter, arrows to move, Enter to pick. */

    function combobox(prefix, opts) {
        opts = opts || {};

        const box     = $(prefix + '-box'),
              trigger = $(prefix + '-trigger'),
              panel   = $(prefix + '-panel'),
              search  = $(prefix + '-search'),
              list    = $(prefix + '-list'),
              empty   = $(prefix + '-empty'),
              label   = $(prefix + '-label'),
              caret   = $(prefix + '-caret'),
              count   = $(prefix + '-count'),
              slot    = $(prefix + '-flag');

        let items = [], filtered = [], active = -1;

        function render() {
            list.replaceChildren();
            show(empty, filtered.length === 0);

            filtered.slice(0, 300).forEach((item, i) => {
                const li = el('li', 'flex cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-[12.5px] transition ' +
                    (i === active
                        ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400'
                        : 'hover:bg-ink-100 dark:hover:bg-ink-800'));
                li.id = prefix + '-opt-' + i;
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', String(i === active));

                if (opts.flags) li.append(flag(item.iso, item.name));
                li.append(el('span', 'flex-1 truncate', item.name));

                // Each row is its own pool at its own price, so the price
                // belongs on the row — it is what separates two rows that
                // otherwise read identically.
                if (item.price) {
                    li.append(el('span', 'shrink-0 font-mono text-[11px] text-ink-500 dark:text-ink-400',
                        item.price.formatted));
                }

                li.addEventListener('click', () => choose(item));
                list.append(li);
            });
        }

        function filter() {
            const q = search.value.trim().toLowerCase();
            // Prefix matches first: typing "ni" should offer Nigeria, not Bosnia.
            const starts = [], contains = [];
            items.forEach((item) => {
                const name = (item.name || '').toLowerCase();
                if (!q || name.startsWith(q)) starts.push(item);
                else if (name.includes(q)) contains.push(item);
            });
            filtered = starts.concat(contains);
            active = filtered.length ? 0 : -1;
            render();

            if (count) {
                count.textContent = q
                    ? filtered.length + ' of ' + items.length
                    : items.length + ' available';
            }
        }

        function open() {
            if (trigger.disabled) return;
            show(panel, true);
            trigger.setAttribute('aria-expanded', 'true');
            caret.style.transform = 'rotate(180deg)';
            search.value = '';
            filter();
            search.focus();
        }

        function close() {
            show(panel, false);
            trigger.setAttribute('aria-expanded', 'false');
            caret.style.transform = '';
        }

        function choose(item) {
            label.textContent = item.name;
            label.className = 'flex-1 truncate text-[13.5px] font-semibold text-ink-900 dark:text-ink-50';
            setFlag(slot, item.iso, item.name);
            close();
            if (opts.onPick) opts.onPick(item);
        }

        trigger.addEventListener('click', () => {
            panel.hasAttribute('data-cloak') ? open() : close();
        });

        // Start typing on the closed trigger and the panel opens with that
        // first letter already in the box — no click required.
        trigger.addEventListener('keydown', (e) => {
            if (e.key.length === 1 && !e.metaKey && !e.ctrlKey && !e.altKey) {
                open();
                search.value = e.key;
                filter();
                e.preventDefault();
            } else if (e.key === 'ArrowDown' || e.key === 'Enter') {
                open();
                e.preventDefault();
            }
        });

        search.addEventListener('input', filter);

        search.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!filtered.length) return;
                active = e.key === 'ArrowDown'
                    ? Math.min(active + 1, filtered.length - 1)
                    : Math.max(active - 1, 0);
                render();
                const node = $(prefix + '-opt-' + active);
                if (node) node.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (filtered[active]) choose(filtered[active]);
            } else if (e.key === 'Escape') {
                close(); trigger.focus();
            }
        });

        document.addEventListener('click', (e) => {
            if (!box.contains(e.target)) close();
        });

        return {
            setItems(next) { items = next || []; filtered = items; active = -1; render(); },
            setDisabled(off) { trigger.disabled = off; if (off) close(); },
            reset(text) {
                label.textContent = text;
                label.className = 'flex-1 truncate text-[13.5px] font-semibold text-ink-500 dark:text-ink-400';
                if (slot) slot.replaceChildren();
                items = []; filtered = []; active = -1;
                render();
            },
        };
    }

    /* ═══════════════ The three steps ═══════════════ */

    let source = cfg.tab;          // 'usa' | 'global'
    let pick   = null;             // { code } for USA, { quote } for global
    let serviceName = '';

    const gCountry = $('g-country'), gService = $('g-service'),
          tiers = $('tiers'), buyBtn = $('buy'), hint = $('tiers-hint');

    const countryPicker = combobox('country', {
        flags: true,
        onPick(c) { gCountry.value = String(c.id); loadServices(); },
    });

    const servicePicker = combobox('service', {
        onPick(svc) {
            gService.value = svc.code || '';
            serviceName = svc.name;
            // A USA row carries its own price; global needs a live quote.
            source === 'usa' ? showTiers([svc]) : loadPrices();
        },
    });

    function resetTiers(message) {
        tiers.replaceChildren();
        show(tiers, false);
        show($('tiers-skeleton'), false);
        hint.textContent = message || '';
        show(hint, !!message);
        pick = null;
        buyBtn.disabled = true;
        buyBtn.textContent = 'Buy';
    }

    /** Clears the error explicitly — only when the customer moves on. */
    function clearBuyError() {
        show($('buy-error'), false);
        show($('buy-error-action'), false);
    }

    /** Renders price options as pills — the same control for both pools. */
    function showTiers(options) {
        tiers.replaceChildren();

        if (!options || !options.length) {
            return resetTiers('No prices available right now.');
        }

        show(hint, options.length > 1);
        hint.textContent = options.length > 1
            ? 'Higher prices usually come from fresher stock.'
            : '';

        options.forEach((opt, i) => {
            const label = el('label', 'anim-fade relative');
            label.style.animationDelay = (i * 45) + 'ms';

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'tier';
            input.className = 'peer sr-only';
            input.value = String(i);

            const pill = el('span', 'lift flex min-w-[124px] cursor-pointer flex-col items-start gap-0.5 rounded-xl border border-ink-200 px-3.5 py-2.5 peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:border-ink-800 dark:peer-checked:bg-brand-500/10');
            pill.append(el('span', 'font-mono text-[15px] font-medium', opt.price.formatted));
            pill.append(el('span', 'text-[10.5px] text-ink-500 dark:text-ink-400',
                opt.available != null
                    ? Number(opt.available).toLocaleString() + ' available'
                    : (options.length === 1
                        ? serviceName
                        : (i === 0 ? 'Cheapest' : 'Option ' + (i + 1)))));

            if (i === 0 && options.length > 1) {
                const star = el('span', 'absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-brand-600 text-[9px] font-bold text-white', '★');
                label.append(star);
            }

            label.append(input, pill);
            tiers.append(label);

            input.addEventListener('change', () => {
                pick = opt.quote
                    ? { quote: opt.quote, price: opt.price.formatted, service: serviceName }
                    : { code: opt.code, price: opt.price.formatted, service: serviceName };
                buyBtn.disabled = false;
                buyBtn.textContent = 'Buy for ' + opt.price.formatted;
                clearBuyError();
            });
        });

        show(tiers, true);

        // A single price is not a choice — take it.
        if (options.length === 1) tiers.querySelector('input').click();
    }

    async function loadServices() {
        servicePicker.setDisabled(true);
        servicePicker.reset('Loading…');
        gService.value = '';
        serviceName = '';
        resetTiers('Pick a service to see prices.');

        if (source === 'usa') {
            const list = cfg.usaServices || [];

            if (!list.length) {
                servicePicker.reset('No services available');
                servicePicker.setDisabled(true);
                return resetTiers('The USA pool is not responding. Try All countries.');
            }

            servicePicker.reset('Select a service…');
            servicePicker.setItems(list);
            servicePicker.setDisabled(false);
            return;
        }

        if (!gCountry.value) {
            servicePicker.reset('Pick a country first');
            return;
        }

        const { ok, body } = await api(cfg.routes.globalServices + '/' + encodeURIComponent(gCountry.value));

        if (!ok) {
            servicePicker.reset('Unavailable');
            return resetTiers(body.message || 'Could not load services.');
        }

        const services = body.data || [];
        if (!services.length) return servicePicker.reset('No services here');

        servicePicker.reset('Select a service…');
        servicePicker.setItems(services);
        servicePicker.setDisabled(false);
    }

    async function loadPrices() {
        resetTiers('');
        show($('tiers-skeleton'), true);

        let ok, body;

        try {
            ({ ok, body } = await api(cfg.routes.globalPrices, {
                method: 'POST',
                body: JSON.stringify({ country: Number(gCountry.value), service: gService.value }),
            }));
        } catch (e) {
            console.error('prices', e);
            return resetTiers('Could not reach the server. Check your connection.');
        } finally {
            // Whatever happens, the placeholders go away. Leaving them up is
            // how step 3 ends up frozen on two grey blocks forever.
            show($('tiers-skeleton'), false);
        }

        if (!ok) {
            resetTiers('');
            return buyError(body.message || 'No prices available right now.');
        }

        showTiers(body.data || []);
    }

    /* ═══════════════ Tabs ═══════════════ */

    function activateTab(name) {
        source = name;

        document.querySelectorAll('.tab-btn').forEach((btn) => {
            const on = btn.dataset.tab === name;
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
            btn.className = 'tab-btn relative flex flex-1 items-center justify-center gap-2 px-4 py-3.5 text-[13px] font-semibold transition ' +
                (on
                    ? 'text-brand-700 shadow-[inset_0_-2px_0_0_currentColor] dark:text-brand-400'
                    : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100');
        });

        if (name === 'usa') {
            $('tip-text').textContent = 'USA numbers deliver fastest. Prices already include your rate.';
            countryPicker.setItems([]);
            countryPicker.reset('United States');
            $('country-label').className = 'flex-1 truncate text-[13.5px] font-semibold text-ink-900 dark:text-ink-50';
            setFlag($('country-flag'), 'us', 'United States');
            countryPicker.setDisabled(true);
            gCountry.value = '';
        } else {
            $('tip-text').textContent = 'Over 200 countries with live pricing. You pay the price you picked.';
            countryPicker.setDisabled(false);
            countryPicker.reset('Select a country…');
            countryPicker.setItems(cfg.countries || []);
            gCountry.value = '';
        }

        gService.value = '';
        clearBuyError();
        resetTiers('Pick a service to see prices.');
        if (name === 'usa') {
            loadServices();
        } else {
            servicePicker.reset('Pick a country first');
            servicePicker.setDisabled(true);
        }

        show($('err-usa'), name === 'usa');
        show($('err-global'), name === 'global');

        const url = new URL(window.location);
        url.searchParams.set('tab', name);
        history.replaceState({}, '', url);
    }

    document.querySelectorAll('.tab-btn').forEach((b) => {
        b.addEventListener('click', () => activateTab(b.dataset.tab));
    });
    activateTab(cfg.tab);

    /* ═══════════════ Orders ═══════════════
       Every number bought stays on screen with its own timer. Buying a second
       one must never hide the first — it is still live and still billed. */

    const stack  = $('orders-stack'),
          oEmpty = $('orders-empty'),
          oCount = $('orders-count');

    const orders = new Map();

    function waiting() {
        let n = 0;
        orders.forEach((o) => { if (o.data.status === 'Waiting') n++; });
        return n;
    }

    const VISIBLE = 3;   // newest few; the rest live on the orders page

    function syncPanel() {
        show(oEmpty, orders.size === 0);
        show(stack, orders.size > 0);
        oCount.textContent = orders.size;

        // Hide the overflow rather than letting the column grow forever.
        let hidden = 0;
        Array.from(stack.children).forEach((card, i) => {
            const over = i >= VISIBLE;
            card.toggleAttribute('data-cloak', over);
            if (over) hidden++;
        });

        show($('orders-more'), orders.size > 0);
        $('orders-more-text').textContent = hidden
            ? hidden + ' more · view all'
            : 'View all orders';
    }

    function copyButton(read, aria, cls) {
        const b = el('button', cls);
        b.type = 'button';
        b.setAttribute('aria-label', aria);
        b.innerHTML = '<svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5"/></svg>';
        b.addEventListener('click', async () => {
            const text = read().trim();
            if (!text || text === '—') return;
            try { await navigator.clipboard.writeText(text); toast('Copied'); }
            catch (e) { toast('Could not copy', 'error'); }
        });
        return b;
    }

    function buildCard(d) {
        const card = el('article', 'anim-fade rounded-xl border border-ink-200 p-3.5 dark:border-ink-800');

        const head  = el('div', 'flex items-center justify-between gap-2');
        const who   = el('span', 'flex min-w-0 items-center gap-2');
        const fslot = el('span', 'flag-slot');
        const title = el('span', 'truncate text-[12.5px] font-semibold');
        who.append(fslot, title);
        const status = el('span', '');
        head.append(who, status);

        const numBox = el('div', 'mt-2.5 flex items-center justify-between gap-2 rounded-lg bg-ink-50 px-2.5 py-2 dark:bg-ink-900');
        const num = el('span', 'truncate font-mono text-[13px]', d.number || '—');
        numBox.append(num, copyButton(() => num.textContent, 'Copy number',
            'grid h-7 w-7 shrink-0 place-items-center rounded-md text-ink-600 transition hover:bg-ink-200 dark:text-ink-300 dark:hover:bg-ink-800'));

        const codeBox = el('div', 'mt-2.5 rounded-lg border-2 border-brand-600 bg-brand-50 px-2.5 py-2 dark:bg-brand-500/10');
        codeBox.setAttribute('data-cloak', '');
        const codeRow = el('div', 'flex items-center justify-between gap-2');
        const code = el('span', 'font-mono text-xl font-medium tracking-[0.12em] text-brand-800 dark:text-brand-300');
        codeRow.append(code, copyButton(() => code.textContent, 'Copy code',
            'grid h-7 w-7 shrink-0 place-items-center rounded-md text-brand-700 transition hover:bg-brand-100 dark:text-brand-300 dark:hover:bg-brand-500/20'));
        codeBox.append(el('p', 'text-[9.5px] font-bold tracking-[0.08em] text-brand-700 dark:text-brand-400', 'VERIFICATION CODE'), codeRow);

        const meta = el('div', 'mt-2.5 flex items-center justify-between text-[11px] text-ink-500 dark:text-ink-400');
        const ref  = el('span', 'font-mono', d.ref);
        const paid = el('span', 'font-mono', d.charged ? d.charged.formatted : '—');
        meta.append(ref, paid);

        const cancel = el('button', 'mt-2.5 h-9 w-full rounded-lg border border-ink-300 text-[12px] font-semibold transition hover:bg-ink-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-ink-700 dark:hover:bg-ink-800');
        cancel.type = 'button';
        cancel.addEventListener('click', () => cancelOrder(d.ref));

        const note = el('p', 'mt-1.5 text-center text-[10px] text-ink-500 dark:text-ink-400');

        card.append(head, numBox, codeBox, meta, cancel, note);

        return { card, fslot, title, status, num, codeBox, code, ref, paid, cancel, note };
    }

    function paint(entry) {
        const d = entry.data, n = entry.nodes;

        n.title.textContent = (d.service || '') + (d.country ? ' · ' + d.country : '');
        n.num.textContent   = d.number || '—';
        n.paid.textContent  = d.charged ? d.charged.formatted : '—';

        try {
            const match = (cfg.countries || []).find((c) => c.name === d.country);
            setFlag(n.fslot, match ? match.iso : (d.mode === 'usa' ? 'us' : null), d.country);
        } catch (e) { /* decoration only */ }

        const tones = {
            Waiting:   'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-300',
            Completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300',
            Cancelled: 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
        };
        n.status.className = 'shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold ' +
            (tones[d.status] || tones.Waiting) + (d.status === 'Waiting' ? ' dot-live' : '');
        n.status.textContent = d.status;

        if (d.code) {
            n.code.textContent = d.code;
            if (n.codeBox.hasAttribute('data-cloak')) {
                show(n.codeBox, true);
                n.codeBox.classList.add('anim-pop');
                n.code.classList.add('flash-in');
            }
        }

        if (d.status === 'Completed') {
            n.cancel.disabled = true;
            n.cancel.textContent = 'Code received';
            n.note.textContent = '';
        } else if (d.status === 'Cancelled') {
            // The provider voided this number. Never show a plain "Cancel" —
            // there's nothing left to cancel. Offer to claim the refund (the
            // backend refunds an upstream-cancelled order locally), or show
            // that it's already been refunded / dismissed.
            if (d.refunded) {
                n.cancel.disabled = true;
                n.cancel.textContent = 'Refunded';
                n.note.textContent = 'This number was cancelled and your refund was credited.';
            } else {
                n.cancel.disabled = false;
                n.cancel.textContent = 'Claim refund';
                n.note.textContent = 'This number was cancelled by the network — claim your refund.';
            }
        } else {
            const left = cfg.cancelLock - Math.floor(Date.now() / 1000 - d.bought_at);
            n.cancel.disabled = left > 0;
            n.cancel.textContent = left > 0 ? 'Cancel in ' + left + 's' : 'Cancel and refund';

            n.note.textContent = left > 0
                ? 'Enter this number in ' + (d.service || 'the app') + ' and request the code.'
                : 'No code yet? Cancel for a full refund.';
        }
    }

    function upsert(d) {
        let entry = orders.get(d.ref);

        if (entry) {
            entry.data = Object.assign(entry.data, d);
        } else {
            entry = { data: d, nodes: buildCard(d), nextPoll: 0 };
            orders.set(d.ref, entry);
            stack.prepend(entry.nodes.card);
        }

        paint(entry);
        syncPanel();
        return entry;
    }

    (cfg.orders || []).slice().reverse().forEach(upsert);
    syncPanel();

    // One sweep drives every card. The gap widens as more numbers run, so ten
    // open orders cannot blow the 60-checks-per-minute ceiling.
    let sweeping = false;

    function gap(mode) {
        const base = (mode === 'usa' ? cfg.pollUsa : cfg.pollGlobal) * 1000;
        return Math.max(base, waiting() * 1300);
    }

    setInterval(async () => {
        if (sweeping) return;
        sweeping = true;

        try {
            const now = Date.now();
            for (const [ref, entry] of orders) {
                if (entry.data.status !== 'Waiting') { paint(entry); continue; }
                if (now < entry.nextPoll) { paint(entry); continue; }
                entry.nextPoll = now + gap(entry.data.mode);
                await pollOne(ref);
            }
        } finally {
            sweeping = false;
        }
    }, 1000);

    async function pollOne(ref) {
        const entry = orders.get(ref);
        if (!entry) return;

        const { ok, body } = await api(cfg.routes.order + '/' + encodeURIComponent(ref));
        if (!ok) return;

        const d = body.data || {};
        const had = entry.data.code;

        entry.data.status = d.status || entry.data.status;
        entry.data.code   = d.code || entry.data.code;
        entry.data.number = d.number || entry.data.number;
        entry.data.refund_claimable = !!d.refund_claimable;

        paint(entry);

        if (d.code && !had) toast('Code for ' + (entry.data.service || ref) + ': ' + d.code);
    }

    async function cancelOrder(ref) {
        const entry = orders.get(ref);
        if (!entry) return;

        const btn = entry.nodes.cancel;
        btn.disabled = true;
        btn.textContent = 'Cancelling…';

        const { ok, body } = await api(cfg.routes.order + '/' + encodeURIComponent(ref) + '/cancel', { method: 'POST' });
        const d = body.data || {};

        if (ok && d.outcome === 'code_received') {
            entry.data.status = 'Completed';
            entry.data.code = d.code;
            paint(entry);
            return toast('Code arrived: ' + d.code);
        }

        if (!ok) {
            entry.data.refund_claimable = true;
            paint(entry);
            return toast(body.message || 'Could not cancel', 'error');
        }

        entry.data.status = 'Cancelled';
        entry.data.refunded = true;
        entry.data.refund_claimable = false;
        paint(entry);
        setBalance(d.balance);
        toast('Refunded ' + d.refund.formatted);
    }

    function buyError(message, short = false) {
        $('buy-error-text').textContent = message;
        // Only offer a top-up when money is actually the problem.
        show($('buy-error-action'), short);
        show($('buy-error'), true);
    }

    // Buy now shows a confirmation preview first; the actual purchase runs
    // from the confirm button inside the modal.
    const confirmModal = $('confirm-buy');
    function openConfirm() {
        if (!pick) return buyError('Pick a price first.');
        $('cb-service').textContent = pick.service || serviceName || '{{ __('Number') }}';
        $('cb-pool').textContent = pick.quote ? '{{ __('Global') }}' : '{{ __('USA') }}';
        $('cb-price').textContent = pick.price || '';
        confirmModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeConfirm() {
        confirmModal.style.display = 'none';
        document.body.style.overflow = '';
    }
    confirmModal.querySelectorAll('[data-confirm-close]').forEach(b => b.addEventListener('click', closeConfirm));

    buyBtn.addEventListener('click', () => openConfirm());

    $('cb-confirm').addEventListener('click', async () => {
        if (!pick) return;
        closeConfirm();

        buyBtn.disabled = true;
        buyBtn.classList.add('is-busy');
        show($('buy-error'), false);

        const payload = pick.quote
            ? { mode: 'global', quote: pick.quote, service_name: serviceName }
            : { mode: 'usa', code: pick.code };

        let ok, body;

        try {
            ({ ok, body } = await api(cfg.routes.purchase, {
                method: 'POST',
                body: JSON.stringify(payload),
            }));
        } catch (e) {
            // Network down, CORS, aborted — any of these used to leave the
            // button spinning with no explanation.
            console.error('purchase', e);
            buyBtn.disabled = false;
            buyBtn.classList.remove('is-busy');
            return buyError('Could not reach the server. Check your connection.');
        } finally {
            // Whatever happens, the button comes back.
            buyBtn.disabled = false;
            buyBtn.classList.remove('is-busy');
        }

        if (!ok) {
            const message = body.message || 'Purchase failed';
            const short   = body.code === 'INSUFFICIENT_FUNDS';

            // No point re-pricing when the problem is money — the quote is
            // fine and /prices is capped at 30 calls a minute.
            if (short) {
                buyError(message, true);
                return;
            }

            // The server already re-quotes a lapsed price by itself. This
            // is the backstop for the rest: stock gone, balance short.
            if (payload.mode === 'global' && gService.value) {
                const chosen = pick;
                await loadPrices();

                // Keep their selection if the same price is still offered.
                const again = Array.from(tiers.querySelectorAll('input'));
                if (chosen && again.length === 1) again[0].click();
            }

            // Shown LAST, on purpose. loadPrices() calls resetTiers(), which
            // clears this box — so displaying the error first meant it was
            // wiped before anyone could read it, and the global tab looked
            // like it failed silently.
            buyError(message);
            return;
        }

        if (!body.data || !body.data.ref) {
            console.error('purchase: unexpected response', body);
            return buyError('The order did not come back properly. Check Your orders.');
        }

        try {
            upsert(body.data);
            setBalance(body.data.balance);
        } catch (e) {
            // The number IS bought at this point, so never swallow this.
            console.error('render order', e);
            toast('Number bought — reload to see it', 'error');
        }

        toast('Number ready — waiting for the SMS');

        // The token is retired server-side once the number is issued.
        if (payload.mode === 'global') resetTiers('Pick a service to see prices.');
    });
})();
</script>
@endverbatim
@endpush