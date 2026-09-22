@extends('layouts.app')

@section('title', __('Orders'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
            {{ __('Your orders') }}
        </h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
            {{ __('Everything you have bought — numbers and accounts.') }}
        </p>
    </div>
@endsection

@section('content')

{{-- Tabs --}}
<div role="tablist" class="mb-5 inline-flex rounded-2xl border border-ink-200 bg-ink-50 p-1 dark:border-ink-800 dark:bg-ink-900/60">
    @foreach (['all' => __('All'), 'numbers' => __('Numbers'), 'accounts' => __('Accounts')] as $key => $label)
        <button type="button" role="tab" data-tab="{{ $key }}"
                class="tab-btn flex h-10 items-center gap-2 rounded-xl px-4 text-[13px] font-bold tracking-[-0.01em] transition-all duration-200">
            {{ $label }}
            <span data-count class="rounded-md px-1.5 py-0.5 font-mono text-[10px] font-semibold tabular-nums transition-colors">{{ $counts[$key] }}</span>
        </button>
    @endforeach
</div>

{{-- Filters: search + status, applied within the active tab --}}
<div class="mb-5 flex flex-col gap-2.5 sm:flex-row sm:items-center">
    <div class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 shadow-sm shadow-ink-900/5 transition-all focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="17" height="17" class="h-[17px] w-[17px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input id="order-search" type="search" placeholder="{{ __('Search by name, code or reference…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </div>

    <div class="flex flex-wrap gap-1.5">
        @foreach (['all' => __('Any status'), 'active' => __('Waiting'), 'done' => __('Completed'), 'closed' => __('Cancelled')] as $key => $label)
            <button type="button" data-status="{{ $key }}"
                    class="status-btn h-11 rounded-xl border px-3.5 text-[12.5px] font-semibold transition-all duration-150">
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>

{{-- Date range: quick presets + a custom picker --}}
<div class="mb-5 flex flex-wrap items-center gap-1.5">
    @foreach (['all' => __('Any time'), 'today' => __('Today'), '7d' => __('7 days'), '30d' => __('30 days')] as $key => $label)
        <button type="button" data-range="{{ $key }}"
                class="range-btn h-9 rounded-lg border px-3 text-[12px] font-semibold transition-all duration-150">
            {{ $label }}
        </button>
    @endforeach

    <span class="mx-1 hidden h-5 w-px bg-ink-200 sm:block dark:bg-ink-800"></span>

    {{-- Custom from/to. Selecting either switches the presets to "custom". --}}
    <div class="flex items-center gap-1.5">
        <input type="date" id="date-from" aria-label="{{ __('From date') }}"
               class="h-9 rounded-lg border-ink-200 text-[12px] font-medium text-ink-700 dark:border-ink-800 dark:bg-ink-950 dark:text-ink-200">
        <span class="text-[12px] text-ink-400">–</span>
        <input type="date" id="date-to" aria-label="{{ __('To date') }}"
               class="h-9 rounded-lg border-ink-200 text-[12px] font-medium text-ink-700 dark:border-ink-800 dark:bg-ink-950 dark:text-ink-200">
        <button type="button" id="date-clear" data-cloak
                class="h-9 rounded-lg px-2 text-[12px] font-semibold text-ink-500 transition hover:text-brand-700 dark:hover:text-brand-400">{{ __('Clear') }}</button>
    </div>
</div>

{{-- No-results notice (shown by JS when filters exclude everything) --}}
<p id="orders-empty-filter" data-cloak class="card px-6 py-14 text-center text-[13px] text-ink-500 dark:text-ink-400">
    {{ __('No orders match your filters.') }}
</p>

@php
    $render = function ($rows) {
        return $rows;
    };
@endphp

{{-- One list per tab; JS shows the active one --}}
@foreach (['all' => $all, 'numbers' => $numbers, 'accounts' => $accounts] as $key => $rows)
    <div data-panel="{{ $key }}" @if ($key !== $tab) data-cloak @endif>
        @forelse ($rows as $row)
            @php
                $tone = match ($row['status']) {
                    'Completed', 'Delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                    'Cancelled', 'Refunded'  => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                    'Waiting', 'Pending'     => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                    default                  => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                };
                $isAccount = $row['kind'] === 'account';
            @endphp

            @php
                // Map every status onto one of the three filter buckets.
                $bucket = match ($row['status']) {
                    'Completed', 'Delivered' => 'done',
                    'Waiting', 'Pending'     => 'active',
                    'Cancelled', 'Refunded'  => 'closed',
                    default                  => 'closed',
                };
                $haystack = \Illuminate\Support\Str::lower(
                    ($row['title'] ?? '') . ' ' . ($row['sub'] ?? '') . ' ' .
                    ($row['ref'] ?? '') . ' ' . ($row['code'] ?? '')
                );
            @endphp
            <div class="order-row card row-in mb-2.5 flex items-center gap-4 p-3.5 transition-shadow hover:shadow-md hover:shadow-ink-900/5 sm:p-4"
                 data-status="{{ $bucket }}" data-search="{{ $haystack }}"
                 data-date="{{ \Illuminate\Support\Carbon::parse($row['at'])->format('Y-m-d') }}"
                 style="animation-delay: {{ min($loop->index * 40, 400) }}ms">
                <span class="relative h-10 w-10 shrink-0">
                    @if (($row['image'] ?? '') !== '')
                        {{-- Admin-uploaded product artwork --}}
                        <img src="{{ $row['image'] }}" alt="" class="h-full w-full rounded-xl object-cover" loading="lazy">
                    @elseif (($row['icon'] ?? '') !== '')
                        {{-- Category brand glyph --}}
                        <span class="grid h-full w-full place-items-center rounded-xl bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200">
                            @include('partials.brand-icon', ['icon' => $row['icon'], 'class' => 'h-[22px] w-[22px]'])
                        </span>
                    @else
                        {{-- Kind fallback: layers for accounts, dialpad for numbers --}}
                        <span class="grid h-full w-full place-items-center rounded-xl bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300">
                            @if ($isAccount)
                                <svg width="19" height="19" class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                            @else
                                <svg width="19" height="19" class="h-[19px] w-[19px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                            @endif
                        </span>
                    @endif

                    @if (($row['flag'] ?? '') !== '')
                        <img src="https://flagcdn.com/w40/{{ $row['flag'] }}.png" alt=""
                             class="absolute -bottom-1 -right-1 h-3.5 w-[18px] rounded-[2px] object-cover ring-2 ring-white dark:ring-ink-950" loading="lazy">
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-bold tracking-[-0.01em] text-ink-900 dark:text-ink-50">{{ $row['title'] }}</p>
                    <p class="truncate text-[11.5px] font-medium text-ink-500 dark:text-ink-400">
                        {{ $row['sub'] }}
                        @if (($row['code'] ?? '') !== '')
                            · <span class="font-mono font-bold text-brand-600 dark:text-brand-400">{{ $row['code'] }}</span>
                        @endif
                    </p>
                </div>

                <span data-status-pill class="hidden shrink-0 rounded-lg px-2.5 py-1 text-[11px] font-bold sm:block {{ $tone }}">{{ $row['status'] }}</span>

                <div class="shrink-0 text-right">
                    <p class="font-mono text-[14px] font-bold text-ink-900 dark:text-ink-50">{{ $row['amount']['formatted'] }}</p>
                    <p class="text-[10.5px] font-medium text-ink-400">{{ \Carbon\Carbon::parse($row['at'])->format('j M, H:i') }}</p>
                </div>

                @if ($isAccount)
                    <a href="{{ route('orders.show', $row['ref']) }}"
                       class="shrink-0 rounded-lg border border-ink-300 px-3 py-1.5 text-[11.5px] font-semibold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-900">
                        {{ __('View') }}
                    </a>
                @elseif (($row['cancellable'] ?? false))
                    <button type="button"
                            class="cancel-btn shrink-0 rounded-lg border border-ink-300 px-3 py-1.5 text-[11.5px] font-semibold transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-ink-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                            data-ref="{{ $row['ref'] }}"
                            data-bought="{{ $row['bought_at'] }}"
                            data-lock="{{ $row['lock'] }}">
                        {{ __('Cancel') }}
                    </button>
                @else
                    <span class="hidden shrink-0 font-mono text-[10.5px] text-ink-400 sm:block">{{ $row['ref'] }}</span>
                @endif
            </div>
        @empty
            <div class="card px-6 py-16 text-center">
                <p class="text-[13px] font-semibold">{{ __('Nothing here yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">
                    @if ($key === 'numbers')
                        {{ __('Buy a virtual number and it will show up here.') }}
                    @elseif ($key === 'accounts')
                        {{ __('Accounts you purchase will appear here to download anytime.') }}
                    @else
                        {{ __('Your purchases will appear here.') }}
                    @endif
                </p>
                <div class="mt-4 flex justify-center gap-2">
                    <a href="{{ route('numbers.index') }}" class="btn-ghost h-10 text-[13px]">{{ __('Buy a number') }}</a>
                    <a href="{{ route('logs.index') }}" class="btn-primary h-10 text-[13px]">{{ __('Browse accounts') }}</a>
                </div>
            </div>
        @endforelse
    </div>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    const params = new URLSearchParams(location.search);
    const has = (v) => ['all','numbers','accounts'].includes(v);

    let tab    = has(params.get('tab')) ? params.get('tab') : @json($tab);
    let status = 'all';
    let query  = '';
    let range  = 'all';        // preset key
    let from   = null;         // 'YYYY-MM-DD' or null
    let to     = null;

    const fromInput = document.getElementById('date-from');
    const toInput   = document.getElementById('date-to');
    const clearBtn  = document.getElementById('date-clear');

    // Turn a preset into concrete from/to bounds (local dates as YYYY-MM-DD).
    function ymd(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }
    function resolveRange() {
        if (from || to) return { lo: from, hi: to };      // custom wins
        const today = new Date();
        if (range === 'today') return { lo: ymd(today), hi: ymd(today) };
        if (range === '7d')  { const d = new Date(); d.setDate(d.getDate() - 6);  return { lo: ymd(d), hi: ymd(today) }; }
        if (range === '30d') { const d = new Date(); d.setDate(d.getDate() - 29); return { lo: ymd(d), hi: ymd(today) }; }
        return { lo: null, hi: null };
    }

    const search = document.getElementById('order-search');
    const emptyNote = document.getElementById('orders-empty-filter');

    /* ── Tabs ─────────────────────────────────────────────────────── */
    function paintTabs() {
        document.querySelectorAll('.tab-btn').forEach((b) => {
            const on = b.dataset.tab === tab;
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.className = 'tab-btn flex h-10 items-center gap-2 rounded-xl px-4 text-[13px] font-bold tracking-[-0.01em] transition-all duration-200 ' +
                (on ? 'bg-white text-ink-900 shadow-sm shadow-ink-900/5 dark:bg-ink-700 dark:text-ink-50'
                    : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100');
            const badge = b.querySelector('[data-count]');
            if (badge) {
                badge.className = 'rounded-md px-1.5 py-0.5 font-mono text-[10px] font-semibold tabular-nums transition-colors ' +
                    (on ? 'bg-brand-600 text-white' : 'bg-ink-200 text-ink-500 dark:bg-ink-800 dark:text-ink-400');
                if (on) { badge.classList.remove('tick'); void badge.offsetWidth; badge.classList.add('tick'); }
            }
        });
    }

    /* ── Status pills ─────────────────────────────────────────────── */
    function paintStatus() {
        document.querySelectorAll('.status-btn').forEach((b) => {
            const on = b.dataset.status === status;
            b.className = 'status-btn h-11 rounded-xl border px-3.5 text-[12.5px] font-semibold transition-all duration-150 ' +
                (on ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400'
                    : 'border-ink-200 text-ink-600 hover:bg-ink-100 dark:border-ink-800 dark:text-ink-300 dark:hover:bg-ink-900');
        });
    }

    function paintRange() {
        document.querySelectorAll('.range-btn').forEach((b) => {
            // A custom date range de-highlights every preset.
            const on = !from && !to && b.dataset.range === range;
            b.className = 'range-btn h-9 rounded-lg border px-3 text-[12px] font-semibold transition-all duration-150 ' +
                (on ? 'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-400'
                    : 'border-ink-200 text-ink-600 hover:bg-ink-100 dark:border-ink-800 dark:text-ink-300 dark:hover:bg-ink-900');
        });
        if (clearBtn) clearBtn.toggleAttribute('data-cloak', !(from || to));
    }

    /* ── Apply all filters together ───────────────────────────────── */
    function apply(animate) {
        // Show only the active tab's panel.
        document.querySelectorAll('[data-panel]').forEach((panel) => {
            panel.toggleAttribute('data-cloak', panel.dataset.panel !== tab);
        });

        const panel = document.querySelector('[data-panel="' + tab + '"]');
        if (!panel) return;

        const { lo, hi } = resolveRange();

        let shown = 0;
        panel.querySelectorAll('.order-row').forEach((row) => {
            const d = row.dataset.date || '';
            const okStatus = status === 'all' || row.dataset.status === status;
            const okSearch = !query || (row.dataset.search || '').includes(query);
            // String compare is safe for YYYY-MM-DD (lexical == chronological).
            const okDate   = (!lo || d >= lo) && (!hi || d <= hi);
            const on = okStatus && okSearch && okDate;
            row.style.display = on ? '' : 'none';
            if (on) {
                if (animate) {
                    row.style.animation = 'none';
                    void row.offsetWidth;
                    row.style.animation = '';
                    row.style.animationDelay = Math.min(shown * 40, 400) + 'ms';
                }
                shown++;
            }
        });

        // The tab's own server-rendered empty-state only shows when the tab
        // has zero orders at all. When filters hide everything, show our
        // filter-specific note instead.
        const hasAnyRows = panel.querySelectorAll('.order-row').length > 0;
        if (emptyNote) emptyNote.toggleAttribute('data-cloak', !(hasAnyRows && shown === 0));
    }

    function setTab(name)   { tab = name; paintTabs(); apply(true);
        const url = new URL(location); url.searchParams.set('tab', name); history.replaceState({}, '', url); }
    function setStatus(v)   { status = v; paintStatus(); apply(true); }

    function setRange(v) {
        range = v; from = null; to = null;
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
        paintRange(); apply(true);
    }
    function setCustom() {
        from = fromInput && fromInput.value ? fromInput.value : null;
        to   = toInput && toInput.value ? toInput.value : null;
        paintRange(); apply(true);
    }

    document.querySelectorAll('.tab-btn').forEach((b) => b.addEventListener('click', () => setTab(b.dataset.tab)));
    document.querySelectorAll('.status-btn').forEach((b) => b.addEventListener('click', () => setStatus(b.dataset.status)));
    document.querySelectorAll('.range-btn').forEach((b) => b.addEventListener('click', () => setRange(b.dataset.range)));
    if (fromInput) fromInput.addEventListener('change', setCustom);
    if (toInput)   toInput.addEventListener('change', setCustom);
    if (clearBtn)  clearBtn.addEventListener('click', () => setRange('all'));
    if (search) search.addEventListener('input', () => { query = search.value.trim().toLowerCase(); apply(false); });

    /* ── Cancel a still-waiting number, reusing the numbers endpoint ── */

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const cancelBase = @json(url('/numbers/order'));

    function tickCancels() {
        const now = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.cancel-btn').forEach((btn) => {
            if (btn.dataset.busy || btn.dataset.done) return;
            const left = (parseInt(btn.dataset.bought, 10) + parseInt(btn.dataset.lock, 10)) - now;
            if (left > 0) {
                btn.disabled = true;
                btn.textContent = 'Cancel in ' + left + 's';
            } else {
                btn.disabled = false;
                btn.textContent = 'Cancel';
            }
        });
    }
    setInterval(tickCancels, 1000);
    tickCancels();

    document.querySelectorAll('.cancel-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled || btn.dataset.busy) return;
            btn.dataset.busy = '1';
            btn.disabled = true;
            btn.textContent = 'Cancelling…';

            let ok, body;
            try {
                const res = await fetch(cancelBase + '/' + encodeURIComponent(btn.dataset.ref) + '/cancel', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                body = await res.json().catch(() => ({}));
                ok = res.ok && body.success !== false;
            } catch (e) {
                delete btn.dataset.busy;
                btn.disabled = false;
                btn.textContent = 'Try again';
                return;
            }

            const d = body.data || {};
            const row = btn.closest('.order-row');

            // The code may have arrived in the same instant we cancelled.
            if (ok && d.outcome === 'code_received') {
                btn.dataset.done = '1';
                btn.textContent = 'Code received';
                if (row) row.dataset.status = 'done';
                return;
            }

            if (!ok) {
                delete btn.dataset.busy;
                btn.disabled = false;
                btn.textContent = 'Try again';
                return;
            }

            // Refunded: mark the row cancelled, retire the button, update balance.
            btn.dataset.done = '1';
            btn.textContent = 'Refunded';
            btn.classList.add('text-ink-400');
            if (row) {
                row.dataset.status = 'closed';
                const pill = row.querySelector('[data-status-pill]');
                if (pill) {
                    pill.textContent = 'Cancelled';
                    pill.className = 'hidden shrink-0 rounded-lg px-2.5 py-1 text-[11px] font-bold sm:block bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300';
                }
            }
            if (d.balance) {
                document.querySelectorAll('#wallet-balance, [data-wallet-balance]')
                    .forEach((n) => { n.textContent = d.balance.formatted; });
            }
        });
    });

        paintTabs(); paintStatus(); paintRange(); apply(false);
})();
</script>
@endpush