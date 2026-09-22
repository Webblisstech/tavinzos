@extends('layouts.admin')

@section('title', __('External catalog'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('External catalog') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Set an exact price per product, or hide it. A blank price uses the auto rate + markup.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 flex items-center gap-2.5 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-3.5 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
        <svg width="16" height="16" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
        {{ session('status') }}
    </div>
@endif

{{-- Toolbar --}}
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex h-10 w-full items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 shadow-sm shadow-ink-900/5 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 sm:max-w-sm dark:border-ink-800 dark:bg-ink-950">
        <svg width="16" height="16" class="h-4 w-4 shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $q }}" type="search" placeholder="{{ __('Search products…') }}"
               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13px] font-medium placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
    </form>
    <div class="flex items-center gap-3 text-[11.5px] font-semibold text-ink-500 dark:text-ink-400">
        <span class="rounded-lg bg-ink-100 px-2.5 py-1 dark:bg-ink-800">{{ number_format($count) }} {{ __('products') }}</span>
        <span class="rounded-lg bg-ink-100 px-2.5 py-1 dark:bg-ink-800">{{ __('rate') }} {{ $symbol }}{{ number_format($rate) }}/$</span>
    </div>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="scroll-y overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-[12.5px]">
            <thead class="border-b border-ink-100 bg-ink-50/70 text-[10.5px] font-bold uppercase tracking-[0.07em] text-ink-500 dark:border-ink-800 dark:bg-ink-900/50 dark:text-ink-400">
                <tr>
                    <th class="px-4 py-2.5 font-bold">{{ __('Product') }}</th>
                    <th class="px-3 py-2.5 text-right font-bold">{{ __('Cost') }}</th>
                    <th class="px-3 py-2.5 text-right font-bold">{{ __('Auto price') }}</th>
                    <th class="px-3 py-2.5 font-bold">{{ __('Your price') }}</th>
                    <th class="px-3 py-2.5 text-center font-bold">{{ __('Hidden') }}</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800/60">
                @forelse ($products as $p)
                    <tr class="transition hover:bg-ink-50/50 dark:hover:bg-ink-900/30 {{ $p['hidden'] ? 'opacity-55' : '' }}">
                        {{-- Product --}}
                        <td class="px-4 py-2.5">
                            <p class="max-w-[380px] truncate text-[12px] font-semibold text-ink-900 dark:text-ink-50" title="{{ $p['name'] }}">{{ $p['name'] }}</p>
                            <div class="mt-0.5 flex items-center gap-1.5 text-[10px] text-ink-400">
                                <span class="rounded bg-ink-100 px-1.5 py-px font-semibold dark:bg-ink-800">{{ $p['category'] }}</span>
                                <span>{{ number_format($p['stock']) }} {{ __('pcs') }}</span>
                            </div>
                        </td>
                        {{-- Cost --}}
                        <td class="px-3 py-2.5 text-right font-mono text-[11.5px] text-ink-400">${{ number_format($p['cost'], 2) }}</td>
                        {{-- Auto --}}
                        <td class="px-3 py-2.5 text-right">
                            <span class="font-mono text-[11.5px] {{ $p['override'] !== null ? 'text-ink-400 line-through' : 'font-semibold text-ink-700 dark:text-ink-200' }}">{{ $symbol }}{{ number_format($p['auto']) }}</span>
                        </td>
                        {{-- Editable price + hide + save (one form) --}}
                        <form method="POST" action="{{ route('admin.external.save', $p['id']) }}" class="contents">
                            @csrf
                            <td class="px-3 py-2.5">
                                <div class="flex h-9 items-center rounded-lg border border-ink-200 bg-white pl-2.5 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/15 dark:border-ink-700 dark:bg-ink-900">
                                    <span class="text-[11px] font-mono text-ink-400">{{ $symbol }}</span>
                                    <input name="price" type="number" step="any" min="0" value="{{ $p['override'] }}"
                                           placeholder="{{ __('auto') }}"
                                           class="h-full w-24 border-0 bg-transparent px-1.5 font-mono text-[12px] font-semibold placeholder:font-normal placeholder:text-ink-300 focus:ring-0 dark:text-ink-50">
                                </div>
                            </td>
                            {{-- Hide --}}
                            <td class="px-3 py-2.5 text-center">
                                <label class="inline-flex cursor-pointer">
                                    <input type="checkbox" name="hidden" value="1" @checked($p['hidden']) class="peer sr-only">
                                    <span class="relative inline-flex h-5 w-9 items-center rounded-full bg-ink-200 transition peer-checked:bg-brand-600 dark:bg-ink-700">
                                        <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-4"></span>
                                    </span>
                                </label>
                            </td>
                            {{-- Save --}}
                            <td class="px-4 py-2.5 text-right">
                                <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-brand-700 active:bg-brand-800">{{ __('Save') }}</button>
                            </td>
                        </form>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center">
                        <p class="text-[13px] font-semibold">{{ __('No products found') }}</p>
                        <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Check the API key and connection, or clear your search.') }}</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="mt-3 text-[11px] text-ink-400">{{ __('Tip: leave the price blank to use the automatic rate + markup. A struck-through auto price means an override is active.') }}</p>
@endsection