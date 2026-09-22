@extends('layouts.admin')

@section('title', $u['name'])

@section('header')
    <div>
        <a href="{{ route('admin.customers.index') }}" class="mb-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-ink-500 transition hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">
            <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            {{ __('All customers') }}
        </a>
        <div class="flex flex-wrap items-center gap-2.5">
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ $u['name'] }}</h2>
            @if ($u['suspended'])
                <span class="rounded-md bg-brand-100 px-2 py-0.5 text-[11px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ __('Suspended') }}</span>
            @else
                <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('Active') }}</span>
            @endif
        </div>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ $u['email'] }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-2xl border border-brand-600/25 bg-brand-50 p-4 text-[13px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Left: profile + activity --}}
    <div class="space-y-4 lg:col-span-2">

        {{-- Stat tiles --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => __('Balance'),  'value' => $u['balance']['formatted'], 'tone' => 'text-brand-600 dark:text-brand-400'],
                ['label' => __('Spent'),    'value' => $u['spent']['formatted'],   'tone' => 'text-ink-900 dark:text-ink-50'],
                ['label' => __('Numbers'),  'value' => number_format($numbers),    'tone' => 'text-ink-900 dark:text-ink-50'],
                ['label' => __('Accounts'), 'value' => number_format($accounts),   'tone' => 'text-ink-900 dark:text-ink-50'],
            ] as $c)
                <div class="card p-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.08em] text-ink-500 dark:text-ink-400">{{ $c['label'] }}</p>
                    <p class="mt-1 font-mono text-[16px] font-bold {{ $c['tone'] }}">{{ $c['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Full profile editor --}}
        <section class="card p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Edit customer') }}</h3>
                @unless ($u['suspended'])
                    <form method="POST" action="{{ route('admin.customers.login-as', $u['id']) }}"
                          onsubmit="return confirm('{{ __('Sign in as this customer? You can return to admin afterwards.') }}')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-ink-200 px-3 py-1.5 text-[12px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">
                            <svg width="14" height="14" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            {{ __('Sign in as') }}
                        </button>
                    </form>
                @endunless
            </div>

            <form method="POST" action="{{ route('admin.customers.profile', $u['id']) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                @method('PUT')

                @php
                    $fields = [
                        ['name', __('Name'), 'text', $u['name'], true],
                        ['email', __('Email'), 'email', $u['email'], true],
                        ['phone', __('Phone'), 'tel', $u['phone'] === '—' ? '' : $u['phone'], false],
                        ['ref_code', __('Referral code'), 'text', $u['ref_code'], false],
                        ['referred_by', __('Referred by (user ID)'), 'number', $u['referred_by'], false],
                        ['wallet_balance', __('Wallet balance'), 'number', $u['raw_balance'], false],
                        ['total_spent', __('Total spent'), 'number', $u['raw_spent'], false],
                        ['affiliate_earned', __('Affiliate earned'), 'number', $u['raw_earned'], false],
                        ['va_account_number', __('VA account number'), 'text', $u['va_number'], false],
                        ['va_account_name', __('VA account name'), 'text', $u['va_name'], false],
                        ['va_bank_name', __('VA bank'), 'text', $u['va_bank'], false],
                    ];
                @endphp

                @foreach ($fields as [$fname, $flabel, $ftype, $fvalue, $freq])
                    <div>
                        <label for="{{ $fname }}" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ $flabel }}</label>
                        <input id="{{ $fname }}" name="{{ $fname }}" type="{{ $ftype }}" @if ($ftype === 'number') step="any" @endif
                               value="{{ old($fname, $fvalue) }}" @if ($freq) required @endif
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900 {{ $ftype === 'number' ? 'font-mono' : '' }}">
                        @error($fname)<p class="mt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                    </div>
                @endforeach

                <div class="flex flex-col justify-end gap-2 pb-1">
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="verified" value="1" @checked($u['verified']) class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-[12.5px] font-medium text-ink-700 dark:text-ink-200">{{ __('Email verified') }}</span>
                    </label>
                    <label class="flex items-center gap-2.5">
                        <input type="checkbox" name="referral_paid" value="1" @checked($u['referral_paid']) class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-[12.5px] font-medium text-ink-700 dark:text-ink-200">{{ __('Referral bonus paid') }}</span>
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Save all changes') }}</button>
                    <span class="ml-3 text-[11px] text-ink-400">{{ __('Editing balance writes a ledger entry.') }}</span>
                </div>
            </form>

            {{-- Read-only facts --}}
            <dl class="mt-5 divide-y divide-ink-100 border-t border-ink-100 pt-4 dark:divide-ink-800/60 dark:border-ink-800/60">
                @foreach ([
                    __('Referred by')  => $u['referrer'] ?: '—',
                    __('Has referred') => $u['referred'] . ' ' . __('users'),
                    __('Joined')       => \Illuminate\Support\Carbon::parse($u['joined'])->format('j M Y, H:i'),
                ] as $label => $value)
                    <div class="flex items-center justify-between gap-4 py-2.5">
                        <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ $label }}</dt>
                        <dd class="truncate text-right text-[13px] font-semibold">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Wallet activity --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Recent wallet activity') }}</h3>
            <ol class="mt-4 space-y-2">
                @forelse ($ledger as $t)
                    <li class="flex items-center justify-between gap-3 rounded-xl border border-ink-100 p-3 dark:border-ink-800">
                        <div class="min-w-0">
                            <p class="truncate text-[12.5px] font-semibold capitalize">{{ $t['type'] }}</p>
                            <p class="truncate text-[11px] text-ink-500 dark:text-ink-400">{{ $t['note'] ?: '—' }} · {{ \Illuminate\Support\Carbon::parse($t['at'])->format('j M, H:i') }}</p>
                        </div>
                        <span class="shrink-0 font-mono text-[13px] font-bold {{ $t['credit'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-800 dark:text-ink-200' }}">{{ $t['credit'] ? '+' : '' }}{{ $t['amount']['formatted'] }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('No wallet activity yet.') }}</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Right: actions --}}
    <div class="space-y-4">

        {{-- Fund / debit --}}
        <section class="card p-5">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Adjust wallet') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Current balance :bal', ['bal' => $u['balance']['formatted']]) }}</p>
            <form method="POST" action="{{ route('admin.customers.adjust', $u['id']) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-ink-200 py-2.5 text-[12.5px] font-bold has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700 dark:border-ink-700 dark:has-[:checked]:bg-emerald-400/10 dark:has-[:checked]:text-emerald-300">
                        <input type="radio" name="direction" value="credit" class="sr-only" checked> {{ __('Fund') }}
                    </label>
                    <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-ink-200 py-2.5 text-[12.5px] font-bold has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700 dark:border-ink-700 dark:has-[:checked]:bg-brand-500/10 dark:has-[:checked]:text-brand-300">
                        <input type="radio" name="direction" value="debit" class="sr-only"> {{ __('Debit') }}
                    </label>
                </div>
                <input name="amount" type="number" step="0.01" min="0.01" required placeholder="{{ __('Amount') }}"
                       class="h-11 w-full rounded-xl border-ink-300 font-mono text-[14px] dark:border-ink-700 dark:bg-ink-900">
                <input name="note" maxlength="255" placeholder="{{ __('Reason (optional)') }}"
                       class="h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                <button type="submit" class="h-11 w-full rounded-xl bg-brand-600 text-[13px] font-bold text-white transition hover:bg-brand-700">{{ __('Apply adjustment') }}</button>
            </form>
        </section>

        {{-- Password reset --}}
        <section class="card p-5">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Reset password') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Set a new password for this customer.') }}</p>
            <form method="POST" action="{{ route('admin.customers.password', $u['id']) }}" class="mt-3 space-y-2">
                @csrf
                <input name="password" type="password" minlength="8" required autocomplete="new-password" placeholder="{{ __('New password') }}"
                       class="h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                <input name="password_confirmation" type="password" minlength="8" required autocomplete="new-password" placeholder="{{ __('Confirm password') }}"
                       class="h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                @error('password')<p class="text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                <button type="submit" class="h-11 w-full rounded-xl border border-ink-200 text-[13px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">{{ __('Set new password') }}</button>
            </form>
        </section>

        {{-- Suspend / reinstate --}}
        <section class="card p-5">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Access') }}</h3>
            @if ($u['suspended'])
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('This account is suspended and cannot sign in.') }}</p>
                @if ($u['suspended_reason'])
                    <p class="mt-2 rounded-lg bg-ink-50 p-2.5 text-[12px] text-ink-600 dark:bg-ink-900 dark:text-ink-300">{{ $u['suspended_reason'] }}</p>
                @endif
                <form method="POST" action="{{ route('admin.customers.suspend', $u['id']) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="action" value="unsuspend">
                    <button type="submit" class="h-11 w-full rounded-xl bg-emerald-600 text-[13px] font-bold text-white transition hover:bg-emerald-700">{{ __('Reinstate account') }}</button>
                </form>
            @else
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Suspending blocks sign-in immediately.') }}</p>
                <form method="POST" action="{{ route('admin.customers.suspend', $u['id']) }}" class="mt-3 space-y-2"
                      onsubmit="return confirm('{{ __('Suspend this account?') }}')">
                    @csrf
                    <input type="hidden" name="action" value="suspend">
                    <input name="reason" maxlength="255" placeholder="{{ __('Reason (optional)') }}"
                           class="h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                    <button type="submit" class="h-11 w-full rounded-xl border border-brand-600/30 text-[13px] font-bold text-brand-700 transition hover:bg-brand-50 dark:border-brand-500/30 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Suspend account') }}</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection