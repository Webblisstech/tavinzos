@extends('layouts.admin')

@section('title', __('Transactions'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Wallet transactions') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Every credit and debit across all customers.') }}</p>
    </div>
@endsection

@section('content')

{{-- Stats --}}
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @php
        $cards = [
            ['label' => __('Top-ups (money in)'), 'value' => $stats['in']['formatted'],   'tone' => 'emerald'],
            ['label' => __('Revenue (net spend)'),'value' => $stats['out']['formatted'],   'tone' => 'brand'],
            ['label' => __('Top-ups today'),      'value' => $stats['today']['formatted'], 'tone' => 'emerald'],
            ['label' => __('Held in wallets'),    'value' => $stats['held']['formatted'],  'tone' => 'ink'],
            ['label' => __('Total top-ups'),      'value' => $stats['topups']['formatted'],'tone' => 'emerald'],
            ['label' => __('Total refunds'),      'value' => $stats['refunds']['formatted'],'tone' => 'amber'],
            ['label' => __('Transactions'),       'value' => number_format($stats['count']),'tone' => 'ink'],
            ['label' => __('Pending'),            'value' => number_format($stats['pending']),'tone' => $stats['pending'] ? 'amber' : 'ink'],
        ];
        $toneMap = [
            'emerald' => 'text-emerald-600 dark:text-emerald-400',
            'brand'   => 'text-brand-600 dark:text-brand-400',
            'amber'   => 'text-amber-600 dark:text-amber-400',
            'ink'     => 'text-ink-900 dark:text-ink-50',
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="card p-4">
            <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ $c['label'] }}</p>
            <p class="mt-1.5 font-mono text-[18px] font-bold tracking-[-0.02em] {{ $toneMap[$c['tone']] }}">{{ $c['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="mb-5 flex flex-col gap-2.5 lg:flex-row lg:items-center">
    <div class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 shadow-sm shadow-ink-900/5 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="17" height="17" class="h-[17px] w-[17px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="{{ __('Search ref, note, name or email…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </div>
    <select name="type" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="">{{ __('All types') }}</option>
        @foreach (['topup' => __('Top-up'), 'purchase' => __('Purchase'), 'refund' => __('Refund'), 'reversal' => __('Reversal'), 'adjustment' => __('Adjustment')] as $k => $label)
            <option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="">{{ __('Any status') }}</option>
        <option value="settled" @selected($filters['status'] === 'settled')>{{ __('Settled') }}</option>
        <option value="pending" @selected($filters['status'] === 'pending')>{{ __('Pending') }}</option>
        <option value="failed"  @selected($filters['status'] === 'failed')>{{ __('Failed') }}</option>
    </select>
    <button type="submit" class="btn-primary h-11 px-5 text-[13px]">{{ __('Apply') }}</button>
    @if ($filters['q'] || $filters['type'] || $filters['status'])
        <a href="{{ route('admin.transactions.index') }}" class="btn-ghost h-11 px-4 text-[13px]">{{ __('Reset') }}</a>
    @endif
</form>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="scroll-y overflow-x-auto">
        <table class="w-full min-w-[860px] text-left">
            <thead class="border-b border-ink-100 bg-ink-50/60 text-[11px] font-bold uppercase tracking-[0.06em] text-ink-500 dark:border-ink-800 dark:bg-ink-900/40 dark:text-ink-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                    <th class="px-4 py-3">{{ __('Customer') }}</th>
                    <th class="px-4 py-3">{{ __('Note') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Before') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('After') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60">
                @forelse ($rows as $t)
                    @php
                        $credit = $t['amount'] >= 0;
                        $pending = $t['status'] === 'pending';
                        $typeTone = match ($t['type']) {
                            'topup'      => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                            'refund'     => 'bg-sky-100 text-sky-700 dark:bg-sky-400/15 dark:text-sky-300',
                            'reversal'   => 'bg-violet-100 text-violet-700 dark:bg-violet-400/15 dark:text-violet-300',
                            'adjustment' => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                            default      => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                        };
                    @endphp
                    <tr class="cursor-pointer transition hover:bg-ink-50/60 dark:hover:bg-ink-900/40 {{ $pending ? 'opacity-60' : '' }}" onclick="window.location='{{ route('admin.transactions.show', $t['ref']) }}'">
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-md px-2 py-0.5 text-[11px] font-bold capitalize {{ $typeTone }}">{{ $t['type'] }}</span>
                            @if ($pending)<span class="ml-1 text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ __('PENDING') }}</span>@endif
                            <p class="mt-1 font-mono text-[10.5px] text-ink-400">{{ $t['ref'] }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-[12.5px] font-semibold">{{ $t['user'] }}</p>
                            <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ $t['email'] }}</p>
                        </td>
                        <td class="max-w-[220px] px-4 py-3">
                            <p class="truncate text-[12px] text-ink-600 dark:text-ink-300">{{ $t['note'] ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[13px] font-bold {{ $credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-800 dark:text-ink-200' }}">
                            {{ $credit ? '+' : '' }}{{ $t['money']['formatted'] }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] text-ink-400">{{ $t['before']['formatted'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] font-semibold text-ink-600 dark:text-ink-300">{{ $t['balance']['formatted'] }}</td>
                        <td class="px-4 py-3 text-right text-[11.5px] text-ink-500 dark:text-ink-400">{{ \Illuminate\Support\Carbon::parse($t['at'])->format('j M, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-16 text-center">
                        <p class="text-[13px] font-semibold">{{ __('No transactions match') }}</p>
                        <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Try widening your filters.') }}</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($rows->count() >= 400)
    <p class="mt-3 text-center text-[12px] text-ink-500 dark:text-ink-400">{{ __('Showing the most recent 400. Narrow with filters for older records.') }}</p>
@endif
@endsection