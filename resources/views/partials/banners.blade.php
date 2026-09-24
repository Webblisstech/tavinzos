@php
    $bannerRows = \Illuminate\Support\Facades\Cache::remember('active_banners', 120, function () {
        try {
            return \Illuminate\Support\Facades\DB::getSchemaBuilder()->hasTable('banners')
                ? \Illuminate\Support\Facades\DB::table('banners')->where('active', true)->orderBy('sort')->orderByDesc('id')->get()
                : collect();
        } catch (\Throwable) { return collect(); }
    });
@endphp

@if ($bannerRows->isNotEmpty())
<div class="banner-slider {{ $class ?? 'mb-4' }} overflow-hidden rounded-2xl" data-count="{{ $bannerRows->count() }}">
    <div class="banner-track flex transition-transform duration-500 ease-out">
        @foreach ($bannerRows as $b)
            <div class="w-full shrink-0">
                @if ($b->link)<a href="{{ $b->link }}" target="_blank" rel="noopener">@endif
                    <img src="{{ route('media.show', $b->image) }}" alt="{{ $b->title }}" class="aspect-[3/1] w-full object-cover sm:aspect-[4/1]">
                @if ($b->link)</a>@endif
            </div>
        @endforeach
    </div>
    @if ($bannerRows->count() > 1)
    <div class="banner-dots mt-2 flex justify-center gap-1.5">
        @foreach ($bannerRows as $i => $b)
            <button type="button" data-dot="{{ $i }}" class="h-1.5 rounded-full bg-ink-300 transition-all dark:bg-ink-700" style="width: {{ $i === 0 ? '18px' : '6px' }}"></button>
        @endforeach
    </div>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('.banner-slider').forEach(function (slider) {
        var track = slider.querySelector('.banner-track');
        var count = parseInt(slider.dataset.count, 10);
        if (!track || count < 2) return;
        var dots = slider.querySelectorAll('[data-dot]');
        var i = 0, timer;
        function go(n) {
            i = (n + count) % count;
            track.style.transform = 'translateX(-' + (i * 100) + '%)';
            dots.forEach(function (d, idx) {
                d.style.width = idx === i ? '18px' : '6px';
                d.classList.toggle('bg-brand-500', idx === i);
                d.classList.toggle('bg-ink-300', idx !== i);
            });
        }
        function start() { timer = setInterval(function () { go(i + 1); }, 4500); }
        function stop() { clearInterval(timer); }
        dots.forEach(function (d) {
            d.addEventListener('click', function () { stop(); go(parseInt(d.dataset.dot, 10)); start(); });
        });
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        go(0); start();
    });
})();
</script>
@endpush
@endonce
@endif