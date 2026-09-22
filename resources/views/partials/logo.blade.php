{{--
    IBSolutions logo. Props (all optional):
      $href  — link target (default: dashboard, else /)
      $size  — 'sm' | 'md' | 'lg'  (default 'md')
      $mark  — show only the IB tile, no wordmark (default false)
    The mark stays red in both themes; the wordmark flips with dark mode.
--}}
@php
    $href = $href ?? (Route::has('dashboard') ? route('dashboard') : url('/'));
    $size = $size ?? 'md';
    $mark = $mark ?? false;
    $dims = [
        'sm' => ['tile' => 'h-8 w-8 rounded-[9px]',  'ib' => 'text-[15px]', 'word' => 'text-[16px]'],
        'md' => ['tile' => 'h-9 w-9 rounded-[10px]', 'ib' => 'text-[17px]', 'word' => 'text-[18px]'],
        'lg' => ['tile' => 'h-11 w-11 rounded-xl',   'ib' => 'text-[20px]', 'word' => 'text-[22px]'],
    ][$size] ?? [];
@endphp

<a href="{{ $href }}" class="flex items-center gap-2.5" aria-label="IBSolutions">
    <span class="grid {{ $dims['tile'] }} shrink-0 place-items-center bg-brand-600">
        <span class="font-sans {{ $dims['ib'] }} font-bold leading-none tracking-[-0.04em] text-white">IB</span>
    </span>
    @unless ($mark)
        <span class="{{ $dims['word'] }} font-bold tracking-[-0.03em] text-ink-900 dark:text-ink-50">IBSolutions</span>
    @endunless
</a>