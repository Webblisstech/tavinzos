<x-auth.shell :heading="__('Verify your email')"
               :subtitle="__('We\'ve sent a verification link to your inbox. Click it to activate your account.')"
               :title="__('Verify email')"
               :panelTitle="__('Almost there.')">
    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-600/25 bg-emerald-50 p-3 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
            {{ __('A new verification link has been sent to your email.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Resend verification email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-[13px] font-semibold text-ink-500 transition hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">{{ __('Log out') }}</button>
    </form>
</x-auth.shell>