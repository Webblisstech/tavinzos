<x-auth.shell :heading="__('Forgot password?')"
               :subtitle="__('Enter your email and we\'ll send you a link to reset it.')"
               :title="__('Reset password')"
               :panelTitle="__('Locked out? We\'ve got you.')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="text-[12.5px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com"
                   class="mt-1.5 h-12 w-full rounded-xl border-ink-300 text-[14px] transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @error('email')<p class="mt-1.5 text-[11.5px] font-medium text-brand-600 dark:text-brand-400">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Email reset link') }}</button>
    </form>
    <p class="mt-6 text-center text-[13px] text-ink-500 dark:text-ink-400">
        {{ __('Remembered it?') }} <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Sign in') }}</a>
    </p>
</x-auth.shell>