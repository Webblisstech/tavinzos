@php
    use Illuminate\Support\Facades\Cache;
    $gs = Cache::get('settings') ?: [];
    $gUrl   = trim((string) ($gs['support.group'] ?? ''));
    $gLabel = trim((string) ($gs['support.group_label'] ?? '')) ?: __('Join our group');
    $gEnabled = filter_var($gs['popup.group_enabled'] ?? false, FILTER_VALIDATE_BOOL);
    $gTitle = trim((string) ($gs['popup.group_title'] ?? '')) ?: __('Join our community');
    $gBody  = trim((string) ($gs['popup.group_body'] ?? '')) ?: __('Get instant updates, support and offers. Tap below to join.');
@endphp

@if ($gEnabled && $gUrl !== '')
<div id="group-modal" data-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-5">
    <div class="absolute inset-0 bg-ink-950/55 backdrop-blur-sm" data-group-close></div>
    <div class="relative w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-ink-900">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 px-6 pb-8 pt-7 text-center text-white">
            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h3 class="text-[19px] font-bold tracking-[-0.02em]">{{ $gTitle }}</h3>
        </div>
        <div class="p-6">
            <p class="text-center text-[13.5px] leading-relaxed text-ink-900 dark:text-ink-100">{{ $gBody }}</p>
            <a href="{{ $gUrl }}" target="_blank" rel="noopener" data-group-close
               class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 text-[14px] font-bold text-white transition hover:bg-emerald-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                {{ $gLabel }}
            </a>
            <button type="button" data-group-close
                    class="mt-2.5 h-10 w-full text-[12.5px] font-semibold text-ink-500 transition hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100">
                {{ __('Maybe later') }}
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var el = document.getElementById('group-modal');
    if (!el) return;
    // Show once per browser session (every fresh visit), not permanently —
    // so it keeps reminding users but doesn't nag on every page click.
    var key = 'group-modal-seen';
    try {
        if (sessionStorage.getItem(key) === '1') { el.remove(); return; }
        sessionStorage.setItem(key, '1');
    } catch (e) {}
    el.removeAttribute('data-cloak');
    document.body.style.overflow = 'hidden';
    function close() { document.body.style.overflow = ''; el.remove(); }
    el.querySelectorAll('[data-group-close]').forEach(function (b) {
        b.addEventListener('click', close);
    });
})();
</script>
@endif