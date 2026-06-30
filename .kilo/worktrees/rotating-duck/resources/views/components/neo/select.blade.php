@props([
    'label' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'placeholder' => 'Seçiniz...',
    'value' => null,
    'id' => null,
    'name' => null,
    'options' => []
])

@php
$inputId = $id ?? ($name ? str_replace(['[', ']'], ['_', ''], $name) : uniqid());
$hasError = $error !== null;
$baseClasses = 'block w-full px-4 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 appearance-none bg-white dark:bg-gray-800 dark:shadow-none';
$errorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500/20';
$normalClasses = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 dark:border-gray-600 dark:text-white';
$disabledClasses = 'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-800 dark:text-gray-400';
$readonlyClasses = 'bg-white text-black dark:bg-gray-800 dark:text-gray-300';
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
        <select
            id="{{ $inputId }}"
            name="{{ $name }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $required ? 'required' : '' }}
            class="{{ $baseClasses }} $hasError ? $errorClasses : $normalClasses $disabled $disabledClasses ($readonly $readonlyClasses '')"
            {{ $attributes }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @if(is_array($options))
                @foreach($options as $key => $option)
                    @if(is_array($option))
                        <option value="{{ $key }}" {{ $value == $key ? 'selected' : '' }}>
                            {{ $option['label'] ?? $option['text'] ?? $option }}
                        </option>
                    @else
                        <option value="{{ $key }}" {{ $value == $key ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endif
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>

        <!-- Custom dropdown arrow -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if($help && !$hasError)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if($hasError)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
