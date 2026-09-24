@extends('layouts.admin')

@section('title', __('Banners'))

@section('header')
    <div>
        <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">{{ __('Dashboard banners') }}</h2>
        <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">{{ __('Sliding banners shown on the customer dashboard.') }}</p>
    </div>
@endsection

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-2xl border border-emerald-600/25 bg-emerald-50 p-3.5 text-[12.5px] font-medium text-emerald-800 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-2xl border border-brand-600/25 bg-brand-50 p-3.5 text-[12.5px] font-medium text-brand-800 dark:border-brand-400/25 dark:bg-brand-500/10 dark:text-brand-300">{{ $errors->first() }}</div>
@endif

{{-- Upload --}}
<section class="card mb-4 p-6">
    <h3 class="text-[14px] font-bold tracking-[-0.02em]">{{ __('Upload a banner') }}</h3>
    <p class="mt-0.5 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Wide image works best (e.g. 1200×400). Max 4MB.') }}</p>
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @csrf
        <div class="sm:col-span-2">
            <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Image') }}</label>
            <input type="file" name="image" accept="image/*" required
                   class="mt-1.5 block w-full text-[13px] file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-[12.5px] file:font-bold file:text-white hover:file:bg-brand-700">
        </div>
        <div>
            <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Link (optional)') }}</label>
            <input name="link" type="url" placeholder="https://…" class="mt-1.5 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
        </div>
        <div>
            <label class="text-[12px] font-semibold text-ink-700 dark:text-ink-200">{{ __('Sort order') }}</label>
            <input name="sort" type="number" min="0" value="0" class="mt-1.5 h-11 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-900">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary h-11 px-6 text-[13px]">{{ __('Upload banner') }}</button>
        </div>
    </form>
</section>

{{-- Existing --}}
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($banners as $b)
        <div class="card overflow-hidden {{ $b->active ? '' : 'opacity-55' }}">
            <div class="aspect-[3/1] w-full overflow-hidden bg-ink-100 dark:bg-ink-800">
                <img src="{{ route('media.show', $b->image) }}" alt="" class="h-full w-full object-cover">
            </div>
            <div class="p-3">
                @if ($b->title)<p class="truncate text-[12.5px] font-semibold">{{ $b->title }}</p>@endif
                @if ($b->link)<p class="truncate text-[11px] text-ink-400">{{ $b->link }}</p>@endif
                <div class="mt-2 flex items-center gap-1.5">
                    <form method="POST" action="{{ route('admin.banners.toggle', $b->id) }}">@csrf<button class="rounded-lg border border-ink-200 px-2.5 py-1 text-[11px] font-bold transition hover:bg-ink-100 dark:border-ink-700 dark:hover:bg-ink-800">{{ $b->active ? __('Hide') : __('Show') }}</button></form>
                    <form method="POST" action="{{ route('admin.banners.destroy', $b->id) }}" onsubmit="return confirm('{{ __('Delete this banner?') }}')">@csrf @method('DELETE')<button class="rounded-lg px-2.5 py-1 text-[11px] font-bold text-brand-600 transition hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">{{ __('Delete') }}</button></form>
                    <span class="ml-auto font-mono text-[10.5px] text-ink-400">#{{ $b->sort }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="card col-span-full py-16 text-center">
            <p class="text-[13px] font-semibold">{{ __('No banners yet') }}</p>
            <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Upload one above to show it on the dashboard.') }}</p>
        </div>
    @endforelse
</div>
@endsection