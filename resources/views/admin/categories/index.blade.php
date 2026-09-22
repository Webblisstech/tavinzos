@extends('layouts.admin')

@section('title', __('Categories'))

@section('header')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-[22px] font-bold leading-tight tracking-[-0.035em] text-ink-900 dark:text-ink-50">
                {{ __('Categories') }}
            </h2>
            <p class="mt-1 text-[13px] text-ink-600 dark:text-ink-400">
                {{ __('The groups products are listed under in the store.') }}
            </p>
        </div>
        <a href="{{ route('admin.logs.index') }}" class="btn-ghost h-10 text-[13px]">{{ __('Back to accounts') }}</a>
    </div>
@endsection

@section('content')

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- New category --}}
    <section class="card p-5 lg:sticky lg:top-[88px] lg:self-start">
        <h3 class="text-[14px] font-bold">{{ __('New category') }}</h3>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-4 space-y-3">
            @csrf
            <div>
                <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Name') }}</label>
                <input name="name" value="{{ old('name') }}" required placeholder="Facebook"
                       class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
                @error('name')<p class="mt-1 text-[11px] font-medium text-brand-700 dark:text-brand-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Icon key (optional)') }}</label>
                <input name="icon" value="{{ old('icon') }}" placeholder="facebook"
                       class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-900">
            </div>
            <div>
                <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Sort order') }}</label>
                <input name="sort" type="number" value="{{ old('sort', 0) }}"
                       class="mt-1 h-11 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-900">
            </div>
            <label class="flex items-center gap-2 text-[12px] font-semibold text-ink-600 dark:text-ink-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                {{ __('Visible in store') }}
            </label>
            <button type="submit" class="btn-primary h-11 w-full text-[13px]">{{ __('Add category') }}</button>
        </form>
    </section>

    {{-- Existing categories --}}
    <div class="space-y-2.5 lg:col-span-2">
        @forelse ($categories as $c)
            <section class="card overflow-hidden">
                <div class="flex flex-wrap items-center gap-3 p-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200">
                        @include('partials.brand-icon', ['icon' => $c->icon, 'class' => 'h-5 w-5'])
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13.5px] font-semibold">{{ $c->name }}
                            @unless ($c->is_active)<span class="ml-1 rounded bg-ink-200 px-1.5 py-0.5 text-[10px] font-bold text-ink-600 dark:bg-ink-700 dark:text-ink-300">HIDDEN</span>@endunless
                        </p>
                        <p class="text-[11.5px] text-ink-500 dark:text-ink-400">{{ $c->products }} {{ __('products') }} · {{ __('sort') }} {{ $c->sort }}</p>
                    </div>
                    <button type="button" data-toggle="edit-c-{{ $c->id }}" class="btn-ghost h-9 text-[12px]">{{ __('Edit') }}</button>
                </div>

                <div id="edit-c-{{ $c->id }}" data-cloak class="border-t border-ink-200 bg-ink-50 p-4 dark:border-ink-800 dark:bg-ink-900">
                    <form method="POST" action="{{ route('admin.categories.update', $c->id) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @csrf
                        @method('PUT')
                        <div class="sm:col-span-2">
                            <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Name') }}</label>
                            <input name="name" value="{{ $c->name }}" required
                                   class="mt-1 h-10 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Sort') }}</label>
                            <input name="sort" type="number" value="{{ $c->sort }}"
                                   class="mt-1 h-10 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-950">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Icon key') }}</label>
                            <input name="icon" value="{{ $c->icon }}"
                                   class="mt-1 h-10 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">
                        </div>
                        <label class="flex items-center gap-2 text-[12px] font-semibold text-ink-600 sm:col-span-3 dark:text-ink-300">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
                            {{ __('Visible in store') }}
                        </label>
                        <div class="flex gap-2 sm:col-span-3">
                            <button type="submit" class="btn-primary h-10 px-5 text-[12px]">{{ __('Save') }}</button>
                            <button type="submit" formaction="{{ route('admin.categories.destroy', $c->id) }}"
                                    onclick="return confirm('Delete this category? If products use it, it will be hidden instead.')"
                                    class="btn-ghost h-10 px-5 text-[12px] text-brand-700 dark:text-brand-400">{{ __('Delete') }}</button>
                        </div>
                    </form>
                </div>
            </section>
        @empty
            <div class="card px-6 py-16 text-center">
                <p class="text-[13px] font-semibold">{{ __('No categories yet') }}</p>
                <p class="mt-1 text-[12px] text-ink-500 dark:text-ink-400">{{ __('Create one on the left — you need at least one before adding products.') }}</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = document.getElementById(btn.dataset.toggle);
            if (el) el.toggleAttribute('data-cloak');
        });
    });
</script>
@endpush