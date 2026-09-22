<x-auth.shell :heading="__('Enter your code')"
              :subtitle="__('We sent a 6-digit code to :email. Enter it below to verify.', ['email' => auth()->user()->email])"
              :title="__('Verify email')"
              :panelTitle="__('Check your inbox.')">

    @error('code')<div class="mb-4 rounded-xl border border-brand-600/25 bg-brand-50 p-3 text-center text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('verification.verify-code') }}" id="code-form">
        @csrf
        {{-- 6 boxes that feed one hidden input --}}
        <div class="flex justify-between gap-2" id="otp">
            @for ($i = 0; $i < 6; $i++)
                <input type="text" inputmode="numeric" maxlength="1" data-otp
                       class="h-14 w-full rounded-xl border-ink-300 text-center font-mono text-[22px] font-bold transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-ink-700 dark:bg-ink-900">
            @endfor
        </div>
        <input type="hidden" name="code" id="code-value">

        <button type="submit" class="mt-6 h-12 w-full rounded-xl bg-brand-600 text-[14px] font-bold text-white transition hover:bg-brand-700">{{ __('Verify email') }}</button>
    </form>

    <div class="mt-6 flex items-center justify-between text-[13px]">
        <form method="POST" action="{{ route('verification.resend-code') }}">
            @csrf
            <button type="submit" class="font-semibold text-brand-600 hover:underline dark:text-brand-400">{{ __('Resend code') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="font-semibold text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">{{ __('Log out') }}</button>
        </form>
    </div>

    {{-- Check-spam reminder --}}
    <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-amber-300/50 bg-amber-50 p-3.5 dark:border-amber-400/25 dark:bg-amber-400/10">
        <svg width="16" height="16" class="mt-px h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
        <p class="text-[12px] leading-relaxed text-amber-800 dark:text-amber-300">
            {{ __("Didn't get the email? Check your Spam or Junk folder — and mark it \"Not spam\" so future codes land in your inbox.") }}
        </p>
    </div>

    <script>
        (function () {
            var boxes = Array.from(document.querySelectorAll('[data-otp]'));
            var hidden = document.getElementById('code-value');
            var form = document.getElementById('code-form');

            function sync() { hidden.value = boxes.map(function (b) { return b.value; }).join(''); }

            boxes.forEach(function (box, i) {
                box.addEventListener('input', function () {
                    box.value = box.value.replace(/\D/g, '').slice(0, 1);
                    if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
                    sync();
                    // Auto-submit when all six are filled.
                    if (hidden.value.length === 6) form.submit();
                });
                box.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
                });
                box.addEventListener('paste', function (e) {
                    e.preventDefault();
                    var digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                    digits.forEach(function (d, j) { if (boxes[j]) boxes[j].value = d; });
                    sync();
                    (boxes[digits.length] || boxes[5]).focus();
                    if (hidden.value.length === 6) form.submit();
                });
            });
            boxes[0] && boxes[0].focus();
        })();
    </script>
</x-auth.shell>