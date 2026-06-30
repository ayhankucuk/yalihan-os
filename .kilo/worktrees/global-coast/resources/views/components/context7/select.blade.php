{{-- ========================================
     CONTEXT7 SELECT COMPONENT
     ========================================
     Context7 Standardı: C7-SELECT-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'placeholder' => 'Seçiniz...',
    'required' => false,
    'disabled' => false,
    'size' => 'md',
    'state' => 'default', // default, error, success, warning
    'help' => null,
    'error' => null,
    'options' => [],
    'class' => '',
])

@php
    $baseClasses =
        'w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-800 dark:text-gray-100 transition-colors w-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 appearance-none bg-white dark:placeholder:text-slate-500';

    // Size classes
    $sizeClasses = [
        'sm' => 'px-4 py-2.5 text-sm rounded-lg',
        'md' => 'px-4 py-2 text-base rounded-lg',
        'lg' => 'px-4 py-3 text-lg rounded-lg',
    ];

    // State classes
    $stateClasses = [
        'default' => 'border-gray-300 focus:ring-primary-500 focus:border-primary-500',
        'error' => 'border-danger-500 focus:ring-danger-500 focus:border-danger-500 bg-danger-50',
        'success' => 'border-success-500 focus:ring-success-500 focus:border-success-500 bg-success-50',
        'warning' => 'border-warning-500 focus:ring-warning-500 focus:border-warning-500 bg-warning-50',
    ];

    $selectClasses = $baseClasses . ' ' . $sizeClasses[$size] . ' ' . $stateClasses[$state] . ' ' . $class;

    // Determine final state
    $finalState = $error ? 'error' : $state;
    $finalSelectClasses = str_replace($stateClasses[$state], $stateClasses[$finalState], $selectClasses);

    // Current value
    $currentValue = old($name, $value);
@endphp

<div class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200-wrapper dark:text-slate-100">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
            {{ $label }}
            @if ($required)
                <span class="text-danger-500 ml-1">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <select name="{{ $name }}" id="{{ $name }}" @if ($required) required @endif
            @if ($disabled) disabled @endif
            {{ $attributes->merge(['class' => $finalSelectClasses]) }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @if ($currentValue == $optionValue) selected @endif>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

        <!-- Custom dropdown arrow -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    @if ($help && !$error)
        <p class="mt-2 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if ($error)
        <p class="mt-2 text-sm text-danger-600">{{ $error }}</p>
    @endif
</div>
