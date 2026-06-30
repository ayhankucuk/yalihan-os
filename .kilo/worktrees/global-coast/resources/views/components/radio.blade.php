{{--
    Radio Button Component

    @component x-radio
    @description Accessible radio input with label and error handling

    @props
        - name: string (required) - Input name (same for all radios in group)
        - label: string (required) - Radio label
        - value: string (required) - Radio value
        - checked: bool (optional) - Checked state - default: false
        - disabled: bool (optional) - Disabled state - default: false
        - error: string (optional) - Error message (only show on last radio)
        - help: string (optional) - Help text
        - id: string (optional) - Custom ID - default: name-value

    @example
        <x-radio
            name="yayin_durumu"
            label="Yayında"
            value="published"
            :checked="old('yayin_durumu', $ilan->yayin_durumu) === 'published'"
        />
        <x-radio
            name="yayin_durumu"
            label="Taslak"
            value="draft"
            :checked="old('yayin_durumu', $ilan->yayin_durumu) === 'draft'"
        />

    @accessibility
        - ARIA labels
        - Keyboard navigation
        - Focus states
        - Error announcements
--}}

@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'id' => null,
])

@php
$radioId = $id ?? 'radio-' . $name . '-' . str_replace(' ', '-', strtolower($value));
$hasError = !empty($error);
@endphp

<div class="flex items-start">
    {{-- Radio Input --}}
    <div class="flex items-center h-5">
        <input
            type="radio"
            id="{{ $radioId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="w-4 h-4 border-gray-300 dark:border-gray-600
                   text-blue-600 dark:text-blue-500
                   focus:ring-2 focus:ring-blue-500 focus:ring-offset-0
                   disabled:opacity-50 disabled:cursor-not-allowed
                   transition-colors duration-200
                   {{ $hasError ? 'border-red-500 focus:ring-red-500' : '' }}"
            aria-describedby="{{ $help ? $radioId . '-help' : '' }} {{ $hasError ? $radioId . '-error' : '' }}"
            {{ $attributes }}
        />
    </div>

    {{-- Label & Help --}}
    <div class="ml-3">
        @if($label)
        <label
            for="{{ $radioId }}"
            class="text-sm font-medium text-gray-900 dark:text-white
                   {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
        >
            {{ $label }}
        </label>
        @endif

        @if($help)
        <p
            id="{{ $radioId }}-help"
            class="mt-1 text-xs text-gray-600 dark:text-gray-400"
        >
            {{ $help }}
        </p>
        @endif

        @if($hasError)
        <p
            id="{{ $radioId }}-error"
            class="mt-1 text-xs text-red-600 dark:text-red-400"
            role="alert"
        >
            {{ $error }}
        </p>
        @endif
    </div>
</div>
