{{--
    Checkbox Component

    @component x-checkbox
    @description Accessible checkbox input with label and error handling

    @props
        - name: string (required) - Input name
        - label: string (required) - Checkbox label
        - value: string (optional) - Checkbox value - default: 1
        - checked: bool (optional) - Checked state - default: false
        - disabled: bool (optional) - Disabled state - default: false
        - error: string (optional) - Error message
        - help: string (optional) - Help text
        - id: string (optional) - Custom ID - default: name

    @example
        <x-checkbox
            name="featured"
            label="Featured Listing"
            :checked="old('featured', $ilan->featured ?? false)"
            help="Featured listings appear on the homepage"
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
    'value' => '1',
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'help' => null,
    'id' => null,
])

@php
$checkboxId = $id ?? 'checkbox-' . $name;
$hasError = !empty($error);
@endphp

<div class="flex items-start">
    {{-- Checkbox Input --}}
    <div class="flex items-center h-5">
        <input
            type="checkbox"
            id="{{ $checkboxId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="w-5 h-5 rounded border-gray-300 dark:border-gray-600
                   text-blue-600 dark:text-blue-500
                   focus:ring-2 focus:ring-blue-500 focus:ring-offset-0
                   disabled:opacity-50 disabled:cursor-not-allowed
                   transition-colors duration-200
                   {{ $hasError ? 'border-red-500 focus:ring-red-500' : '' }}"
            aria-describedby="{{ $help ? $checkboxId . '-help' : '' }} {{ $hasError ? $checkboxId . '-error' : '' }}"
            {{ $attributes }}
        />
    </div>

    {{-- Label & Help --}}
    <div class="ml-3">
        @if($label)
        <label
            for="{{ $checkboxId }}"
            class="text-sm font-medium text-gray-900 dark:text-white
                   {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
        >
            {{ $label }}
        </label>
        @endif

        @if($help)
        <p
            id="{{ $checkboxId }}-help"
            class="mt-1 text-xs text-gray-600 dark:text-gray-400"
        >
            {{ $help }}
        </p>
        @endif

        @if($hasError)
        <p
            id="{{ $checkboxId }}-error"
            class="mt-1 text-xs text-red-600 dark:text-red-400"
            role="alert"
        >
            {{ $error }}
        </p>
        @endif
    </div>
</div>
