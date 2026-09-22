<x-auth.shell :heading="__('Set a new password')"
               :subtitle="__('Choose a strong password you haven\'t used before.')"
               :title="__('Reset password')"
               :panelTitle="__('A fresh start.')">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @error('email')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('New password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="••••••••"
                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @error('password')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="••••••••"
                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @error('password_confirmation')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Reset password') }}</button>
    </form>
</x-auth.shell>