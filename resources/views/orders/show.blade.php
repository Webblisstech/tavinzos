@extends('layouts.app')

@section('title', __('Order :ref', ['ref' => $order->reference]))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="{{ route('orders.index', ['tab' => 'accounts']) }}"
               class="mb-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink-500 transition hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">
                <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                {{ __('All orders') }}
            </a>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ $order->product_name }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
                {{ $order->quantity }} {{ __('accounts') }} · {{ $total['formatted'] }} ·
                <span class="font-mono">{{ $order->reference }}</span>
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <a href="{{ route('logs.download', $order->reference) }}" class="btn-ghost h-11 text-[13px]">
                <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                {{ __('.txt') }}
            </a>
            <a href="{{ route('logs.download', $order->reference) }}?format=pdf" class="btn-primary h-11 text-[13px]">
                <svg width="15" height="15" class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                {{ __('PDF') }}
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-2.5">
    @foreach ($items as $i => $item)
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-100 px-3.5 py-2.5 dark:border-ink-800/60">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="text-[11px] font-bold tracking-[0.06em] text-ink-500 dark:text-ink-400">{{ __('ACCOUNT') }} {{ $i + 1 }}</span>
                    @if ($item->label)
                        <span class="truncate text-[12px] font-semibold">{{ $item->label }}</span>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    @if ($item->preview_url)
                        <a href="{{ $item->preview_url }}" target="_blank" rel="noopener noreferrer"
                           class="rounded-md px-2 py-1 text-[11px] font-semibold text-ink-600 transition hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800">{{ __('Preview ↗') }}</a>
                    @endif
                    <button type="button" data-copy="{{ $i }}"
                            class="rounded-md px-2 py-1 text-[11px] font-semibold text-brand-700 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Copy') }}</button>
                </div>
            </div>
            <pre data-content="{{ $i }}" class="scroll-y max-h-48 overflow-auto whitespace-pre-wrap break-all px-3.5 py-3 font-mono text-[12px] leading-relaxed">{{ $item->content }}</pre>
        </div>
    @endforeach
</div>

<div class="mt-4 flex items-center gap-2">
    <button type="button" id="copy-all" class="btn-ghost h-11 flex-1 text-[13px] sm:flex-none sm:px-6">{{ __('Copy all') }}</button>
    <a href="{{ route('logs.download', $order->reference) }}" class="btn-ghost h-11 flex-1 text-[13px] sm:flex-none sm:px-6">{{ __('Download .txt') }}</a>
    <a href="{{ route('logs.download', $order->reference) }}?format=pdf" class="btn-primary h-11 flex-1 text-[13px] sm:flex-none sm:px-6">{{ __('Download PDF') }}</a>
</div>

<div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl bg-ink-900 px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg dark:bg-ink-100 dark:text-ink-900" data-cloak></div>
@endsection

@push('scripts')
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    let toastTimer;
    function toast(msg) {
        const t = $('toast');
        t.textContent = msg;
        t.removeAttribute('data-cloak');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.setAttribute('data-cloak', ''), 2400);
    }

    async function copy(text) {
        try { await navigator.clipboard.writeText(text); toast('Copied'); }
        catch (e) { toast('Could not copy'); }
    }

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const pre = document.querySelector('[data-content="' + btn.dataset.copy + '"]');
            if (pre) copy(pre.textContent);
        });
    });

    $('copy-all').addEventListener('click', () => {
        const all = [...document.querySelectorAll('[data-content]')]
            .map((p) => p.textContent)
            .join('\n\n' + '─'.repeat(40) + '\n\n');
        copy(all);
    });
})();
</script>
@endpush