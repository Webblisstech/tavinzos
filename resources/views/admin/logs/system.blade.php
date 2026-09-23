@extends('layouts.admin')

@section('title', __('System logs'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('System logs') }}</h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Errors and warnings happening in the system.') }}</p>
        </div>
        @if ($exists)
            <form method="POST" action="{{ route('admin.system-logs.clear') }}" onsubmit="return confirm('{{ __('Clear the entire log file?') }}')">
                @csrf
                <button type="submit" class="btn-ghost h-10 px-4 text-[12.5px]">{{ __('Clear log') }} · {{ $sizeKb }}KB</button>
            </form>
        @endif
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-3.5 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
@endif

{{-- Filter chips + search --}}
<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap gap-1.5">
        @php
            $chip = fn($active) => 'rounded-lg px-3 py-1.5 text-[12px] font-bold transition ' . ($active ? 'bg-ink-900 text-white dark:bg-ink-100 dark:text-ink-900' : 'border border-ink-200 text-ink-600 hover:bg-ink-100 dark:border-ink-700 dark:text-ink-300 dark:hover:bg-ink-800');
            $tone = ['error'=>'text-brand-600','critical'=>'text-brand-600','emergency'=>'text-brand-600','alert'=>'text-brand-600','warning'=>'text-amber-600','notice'=>'text-sky-600','info'=>'text-ink-500','debug'=>'text-ink-400'];
        @endphp
        <a href="{{ route('admin.system-logs.index', ['q' => $q]) }}" class="{{ $chip($level === 'all') }}">{{ __('All') }}</a>
        @foreach ($levels as $lv)
            @php $c = $counts[$lv] ?? 0; @endphp
            @if ($c > 0)
                <a href="{{ route('admin.system-logs.index', ['level' => $lv, 'q' => $q]) }}" class="{{ $chip($level === $lv) }}">
                    {{ ucfirst($lv) }} <span class="ml-1 opacity-60">{{ $c }}</span>
                </a>
            @endif
        @endforeach
    </div>
    <form method="GET" class="flex h-10 items-center gap-2.5 rounded-xl border border-ink-200 bg-white px-3.5 dark:border-ink-800 dark:bg-ink-950 lg:w-72">
        @if ($level !== 'all')<input type="hidden" name="level" value="{{ $level }}">@endif
        <svg width="16" height="16" class="h-4 w-4 shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
        <input name="q" value="{{ $q }}" type="search" placeholder="{{ __('Search messages…') }}" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13px] font-medium focus:ring-0 dark:text-ink-50">
    </form>
</div>

{{-- Entries --}}
<div class="space-y-2">
    @forelse ($entries as $e)
        @php
            $isError = in_array($e['level'], ['error','critical','emergency','alert'], true);
            $badge = [
                'error'=>'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
                'critical'=>'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
                'emergency'=>'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
                'alert'=>'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
                'warning'=>'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                'notice'=>'bg-sky-100 text-sky-700 dark:bg-sky-400/15 dark:text-sky-300',
                'info'=>'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                'debug'=>'bg-ink-100 text-ink-500 dark:bg-ink-800 dark:text-ink-400',
            ][$e['level']] ?? 'bg-ink-100 text-ink-600';
        @endphp
        <details class="card group overflow-hidden {{ $isError ? 'border-brand-200 dark:border-brand-500/25' : '' }}">
            <summary class="flex cursor-pointer list-none items-start gap-3 p-4">
                <span class="mt-0.5 shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badge }}">{{ $e['level'] }}</span>
                <div class="min-w-0 flex-1">
                    <p class="break-words text-[12.5px] font-semibold leading-snug text-ink-900 dark:text-ink-50">{{ \Illuminate\Support\Str::limit($e['message'], 200) }}</p>
                    <p class="mt-1 font-mono text-[10.5px] text-ink-400">{{ $e['time'] }} · {{ $e['env'] }}</p>
                </div>
                @if ($e['trace'])
                    <svg class="mt-1 h-4 w-4 shrink-0 text-ink-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                @endif
            </summary>
            @if ($e['trace'])
                <pre class="max-h-80 overflow-auto border-t border-ink-100 bg-ink-50 p-4 font-mono text-[11px] leading-relaxed text-ink-600 dark:border-ink-800 dark:bg-ink-950 dark:text-ink-400">{{ $e['message'] }}
{{ $e['trace'] }}</pre>
            @endif
        </details>
    @empty
        <div class="card py-16 text-center">
            <p class="text-[13px] font-semibold">{{ $exists ? __('No matching log entries') : __('No log file yet') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ $exists ? __('Nothing matches this filter — a good sign.') : __('Errors will appear here once logged.') }}</p>
        </div>
    @endforelse
</div>

@if ($entries->count() >= 300)
    <p class="mt-4 text-center text-[11.5px] text-ink-400">{{ __('Showing the 300 most recent. Older entries are in the log file.') }}</p>
@endif
@endsection