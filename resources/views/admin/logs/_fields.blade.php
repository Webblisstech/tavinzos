@php $p = $p ?? null; @endphp

<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Name') }}</label>
    <input name="name" value="{{ old('name', $p->name ?? '') }}" required
           class="mt-1 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">
</div>
<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Category') }}</label>
    @php $cats = $cats ?? \Illuminate\Support\Facades\DB::table('log_categories')->where('is_active', true)->orderBy('sort')->orderBy('name')->get(); @endphp
    <select name="log_category_id" required
            class="mt-1 h-11 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">
        <option value="">{{ __('Select a category…') }}</option>
        @foreach ($cats as $cat)
            <option value="{{ $cat->id }}" @selected(old('log_category_id', $p->log_category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </select>
    @if ($cats->isEmpty())
        <p class="mt-1 text-[11px] text-brand-700 dark:text-brand-400">
            {{ __('No categories yet — ') }}<a href="{{ route('admin.categories.index') }}" class="underline">{{ __('create one first') }}</a>.
        </p>
    @endif
</div>
<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Country (optional)') }}</label>
    <input name="country" value="{{ old('country', $p->country ?? '') }}" list="country-list" placeholder="{{ __('Start typing…') }}"
           class="mt-1 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">
    <datalist id="country-list">
        @foreach (['USA','United Kingdom','Canada','Spain','Germany','France','Italy','Netherlands','Poland','Portugal','Indonesia','India','Philippines','Vietnam','Thailand','Malaysia','Nigeria','Ghana','Kenya','South Africa','Egypt','Morocco','Brazil','Mexico','Argentina','Colombia','Turkey','Russia','Ukraine','Australia','Japan','Korea','China','Pakistan','Bangladesh','Saudi Arabia','UAE'] as $country)
            <option value="{{ $country }}">
        @endforeach
    </datalist>
    <p class="mt-1 text-[10.5px] text-ink-500 dark:text-ink-400">{{ __('The flag is set automatically from the country.') }}</p>
</div>
<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Product image (optional)') }}</label>
    @if (($p->image ?? null))
        <div class="mt-1 mb-2 flex items-center gap-2">
            <img src="{{ route('media.show', $p->image) }}" alt="" class="h-10 w-10 rounded-lg object-cover ring-1 ring-ink-200 dark:ring-ink-800">
            <span class="text-[11px] text-ink-500 dark:text-ink-400">{{ __('Current — upload to replace') }}</span>
        </div>
    @endif
    <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
           class="mt-1 w-full rounded-xl border border-ink-300 p-2 text-[12px] file:mr-2 file:rounded-md file:border-0 file:bg-ink-200 file:px-2 file:py-1 file:text-[11px] file:font-semibold dark:border-ink-700 dark:bg-ink-950 dark:file:bg-ink-800">
    <p class="mt-1 text-[10.5px] text-ink-500 dark:text-ink-400">{{ __('Falls back to the category logo if left empty.') }}</p>
</div>
<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Price') }}</label>
    <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $p->price ?? '') }}" required
           class="mt-1 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-950">
</div>
<div>
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Max per order') }}</label>
    <input name="max_per_order" type="number" min="1" value="{{ old('max_per_order', $p->max_per_order ?? 50) }}" required
           class="mt-1 w-full rounded-xl border-ink-300 font-mono text-[13px] dark:border-ink-700 dark:bg-ink-950">
</div>
<div class="sm:col-span-2">
    <label class="text-[11px] font-semibold text-ink-600 dark:text-ink-300">{{ __('Instructions (shown in the buy modal)') }}</label>
    <textarea name="instructions" rows="3"
              class="mt-1 w-full rounded-xl border-ink-300 text-[13px] dark:border-ink-700 dark:bg-ink-950">{{ old('instructions', $p->instructions ?? '') }}</textarea>
</div>
<div class="flex items-center gap-4 sm:col-span-2">
    <label class="flex items-center gap-2 text-[12px] font-semibold text-ink-600 dark:text-ink-300">
        <input type="hidden" name="pre_order" value="0">
        <input type="checkbox" name="pre_order" value="1" @checked(old('pre_order', $p->pre_order ?? false)) class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
        {{ __('Allow pre-order with no stock') }}
    </label>
    <label class="flex items-center gap-2 text-[12px] font-semibold text-ink-600 dark:text-ink-300">
        <input type="hidden" name="previewable" value="0">
        <input type="checkbox" name="previewable" value="1" @checked(old('previewable', $p->previewable ?? false)) class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
        {{ __('Browsable with preview links') }}
    </label>
    <label class="flex items-center gap-2 text-[12px] font-semibold text-ink-600 dark:text-ink-300">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p->is_active ?? true)) class="rounded border-ink-300 text-brand-600 focus:ring-brand-500">
        {{ __('Visible in store') }}
    </label>
</div>