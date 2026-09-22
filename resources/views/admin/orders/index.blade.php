@extends('layouts.admin')

@section('title', __('Orders'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Orders') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Every order across numbers and accounts.') }}</p>
    </div>
@endsection

@section('content')

{{-- Stat cards --}}
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    @php
        $cards = [
            ['label' => __('Total orders'),   'value' => number_format($stats['orders']),   'tone' => 'ink'],
            ['label' => __('Revenue'),        'value' => $stats['revenue']['formatted'],    'tone' => 'brand'],
            ['label' => __('Earned today'),   'value' => $stats['today']['formatted'],      'tone' => 'emerald'],
            ['label' => __('Refunded'),       'value' => $stats['refunded']['formatted'],   'tone' => 'amber'],
            ['label' => __('Numbers'),        'value' => number_format($stats['numbers']),  'tone' => 'ink'],
            ['label' => __('Accounts'),       'value' => number_format($stats['accounts']), 'tone' => 'ink'],
            ['label' => __('Waiting'),        'value' => number_format($stats['waiting']),  'tone' => $stats['waiting'] ? 'amber' : 'ink'],
            ['label' => __('Customers'),      'value' => number_format($stats['customers']),'tone' => 'ink'],
        ];
        $toneMap = [
            'brand'   => 'text-brand-600 dark:text-brand-400',
            'emerald' => 'text-emerald-600 dark:text-emerald-400',
            'amber'   => 'text-amber-600 dark:text-amber-400',
            'ink'     => 'text-ink-900 dark:text-ink-50',
        ];
    @endphp
    @foreach ($cards as $c)
        <div class="card p-4">
            <p class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ $c['label'] }}</p>
            <p class="mt-1.5 font-mono text-[19px] font-bold tracking-[-0.02em] {{ $toneMap[$c['tone']] }}">{{ $c['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" class="mb-5 flex flex-col gap-2.5 lg:flex-row lg:items-center">
    <div class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 shadow-sm shadow-ink-900/5 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-800 dark:bg-ink-950">
        <svg width="17" height="17" class="h-[17px] w-[17px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="{{ __('Search ref, service, phone or email…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] font-medium text-ink-900 placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </div>
    <select name="type" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="all"      @selected($filters['type'] === 'all')>{{ __('All types') }} ({{ $counts['all'] }})</option>
        <option value="numbers"  @selected($filters['type'] === 'numbers')>{{ __('Numbers') }} ({{ $counts['numbers'] }})</option>
        <option value="accounts" @selected($filters['type'] === 'accounts')>{{ __('Accounts') }} ({{ $counts['accounts'] }})</option>
    </select>
    <select name="status" class="h-11 rounded-xl border-ink-200 text-[13px] font-medium dark:border-ink-800 dark:bg-ink-950">
        <option value="">{{ __('Any status') }}</option>
        <option value="waiting"   @selected($filters['status'] === 'waiting')>{{ __('Waiting') }}</option>
        <option value="completed" @selected($filters['status'] === 'completed')>{{ __('Completed') }}</option>
        <option value="delivered" @selected($filters['status'] === 'delivered')>{{ __('Delivered') }}</option>
        <option value="cancelled" @selected($filters['status'] === 'cancelled')>{{ __('Cancelled') }}</option>
    </select>
    <button type="submit" class="btn-primary h-11 px-5 text-[13px]">{{ __('Apply') }}</button>
    @if ($filters['q'] || $filters['type'] !== 'all' || $filters['status'])
        <a href="{{ route('admin.orders.index') }}" class="btn-ghost h-11 px-4 text-[13px]">{{ __('Reset') }}</a>
    @endif
</form>

{{-- Orders table --}}
<div class="card overflow-hidden">
    <div class="scroll-y overflow-x-auto">
        <table class="w-full min-w-[720px] text-left">
            <thead class="border-b border-ink-100 bg-ink-50/60 text-[11px] font-bold uppercase tracking-[0.06em] text-ink-500 dark:border-ink-800 dark:bg-ink-900/40 dark:text-ink-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Order') }}</th>
                    <th class="px-4 py-3">{{ __('Customer') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60">
                @forelse ($rows as $r)
                    @php
                        $tone = match ($r['status']) {
                            'Completed', 'Delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                            'Waiting', 'Pending'     => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                            default                  => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                        };
                    @endphp
                    <tr class="cursor-pointer transition hover:bg-ink-50/60 dark:hover:bg-ink-900/40" onclick="window.location='{{ route('admin.orders.show', $r['ref']) }}'">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                                    @if ($r['kind'] === 'account')
                                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/></svg>
                                    @else
                                        <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-semibold text-ink-900 dark:text-ink-50">{{ $r['title'] }}</p>
                                    <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ $r['sub'] }} · <span class="font-mono">{{ $r['ref'] }}</span></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-[12.5px] font-semibold">{{ $r['user'] }}</p>
                            <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ $r['email'] }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-md px-2 py-0.5 text-[11px] font-bold {{ $tone }}">{{ $r['status'] }}</span>
                            @if (($r['extra'] ?? '') !== '')
                                <span class="ml-1 font-mono text-[11px] font-bold text-brand-600 dark:text-brand-400">{{ $r['extra'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[13px] font-bold">{{ $r['amount']['formatted'] }}</td>
                        <td class="px-4 py-3 text-right text-[11.5px] text-ink-500 dark:text-ink-400">{{ \Illuminate\Support\Carbon::parse($r['at'])->format('j M, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-16 text-center">
                        <p class="text-[13px] font-semibold">{{ __('No orders match') }}</p>
                        <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Try widening your filters.') }}</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($rows->count() >= 300)
    <p class="mt-3 text-center text-[12px] text-ink-500 dark:text-ink-400">{{ __('Showing the most recent 300. Narrow with filters to see older orders.') }}</p>
@endif
@endsection