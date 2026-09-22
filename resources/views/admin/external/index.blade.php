@extends('layouts.admin')

@section('title', __('External catalog'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('External catalog') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Set an exact price per product, or hide it. Blank price uses the auto rate + markup.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
@endif

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
    <form method="GET" class="flex h-11 flex-1 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 dark:border-ink-800 dark:bg-ink-950">
        <svg width="17" height="17" class="h-[17px] w-[17px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $q }}" type="search" placeholder="{{ __('Search products…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] font-medium focus:ring-0 dark:text-ink-50">
    </form>
    <span class="text-[12px] font-semibold text-ink-500 dark:text-ink-400">{{ number_format($count) }} {{ __('products') }} · {{ __('rate') }} {{ number_format($rate) }}</span>
</div>

<div class="card overflow-hidden">
    <div class="scroll-y overflow-x-auto">
        <table class="w-full min-w-[820px] text-left">
            <thead class="border-b border-ink-100 bg-ink-50/60 text-[11px] font-bold uppercase tracking-[0.06em] text-ink-500 dark:border-ink-800 dark:bg-ink-900/40 dark:text-ink-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Product') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Cost') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Auto') }}</th>
                    <th class="px-4 py-3">{{ __('Your price') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Hide') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60">
                @forelse ($products as $p)
                    <tr class="{{ $p['hidden'] ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <p class="max-w-[360px] truncate text-[12.5px] font-semibold" title="{{ $p['name'] }}">{{ $p['name'] }}</p>
                            <p class="text-[10.5px] text-ink-400">{{ $p['category'] }} · {{ $p['stock'] }} {{ __('in stock') }}</p>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] text-ink-400">${{ number_format($p['cost'], 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-[12px] text-ink-500 dark:text-ink-400">{{ $symbol }}{{ number_format($p['auto']) }}</td>
                        <form method="POST" action="{{ route('admin.external.save', $p['id']) }}" class="contents">
                            @csrf
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <span class="text-[12px] font-mono text-ink-400">{{ $symbol }}</span>
                                    <input name="price" type="number" step="any" min="0" value="{{ $p['override'] }}"
                                           placeholder="{{ __('auto') }}"
                                           class="h-9 w-28 rounded-lg border-ink-300 font-mono text-[12.5px] dark:border-ink-700 dark:bg-ink-900">
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="hidden" value="1" @checked($p['hidden']) class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-[11.5px] font-bold text-white transition hover:bg-brand-700">{{ __('Save') }}</button>
                            </td>
                        </form>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center">
                        <p class="text-[13px] font-semibold">{{ __('No products') }}</p>
                        <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Check the API key and connection.') }}</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection