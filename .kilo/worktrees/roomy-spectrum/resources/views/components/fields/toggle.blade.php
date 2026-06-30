{{--
    Schema Field: Toggle (Boolean)
    Props: $field (array from schema)
--}}
@props(['field', 'value' => false])

@php
    $isChecked = old('features.' . $field['slug'], $value) ? true : false;
@endphp

<div class="field-wrapper" data-field-slug="{{ $field['slug'] }}" data-field-type="boolean">
    <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white/80 px-4 py-3 shadow-sm backdrop-blur-sm transition-all duration-200 hover:border-blue-300 dark:border-slate-700 dark:bg-slate-800/80 dark:hover:border-blue-600"
        x-data="{ enabled: {{ $isChecked ? 'true' : 'false' }} }">

        <div class="flex items-center gap-3">
            @if($field['icon'] ?? false)
                <span class="text-lg">{{ $field['icon'] }}</span>
            @endif
            <div>
                <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">{{ $field['name'] }}</span>
                @if($field['help_text'] ?? false)
                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $field['help_text'] }}</p>
                @endif
            </div>
        </div>

        <div class="relative">
            <input type="hidden" name="features[{{ $field['slug'] }}]" :value="enabled ? '1' : '0'" />
            <button type="button"
                class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
                :class="enabled ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-200 dark:bg-slate-600'"
                @click="enabled = !enabled; $dispatch('field-changed', { slug: '{{ $field['slug'] }}', value: enabled, type: 'boolean' })"
                role="switch"
                :aria-checked="enabled.toString()">
                <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition-transform duration-300 ease-in-out dark:bg-slate-200"
                    :class="enabled ? 'translate-x-5' : 'translate-x-0'">
                </span>
            </button>
        </div>
    </div>
</div>
