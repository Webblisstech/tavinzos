@extends('layouts.admin')

@section('title', __('Settings'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Settings') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Pricing, currency, wallet limits and site options.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
        <svg width="18" height="18" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    {{-- Group tabs --}}
    <div class="mb-5 flex flex-wrap gap-1.5 rounded-2xl border border-ink-200 bg-ink-50 p-1 dark:border-ink-800 dark:bg-ink-900/60">
        @foreach (array_keys($groups) as $i => $group)
            <button type="button" data-tab="{{ Str::slug($group) }}"
                    class="grp-tab h-9 rounded-xl px-3.5 text-[12.5px] font-bold transition">{{ $group }}</button>
        @endforeach
    </div>

    @foreach ($groups as $group => $fields)
        <section data-panel="{{ Str::slug($group) }}" class="card mb-4 p-6 @if (! $loop->first) hidden @endif">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ $group }}</h3>

            @if ($group === 'Payments')
                {{-- Gateway connection — configured in .env, shown read-only here.
                     The secret is never displayed; only whether it's set. --}}
                <div class="mt-4 rounded-2xl border border-ink-200 bg-ink-50/60 p-4 dark:border-ink-800 dark:bg-ink-900/40">
                    <div class="flex items-center justify-between">
                        <p class="text-[12.5px] font-bold">{{ __('WebBlissPay connection') }}</p>
                        @if ($gateway['has_secret'] && $gateway['base'])
                            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('Connected') }}</span>
                        @else
                            <span class="rounded-md bg-brand-100 px-2 py-0.5 text-[11px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ __('Not configured') }}</span>
                        @endif
                    </div>
                    <dl class="mt-3 space-y-2 text-[12px]">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-500 dark:text-ink-400">{{ __('API base') }}</dt>
                            <dd class="truncate font-mono text-ink-700 dark:text-ink-200">{{ $gateway['base'] ?: '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-ink-500 dark:text-ink-400">{{ __('Secret key') }}</dt>
                            <dd class="font-mono text-ink-700 dark:text-ink-200">{{ $gateway['has_secret'] ? '•••• set in .env' : __('missing') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="shrink-0 text-ink-500 dark:text-ink-400">{{ __('Webhook URL') }}</dt>
                            <dd class="break-all text-right font-mono text-[11px] text-ink-700 dark:text-ink-200">{{ $gateway['webhook'] }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-[11px] text-ink-500 dark:text-ink-400">{{ __('Keys live in your .env for security. Set the webhook URL above in your WebBlissPay dashboard.') }}</p>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach ($fields as $f)
                    @php $name = str_replace('.', '__', $f['key']); @endphp
                    <div class="@if (Str::startsWith($f['input'], 'toggle')) sm:col-span-2 @endif">
                        @if ($f['input'] === 'toggle')
                            <label class="flex items-start justify-between gap-4 rounded-xl border border-ink-200 p-3.5 dark:border-ink-800">
                                <span>
                                    <span class="block text-[13px] font-semibold">{{ $f['label'] }}</span>
                                    <span class="mt-0.5 block text-[11.5px] text-ink-500 dark:text-ink-400">{{ $f['hint'] }}</span>
                                </span>
                                <span class="relative mt-0.5 inline-flex shrink-0">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked($f['value']) class="peer sr-only">
                                    <span class="h-6 w-11 rounded-full bg-ink-200 transition peer-checked:bg-brand-600 dark:bg-ink-700"></span>
                                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                                </span>
                            </label>
                        @else
                            <label for="{{ $name }}" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ $f['label'] }}</label>
                            @if (Str::startsWith($f['input'], 'select:'))
                                <select id="{{ $name }}" name="{{ $name }}" class="mt-1.5 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                                    @foreach (explode(',', Str::after($f['input'], 'select:')) as $opt)
                                        <option value="{{ $opt }}" @selected($f['value'] === $opt)>{{ ucfirst($opt) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($f['input'] === 'color')
                                @php $cval = preg_match('/^#?[0-9a-fA-F]{6}$/', (string) $f['value']) ? '#'.ltrim((string)$f['value'],'#') : '#D91F2C'; @endphp
                                <div class="mt-1.5 flex items-center gap-2" data-color-group>
                                    <input type="color" value="{{ $cval }}" data-color-swatch
                                           class="h-11 w-14 shrink-0 cursor-pointer rounded-xl border border-ink-300 bg-transparent p-1 dark:border-ink-700">
                                    <input id="{{ $name }}" name="{{ $name }}" value="{{ $cval }}" data-color-hex maxlength="7"
                                           class="h-11 w-full rounded-xl border-ink-300 font-mono text-[13.5px] uppercase dark:border-ink-700 dark:bg-ink-900">
                                </div>
                            @else
                                <input id="{{ $name }}" name="{{ $name }}"
                                       type="{{ $f['input'] === 'number' ? 'number' : 'text' }}"
                                       @if ($f['input'] === 'number') step="any" @endif
                                       value="{{ $f['value'] }}"
                                       class="mt-1.5 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900 {{ $f['input'] === 'number' ? 'font-mono' : '' }}">
                            @endif
                            <p class="mt-1 text-[11px] text-ink-500 dark:text-ink-400">{{ $f['hint'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="sticky bottom-0 -mx-4 mt-4 border-t border-ink-200 bg-white/90 px-4 py-4 backdrop-blur-md sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 dark:border-ink-800 dark:bg-ink-950/90">
        <div class="mx-auto flex max-w-[1400px] items-center justify-between">
            <p class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Changes take effect immediately.') }}</p>
            <button type="submit" class="btn-primary h-11 px-8 text-[13px]">{{ __('Save settings') }}</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        var tabs = document.querySelectorAll('.grp-tab');
        var panels = document.querySelectorAll('[data-panel]');
        function activate(slug) {
            tabs.forEach(function (t) {
                var on = t.dataset.tab === slug;
                t.className = 'grp-tab h-9 rounded-xl px-3.5 text-[12.5px] font-bold transition ' +
                    (on ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-ink-50'
                        : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100');
            });
            panels.forEach(function (p) { p.classList.toggle('hidden', p.dataset.panel !== slug); });
        }
        tabs.forEach(function (t) { t.addEventListener('click', function () { activate(t.dataset.tab); }); });
        if (tabs.length) activate(tabs[0].dataset.tab);

        // Color pickers: keep the swatch and the hex field in sync.
        document.querySelectorAll('[data-color-group]').forEach(function (g) {
            var sw = g.querySelector('[data-color-swatch]');
            var hex = g.querySelector('[data-color-hex]');
            sw.addEventListener('input', function () { hex.value = sw.value.toUpperCase(); });
            hex.addEventListener('input', function () {
                var v = hex.value.trim();
                if (/^#?[0-9a-fA-F]{6}$/.test(v)) sw.value = '#' + v.replace('#', '');
            });
        });
    })();
</script>
@endpush
@endsection