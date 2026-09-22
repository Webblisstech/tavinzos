@extends('layouts.admin')

@section('title', __('Numbers catalog'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Numbers catalog') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Toggle services and countries on or off, and adjust prices.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-2xl border border-brand-600/25 bg-brand-50 p-4 text-[13px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ session('error') }}</div>
@endif

{{-- Tabs --}}
<div class="mb-5 flex gap-1.5 rounded-2xl border border-ink-200 bg-ink-50 p-1 dark:border-ink-800 dark:bg-ink-900/60">
    @foreach (['services' => __('USA services') . ' (' . $services->count() . ')', 'countries' => __('Countries') . ' (' . $countries->count() . ')', 'tiers' => __('Tier prices'), 'pricing' => __('Markup')] as $key => $label)
        <button type="button" data-tab="{{ $key }}" class="cat-tab h-9 flex-1 rounded-xl text-[12px] font-bold transition">{{ $label }}</button>
    @endforeach
</div>

{{-- ─── Services ─── --}}
<section data-panel="services" class="card p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Services') }}</h3>
        <input type="search" data-filter="services" placeholder="{{ __('Filter…') }}"
               class="h-9 w-40 rounded-xl border-ink-300 text-[12.5px] dark:border-ink-700 dark:bg-ink-950">
    </div>
    @if ($services->isEmpty())
        <p class="py-10 text-center text-[13px] text-ink-500 dark:text-ink-400">{{ __('Could not load the catalog. Check the numbers API connection.') }}</p>
    @else
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3" data-list="services">
            @foreach ($services as $s)
                <label data-name="{{ mb_strtolower($s['name']) }} {{ $s['code'] }}"
                       class="flex items-center justify-between gap-2 rounded-xl border p-3 transition {{ $s['blocked'] ? 'border-brand-200 bg-brand-50/50 dark:border-brand-500/25 dark:bg-brand-500/5' : 'border-ink-100 dark:border-ink-800' }}">
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-semibold {{ $s['blocked'] ? 'text-ink-400 line-through' : '' }}">{{ $s['name'] }}</span>
                        <span class="block truncate text-[10.5px] text-ink-400"><span class="font-mono">{{ $s['code'] }}</span> · ${{ number_format($s['price'], 2) }}</span>
                    </span>
                    <form method="POST" action="{{ route('admin.numbers.toggle-block') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="type" value="block_service">
                        <input type="hidden" name="target" value="{{ $s['code'] }}">
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition {{ $s['blocked'] ? 'bg-ink-300 dark:bg-ink-700' : 'bg-emerald-500' }}" aria-label="{{ __('Toggle') }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $s['blocked'] ? 'translate-x-0.5' : 'translate-x-[22px]' }}"></span>
                        </button>
                    </form>
                </label>
            @endforeach
        </div>
    @endif
</section>

{{-- ─── Countries ─── --}}
<section data-panel="countries" class="card hidden p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Countries') }}</h3>
        <input type="search" data-filter="countries" placeholder="{{ __('Filter…') }}"
               class="h-9 w-40 rounded-xl border-ink-300 text-[12.5px] dark:border-ink-700 dark:bg-ink-950">
    </div>
    @if ($countries->isEmpty())
        <p class="py-10 text-center text-[13px] text-ink-500 dark:text-ink-400">{{ __('Could not load countries. Check the numbers API connection.') }}</p>
    @else
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3" data-list="countries">
            @foreach ($countries as $c)
                <label data-name="{{ mb_strtolower($c['name']) }}"
                       class="flex items-center justify-between gap-2 rounded-xl border p-3 transition {{ $c['blocked'] ? 'border-brand-200 bg-brand-50/50 dark:border-brand-500/25 dark:bg-brand-500/5' : 'border-ink-100 dark:border-ink-800' }}">
                    <span class="min-w-0 truncate text-[13px] font-semibold {{ $c['blocked'] ? 'text-ink-400 line-through' : '' }}">{{ $c['name'] }}</span>
                    <form method="POST" action="{{ route('admin.numbers.toggle-block') }}" class="shrink-0">
                        @csrf
                        <input type="hidden" name="type" value="block_country">
                        <input type="hidden" name="target" value="{{ $c['name'] }}">
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition {{ $c['blocked'] ? 'bg-ink-300 dark:bg-ink-700' : 'bg-emerald-500' }}" aria-label="{{ __('Toggle') }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $c['blocked'] ? 'translate-x-0.5' : 'translate-x-[22px]' }}"></span>
                        </button>
                    </form>
                </label>
            @endforeach
        </div>
    @endif
</section>

{{-- ─── Tier prices (country → service → tiers) ─── --}}
<section data-panel="tiers" class="card hidden p-5">
    <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Disable specific price tiers') }}</h3>
    <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Pick a country, then a service, then switch off the prices you don\'t want to offer.') }}</p>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Country') }}</label>
            <select id="tier-country" class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                <option value="">{{ __('Select a country…') }}</option>
                @foreach ($countryList as $c)
                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Service') }}</label>
            <select id="tier-service" disabled class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] disabled:opacity-50 dark:border-ink-700 dark:bg-ink-900">
                <option value="">{{ __('Select a country first') }}</option>
            </select>
        </div>
    </div>

    <div id="tier-list" class="mt-5 space-y-2"></div>
    <p id="tier-hint" class="mt-4 text-center text-[12.5px] text-ink-400">{{ __('Choose a country and service to see its prices.') }}</p>
</section>

{{-- ─── Pricing (markup rules) ─── --}}
<section data-panel="pricing" class="hidden space-y-4">
    <div class="card p-6">
        <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Add a price rule') }}</h3>
        <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Stacks on top of your base markup.') }}</p>
        <form method="POST" action="{{ route('admin.numbers.store') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4" id="price-form">
            @csrf
            <div>
                <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Scope') }}</label>
                <select name="type" id="price-type" class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                    <option value="price_all">{{ __('All countries') }}</option>
                    <option value="price_service">{{ __('One service') }}</option>
                    <option value="price_country">{{ __('One country') }}</option>
                </select>
            </div>
            <div data-target-field class="hidden">
                <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Target') }}</label>
                <input name="target" placeholder="{{ __('Service or country') }}"
                       class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Mode') }}</label>
                <select name="price_mode" class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                    <option value="percent">{{ __('Percent (%)') }}</option>
                    <option value="flat">{{ __('Flat amount') }}</option>
                </select>
            </div>
            <div>
                <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Value') }}</label>
                <input name="price_value" type="number" step="any" placeholder="20"
                       class="mt-1 h-11 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-900">
            </div>
            <div class="sm:col-span-4"><button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Add rule') }}</button></div>
        </form>
    </div>

    <div class="card p-6">
        <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Active price rules') }}</h3>
        <div class="mt-4 space-y-2">
            @forelse ($priceRules as $r)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-ink-100 p-3 dark:border-ink-800 {{ $r->active ? '' : 'opacity-50' }}">
                    <div>
                        <span class="text-[13px] font-semibold">
                            @switch($r->type)
                                @case('price_all') {{ __('All countries') }} @break
                                @case('price_service') {{ __('Service') }}: {{ $r->target }} @break
                                @case('price_country') {{ __('Country') }}: {{ $r->target }} @break
                            @endswitch
                        </span>
                        <span class="ml-2 rounded-md bg-ink-100 px-2 py-0.5 font-mono text-[11px] font-bold dark:bg-ink-800">{{ $r->price_mode === 'flat' ? '+' : '' }}{{ $r->price_value }}{{ $r->price_mode === 'percent' ? '%' : '' }}</span>
                    </div>
                    <span class="flex items-center gap-1.5">
                        <form method="POST" action="{{ route('admin.numbers.toggle', $r->id) }}">@csrf<button class="rounded-lg border border-ink-200 px-2.5 py-1 text-[11px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">{{ $r->active ? __('Disable') : __('Enable') }}</button></form>
                        <form method="POST" action="{{ route('admin.numbers.destroy', $r->id) }}">@csrf @method('DELETE')<button class="rounded-lg px-2 py-1 text-[11px] font-bold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Remove') }}</button></form>
                    </span>
                </div>
            @empty
                <p class="py-6 text-center text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('No price rules yet.') }}</p>
            @endforelse
        </div>
    </div>
</section>

@push('scripts')
<script>
    (function () {
        // Tabs
        var tabs = document.querySelectorAll('.cat-tab');
        var panels = document.querySelectorAll('[data-panel]');
        function activate(key) {
            tabs.forEach(function (t) {
                var on = t.dataset.tab === key;
                t.className = 'cat-tab h-9 flex-1 rounded-xl text-[12.5px] font-bold transition ' +
                    (on ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-ink-50' : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100');
            });
            panels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== key); });
        }
        tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.tab); }); });
        activate('services');

        // Live filter within a list
        document.querySelectorAll('[data-filter]').forEach(function (input) {
            input.addEventListener('input', function () {
                var q = input.value.toLowerCase();
                var list = document.querySelector('[data-list="' + input.dataset.filter + '"]');
                list && list.querySelectorAll('[data-name]').forEach(function (row) {
                    row.style.display = row.dataset.name.indexOf(q) === -1 ? 'none' : '';
                });
            });
        });

        // Price form: target field only for service/country scopes
        var ptype = document.getElementById('price-type');
        var tfield = document.querySelector('[data-target-field]');
        function ptoggle() { tfield.classList.toggle('hidden', ptype.value === 'price_all'); }
        ptype.addEventListener('change', ptoggle); ptoggle();

        // ── Tier drill-down: country → service → tiers ──
        var tc = document.getElementById('tier-country');
        var ts = document.getElementById('tier-service');
        var tlist = document.getElementById('tier-list');
        var thint = document.getElementById('tier-hint');
        var csrf = document.querySelector('meta[name=csrf-token]')?.content || '';

        function loadServices(country) {
            ts.disabled = true; ts.innerHTML = '<option>' + @json(__('Loading…')) + '</option>';
            tlist.innerHTML = ''; thint.textContent = @json(__('Choose a service.'));
            fetch(@json(url('admin/numbers/country')) + '/' + country + '/services', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    ts.innerHTML = '<option value="">' + @json(__('Select a service…')) + '</option>';
                    (d.services || []).forEach(function (s) {
                        var o = document.createElement('option');
                        o.value = s.code; o.textContent = s.name + ' (' + s.code + ')';
                        ts.appendChild(o);
                    });
                    ts.disabled = false;
                });
        }

        function loadTiers(country, service) {
            tlist.innerHTML = ''; thint.textContent = @json(__('Loading prices…'));
            fetch(@json(url('admin/numbers/country')) + '/' + country + '/service/' + encodeURIComponent(service) + '/tiers', { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var tiers = d.tiers || [];
                    if (!tiers.length) { thint.textContent = @json(__('No prices for this service.')); return; }
                    thint.textContent = '';
                    tiers.forEach(function (t) {
                        var row = document.createElement('div');
                        row.className = 'flex items-center justify-between gap-3 rounded-xl border p-3 ' +
                            (t.blocked ? 'border-brand-200 bg-brand-50/50 dark:border-brand-500/25 dark:bg-brand-500/5' : 'border-ink-100 dark:border-ink-800');
                        row.innerHTML =
                            '<div><span class="font-mono text-[14px] font-bold ' + (t.blocked ? 'text-ink-400 line-through' : '') + '">' + t.price + '</span>' +
                            '<span class="ml-2 text-[11px] text-ink-400">' + t.available + ' ' + @json(__('available')) + '</span></div>';
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'relative inline-flex h-6 w-11 items-center rounded-full transition ' + (t.blocked ? 'bg-ink-300 dark:bg-ink-700' : 'bg-emerald-500');
                        btn.innerHTML = '<span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition ' + (t.blocked ? 'translate-x-0.5' : 'translate-x-[22px]') + '"></span>';
                        btn.addEventListener('click', function () {
                            btn.disabled = true;
                            fetch(@json(route('admin.numbers.toggle-tier')), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                                body: JSON.stringify({ target: t.key }),
                            }).then(function (r) { return r.json(); }).then(function () { loadTiers(country, service); });
                        });
                        row.appendChild(btn);
                        tlist.appendChild(row);
                    });
                });
        }

        tc && tc.addEventListener('change', function () {
            if (tc.value) loadServices(tc.value);
            else { ts.disabled = true; ts.innerHTML = ''; tlist.innerHTML = ''; }
        });
        ts && ts.addEventListener('change', function () {
            if (tc.value && ts.value) loadTiers(tc.value, ts.value);
        });
    })();
</script>
@endpush
@endsection