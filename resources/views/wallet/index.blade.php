@extends('layouts.app')

@section('title', __('Add funds'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Add Funds') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Add money to your balance and see your activity.') }}</p>
    </div>
@endsection

@section('content')

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

    {{-- ═══════════ Deposit form ═══════════ --}}
    <section class="card p-6">
        @if (session('error'))
            <p class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ session('error') }}</p>
        @endif

        @unless ($deposits)
            {{-- Deposits switched off in admin payment settings --}}
            <div class="py-10 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-400">
                    <svg width="26" height="26" class="h-[26px] w-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <h3 class="mt-5 text-[17px] font-bold tracking-[-0.02em]">{{ __('Deposits are paused') }}</h3>
                <p class="mx-auto mt-2 max-w-xs text-[13px] leading-relaxed text-ink-500 dark:text-ink-400">
                    {{ __('Adding funds is temporarily unavailable. Please check back shortly — your balance is safe.') }}
                </p>
                <a href="{{ route('dashboard') }}" class="btn-ghost mt-6 inline-flex h-11 px-6 text-[13px]">{{ __('Back to dashboard') }}</a>
            </div>
        @else
        <form method="POST" action="{{ route('wallet.deposit') }}">
            @csrf

            @error('amount')
                <p class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $message }}</p>
            @enderror

            {{-- Payment method --}}
            <label class="text-[12.5px] font-bold text-ink-800 dark:text-ink-100">{{ __('Payment Method') }}</label>
            <div class="mt-2 flex h-12 items-center gap-2.5 rounded-xl border border-ink-300 px-3.5 dark:border-ink-700 dark:bg-ink-900">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-brand-600 text-white">
                    <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
                </span>
                <span class="flex-1 text-[13px] font-semibold text-ink-900 dark:text-ink-50">{{ $gateway }} <span class="font-normal text-ink-500 dark:text-ink-400">— {{ __('Transfer, Card, USSD, QR') }}</span></span>
                <svg width="16" height="16" class="h-4 w-4 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </div>

            {{-- Amount --}}
            <label for="amount" class="mt-5 block text-[12.5px] font-bold text-ink-800 dark:text-ink-100">{{ __('Enter Amount') }}</label>

            <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach ([1000, 2000, 5000, 10000, 20000, 50000] as $preset)
                    @continue($preset < $min || $preset > $max)
                    <button type="button" data-preset="{{ $preset }}"
                            class="h-9 rounded-lg border border-ink-200 text-[11.5px] font-bold text-ink-700 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-800 dark:text-ink-200 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                        {{ $symbol }}{{ number_format($preset) }}
                    </button>
                @endforeach
            </div>

            <div class="mt-3 flex h-14 items-center rounded-xl border border-ink-300 px-4 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
                <span class="mr-1 font-mono text-[18px] font-bold text-ink-500 dark:text-ink-400">{{ $symbol }}</span>
                <input id="amount" name="amount" type="number" inputmode="numeric"
                       min="{{ (int) $min }}" max="{{ (int) $max }}" step="1" required
                       value="{{ old('amount') }}" placeholder="{{ __('Enter amount to deposit') }}"
                       class="min-w-0 flex-1 border-0 bg-transparent p-0 font-mono text-[20px] font-bold text-ink-900 placeholder:font-sans placeholder:text-[15px] placeholder:font-normal placeholder:text-ink-400 focus:ring-0 dark:text-ink-50">
            </div>
            <p class="mt-1.5 text-[11px] text-ink-500 dark:text-ink-400">{{ __('Between') }} {{ $symbol }}{{ number_format($min) }} {{ __('and') }} {{ $symbol }}{{ number_format($max) }}.</p>

            <button type="submit" class="btn-primary mt-4 h-12 w-full text-[14px]">{{ __('Make Payment') }}</button>

            @if ($note)
                <p class="mt-3 text-center text-[11.5px] text-ink-500 dark:text-ink-400">{{ $note }}</p>
            @endif

            <p class="mt-3 flex items-center justify-center gap-1.5 text-[11px] text-ink-500 dark:text-ink-400">
                <svg width="13" height="13" class="h-[13px] w-[13px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                {{ __('Card, transfer, USSD or QR — secured by :gateway', ['gateway' => $gateway]) }}
            </p>
        </form>

        {{-- Or pay by direct bank transfer to a dedicated account --}}
        <div class="mt-5 border-t border-ink-100 pt-5 dark:border-ink-800">
            <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-ink-400">{{ __('Or transfer to your account') }}</p>

            <div id="va-box" class="mt-3 {{ $va ? '' : 'hidden' }}">
                <div class="rounded-2xl border border-brand-200 bg-brand-50/50 p-4 dark:border-brand-500/25 dark:bg-brand-500/5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ __('Bank') }}</span>
                        <span id="va-bank" class="text-[13px] font-bold">{{ $va['bank'] ?? '' }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <span id="va-number" class="font-mono text-[24px] font-bold tracking-[0.02em]">{{ $va['number'] ?? '' }}</span>
                        <button type="button" id="va-copy" class="shrink-0 rounded-lg bg-brand-600 px-3 py-1.5 text-[11.5px] font-bold text-white transition hover:bg-brand-700">{{ __('Copy') }}</button>
                    </div>
                    <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400"><span id="va-name">{{ $va['name'] ?? '' }}</span></p>
                    <p class="mt-3 text-[11px] leading-relaxed text-ink-500 dark:text-ink-400">{{ __('Transfer any amount to this account. Your wallet is credited automatically once the transfer is received.') }}</p>
                </div>
            </div>

            <button type="button" id="va-generate" class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-ink-200 text-[13px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800 {{ $va ? 'hidden' : '' }}">
                <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
                {{ __('Get my bank account number') }}
            </button>
            <p id="va-error" class="mt-2 hidden text-center text-[12px] font-medium text-brand-600 dark:text-brand-400"></p>
        </div>
        @endunless
    </section>

    {{-- ═══════════ How to deposit ═══════════ --}}
    <section class="card overflow-hidden">
        <h3 class="px-6 pt-6 text-[14px] font-bold tracking-[-0.02em]">{{ __('How to make deposits') }}</h3>
        @if ($tutorial)
            <div class="mt-4 aspect-video w-full bg-ink-100 dark:bg-ink-900">
                <iframe src="{{ $tutorial }}" title="{{ __('How to make deposits') }}"
                        class="h-full w-full" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            </div>
        @else
            <div class="mt-4 grid aspect-video w-full place-items-center border-y border-ink-200 bg-ink-50 text-center dark:border-ink-800 dark:bg-ink-900">
                <div class="px-6">
                    <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-brand-600 text-white"><svg width="18" height="18" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg></span>
                    <p class="mt-3 text-[12.5px] font-semibold">{{ __('Walkthrough coming soon') }}</p>
                    <p class="mt-1 text-[11.5px] text-ink-500 dark:text-ink-400">{{ __('Add a video link in settings under site.deposit_tutorial_url.') }}</p>
                </div>
            </div>
        @endif
        <ol class="space-y-2.5 p-6">
            @foreach ([
                __('Enter the amount you want to add.'),
                __('Press Make Payment and pick a method.'),
                __('Pay by card, transfer, USSD or QR.'),
                __('Your balance updates the moment it clears.'),
            ] as $i => $step)
                <li class="flex gap-2.5 text-[12px] text-ink-600 dark:text-ink-300">
                    <span class="grid h-[18px] w-[18px] shrink-0 place-items-center rounded-full bg-ink-100 text-[9.5px] font-bold text-ink-600 dark:bg-ink-800 dark:text-ink-300">{{ $i + 1 }}</span>
                    <span>{{ $step }}</span>
                </li>
            @endforeach
        </ol>
    </section>
</div>

{{-- ═══════════ Deposit history (top-ups only) ═══════════ --}}
<section class="card mt-4 p-5">
    <h3 class="mb-4 text-[14px] font-bold tracking-[-0.02em]">{{ __('Deposit history') }}</h3>

    @forelse ($ledger as $t)
        @php $pending = ($t['status'] ?? 'settled') === 'pending'; @endphp
        <div class="flex items-center gap-3 border-t border-ink-100 py-3 first:border-t-0 dark:border-ink-800/60 {{ $pending ? 'opacity-60' : '' }}">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-400">
                <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-semibold">
                    {{ __('Wallet top-up') }}
                    @if ($pending)<span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[9.5px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ __('PENDING') }}</span>@endif
                </p>
                <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ \Illuminate\Support\Carbon::parse($t['at'])->format('j M Y, H:i') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                @if ($pending)
                    <span data-pending-ref="{{ $t['ref'] }}" class="flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        </span>
                        {{ __('Confirming…') }}
                    </span>
                    <a href="{{ route('wallet.recheck', $t['ref']) }}"
                       class="rounded-lg border border-ink-300 px-2.5 py-1 text-[11px] font-semibold transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">{{ __('Check status') }}</a>
                @endif
                <span class="font-mono text-[14px] font-bold text-emerald-600 dark:text-emerald-400">+{{ $t['money']['formatted'] }}</span>
            </div>
        </div>
    @empty
        <div class="py-16 text-center">
            <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-ink-100 text-ink-400 dark:bg-ink-800"><svg width="22" height="22" class="h-[22px] w-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></span>
            <p class="text-[13px] font-semibold">{{ __('No deposits yet') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your top-ups will appear here.') }}</p>
        </div>
    @endforelse
</section>

@push('scripts')

<script>
(function () {
    // Auto-confirm pending payments: silently poll the status endpoint every
    // few seconds so the wallet updates on its own — no "Check status" needed.
    var pending = document.querySelectorAll('[data-pending-ref]');
    if (!pending.length) return;

    var statusBase = @json(url('wallet/status'));
    var tries = 0;
    var maxTries = 40;   // ~2 minutes at 3s intervals

    function poll() {
        tries++;
        var anyStillPending = false;

        pending.forEach(function (el) {
            var ref = el.dataset.pendingRef;
            if (!ref || el.dataset.done === '1') return;
            anyStillPending = true;

            fetch(statusBase + '/' + encodeURIComponent(ref), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d && d.settled) {
                        el.dataset.done = '1';
                        // Reload so the balance + row status refresh cleanly.
                        location.reload();
                    }
                })
                .catch(function () {});
        });

        if (anyStillPending && tries < maxTries) {
            setTimeout(poll, 3000);
        }
    }

    // First check quickly (in case it already settled), then keep polling.
    setTimeout(poll, 1500);
})();
</script>

{{-- Phone modal — shown when the VA API needs a valid 11-digit number --}}
<div id="phone-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-6 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-ink-900">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-[16px] font-bold tracking-[-0.02em]">{{ __('Enter your phone number') }}</h3>
                <p class="mt-1 text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('We need a valid 11-digit number to create your account.') }}</p>
            </div>
            <button type="button" id="phone-close" class="grid h-8 w-8 place-items-center rounded-lg text-ink-400 transition hover:bg-ink-100 dark:hover:bg-ink-800" aria-label="{{ __('Close') }}">
                <svg width="18" height="18" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <input id="phone-input" type="tel" inputmode="numeric" maxlength="11" placeholder="08012345678"
               class="mt-5 h-12 w-full rounded-xl border-ink-300 text-center font-mono text-[16px] tracking-[0.15em] dark:border-ink-700 dark:bg-ink-950">
        <p id="phone-err" class="mt-2 hidden text-center text-[12px] font-medium text-brand-600 dark:text-brand-400"></p>
        <button type="button" id="phone-submit" class="mt-4 h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Create account') }}</button>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('amount');
    document.querySelectorAll('[data-preset]').forEach((b) => {
        b.addEventListener('click', () => { input.value = b.dataset.preset; input.focus(); });
    });

    // ── Virtual account: generate (with phone modal fallback) + copy ──
    const genBtn = document.getElementById('va-generate');
    const box    = document.getElementById('va-box');
    const errEl  = document.getElementById('va-error');
    const modal  = document.getElementById('phone-modal');
    const pInput = document.getElementById('phone-input');
    const pErr   = document.getElementById('phone-err');
    const csrf   = document.querySelector('meta[name=csrf-token]')?.content || '';
    const url    = @json(route('wallet.virtual-account'));

    function show(acc) {
        document.getElementById('va-bank').textContent   = acc.bank || '';
        document.getElementById('va-number').textContent = acc.number || '';
        document.getElementById('va-name').textContent   = acc.name || '';
        box.classList.remove('hidden');
        genBtn && genBtn.classList.add('hidden');
    }
    function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); pInput.focus(); }
    function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    async function generate(phone) {
        const body = phone ? JSON.stringify({ phone }) : null;
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body,
        });
        return { ok: res.ok, data: await res.json() };
    }

    if (genBtn) {
        genBtn.addEventListener('click', async () => {
            errEl.classList.add('hidden');
            genBtn.disabled = true;
            const orig = genBtn.innerHTML;
            genBtn.textContent = @json(__('Generating…'));
            try {
                const { data } = await generate(null);
                if (data.success && data.account) {
                    show(data.account);
                } else if (data.need_phone) {
                    openModal();               // no valid phone on file → ask
                    genBtn.disabled = false; genBtn.innerHTML = orig;
                } else {
                    errEl.textContent = data.message || @json(__('Could not generate an account.'));
                    errEl.classList.remove('hidden');
                    genBtn.disabled = false; genBtn.innerHTML = orig;
                }
            } catch (e) {
                errEl.textContent = @json(__('Network error. Please try again.'));
                errEl.classList.remove('hidden');
                genBtn.disabled = false; genBtn.innerHTML = orig;
            }
        });
    }

    // Modal submit
    document.getElementById('phone-submit')?.addEventListener('click', async () => {
        const phone = (pInput.value || '').replace(/\D+/g, '');
        pErr.classList.add('hidden');
        if (phone.length !== 11) {
            pErr.textContent = @json(__('Please enter exactly 11 digits.'));
            pErr.classList.remove('hidden');
            return;
        }
        const btn = document.getElementById('phone-submit');
        btn.disabled = true; const t = btn.textContent; btn.textContent = @json(__('Creating…'));
        try {
            const { data } = await generate(phone);
            if (data.success && data.account) {
                closeModal();
                show(data.account);
            } else {
                pErr.textContent = data.message || @json(__('Could not create the account.'));
                pErr.classList.remove('hidden');
                btn.disabled = false; btn.textContent = t;
            }
        } catch (e) {
            pErr.textContent = @json(__('Network error. Please try again.'));
            pErr.classList.remove('hidden');
            btn.disabled = false; btn.textContent = t;
        }
    });

    document.getElementById('phone-close')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    const copyBtn = document.getElementById('va-copy');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const num = document.getElementById('va-number').textContent.trim();
            navigator.clipboard?.writeText(num);
            const t = copyBtn.textContent;
            copyBtn.textContent = @json(__('Copied!'));
            setTimeout(() => { copyBtn.textContent = t; }, 1500);
        });
    }
})();
</script>
@endpush
@endsection