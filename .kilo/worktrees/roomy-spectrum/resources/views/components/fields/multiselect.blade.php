{{--
    Schema Field: Multiselect (tags/checkboxes)
    Props: $field (array from schema)
--}}
@props(['field', 'value' => []])

@php
    $selectedValues = old('features.' . $field['slug'], $value);
    if (is_string($selectedValues)) {
        $selectedValues = json_decode($selectedValues, true) ?? [];
    }
    $selectedValues = (array) $selectedValues;
@endphp

<div class="field-wrapper" data-field-slug="{{ $field['slug'] }}" data-field-type="multiselect"
    x-data="{ selected: @js($selectedValues) }">

    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-slate-300">
        @if($field['icon'] ?? false)
            <span class="text-base">{{ $field['icon'] }}</span>
        @endif
        {{ $field['name'] }}
        @if($field['required'] ?? false)
            <span class="text-red-500">*</span>
        @endif
        <span class="text-xs font-normal text-gray-400 dark:text-slate-500"
            x-text="selected.length > 0 ? `(${selected.length} seçili)` : ''">
        </span>
    </label>

    {{-- Hidden input for form submission --}}
    <input type="hidden" name="features[{{ $field['slug'] }}]" :value="JSON.stringify(selected)" />

    <div class="flex flex-wrap gap-2 rounded-xl border border-gray-200 bg-white/80 p-3 shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/80">
        @if(!empty($field['options']))
            @foreach($field['options'] as $option)
                <button type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
                    :class="selected.includes('{{ $option['value'] }}')
                        ? 'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-900/30 dark:border-blue-600 dark:text-blue-400 shadow-sm'
                        : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-blue-200 hover:bg-blue-50/50 dark:bg-slate-700/50 dark:border-slate-600 dark:text-slate-400 dark:hover:border-blue-700'"
                    @click="
                        if (selected.includes('{{ $option['value'] }}')) {
                            selected = selected.filter(v => v !== '{{ $option['value'] }}');
                        } else {
                            selected.push('{{ $option['value'] }}');
                        }
                        $dispatch('field-changed', { slug: '{{ $field['slug'] }}', value: selected, type: 'multiselect' })
                    ">
                    <span class="transition-transform duration-200"
                        :class="selected.includes('{{ $option['value'] }}') ? 'scale-110' : ''">
                        <template x-if="selected.includes('{{ $option['value'] }}')">
                            <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </template>
                        <template x-if="!selected.includes('{{ $option['value'] }}')">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </template>
                    </span>
                    {{ $option['label'] }}
                </button>
            @endforeach
        @endif
    </div>

    @if($field['help_text'] ?? false)
        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $field['help_text'] }}</p>
    @endif
</div>
