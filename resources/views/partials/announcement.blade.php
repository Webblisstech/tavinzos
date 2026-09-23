@php
    use Illuminate\Support\Facades\Cache;
    $s = Cache::get('settings') ?: [];
    $on = filter_var($s['popup.enabled'] ?? false, FILTER_VALIDATE_BOOL);
    $title = trim((string) ($s['popup.title'] ?? ''));
    $body  = trim((string) ($s['popup.body'] ?? ''));
    $btn   = trim((string) ($s['popup.button_text'] ?? '')) ?: __('Got it');
    $url   = trim((string) ($s['popup.button_url'] ?? ''));
    $ver   = trim((string) ($s['popup.version'] ?? '1'));
@endphp

@if ($on && ($title !== '' || $body !== ''))
<div id="announcement" data-ver="{{ $ver }}" data-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-5">
    <div class="absolute inset-0 bg-ink-950/50 backdrop-blur-sm" data-announce-close></div>
    <div class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-ink-900">
        {{-- accent header --}}
        <div class="h-1.5 w-full bg-brand-600"></div>
        <div class="p-6 sm:p-7">
            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 21 4l-4 16-5-6-5 3V11Z"/></svg>
            </div>
            @if ($title !== '')
                <h3 class="text-[18px] font-bold tracking-[-0.02em] text-ink-900 dark:text-ink-50">{{ $title }}</h3>
            @endif
            @if ($body !== '')
                @php
                    // Preserve intended line breaks even if the save stripped
                    // them: put each "N." numbered item on its own line, and
                    // keep any real newlines the admin typed.
                    $formatted = preg_replace('/\s+(\d+\.\s)/', "\n$1", $body);
                @endphp
                <div class="mt-2 max-h-[50vh] space-y-2 overflow-y-auto pr-1 text-[13.5px] leading-relaxed text-ink-900 dark:text-ink-100">
                    @foreach (preg_split('/\n+/', trim($formatted)) as $line)
                        @if (trim($line) !== '')
                            <p class="whitespace-pre-line">{{ trim($line) }}</p>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="mt-6 flex gap-2.5">
                @if ($url !== '')
                    <a href="{{ $url }}" class="btn-primary h-11 flex-1 text-[13.5px]" data-announce-close>{{ $btn }}</a>
                    <button type="button" class="btn-ghost h-11 px-5 text-[13.5px]" data-announce-close>{{ __('Close') }}</button>
                @else
                    <button type="button" class="btn-primary h-11 w-full text-[13.5px]" data-announce-close>{{ $btn }}</button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var el = document.getElementById('announcement');
    if (!el) return;
    var key = 'announce-dismissed';
    var ver = el.dataset.ver;
    try {
        if (localStorage.getItem(key) === ver) { el.remove(); return; }
    } catch (e) {}
    // Show it.
    el.removeAttribute('data-cloak');
    document.body.style.overflow = 'hidden';
    function close() {
        try { localStorage.setItem(key, ver); } catch (e) {}
        document.body.style.overflow = '';
        el.remove();
    }
    el.querySelectorAll('[data-announce-close]').forEach(function (b) {
        // A link with a URL should navigate AND remember dismissal.
        b.addEventListener('click', function () { close(); });
    });
})();
</script>
@endif