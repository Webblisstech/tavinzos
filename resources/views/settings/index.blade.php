@extends('layouts.app')

@section('title', __('Settings'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Settings') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Manage your profile, password and account.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 flex items-center gap-3 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-4 text-[13px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
        <svg width="18" height="18" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
        {{ session('status') }}
    </div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Account summary --}}
    <aside class="lg:col-span-1">
        <div class="card p-5 lg:sticky lg:top-[88px]">
            @php
                $initials = \Illuminate\Support\Str::of($user->name ?: 'U')->explode(' ')->take(2)
                    ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))->implode('');
            @endphp
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-brand-600 text-[16px] font-bold text-white">{{ $initials }}</span>
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-bold">{{ $user->name }}</p>
                    <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 border-t border-ink-100 pt-4 dark:border-ink-800/60">
                <div class="flex items-center justify-between">
                    <dt class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Wallet balance') }}</dt>
                    <dd class="font-mono text-[13px] font-bold text-brand-600 dark:text-brand-400">{{ config('services.numbers.currency.symbol', '₦') }}{{ number_format((float) $user->wallet_balance, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Email status') }}</dt>
                    <dd>
                        @if ($user->email_verified_at)
                            <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('Verified') }}</span>
                        @else
                            <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ __('Unverified') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Member since') }}</dt>
                    <dd class="text-[13px] font-semibold">{{ \Illuminate\Support\Carbon::parse($user->created_at)->format('M Y') }}</dd>
                </div>
            </dl>
        </div>
    </aside>

    {{-- Forms --}}
    <div class="space-y-4 lg:col-span-2">

        {{-- Profile (read-only — contact support to change these) --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Profile') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your account details. Contact support to change these.') }}</p>

            <dl class="mt-4 divide-y divide-ink-100 dark:divide-ink-800/60">
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Name') }}</dt>
                    <dd class="truncate text-right text-[13.5px] font-semibold">{{ $user->name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Email') }}</dt>
                    <dd class="truncate text-right text-[13.5px] font-semibold">{{ $user->email }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-[12.5px] text-ink-500 dark:text-ink-400">{{ __('Phone') }}</dt>
                    <dd class="truncate text-right text-[13.5px] font-semibold">{{ $user->phone ?: '—' }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex items-start gap-2 rounded-xl bg-ink-50 p-3 dark:bg-ink-900">
                <svg width="15" height="15" class="mt-0.5 h-[15px] w-[15px] shrink-0 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                <p class="text-[11.5px] leading-relaxed text-ink-500 dark:text-ink-400">{{ __('To update your name, email or phone number, please contact support.') }}</p>
            </div>
        </section>

        {{-- Password --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Password') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Use a long, unique password.') }}</p>

            <form method="POST" action="{{ route('settings.password') }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Current password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    @error('current_password')<p class="mt-1 text-[11px] font-medium text-brand-700 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="password" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('New password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                        @error('password')<p class="mt-1 text-[11px] font-medium text-brand-700 dark:text-brand-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Confirm password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    </div>
                </div>
                <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Change password') }}</button>
            </form>
        </section>

        {{-- Appearance --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Appearance') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Choose how the app looks. Saved on this device.') }}</p>
            <div class="mt-4 grid grid-cols-3 gap-2" id="theme-picker">
                @foreach (['light' => __('Light'), 'dark' => __('Dark'), 'system' => __('System')] as $key => $label)
                    <button type="button" data-theme="{{ $key }}"
                            class="theme-opt flex h-11 items-center justify-center gap-2 rounded-xl border border-ink-200 text-[12.5px] font-semibold transition dark:border-ink-800">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const current = localStorage.getItem('theme') || 'system';
    function paint(sel) {
        document.querySelectorAll('.theme-opt').forEach((b) => {
            const on = b.dataset.theme === sel;
            b.classList.toggle('border-brand-600', on);
            b.classList.toggle('bg-brand-50', on);
            b.classList.toggle('text-brand-700', on);
            b.classList.toggle('dark:bg-brand-500/10', on);
            b.classList.toggle('dark:text-brand-400', on);
        });
    }
    function apply(sel) {
        if (sel === 'system') {
            localStorage.removeItem('theme');
            document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
        } else {
            localStorage.setItem('theme', sel);
            document.documentElement.classList.toggle('dark', sel === 'dark');
        }
        paint(sel);
    }
    document.querySelectorAll('.theme-opt').forEach((b) => b.addEventListener('click', () => apply(b.dataset.theme)));
    paint(current);
})();
</script>
@endpush
@endsection