@extends('layouts.admin')

@section('title', __('My account'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('My account') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Manage your admin profile and password.') }}</p>
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

    {{-- Identity summary --}}
    <aside class="lg:col-span-1">
        <div class="card p-6 lg:sticky lg:top-[88px]">
            @php $initials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($admin->name ?: 'A', 0, 2)); @endphp
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-brand-600 text-[16px] font-bold text-white">{{ $initials }}</span>
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-bold">{{ $admin->name }}</p>
                    <p class="truncate text-[12px] text-ink-500 dark:text-ink-400">{{ $admin->email }}</p>
                </div>
            </div>
            <dl class="mt-5 space-y-3 border-t border-ink-100 pt-4 dark:border-ink-800/60">
                <div class="flex items-center justify-between">
                    <dt class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Role') }}</dt>
                    <dd><span class="rounded-md bg-brand-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $admin->role ?? 'admin' }}</span></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-[12px] text-ink-500 dark:text-ink-400">{{ __('Status') }}</dt>
                    <dd><span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300">{{ __('Active') }}</span></dd>
                </div>
            </dl>
        </div>
    </aside>

    {{-- Forms --}}
    <div class="space-y-4 lg:col-span-2">

        {{-- Profile --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Profile') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Your name and login email.') }}</p>
            <form method="POST" action="{{ route('admin.account.profile') }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Name') }}</label>
                    <input id="name" name="name" value="{{ old('name', $admin->name) }}" required
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    @error('name')<p class="mt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $admin->email) }}" required
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    @error('email')<p class="mt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Save profile') }}</button>
            </form>
        </section>

        {{-- Password --}}
        <section class="card p-6">
            <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Password') }}</h3>
            <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Use a long, unique password.') }}</p>
            <form method="POST" action="{{ route('admin.account.password') }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label for="current_password" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Current password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    @error('current_password')<p class="mt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="password" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('New password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password"
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                        @error('password')<p class="mt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Confirm new password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                               class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13.5px] dark:border-ink-700 dark:bg-ink-900">
                    </div>
                </div>
                <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Change password') }}</button>
            </form>
        </section>
    </div>
</div>
@endsection