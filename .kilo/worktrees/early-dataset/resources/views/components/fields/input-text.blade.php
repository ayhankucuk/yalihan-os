{{--
    Schema Field: Text Input
    Props: $field (array from schema)
--}}
@props(['field', 'value' => ''])

<div class="field-wrapper" data-field-slug="{{ $field['slug'] }}" data-field-type="text">
    <label for="field_{{ $field['slug'] }}"
        class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-slate-300">
        @if($field['icon'] ?? false)
            <span class="text-base">{{ $field['icon'] }}</span>
        @endif
        {{ $field['name'] }}
        @if($field['required'] ?? false)
            <span class="text-red-500">*</span>
        @endif
        @if($field['ai_suggestion'] ?? false)
            <span class="inline-flex items-center rounded-full bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
                title="AI önerisi destekli">
                🤖 AI
            </span>
        @endif
    </label>

    <input type="text"
        id="field_{{ $field['slug'] }}"
        name="features[{{ $field['slug'] }}]"
        value="{{ old('features.' . $field['slug'], $value) }}"
        placeholder="{{ $field['placeholder'] ?? $field['name'] . ' giriniz' }}"
        @if($field['required'] ?? false) required @endif
        @if($field['max'] ?? false) maxlength="{{ $field['max'] }}" @endif
        class="w-full rounded-xl border border-gray-200 bg-white/80 px-4 py-3 text-sm text-gray-900 shadow-sm backdrop-blur-sm transition-all duration-200 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-400/20"
        x-on:input.debounce.500ms="$dispatch('field-changed', { slug: '{{ $field['slug'] }}', value: $el.value, type: 'text' })"
    />

    @if($field['help_text'] ?? false)
        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $field['help_text'] }}</p>
    @endif

    @if($field['unit'] ?? false)
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400 dark:text-slate-500">
            {{ $field['unit'] }}
        </span>
    @endif
</div>
