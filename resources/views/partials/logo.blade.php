{{--
    Brand logo — custom mark. Props (all optional):
      $href  — link target (default: dashboard, else /)
      $size  — 'sm' | 'md' | 'lg'  (default 'md')
      $mark  — show only the icon tile, no wordmark (default false)
    The wordmark reads from the site setting / APP_NAME, so renaming updates it.
    The mark is an original glyph: a signal "pulse" rising to a verified node —
    connection + trust, for a numbers/accounts platform.
--}}
@php
    $href = $href ?? (Route::has('dashboard') ? route('dashboard') : url('/'));
    $size = $size ?? 'md';
    $mark = $mark ?? false;

    $brand = trim((string) (\Illuminate\Support\Facades\Cache::get('settings')['site.name'] ?? '')) ?: config('app.name', 'Tavinzos');

    $dims = [
        'sm' => ['tile' => 'h-8 w-8 rounded-[9px]',  'svg' => 'h-[18px] w-[18px]', 'word' => 'text-[16px]'],
        'md' => ['tile' => 'h-9 w-9 rounded-[10px]', 'svg' => 'h-5 w-5',           'word' => 'text-[18px]'],
        'lg' => ['tile' => 'h-11 w-11 rounded-xl',   'svg' => 'h-6 w-6',           'word' => 'text-[22px]'],
    ][$size] ?? [];
@endphp

<a href="{{ $href }}" class="flex items-center gap-2.5" aria-label="{{ $brand }}">
    <span class="grid {{ $dims['tile'] }} shrink-0 place-items-center bg-brand-600">
        {{-- Signal pulse rising to a verified node --}}
        <svg class="{{ $dims['svg'] }} text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            {{-- ascending signal bars --}}
            <path d="M5 16.5v-2M9 16.5v-5M13 16.5v-8" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
            {{-- pulse sweep --}}
            <path d="M5 12.5c3-4 6-5.5 9.5-6.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" opacity="0.55"/>
            {{-- verified node --}}
            <circle cx="18" cy="6.5" r="3.4" fill="currentColor"/>
            <path d="m16.7 6.5 1 1 1.6-1.9" stroke="var(--tile-check, #D91F2C)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    @unless ($mark)
        <span class="{{ $dims['word'] }} font-bold tracking-[-0.03em] text-ink-900 dark:text-ink-50">{{ $brand }}</span>
    @endunless
</a>