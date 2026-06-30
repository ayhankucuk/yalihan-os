{{--
    Schema Field: Select Dropdown
    Props: $field (array from schema)
--}}
@props(['field', 'value' => ''])

<div class="field-wrapper" data-field-slug="{{ $field['slug'] }}" data-field-type="select">
    <label for="field_{{ $field['slug'] }}"
        class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-slate-300">
        @if($field['icon'] ?? false)
            <span class="text-base">{{ $field['icon'] }}</span>
        @endif
        {{ $field['name'] }}
        @if($field['required'] ?? false)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <select
        id="field_{{ $field['slug'] }}"
        name="features[{{ $field['slug'] }}]"
        @if($field['required'] ?? false) required @endif
        class="w-full appearance-none rounded-xl border border-gray-200 bg-white/80 px-4 py-3 text-sm text-gray-900 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
        x-on:change="$dispatch('field-changed', { slug: '{{ $field['slug'] }}', value: $el.value, type: 'select' })"
    >
        <option value="">{{ $field['placeholder'] ?? '-- Seçiniz --' }}</option>
        @if(!empty($field['options']))
            @foreach($field['options'] as $option)
                <option value="{{ $option['value'] }}"
                    {{ old('features.' . $field['slug'], $value) === $option['value'] ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        @endif
    </select>

    @if($field['help_text'] ?? false)
        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $field['help_text'] }}</p>
    @endif
</div>
