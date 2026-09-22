@extends('layouts.admin')

@section('title', __('Order :ref', ['ref' => $order['ref']]))

@section('header')
    <div>
        <a href="{{ route('admin.orders.index') }}" class="mb-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink-500 transition hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">
            <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            {{ __('All orders') }}
        </a>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ $order['title'] }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400"><span class="font-mono">{{ $order['ref'] }}</span> · {{ ucfirst($order['kind']) }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
        <svg width="18" height="18" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
        {{ session('status') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-2xl border border-brand-600/25 bg-brand-50 p-4 text-[13px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Order detail --}}
    <section class="card p-6 lg:col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Order details') }}</h3>
            @php
                $tone = match ($order['status']) {
                    'Completed','Delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
                    'Waiting','Pending'     => 'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
                    default                 => 'bg-ink-100 text-ink-600 dark:bg-ink-800 dark:text-ink-300',
                };
            @endphp
            <span class="rounded-lg px-2.5 py-1 text-[12px] font-bold {{ $tone }}">{{ $order['status'] }}</span>
        </div>

        <dl class="mt-5 divide-y divide-ink-100 dark:divide-ink-800/60">
            <div class="flex items-center justify-between py-3">
                <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Amount') }}</dt>
                <dd class="font-mono text-[15px] font-bold text-brand-600 dark:text-brand-400">{{ $order['amount']['formatted'] }}</dd>
            </div>
            @foreach ($order['meta'] as $label => $value)
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ $label }}</dt>
                    <dd class="truncate text-right font-mono text-[13px] font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between py-3">
                <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Placed') }}</dt>
                <dd class="text-[13px] font-semibold">{{ \Illuminate\Support\Carbon::parse($order['at'])->format('j M Y, H:i') }}</dd>
            </div>
        </dl>
    </section>

    {{-- Customer + refund --}}
    <div class="space-y-4">
        <section class="card p-5">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Customer') }}</h3>
            <p class="mt-3 text-[14px] font-semibold">{{ $order['user'] }}</p>
            <p class="truncate text-[12.5px] text-ink-500 dark:text-ink-400">{{ $order['email'] }}</p>
        </section>

        <section class="card p-5">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Refund') }}</h3>

            @if ($order['refunded'])
                <div class="mt-3 rounded-xl border border-ink-200 bg-ink-50 p-3.5 dark:border-ink-800 dark:bg-ink-900">
                    <p class="text-[12.5px] font-semibold">{{ __('Already refunded') }}</p>
                    @if ($order['refund'])
                        <p class="mt-0.5 font-mono text-[13px] font-bold text-emerald-600 dark:text-emerald-400">{{ $order['refund']['formatted'] }}</p>
                    @endif
                </div>
            @else
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Return :amt to the customer\'s wallet.', ['amt' => $order['amount']['formatted']]) }}</p>
                <form method="POST" action="{{ route('admin.orders.refund', $order['ref']) }}" class="mt-3 space-y-3" id="refund-form">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $order['kind'] }}">
                    <div>
                        <label for="reason" class="text-[11.5px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Reason (optional)') }}</label>
                        <input id="reason" name="reason" maxlength="255" placeholder="{{ __('e.g. code never arrived') }}"
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                    </div>
                    <button type="button" id="refund-btn" class="h-11 w-full rounded-xl bg-brand-600 text-[13px] font-bold text-white transition hover:bg-brand-700">
                        {{ __('Refund') }} {{ $order['amount']['formatted'] }}
                    </button>
                </form>
            @endif
        </section>
    </div>
</div>

@if (! $order['refunded'])
@php
    $confirmMsg = __('Return :amt to :email. This cannot be undone.', [
        'amt'   => $order['amount']['formatted'],
        'email' => $order['email'],
    ]);
@endphp
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    (function () {
        var btn = document.getElementById('refund-btn');
        var form = document.getElementById('refund-form');
        if (!btn || !form) return;

        var dark = document.documentElement.classList.contains('dark');

        btn.addEventListener('click', function () {
            Swal.fire({
                title: @json(__('Refund this order?')),
                html: @json($confirmMsg),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: @json(__('Yes, refund')),
                cancelButtonText: @json(__('Cancel')),
                confirmButtonColor: '#D91F2C',
                cancelButtonColor: dark ? '#3a3131' : '#a89898',
                reverseButtons: true,
                background: dark ? '#100c0c' : '#ffffff',
                color: dark ? '#faf7f7' : '#14100f',
            }).then(function (result) {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.textContent = @json(__('Refunding…'));
                    form.submit();
                }
            });
        });
    })();
</script>
@endif

{{-- Result toast after a refund attempt --}}
@if (session('status') || session('error'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            var dark = document.documentElement.classList.contains('dark');
            Swal.fire({
                toast: true, position: 'top-end', showConfirmButton: false,
                timer: 3500, timerProgressBar: true,
                icon: @json(session('status') ? 'success' : 'error'),
                title: @json(session('status') ?: session('error')),
                background: dark ? '#100c0c' : '#ffffff',
                color: dark ? '#faf7f7' : '#14100f',
            });
        })();
    </script>
@endif
@endsection