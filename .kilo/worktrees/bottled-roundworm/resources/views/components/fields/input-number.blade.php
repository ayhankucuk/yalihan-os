{{--
    Schema Field: Number Input
    Props: $field (array from schema)
--}}
@props(['field', 'value' => ''])

<div class="field-wrapper" data-field-slug="{{ $field['slug'] }}" data-field-type="number">
    <label for="field_{{ $field['slug'] }}"
        class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-slate-300">
        @if($field['icon'] ?? false)
            <span class="text-base">{{ $field['icon'] }}</span>
        @endif
        {{ $field['name'] }}
        @if($field['required'] ?? false)
            <span class="text-red-500">*</span>
        @endif
        @if($field['ai_auto_fill'] ?? false)
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                title="AI otomatik doldurma">
                ⚡ Auto
            </span>
        @endif
    </label>

    <div class="relative">
        <input type="number"
            id="field_{{ $field['slug'] }}"
            name="features[{{ $field['slug'] }}]"
            value="{{ old('features.' . $field['slug'], $value) }}"
            placeholder="{{ $field['placeholder'] ?? '0' }}"
            @if($field['required'] ?? false) required @endif
            @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
            @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
            @if(isset($field['step'])) step="{{ $field['step'] }}" @else step="any" @endif
            class="w-full rounded-xl border border-gray-200 bg-white/80 px-4 py-3 text-sm text-gray-900 shadow-sm backdrop-blur-sm transition-all duration-200 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/20 {{ ($field['unit'] ?? false) ? 'pr-16' : '' }}"
            x-on:input.debounce.500ms="$dispatch('field-changed', { slug: '{{ $field['slug'] }}', value: $el.value, type: 'number' })"
        />

        @if($field['unit'] ?? false)
            <span class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500 dark:bg-slate-700 dark:text-slate-400">
                {{ $field['unit'] }}
            </span>
        @endif
    </div>

    @if($field['help_text'] ?? false)
        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $field['help_text'] }}</p>
    @endif
</div>
