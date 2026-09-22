@extends('layouts.admin')

@section('title', __('Manage accounts'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ __('Account stock') }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
                {{ $stats['products'] }} {{ __('products') }} · {{ $stats['sold'] }} {{ __('sold to date') }}
            </p>
        </div>
        <button type="button" data-toggle="new-product" class="btn-primary h-10 text-[13px]">
            <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('New product') }}
        </button>
    </div>
@endsection

@section('content')

{{-- New product form (hidden until toggled) --}}
<section id="new-product" data-cloak class="card mb-4 p-5">
    <h3 class="text-[14px] font-bold">{{ __('New product') }}</h3>
    <form method="POST" action="{{ route('admin.logs.store') }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @csrf
        @include('admin.logs._fields')
        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Create product') }}</button>
        </div>
    </form>
</section>

@if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-600/25 bg-emerald-50 p-3.5 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
        {{ session('status') }}
    </div>
@endif

@error('paste')
    <div class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3.5 text-[13px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $message }}</div>
@enderror

@error('item')
    <div class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3.5 text-[13px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $message }}</div>
@enderror

{{-- ── Headline stats ─────────────────────────────────────────── --}}
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
    @php
        $sym = config('services.numbers.currency.symbol', '₦');
        $cards = [
            ['label' => __('In stock'),  'value' => number_format($stats['in_stock']), 'tone' => 'emerald'],
            ['label' => __('Sold'),      'value' => number_format($stats['sold']),     'tone' => 'ink'],
            ['label' => __('Revenue'),   'value' => $sym . number_format($stats['revenue']), 'tone' => 'brand'],
            ['label' => __('Low stock'), 'value' => number_format($stats['low']),       'tone' => $stats['low'] ? 'amber' : 'ink'],
            ['label' => __('Empty'),     'value' => number_format($stats['empty']),     'tone' => $stats['empty'] ? 'rose' : 'ink'],
        ];
        $toneMap = [
            'emerald' => 'text-emerald-600 dark:text-emerald-400',
            'brand'   => 'text-brand-600 dark:text-brand-400',
            'amber'   => 'text-amber-600 dark:text-amber-400',
            'rose'    => 'text-rose-600 dark:text-rose-400',
            'ink'     => 'text-ink-900 dark:text-ink-50',
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="card p-4">
            <p class="text-[10.5px] font-bold uppercase tracking-[0.09em] text-ink-500 dark:text-ink-400">{{ $c['label'] }}</p>
            <p class="mt-1.5 font-mono text-[20px] font-bold tracking-[-0.02em] {{ $toneMap[$c['tone']] }}">{{ $c['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ── Filter bar ─────────────────────────────────────────────── --}}
<form method="GET" class="mb-5 flex flex-col gap-2.5 lg:flex-row lg:items-center">
    <div class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 shadow-sm shadow-ink-900/5 transition-all focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="17" height="17" class="h-[17px] w-[17px] shrink-0 text-ink-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="{{ __('Search products…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </div>

    <select name="category" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="">{{ __('All categories') }}</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" @selected($filters['category'] == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>

    <select name="status" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="">{{ __('Any status') }}</option>
        <option value="active" @selected($filters['status'] === 'active')>{{ __('Visible') }}</option>
        <option value="hidden" @selected($filters['status'] === 'hidden')>{{ __('Hidden') }}</option>
    </select>

    <select name="sort" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="stock_asc"  @selected($filters['sort'] === 'stock_asc')>{{ __('Lowest stock') }}</option>
        <option value="stock_desc" @selected($filters['sort'] === 'stock_desc')>{{ __('Highest stock') }}</option>
        <option value="sold_desc"  @selected($filters['sort'] === 'sold_desc')>{{ __('Best selling') }}</option>
        <option value="price_desc" @selected($filters['sort'] === 'price_desc')>{{ __('Price: high') }}</option>
        <option value="price_asc"  @selected($filters['sort'] === 'price_asc')>{{ __('Price: low') }}</option>
        <option value="newest"     @selected($filters['sort'] === 'newest')>{{ __('Newest') }}</option>
        <option value="name"       @selected($filters['sort'] === 'name')>{{ __('Name A–Z') }}</option>
    </select>

    <button type="submit" class="btn-primary h-11 px-5 text-[13px]">{{ __('Apply') }}</button>
    @if ($filters['q'] || $filters['category'] || $filters['status'] || $filters['sort'] !== 'stock_asc')
        <a href="{{ route('admin.logs.index') }}" class="btn-ghost h-11 px-4 text-[13px]">{{ __('Reset') }}</a>
    @endif
</form>

{{-- Product list --}}
<div class="space-y-3">
    @forelse ($products as $p)
        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 p-4">
                <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200">
                    @if ($p->image)
                        <img src="{{ route('media.show', $p->image) }}" alt="" class="h-full w-full object-cover">
                    @elseif ($p->flag)
                        <img src="https://flagcdn.com/w80/{{ $p->flag }}.png" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="text-[12px] font-bold">{{ Str::substr($p->name, 0, 2) }}</span>
                    @endif
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13.5px] font-bold tracking-[-0.01em] text-ink-900 dark:text-ink-50">{{ $p->name }}
                        @unless ($p->is_active)<span class="ml-1 rounded bg-ink-200 px-1.5 py-0.5 text-[10px] font-bold text-ink-600 dark:bg-ink-700 dark:text-ink-300">HIDDEN</span>@endunless
                    </p>
                    <p class="text-[11.5px] font-medium text-ink-500 dark:text-ink-400">
                        {{ $p->category_name ?? $p->category }} ·
                        <span class="font-mono font-semibold text-brand-600 dark:text-brand-400">{{ config('services.numbers.currency.symbol', '₦') }}{{ number_format($p->price) }}</span>
                        · {{ $p->sold_count }} {{ __('sold') }}
                    </p>
                </div>

                @php
                    // Three states: empty (unless pre-order), low, healthy.
                    $isEmpty = $p->stock === 0 && ! $p->pre_order;
                    $isLow   = $p->stock > 0 && $p->stock <= $lowStock;
                    [$badgeCls, $dotCls, $badgeText] = $isEmpty
                        ? ['bg-rose-100 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300', 'bg-rose-500', __('Out of stock')]
                        : ($isLow
                            ? ['bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300', 'bg-amber-500', $p->stock . ' ' . __('left')]
                            : ['bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300', 'bg-emerald-500', $p->stock . ' ' . __('in stock')]);
                @endphp
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11.5px] font-bold {{ $badgeCls }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $dotCls }} {{ $isLow || $isEmpty ? 'halo-live' : '' }}"></span>
                    {{ $badgeText }}
                </span>
                <button type="button" data-toggle="stock-{{ $p->id }}" class="btn-ghost h-9 text-[12px]">{{ __('Add stock') }}</button>
                <button type="button" data-items="{{ $p->id }}" data-toggle="items-{{ $p->id }}" class="btn-ghost h-9 text-[12px]">{{ __('Manage') }}</button>
                <button type="button" data-toggle="edit-{{ $p->id }}" class="btn-ghost h-9 text-[12px]">{{ __('Edit') }}</button>
            </div>

            {{-- Add stock --}}
            <div id="stock-{{ $p->id }}" data-cloak class="border-t border-ink-200 bg-ink-50 p-4 dark:border-ink-800 dark:bg-ink-900">
                <form method="POST" action="{{ route('admin.logs.stock', $p->id) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Paste accounts') }}</label>
                            <textarea name="paste" rows="5" placeholder="user:pass | https://facebook.com/username"
                                      class="mt-1 w-full rounded-xl border-ink-300 font-mono text-[12px] dark:border-ink-700 dark:bg-ink-950"></textarea>
                            <p class="mt-1 text-[10.5px] leading-relaxed text-ink-500 dark:text-ink-400">
                                {{ __('Add a preview link after a pipe: ') }}<code class="font-mono">user:pass | https://facebook.com/username</code>.
                                {{ __('Blank-line mode: put the URL on its own line in the block. CSV: second column is the URL.') }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('…or upload a file') }}</label>
                                <input type="file" name="file" accept=".txt,.csv"
                                       class="mt-1 w-full rounded-xl border border-ink-300 p-2 text-[12px] file:mr-2 file:rounded-md file:border-0 file:bg-ink-200 file:px-2 file:py-1 file:text-[11px] file:font-semibold dark:border-ink-700 dark:bg-ink-950 dark:file:bg-ink-800">
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Split each account by') }}</label>
                                <select name="delimiter" class="mt-1 w-full rounded-xl border-ink-300 text-[12px] dark:border-ink-700 dark:bg-ink-950">
                                    <option value="line">{{ __('New line (one per line)') }}</option>
                                    <option value="blank">{{ __('Blank line (multi-line blocks)') }}</option>
                                    <option value="comma">{{ __('CSV row') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary h-10 px-5 text-[12px]">{{ __('Add to stock') }}</button>
                </form>
            </div>

            {{-- Manage individual items (lazy-loaded) --}}
            <div id="items-{{ $p->id }}" data-cloak data-panel-for="{{ $p->id }}" class="border-t border-ink-200 bg-white p-4 dark:border-ink-800 dark:bg-ink-950">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-[12px] font-bold text-ink-600 dark:text-ink-300">{{ __('Stock items') }}</p>
                    <input type="search" data-filter="{{ $p->id }}" placeholder="{{ __('Filter…') }}"
                           class="h-8 w-40 rounded-lg border-ink-300 text-[12px] dark:border-ink-700 dark:bg-ink-900">
                </div>
                <div data-rows="{{ $p->id }}" class="scroll-y max-h-96 space-y-1.5 overflow-y-auto pr-1">
                    <p class="py-6 text-center text-[12px] text-ink-500">{{ __('Loading…') }}</p>
                </div>
            </div>

            {{-- Edit --}}
            <div id="edit-{{ $p->id }}" data-cloak class="border-t border-ink-200 bg-ink-50 p-4 dark:border-ink-800 dark:bg-ink-900">
                <form method="POST" action="{{ route('admin.logs.update', $p->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    @include('admin.logs._fields', ['p' => $p])
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" class="btn-primary h-10 px-5 text-[12px]">{{ __('Save') }}</button>
                        <button type="submit" formaction="{{ route('admin.logs.destroy', $p->id) }}" formmethod="POST"
                                onclick="return confirm('Hide this product and clear unsold stock?')"
                                class="btn-ghost h-10 px-5 text-[12px] text-brand-700 dark:text-brand-400">{{ __('Hide') }}</button>
                    </div>
                </form>
            </div>
        </section>
    @empty
        <div class="card px-6 py-16 text-center">
            <p class="text-[13px] font-semibold">{{ __('No products yet') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Create one above, then load it with stock.') }}</p>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const routes = {
        items:  @json(url('/admin/accounts')),        // + /{id}/items
        update: @json(url('/admin/accounts/item')),   // + /{id}
        remove: @json(url('/admin/accounts/item')),   // + /{id}/destroy
    };

    // Generic panel toggles.
    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = document.getElementById(btn.dataset.toggle);
            if (el) el.toggleAttribute('data-cloak');
        });
    });

    // Lazy-load a product's items the first time its Manage panel opens.
    const loaded = new Set();
    document.querySelectorAll('[data-items]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.items;
            if (loaded.has(id)) return;
            loaded.add(id);
            loadItems(id);
        });
    });

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str == null ? '' : str;
        return d.innerHTML;
    }

    async function loadItems(id) {
        const box = document.querySelector('[data-rows="' + id + '"]');
        try {
            const res = await fetch(routes.items + '/' + id + '/items', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const body = await res.json();
            renderItems(box, body.items || []);
        } catch (e) {
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-brand-700">Could not load items.</p>';
        }
    }

    function renderItems(box, items) {
        box.replaceChildren();
        if (!items.length) {
            box.innerHTML = '<p class="py-6 text-center text-[12px] text-ink-500">No items yet — add stock above.</p>';
            return;
        }

        items.forEach((it) => {
            const row = document.createElement('div');
            row.className = 'rounded-lg border border-ink-200 dark:border-ink-800';
            row.dataset.search = ((it.label || '') + ' ' + (it.preview || '') + ' ' + (it.excerpt || '')).toLowerCase();

            // ── Summary line ──
            const head = document.createElement('div');
            head.className = 'flex items-center gap-2 px-3 py-2';

            const status = document.createElement('span');
            status.className = 'shrink-0 rounded px-1.5 py-0.5 text-[10px] font-bold ' +
                (it.sold ? 'bg-ink-200 text-ink-600 dark:bg-ink-700 dark:text-ink-300'
                         : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300');
            status.textContent = it.sold ? 'SOLD' : 'LIVE';

            const label = document.createElement('span');
            label.className = 'min-w-0 flex-1 truncate text-[12.5px]';
            label.innerHTML = '<span class="font-semibold">' + esc(it.label || 'Account') + '</span>' +
                (it.preview ? ' <span class="text-ink-600">·</span> <span class="text-[11px] text-ink-500">' + esc(it.preview.replace(/^https?:\/\//, '')) + '</span>' : '');

            head.append(status, label);

            if (!it.sold) {
                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'shrink-0 rounded-md px-2 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10';
                edit.textContent = 'Edit';
                const form = buildForm(it);
                edit.addEventListener('click', () => form.toggleAttribute('data-cloak'));
                head.append(edit);
                row.append(head, form);
            } else {
                row.append(head);
            }

            box.append(row);
        });
    }

    function buildForm(it) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = routes.update + '/' + it.id;
        form.setAttribute('data-cloak', '');
        form.className = 'space-y-2 border-t border-ink-100 p-3 dark:border-ink-800/60';
        form.innerHTML =
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="PUT">' +
            '<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">' +
              '<input name="label" value="' + esc(it.label || '') + '" placeholder="Label / username" class="h-9 rounded-lg border-ink-300 text-[12px] dark:border-ink-700 dark:bg-ink-950">' +
              '<input name="preview_url" value="' + esc(it.preview || '') + '" placeholder="https://facebook.com/username" class="h-9 rounded-lg border-ink-300 text-[12px] dark:border-ink-700 dark:bg-ink-950">' +
            '</div>' +
            '<textarea name="content" rows="3" required placeholder="Full account content" class="w-full rounded-lg border-ink-300 font-mono text-[12px] dark:border-ink-700 dark:bg-ink-950">' + esc(it.content || '') + '</textarea>' +
            '<div class="flex gap-2">' +
              '<button type="submit" class="h-9 rounded-lg bg-brand-600 px-4 text-[12px] font-semibold text-white hover:bg-brand-700">Save</button>' +
              '<button type="submit" formaction="' + routes.remove + '/' + it.id + '/destroy" formmethod="POST" onclick="return confirm(\'Remove this account from stock?\')" class="h-9 rounded-lg border border-ink-300 px-4 text-[12px] font-semibold text-brand-700 hover:bg-brand-50 dark:border-ink-700 dark:text-brand-400">Delete</button>' +
            '</div>';
        // The delete button posts, not puts — strip the _method for that submit.
        form.querySelector('[formaction]').addEventListener('click', () => {
            form.querySelector('[name="_method"]').value = 'POST';
        });
        // Load the full content lazily into the textarea when the form opens
        // (the list only carried an excerpt). We fetch on first expand.
        return form;
    }

    // Client-side filter within a product's item list.
    document.querySelectorAll('[data-filter]').forEach((input) => {
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            const box = document.querySelector('[data-rows="' + input.dataset.filter + '"]');
            box.querySelectorAll('[data-search]').forEach((row) => {
                row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
            });
        });
    });
})();
</script>
@endpush