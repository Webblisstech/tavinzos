@extends('layouts.app')

@section('title', __('Services'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ __('Accounts & logs') }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
                {{ __('Ready-made accounts, delivered the instant you buy.') }}
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

{{-- Sticky category jump bar --}}
@if (count($groups) > 1)
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($groups as $g)
            <a href="#cat-{{ Str::slug($g['name']) }}"
               class="flex h-9 items-center gap-2 rounded-xl border border-ink-200 px-3 text-[12px] font-semibold text-ink-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-800 dark:text-ink-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                @include('partials.brand-icon', ['icon' => $g['icon'] ?? '', 'class' => 'h-4 w-4'])
                {{ $g['name'] }}
            </a>
        @endforeach
    </div>
@endif

{{-- Product search --}}
<div class="mb-5">
    <label for="product-search" class="sr-only">{{ __('Search products') }}</label>
    <div class="flex h-12 w-full items-center gap-3 rounded-2xl border border-ink-200 bg-white px-4 shadow-sm shadow-ink-900/5 transition-all focus-within:border-brand-500 focus-within:shadow-md focus-within:shadow-brand-500/10 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="18" height="18" class="h-[18px] w-[18px] shrink-0 text-ink-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input id="product-search" type="search" placeholder="{{ __('Search all products…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </div>
</div>

{{-- Grouped sections --}}
<div id="product-list" class="space-y-8">
    @forelse ($groups as $g)
        <section data-group id="cat-{{ Str::slug($g['name']) }}" class="anim-fade scroll-mt-24">
            {{-- Category banner --}}
            <div class="mb-3 flex items-center gap-3 rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-3.5 text-white shadow-sm shadow-brand-900/10">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/15">
                    @include('partials.brand-icon', ['icon' => $g['icon'] ?? '', 'class' => 'h-[18px] w-[18px]'])
                </span>
                <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ $g['name'] }}</h3>
                <span class="ml-auto rounded-lg bg-white/15 px-2 py-0.5 text-[11px] font-bold">{{ count($g['products']) }}</span>
            </div>

            <div class="space-y-2.5">
                @foreach ($g['products'] as $p)
                    <article data-name="{{ Str::lower($p['name']) }}"
                             class="card lift row-in flex items-center gap-4 p-3.5 transition-shadow hover:shadow-md hover:shadow-ink-900/5 sm:p-4"
                             style="animation-delay: {{ min($loop->index * 35, 350) }}ms">

                        <span class="relative h-11 w-11 shrink-0">
                            @if (!empty($p['image']))
                                <img src="{{ $p['image'] }}" alt="" class="h-full w-full rounded-xl object-cover" loading="lazy">
                            @else
                                <span class="grid h-full w-full place-items-center rounded-xl bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200">
                                    @include('partials.brand-icon', ['icon' => $p['icon'] ?? '', 'class' => 'h-6 w-6'])
                                </span>
                            @endif
                            @if ($p['flag'])
                                <img src="https://flagcdn.com/w40/{{ $p['flag'] }}.png" alt=""
                                     class="absolute -bottom-1 -right-1 h-4 w-5 rounded-[2px] object-cover ring-2 ring-white dark:ring-ink-950" loading="lazy">
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13.5px] font-bold tracking-[-0.01em] text-ink-900 dark:text-ink-50">{{ $p['name'] }}</p>
                            <p class="text-[11.5px] font-medium text-ink-500 dark:text-ink-400">
                                @if ($p['country']){{ $p['country'] }} · @endif{{ $p['category'] }}
                            </p>
                        </div>

                        @if ($p['pre_order'] && $p['stock'] === 0)
                            <span class="hidden shrink-0 items-center gap-1.5 rounded-lg bg-amber-100 px-2.5 py-1 text-[11.5px] font-bold text-amber-700 sm:inline-flex dark:bg-amber-400/15 dark:text-amber-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ __('Pre-order') }}
                            </span>
                        @else
                            <span class="hidden shrink-0 items-center gap-1.5 rounded-lg bg-emerald-100 px-2.5 py-1 text-[11.5px] font-bold text-emerald-700 sm:inline-flex dark:bg-emerald-400/15 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ $p['stock'] }} {{ __('pcs') }}
                            </span>
                        @endif

                        <span class="shrink-0 font-mono text-[15px] font-semibold text-brand-600 dark:text-brand-400">{{ $p['price']['formatted'] }}</span>

                        <button type="button"
                                data-buy="{{ $p['slug'] }}"
                                @disabled(!$p['pre_order'] && $p['stock'] === 0)
                                class="shrink-0 rounded-lg px-4 py-2 text-[12.5px] font-bold text-white transition disabled:cursor-not-allowed disabled:opacity-40 {{ $p['pre_order'] && $p['stock'] === 0 ? 'bg-amber-500 hover:bg-amber-600' : 'bg-brand-600 hover:bg-brand-700' }}">
                            {{ $p['pre_order'] && $p['stock'] === 0 ? __('Pre-order') : __('Buy') }}
                        </button>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card px-6 py-16 text-center">
            <p class="text-[13px] font-semibold">{{ __('Nothing listed yet') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Check back soon — stock is added regularly.') }}</p>
        </div>
    @endforelse

    <p id="no-match" data-cloak class="card px-6 py-14 text-center text-[12px] text-ink-500 dark:text-ink-400">
        {{ __('No products match your search.') }}
    </p>
</div>

{{-- ═══════════════ Buy modal ═══════════════ --}}
<div id="modal" data-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
    <div id="modal-scrim" class="absolute inset-0 bg-ink-950/50 backdrop-blur-[2px]"></div>

    <div class="anim-pop relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white sm:max-w-md sm:rounded-2xl dark:bg-ink-950">

        <div class="flex items-start gap-3 border-b border-ink-200 p-5 dark:border-ink-800">
            <span id="m-flag" class="flag-slot h-10 w-10 shrink-0 overflow-hidden rounded-xl"></span>
            <div class="min-w-0 flex-1">
                <h3 id="m-name" class="break-words text-[15px] font-bold leading-tight"></h3>
                <p class="mt-0.5 flex items-center gap-2">
                    <span id="m-price" class="font-mono text-[13.5px] font-bold text-brand-600 dark:text-brand-400"></span>
                    <span id="m-stock" class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300"></span>
                </p>
            </div>
            <button type="button" id="m-close" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-ink-500 transition hover:bg-ink-100 dark:hover:bg-ink-800" aria-label="{{ __('Close') }}">
                <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="scroll-y flex-1 overflow-y-auto p-5">
            <p id="m-instructions" data-cloak class="mb-4 whitespace-pre-line break-words rounded-xl border border-ink-200 bg-ink-50 p-3 text-[12px] leading-relaxed text-ink-600 dark:border-ink-800 dark:bg-ink-900 dark:text-ink-300"></p>

            {{-- Buy-mode toggle: pick specific accounts, or just a quantity.
                 Only shown for products that have preview links. --}}
            <div id="m-mode" data-cloak class="mb-4">
                <div class="grid grid-cols-2 gap-2 rounded-xl bg-ink-100 p-1 dark:bg-ink-900">
                    <button type="button" data-mode="select"
                            class="mode-btn h-9 rounded-lg text-[12px] font-semibold transition">{{ __('Pick accounts') }}</button>
                    <button type="button" data-mode="quantity"
                            class="mode-btn h-9 rounded-lg text-[12px] font-semibold transition">{{ __('Just a quantity') }}</button>
                </div>
            </div>

            {{-- Selectable preview list --}}
            <div id="m-previews" data-cloak class="mb-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Choose accounts') }}</span>
                    <span id="m-preview-hint" class="text-[11px] text-ink-500 dark:text-ink-400"></span>
                </div>
                <div id="m-preview-list" class="scroll-y max-h-64 space-y-1.5 overflow-y-auto pr-1"></div>
            </div>

            {{-- Quantity (plain pool products only) --}}
            <div id="m-qty-block">
            <div class="flex items-center justify-between">
                <label class="text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Quantity') }}</label>
                <span id="m-max" class="text-[11px] text-ink-500 dark:text-ink-400"></span>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <button type="button" id="m-minus" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-ink-300 text-[18px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">−</button>
                <input id="m-qty" type="number" min="1" value="1" inputmode="numeric"
                       class="h-11 w-full rounded-xl border-ink-300 text-center font-mono text-[15px] font-medium dark:border-ink-700 dark:bg-ink-900">
                <button type="button" id="m-plus" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-ink-300 text-[18px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">+</button>
            </div>
            </div>{{-- /m-qty-block --}}

            {{-- Delivery choice --}}
            <p class="mt-4 text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Delivery') }}</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <label class="relative">
                    <input type="radio" name="m-delivery" value="screen" class="peer sr-only" checked>
                    <span class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-ink-300 text-[12.5px] font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:border-ink-700 dark:peer-checked:bg-brand-500/10">
                        {{ __('Show on screen') }}
                    </span>
                </label>
                <label class="relative">
                    <input type="radio" name="m-delivery" value="file" class="peer sr-only">
                    <span class="flex h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-ink-300 text-[12.5px] font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:border-ink-700 dark:peer-checked:bg-brand-500/10">
                        {{ __('Download file') }}
                    </span>
                </label>
            </div>

            <p id="m-error" data-cloak class="mt-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300"></p>
        </div>

        <div class="border-t border-ink-200 p-5 dark:border-ink-800">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-[13px] font-bold">{{ __('Total') }}</span>
                <span id="m-total" class="font-mono text-[17px] font-medium"></span>
            </div>
            <button type="button" id="m-buy" class="btn-primary h-12 w-full text-[14px]"></button>
        </div>
    </div>
</div>

{{-- ═══════════════ Delivery / receipt ═══════════════ --}}
<div id="receipt" data-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
    <div id="receipt-scrim" class="absolute inset-0 bg-ink-950/50 backdrop-blur-[2px]"></div>

    <div class="anim-pop relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white sm:max-w-lg sm:rounded-2xl dark:bg-ink-950">
        <div class="flex items-center gap-3 border-b border-ink-200 p-5 dark:border-ink-800">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">
                <svg width="18" height="18" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="text-[15px] font-bold">{{ __('Order delivered') }}</h3>
                <p id="r-sub" class="text-[12px] text-ink-500 dark:text-ink-400"></p>
            </div>
            <button type="button" id="r-close" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-ink-500 transition hover:bg-ink-100 dark:hover:bg-ink-800" aria-label="{{ __('Close') }}">
                <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="scroll-y flex-1 overflow-y-auto p-5">
            {{-- Screen delivery: the accounts, each in its own block --}}
            <div id="r-items" class="space-y-2.5"></div>

            {{-- File delivery: a single download prompt --}}
            <div id="r-file" data-cloak class="rounded-xl border border-ink-200 bg-ink-50 p-6 text-center dark:border-ink-800 dark:bg-ink-900">
                <p class="text-[13px] font-semibold">{{ __('Your accounts are ready') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Kept in your orders — you can download again anytime.') }}</p>
                <div class="mt-4 flex justify-center gap-2">
                    <a id="r-download" href="#" class="btn-ghost inline-flex h-11 px-5 text-[13px]">
                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        {{ __('.txt') }}
                    </a>
                    <a id="r-download-pdf" href="#" class="btn-primary inline-flex h-11 px-5 text-[13px]">
                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                        {{ __('PDF') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="border-t border-ink-200 p-5 dark:border-ink-800">
            <div class="flex items-center gap-2">
                <button type="button" id="r-copy-all" class="btn-ghost h-11 flex-1 text-[13px]">{{ __('Copy all') }}</button>
                <a id="r-orders" href="{{ Route::has('orders.index') ? route('orders.index', ['tab' => 'accounts']) : '#' }}" class="btn-primary h-11 flex-1 text-[13px]">{{ __('My orders') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════ Preview picker (per-item) ═══════════════ --}}
<div id="picker" data-cloak class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
    <div id="picker-scrim" class="absolute inset-0 bg-ink-950/50 backdrop-blur-[2px]"></div>

    <div class="anim-pop relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white sm:max-w-lg sm:rounded-2xl dark:bg-ink-950">
        <div class="flex items-start gap-3 border-b border-ink-200 p-5 dark:border-ink-800">
            <div class="min-w-0 flex-1">
                <h3 id="pk-name" class="text-[15px] font-bold leading-tight"></h3>
                <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">
                    {{ __('Open a profile to preview it, then select the ones you want.') }}
                </p>
            </div>
            <button type="button" id="pk-close" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-ink-500 transition hover:bg-ink-100 dark:hover:bg-ink-800" aria-label="{{ __('Close') }}">
                <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div id="pk-list" class="scroll-y flex-1 space-y-2 overflow-y-auto p-4"></div>

        <div class="border-t border-ink-200 p-5 dark:border-ink-800">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-[12px] font-semibold text-ink-600 dark:text-ink-300"><span id="pk-count">0</span> {{ __('selected') }}</span>
                <span id="pk-total" class="font-mono text-[16px] font-medium"></span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <label class="relative">
                    <input type="radio" name="pk-delivery" value="screen" class="peer sr-only" checked>
                    <span class="flex h-10 cursor-pointer items-center justify-center rounded-xl border border-ink-300 text-[12px] font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:border-ink-700 dark:peer-checked:bg-brand-500/10">{{ __('On screen') }}</span>
                </label>
                <label class="relative">
                    <input type="radio" name="pk-delivery" value="file" class="peer sr-only">
                    <span class="flex h-10 cursor-pointer items-center justify-center rounded-xl border border-ink-300 text-[12px] font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 dark:border-ink-700 dark:peer-checked:bg-brand-500/10">{{ __('Download') }}</span>
                </label>
            </div>
            <p id="pk-error" data-cloak class="mt-3 rounded-xl border border-brand-600/25 bg-brand-50 p-2.5 text-center text-[12px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300"></p>
            <button type="button" id="pk-buy" disabled class="btn-primary mt-3 h-12 w-full text-[14px] disabled:cursor-not-allowed disabled:opacity-40">{{ __('Select accounts') }}</button>
        </div>
    </div>
</div>

<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-xl px-4 py-2.5 text-[13px] font-semibold shadow-lg" data-cloak></div>
@endsection

@push('scripts')
<script>
    window.LOGS = {
        products: @json(collect($groups)->flatMap(fn ($g) => $g['products'])->values()),
        routes: {
            show:     @json(url('/services/item')),
            items:    @json(url('/services/item')),
            purchase: @json(route('logs.store')),
            download: @json(url('/services/download')),
        },
    };
</script>
@verbatim
<script>
(function () {
    const cfg  = window.LOGS;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const $    = (id) => document.getElementById(id);
    const show = (node, on) => node && node.toggleAttribute('data-cloak', !on);

    // Small DOM builder used by the preview/receipt lists.
    function el(tag, cls, text) {
        const node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text != null) node.textContent = text;
        return node;
    }

    const bySlug = new Map((cfg.products || []).map((p) => [p.slug, p]));

    async function api(url, options = {}) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
            },
            ...options,
        });
        let body = {};
        try { body = await res.json(); } catch (e) {}
        if (!(res.ok && body.success !== false) && !body.message) {
            body.message = res.status === 419
                ? 'Your session expired. Reload and try again.'
                : 'Request failed. Try again.';
        }
        return { ok: res.ok && body.success !== false, status: res.status, body };
    }

    let toastTimer;
    function toast(msg, tone = 'ok') {
        const el = $('toast');
        el.textContent = msg;
        el.className = 'toast-in pointer-events-none fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 rounded-xl px-4 py-2.5 text-[13px] font-semibold shadow-lg ' +
            (tone === 'error' ? 'bg-brand-600 text-white' : 'bg-ink-900 text-white dark:bg-ink-100 dark:text-ink-900');
        show(el, true);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => show(el, false), 3600);
    }

    function setBalance(money) {
        if (!money) return;
        document.querySelectorAll('#wallet-balance, [data-wallet-balance]')
            .forEach((n) => { n.textContent = money.formatted; });
    }

    /* ── Search across every section ────────────────────────────── */

    const search = $('product-search');

    if (search) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let total = 0;

            // Hide non-matching rows, then hide any section left empty.
            document.querySelectorAll('#product-list [data-group]').forEach((section) => {
                let shown = 0;
                section.querySelectorAll('article[data-name]').forEach((card) => {
                    const on = !q || card.dataset.name.includes(q);
                    card.style.display = on ? '' : 'none';
                    if (on) {
                        // Gentle re-entrance for each surviving row.
                        card.style.animation = 'none';
                        void card.offsetWidth;
                        card.style.animation = '';
                        card.style.animationDelay = Math.min(shown * 30, 300) + 'ms';
                        shown++;
                    }
                });
                section.style.display = shown ? '' : 'none';
                total += shown;
            });

            show($('no-match'), total === 0);
        });
    }

    /* ── Buy modal ──────────────────────────────────────────────── */

    let current = null;

    let modalTokens = new Set();   // chosen preview items, if any
    let buyMode = 'quantity';      // 'select' | 'quantity'

    function setMode(mode) {
        buyMode = mode;

        document.querySelectorAll('.mode-btn').forEach((b) => {
            const on = b.dataset.mode === mode;
            b.className = 'mode-btn h-9 rounded-lg text-[12px] font-semibold transition ' +
                (on ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-ink-50'
                    : 'text-ink-500 dark:text-ink-400');
        });

        // Show the matching body; the other collapses.
        show($('m-previews'), mode === 'select');
        show($('m-qty-block'), mode === 'quantity');

        // Reset the max hint for quantity mode from the product's stock.
        if (mode === 'quantity' && current) {
            const ceiling = current.pre_order && current.stock === 0 ? current.max : Math.min(current.max, current.stock);
            $('m-qty').max = ceiling;
            if ((parseInt($('m-qty').value, 10) || 1) > ceiling) $('m-qty').value = ceiling;
            $('m-max').textContent = 'Max ' + ceiling;
        }

        recalc();
    }

    document.querySelectorAll('.mode-btn').forEach((b) => {
        b.addEventListener('click', () => setMode(b.dataset.mode));
    });

    function openModal(slug) {
        const p = bySlug.get(slug);
        if (!p) return;
        current = p;
        modalTokens.clear();

        $('m-name').textContent  = p.name;
        $('m-price').textContent = p.price.formatted + ' each';

        const stockEl = $('m-stock');
        if (p.pre_order && p.stock === 0) {
            stockEl.textContent = 'Pre-order';
            stockEl.className = 'rounded-md bg-amber-100 px-2 py-0.5 text-[10.5px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300';
        } else {
            stockEl.textContent = p.stock + ' in stock';
            stockEl.className = 'rounded-md bg-emerald-100 px-2 py-0.5 text-[10.5px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300';
        }

        // Modal thumbnail: flag if the product has a country, else nothing
        // fancy — the brand is already obvious from the name and the pill.
        const fs = $('m-flag');
        fs.replaceChildren();
        if (p.flag) {
            const img = document.createElement('img');
            img.src = 'https://flagcdn.com/w80/' + p.flag + '.png';
            img.className = 'h-full w-full object-cover';
            fs.append(img);
        } else {
            const s = document.createElement('span');
            s.className = 'grid h-full w-full place-items-center bg-ink-100 text-[12px] font-bold text-ink-500 dark:bg-ink-800 dark:text-ink-400';
            s.textContent = (p.category || '?').slice(0, 2);
            fs.append(s);
        }

        if (p.instructions) {
            $('m-instructions').textContent = p.instructions;
            show($('m-instructions'), true);
        } else {
            show($('m-instructions'), false);
        }

        // Max is the smaller of stock and per-order cap; pre-order uses the cap.
        const ceiling = p.pre_order && p.stock === 0 ? p.max : Math.min(p.max, p.stock);
        $('m-qty').max = ceiling;
        $('m-qty').value = 1;
        $('m-max').textContent = 'Max ' + ceiling;

        show($('m-error'), false);

        if (p.has_previews) {
            // Two ways to buy: pick specific accounts, or just enter a number.
            show($('m-mode'), true);
            setMode('select');           // default to picking
            loadModalPreviews(p);
        } else {
            // Plain pool: quantity stepper only.
            show($('m-mode'), false);
            show($('m-previews'), false);
            show($('m-qty-block'), true);
            buyMode = 'quantity';
        }

        recalc();
        show($('modal'), true);
        document.body.style.overflow = 'hidden';
    }

    async function loadModalPreviews(p) {
        const box = $('m-preview-list');
        box.innerHTML = '<p class="py-6 text-center text-[12px] text-ink-500">Loading accounts…</p>';
        $('m-preview-hint').textContent = '';

        let ok, body;
        try {
            ({ ok, body } = await api(cfg.routes.items + '/' + encodeURIComponent(p.slug) + '/list'));
        } catch (e) {
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-brand-700">Could not load accounts.</p>';
            return;
        }
        if (!ok) {
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-brand-700">' + (body.message || 'Unavailable') + '</p>';
            return;
        }

        const items = body.data.items || [];
        if (!items.length) {
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-ink-500">No accounts available.</p>';
            return;
        }

        $('m-preview-hint').textContent = items.length + ' available';
        box.replaceChildren();

        try {
        items.forEach((it) => {
            const row = el('label', 'flex w-full min-w-0 items-center gap-2.5 rounded-xl border border-ink-200 p-2.5 transition hover:border-ink-300 dark:border-ink-800 dark:hover:border-ink-700');

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-500';
            cb.addEventListener('change', () => {
                cb.checked ? modalTokens.add(it.token) : modalTokens.delete(it.token);
                row.classList.toggle('border-brand-600', cb.checked);
                row.classList.toggle('bg-brand-50', cb.checked);
                recalc();
            });

            const label = el('span', 'min-w-0 flex-1 overflow-hidden');
            label.append(el('span', 'block truncate text-[12.5px] font-semibold', it.label || 'Account'));
            if (it.preview) {
                label.append(el('span', 'block truncate text-[11px] text-ink-500 dark:text-ink-400',
                    it.preview.replace(/^https?:\/\//, '')));
            }

            row.append(cb, label);

            if (it.preview) {
                const open = document.createElement('a');
                open.href = it.preview;
                open.target = '_blank';
                open.rel = 'noopener noreferrer';
                open.className = 'shrink-0 rounded-lg border border-ink-300 px-2.5 py-1.5 text-[11px] font-semibold text-ink-700 transition hover:bg-ink-100 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-800';
                open.textContent = 'Preview ↗';
                open.addEventListener('click', (e) => e.stopPropagation());
                row.append(open);
            }

            box.append(row);
        });
        } catch (e) {
            console.error('preview list', e);
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-brand-700">Could not render accounts.</p>';
        }
    }

    function closeModal() {
        show($('modal'), false);
        document.body.style.overflow = '';
    }

    function recalc() {
        if (!current) return;

        let q;
        if (buyMode === 'select') {
            q = modalTokens.size;                       // count of chosen accounts
            $('m-buy').disabled = q === 0;
            $('m-buy').textContent = q === 0 ? 'Select accounts'
                : 'Buy ' + q + (q > 1 ? ' accounts' : ' account');
        } else {
            const ceiling = current.pre_order && current.stock === 0 ? current.max : Math.min(current.max, current.stock);
            q = parseInt($('m-qty').value, 10);
            if (isNaN(q) || q < 1) q = 1;
            if (q > ceiling) q = ceiling;
            $('m-qty').value = q;
            $('m-buy').disabled = false;
            $('m-buy').textContent = current.pre_order && current.stock === 0
                ? 'Pre-order ' + q
                : 'Buy ' + q + (q > 1 ? ' accounts' : ' account');
        }

        const total = current.price.amount * q;
        $('m-total').textContent = current.price.formatted.replace(/[\d.,]+/, total.toLocaleString());
    }

    document.querySelectorAll('[data-buy]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const p = bySlug.get(btn.dataset.buy);
            if (p && p.previewable) openPicker(p);
            else openModal(btn.dataset.buy);
        });
    });
    $('m-close').addEventListener('click', closeModal);
    $('modal-scrim').addEventListener('click', closeModal);
    $('m-minus').addEventListener('click', () => { $('m-qty').value = Math.max(1, (parseInt($('m-qty').value, 10) || 1) - 1); recalc(); });
    $('m-plus').addEventListener('click',  () => { $('m-qty').value = (parseInt($('m-qty').value, 10) || 1) + 1; recalc(); });
    $('m-qty').addEventListener('input', recalc);

    $('m-buy').addEventListener('click', async () => {
        if (!current) return;

        const btn = $('m-buy');
        btn.disabled = true;
        btn.classList.add('is-busy');
        show($('m-error'), false);

        const delivery = document.querySelector('input[name="m-delivery"]:checked').value;

        const payload = buyMode === 'select'
            ? { slug: current.slug, tokens: [...modalTokens], delivery }
            : { slug: current.slug, quantity: parseInt($('m-qty').value, 10) || 1, delivery };

        if (buyMode === 'select' && modalTokens.size === 0) {
            $('m-error').textContent = 'Select at least one account, or switch to Just a quantity.';
            return show($('m-error'), true);
        }

        let ok, body;
        try {
            ({ ok, body } = await api(cfg.routes.purchase, {
                method: 'POST',
                body: JSON.stringify(payload),
            }));
        } catch (e) {
            btn.disabled = false; btn.classList.remove('is-busy');
            $('m-error').textContent = 'Could not reach the server. Check your connection.';
            return show($('m-error'), true);
        } finally {
            btn.disabled = false; btn.classList.remove('is-busy');
        }

        if (!ok) {
            $('m-error').textContent = body.message || 'Purchase failed.';
            return show($('m-error'), true);
        }

        setBalance(body.data.balance);
        closeModal();
        openReceipt(body.data, delivery);
    });

    /* ── Preview picker (per-item, previewable products) ─────────── */

    const pk = {
        el: $('picker'), list: $('pk-list'), name: $('pk-name'),
        count: $('pk-count'), total: $('pk-total'), buy: $('pk-buy'), error: $('pk-error'),
    };
    let pkProduct = null;
    const pkSelected = new Set();

    async function openPicker(product) {
        pkProduct = product;
        pkSelected.clear();
        pk.name.textContent = product.name;
        pk.list.innerHTML = '<p class="py-10 text-center text-[12px] text-ink-500">Loading accounts…</p>';
        show(pk.error, false);
        refreshPickerFooter();
        show(pk.el, true);
        document.body.style.overflow = 'hidden';

        let ok, body;
        try {
            ({ ok, body } = await api(cfg.routes.items + '/' + encodeURIComponent(product.slug) + '/list'));
        } catch (e) {
            pk.list.innerHTML = '<p class="py-10 text-center text-[12px] text-brand-700">Could not load accounts.</p>';
            return;
        }
        if (!ok) {
            pk.list.innerHTML = '<p class="py-10 text-center text-[12px] text-brand-700">' + (body.message || 'Unavailable') + '</p>';
            return;
        }

        const items = body.data.items || [];
        if (!items.length) {
            pk.list.innerHTML = '<p class="py-10 text-center text-[12px] text-ink-500">No accounts available right now.</p>';
            return;
        }

        pk.list.replaceChildren();
        items.forEach((it) => {
            const row = el('label', 'flex w-full min-w-0 items-center gap-3 rounded-xl border border-ink-200 p-3 transition hover:border-ink-300 dark:border-ink-800 dark:hover:border-ink-700');

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-500';
            cb.addEventListener('change', () => {
                cb.checked ? pkSelected.add(it.token) : pkSelected.delete(it.token);
                row.classList.toggle('border-brand-600', cb.checked);
                row.classList.toggle('bg-brand-50', cb.checked);
                refreshPickerFooter();
            });

            const label = el('span', 'min-w-0 flex-1 overflow-hidden');
            label.append(el('span', 'block truncate text-[13px] font-semibold', it.label || 'Account'));
            if (it.preview) {
                const sub = el('span', 'block truncate text-[11px] text-ink-500 dark:text-ink-400', it.preview.replace(/^https?:\/\//, ''));
                label.append(sub);
            }

            row.append(cb, label);

            // Preview link opens the profile in a new tab; clicking it must not toggle the checkbox.
            if (it.preview) {
                const open = document.createElement('a');
                open.href = it.preview;
                open.target = '_blank';
                open.rel = 'noopener noreferrer';
                open.className = 'shrink-0 rounded-lg border border-ink-300 px-2.5 py-1.5 text-[11px] font-semibold text-ink-700 transition hover:bg-ink-100 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-800';
                open.textContent = 'Preview ↗';
                open.addEventListener('click', (e) => e.stopPropagation());
                row.append(open);
            }

            pk.list.append(row);
        });
    }

    function refreshPickerFooter() {
        const n = pkSelected.size;
        pk.count.textContent = n;
        const total = pkProduct ? pkProduct.price.amount * n : 0;
        pk.total.textContent = pkProduct ? pkProduct.price.formatted.replace(/[\d.,]+/, total.toLocaleString()) : '';
        pk.buy.disabled = n === 0;
        pk.buy.textContent = n === 0 ? 'Select accounts' : 'Buy ' + n + (n > 1 ? ' accounts' : ' account');
    }

    function closePicker() {
        show(pk.el, false);
        document.body.style.overflow = '';
    }

    $('pk-close').addEventListener('click', closePicker);
    $('picker-scrim').addEventListener('click', closePicker);

    pk.buy.addEventListener('click', async () => {
        if (!pkProduct || pkSelected.size === 0) return;

        pk.buy.disabled = true;
        pk.buy.classList.add('is-busy');
        show(pk.error, false);

        const delivery = document.querySelector('input[name="pk-delivery"]:checked').value;

        let ok, body;
        try {
            ({ ok, body } = await api(cfg.routes.purchase, {
                method: 'POST',
                body: JSON.stringify({ slug: pkProduct.slug, tokens: [...pkSelected], delivery }),
            }));
        } catch (e) {
            pk.buy.disabled = false; pk.buy.classList.remove('is-busy');
            pk.error.textContent = 'Could not reach the server.';
            return show(pk.error, true);
        } finally {
            pk.buy.disabled = false; pk.buy.classList.remove('is-busy');
        }

        if (!ok) {
            pk.error.textContent = body.message || 'Purchase failed.';
            return show(pk.error, true);
        }

        setBalance(body.data.balance);
        closePicker();
        openReceipt(body.data, delivery);
    });

    /* ── Receipt / delivery ─────────────────────────────────────── */

    let receiptItems = [];

    function openReceipt(data, delivery) {
        receiptItems = data.items || [];
        $('r-sub').textContent = data.quantity + ' × ' + data.product + ' · ' + data.total.formatted;

        const list = $('r-items');
        list.replaceChildren();

        if (delivery === 'file') {
            show($('r-items'), false);
            show($('r-file'), true);
            const dl = cfg.routes.download + '/' + encodeURIComponent(data.ref);
            $('r-download').href = dl;
            $('r-download-pdf').href = dl + '?format=pdf';
        } else {
            show($('r-file'), false);
            show($('r-items'), true);

            receiptItems.forEach((content, i) => {
                const box = document.createElement('div');
                box.className = 'rounded-xl border border-ink-200 dark:border-ink-800';

                const head = document.createElement('div');
                head.className = 'flex items-center justify-between border-b border-ink-100 px-3 py-2 dark:border-ink-800/60';
                const label = document.createElement('span');
                label.className = 'text-[11px] font-bold tracking-[0.06em] text-ink-500 dark:text-ink-400';
                label.textContent = 'ACCOUNT ' + (i + 1);

                const copy = document.createElement('button');
                copy.type = 'button';
                copy.className = 'rounded-md px-2 py-1 text-[11px] font-semibold text-brand-700 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10';
                copy.textContent = 'Copy';
                copy.addEventListener('click', async () => {
                    try { await navigator.clipboard.writeText(content); toast('Copied'); }
                    catch (e) { toast('Could not copy', 'error'); }
                });
                head.append(label, copy);

                const pre = document.createElement('pre');
                pre.className = 'scroll-y max-h-40 overflow-auto whitespace-pre-wrap break-all px-3 py-2.5 font-mono text-[12px] leading-relaxed';
                pre.textContent = content;

                box.append(head, pre);
                list.append(box);
            });
        }

        show($('receipt'), true);
        document.body.style.overflow = 'hidden';
    }

    function closeReceipt() {
        show($('receipt'), false);
        document.body.style.overflow = '';
        // Stock changed — a reload keeps the counts honest.
        setTimeout(() => window.location.reload(), 150);
    }

    $('r-close').addEventListener('click', closeReceipt);
    $('receipt-scrim').addEventListener('click', closeReceipt);
    $('r-copy-all').addEventListener('click', async () => {
        if (!receiptItems.length) return;
        try {
            await navigator.clipboard.writeText(receiptItems.join('\n\n' + '─'.repeat(40) + '\n\n'));
            toast('All copied');
        } catch (e) { toast('Could not copy', 'error'); }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeModal(); if (!$('receipt').hasAttribute('data-cloak')) closeReceipt(); }
    });
})();
</script>
@endverbatim
@endpush