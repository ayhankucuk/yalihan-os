@props([
    'label' => null,
    'error' => null,
    'help' => null,
    'debounce' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'id' => null,
    'name' => null
])

@php
$inputId = $id ?? ($name ? str_replace(['[', ']'], ['_', ''], $name) : uniqid());
$hasError = $error !== null;
$baseClasses = 'block w-full px-4 py-2.5 border rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 dark:shadow-none';
$errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500/20';
$normalClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400';
$disabledClasses = 'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-gray-400';
$readonlyClasses = 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
@endphp

<div class="space-y-1">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $required ? 'required' : '' }}
            @if($debounce)
                x-model.debounce.{{ $debounce }}="$el.value"
            @endif
            class="{{ $baseClasses }} $hasError ? $errorClasses : $normalClasses $disabled $disabledClasses ($readonly $readonlyClasses '')"
            {{ $attributes }}
        />

        @if($attributes->has('x-model') && $debounce)
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400 animate-spin" x-show="false" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        @endif
    </div>

    @if($help && !$hasError)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
