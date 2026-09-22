<x-auth.shell :heading="__('Confirm your password')"
               :subtitle="__('This is a secure area. Please confirm your password to continue.')"
               :title="__('Confirm password')"
               :panelTitle="__('One more check.')">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" autofocus placeholder="••••••••"
                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @error('password')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Confirm') }}</button>
    </form>
</x-auth.shell>