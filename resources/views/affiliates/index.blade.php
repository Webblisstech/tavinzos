@extends('layouts.app')

@section('title', __('Affiliates'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Affiliate dashboard') }}</h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Track your referrals, conversions, and earnings.') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <span class="rounded-lg bg-amber-100 px-3 py-1.5 text-[12px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ Str::upper($tier['name']) }} {{ __('Tier') }}</span>
            <span class="rounded-lg bg-emerald-100 px-3 py-1.5 text-[12px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ number_format($tier['rate'], 2) }}% {{ __('Commission') }}</span>
        </div>
    </div>
@endsection

@section('content')

{{-- Stat cards --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @php
        $cards = [
            ['label' => __('Total Visits'),   'value' => number_format($stats['visits']),       'hint' => $stats['unique'] . ' ' . __('unique'), 'icon' => 'eye',   'tone' => 'sky'],
            ['label' => __('Conversions'),    'value' => number_format($stats['conversions']),  'hint' => $stats['rate'] . '% ' . __('rate'),    'icon' => 'check', 'tone' => 'violet'],
            ['label' => __('Referred Users'), 'value' => number_format($stats['referred']),     'hint' => $stats['ltv']['formatted'] . ' ' . __('LTV'), 'icon' => 'users', 'tone' => 'indigo'],
            ['label' => __('Total Earned'),   'value' => $stats['earned']['formatted'],         'hint' => __('all time'), 'icon' => 'cash', 'tone' => 'emerald'],
        ];
        $tones = [
            'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-400/15 dark:text-sky-400',
            'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-400/15 dark:text-violet-400',
            'indigo'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-400/15 dark:text-indigo-400',
            'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400',
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.07em] text-ink-500 dark:text-ink-400">{{ $c['label'] }}</p>
                    <p class="mt-2 font-mono text-[24px] font-bold tracking-[-0.02em] text-ink-900 dark:text-ink-50">{{ $c['value'] }}</p>
                    <p class="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{{ $c['hint'] }}</p>
                </div>
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $tones[$c['tone']] }}">
                    @switch($c['icon'])
                        @case('eye')   <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg> @break
                        @case('check') <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg> @break
                        @case('users') <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M18.5 20a5.5 5.5 0 0 0-3-4.9"/></svg> @break
                        @default       <svg width="17" height="17" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
                    @endswitch
                </span>
            </div>
        </div>
    @endforeach
</div>

{{-- Period performance --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    @php
        $periods = [
            ['label' => __("Today's Performance"), 'data' => $today, 'accent' => true],
            ['label' => __('Last 7 Days'),  'data' => $last7,  'accent' => false],
            ['label' => __('Last 30 Days'), 'data' => $last30, 'accent' => false],
        ];
    @endphp
    @foreach ($periods as $p)
        <div class="card p-5 {{ $p['accent'] ? 'bg-brand-600 text-white' : '' }}">
            <p class="text-[13px] font-bold {{ $p['accent'] ? 'text-white' : '' }}">{{ $p['label'] }}</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <div>
                    <p class="font-mono text-[22px] font-bold {{ $p['accent'] ? 'text-white' : 'text-ink-900 dark:text-ink-50' }}">{{ $p['data']['visits'] }}</p>
                    <p class="text-[11px] {{ $p['accent'] ? 'text-white/70' : 'text-ink-500 dark:text-ink-400' }}">{{ __('Visits') }}</p>
                </div>
                <div>
                    <p class="font-mono text-[22px] font-bold {{ $p['accent'] ? 'text-white' : 'text-ink-900 dark:text-ink-50' }}">{{ $p['data']['conversions'] }}</p>
                    <p class="text-[11px] {{ $p['accent'] ? 'text-white/70' : 'text-ink-500 dark:text-ink-400' }}">{{ __('Conversions') }}</p>
                </div>
                <div>
                    <p class="font-mono text-[22px] font-bold {{ $p['accent'] ? 'text-white' : 'text-emerald-600 dark:text-emerald-400' }}">{{ $p['data']['earned']['formatted'] }}</p>
                    <p class="text-[11px] {{ $p['accent'] ? 'text-white/70' : 'text-ink-500 dark:text-ink-400' }}">{{ __('Earned') }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Referral link --}}
<section class="card mt-4 p-5">
    <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Your referral link') }}</h3>
    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <input id="ref-link" type="text" readonly value="{{ $refLink }}"
               class="h-11 flex-1 rounded-xl border-ink-200 bg-ink-50 font-mono text-[13px] text-ink-700 dark:border-ink-800 dark:bg-ink-900 dark:text-ink-200">
        <button type="button" id="copy-ref" class="btn-primary h-11 px-5 text-[13px]">
            <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5"/></svg>
            {{ __('Copy link') }}
        </button>
    </div>
    <p class="mt-2.5 text-[12px] text-ink-500 dark:text-ink-400">
        {{ __('Share this link. You earn') }} <span class="font-semibold text-brand-600 dark:text-brand-400">{{ number_format($tier['rate'], 2) }}%</span>
        {{ __('commission on every deposit made by users who sign up through it.') }}
        @if ($nextTier)
            <br>{{ __('Earn') }} <span class="font-semibold">{{ $nextTier['remaining']['formatted'] }}</span> {{ __('more to reach') }} <span class="font-semibold">{{ $nextTier['name'] }}</span> ({{ $nextTier['rate'] }}%).
        @endif
    </p>
</section>

{{-- Recent referrals + conversions --}}
<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <section class="card p-5">
        <h3 class="mb-4 text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent referrals') }}</h3>
        @forelse ($referrals as $r)
            <div class="flex items-center gap-3 border-t border-ink-100 py-2.5 first:border-t-0 dark:border-ink-800/60">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink-100 text-[11px] font-bold text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ Str::substr($r['name'], 0, 1) }}</span>
                <p class="flex-1 truncate text-[13px] font-semibold">{{ $r['name'] }}</p>
                <p class="shrink-0 text-[11px] text-ink-400">{{ \Illuminate\Support\Carbon::parse($r['at'])->diffForHumans() }}</p>
            </div>
        @empty
            <div class="py-10 text-center">
                <span class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="20" height="20" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6"/></svg></span>
                <p class="text-[13px] font-semibold">{{ __('No referrals yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Share your link to get started.') }}</p>
            </div>
        @endforelse
    </section>

    <section class="card p-5">
        <h3 class="mb-4 text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent conversions') }}</h3>
        @forelse ($recent as $c)
            <div class="flex items-center gap-3 border-t border-ink-100 py-2.5 first:border-t-0 dark:border-ink-800/60">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400"><svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[12.5px] font-semibold">{{ $c['user'] }}</p>
                    <p class="truncate text-[10.5px] text-ink-400">{{ \Illuminate\Support\Carbon::parse($c['at'])->diffForHumans() }} · {{ $c['amount']['formatted'] }} {{ $c['type'] }}</p>
                </div>
                <span class="shrink-0 font-mono text-[13px] font-bold text-emerald-600 dark:text-emerald-400">+{{ $c['commission']['formatted'] }}</span>
            </div>
        @empty
            <div class="py-10 text-center">
                <span class="mx-auto mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="20" height="20" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19V9m5 10V5m5 14v-7m5 7V11"/></svg></span>
                <p class="text-[13px] font-semibold">{{ __('No conversions yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Start sharing your link!') }}</p>
            </div>
        @endforelse
    </section>
</div>

<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl bg-ink-900 px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg dark:bg-ink-100 dark:text-ink-900" data-cloak></div>
@endsection

@push('scripts')
<script>
(function () {
    const btn = document.getElementById('copy-ref');
    const link = document.getElementById('ref-link');
    const toast = document.getElementById('toast');
    let t;
    btn && btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(link.value);
        } catch (e) {
            link.select(); document.execCommand('copy');
        }
        toast.textContent = 'Link copied';
        toast.removeAttribute('data-cloak');
        clearTimeout(t);
        t = setTimeout(() => toast.setAttribute('data-cloak', ''), 2200);
    });
})();
</script>
@endpush