@extends('layouts.app')

@section('title', __('Transactions'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Transactions') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Every credit and debit on your wallet.') }}</p>
    </div>
@endsection

@section('content')

{{-- Summary --}}
<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div class="card p-4">
        <p class="text-[11.5px] font-medium text-ink-500 dark:text-ink-400">{{ __('Balance') }}</p>
        <p class="mt-1 font-mono text-[20px] font-bold tracking-[-0.02em]">{{ $balance['formatted'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-[11.5px] font-medium text-ink-500 dark:text-ink-400">{{ __('Total in') }}</p>
        <p class="mt-1 font-mono text-[20px] font-bold tracking-[-0.02em] text-emerald-600 dark:text-emerald-400">{{ $in['formatted'] }}</p>
    </div>
    <div class="card p-4">
        <p class="text-[11.5px] font-medium text-ink-500 dark:text-ink-400">{{ __('Total out') }}</p>
        <p class="mt-1 font-mono text-[20px] font-bold tracking-[-0.02em]">{{ $out['formatted'] }}</p>
    </div>
</div>

{{-- Filter tabs --}}
<div class="mt-4 flex gap-1.5 overflow-x-auto rounded-2xl border border-ink-200 bg-ink-50 p-1 dark:border-ink-800 dark:bg-ink-900/60">
    @foreach (['all' => __('All'), 'in' => __('Money in'), 'out' => __('Money out'), 'topup' => __('Top-ups'), 'purchase' => __('Purchases'), 'refund' => __('Refunds')] as $key => $label)
        <a href="{{ route('transactions.index', ['type' => $key]) }}"
           class="shrink-0 rounded-xl px-3.5 py-2 text-[12.5px] font-bold transition {{ $filter === $key ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-ink-50' : 'text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- List --}}
<section class="card mt-4 overflow-hidden">
    @forelse ($tx as $t)
        @php
            $credit = $t['amount'] >= 0;
            $meta = match ($t['type']) {
                'topup'    => [__('Wallet funded'), 'plus', 'emerald'],
                'purchase' => [__('Purchase'), 'cart', 'ink'],
                'refund'   => [__('Refund'), 'undo', 'sky'],
                'reversal' => [__('Reversal'), 'undo', 'violet'],
                default    => [__('Adjustment'), 'dot', 'amber'],
            };
            $tone = ['emerald'=>'bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400','ink'=>'bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400','sky'=>'bg-sky-50 text-sky-600 dark:bg-sky-400/10 dark:text-sky-400','violet'=>'bg-violet-50 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400','amber'=>'bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400'][$meta[2]];
        @endphp
        <div class="flex items-center gap-3 border-b border-ink-100 px-4 py-3.5 last:border-0 dark:border-ink-800/60">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $tone }}">
                @switch($meta[1])
                    @case('plus') <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> @break
                    @case('cart') <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg> @break
                    @case('undo') <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg> @break
                    @default <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                @endswitch
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="text-[13px] font-semibold">{{ $meta[0] }}</p>
                    @if ($t['status'] !== 'settled')
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ ucfirst($t['status']) }}</span>
                    @endif
                </div>
                <p class="truncate text-[11.5px] text-ink-500 dark:text-ink-400">{{ $t['note'] ?: $t['reference'] }} · {{ \Illuminate\Support\Carbon::parse($t['at'])->diffForHumans() }}</p>
            </div>
            <div class="shrink-0 text-right">
                <p class="font-mono text-[13.5px] font-bold {{ $credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-800 dark:text-ink-200' }}">{{ $t['formatted'] }}</p>
                <p class="font-mono text-[10.5px] text-ink-400">{{ __('bal') }} {{ $t['balance_after'] }}</p>
            </div>
        </div>
    @empty
        <div class="py-16 text-center">
            <p class="text-[13px] font-semibold">{{ __('No transactions') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your wallet activity will show here.') }}</p>
        </div>
    @endforelse
</section>

{{-- Pagination --}}
@if ($paginator->hasPages())
    <div class="mt-4">{{ $paginator->links() }}</div>
@endif
@endsection