@props([
    'type' => 'text',
    'variant' => 'default',
    'size' => 'md',
    'error' => null,
    'label' => null,
    'help' => null,
    'class' => '',
])

@php
    $baseClasses =
        'block w-full rounded-lg border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0';

    $variantClasses = [
        'default' => 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
        'error' => 'border-red-300 focus:border-red-500 focus:ring-red-500',
        'success' => 'border-green-300 focus:border-green-500 focus:ring-green-500',
        'warning' => 'border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500',
    ];

    $sizeClasses = [
        'sm' => 'px-4 py-2.5 text-sm',
        'md' => 'px-4 py-3 text-base',
        'lg' => 'px-5 py-4 text-lg',
    ];

    $classes =
        $baseClasses . ' ' . $variantClasses[$error ? 'error' : $variant] . ' ' . $sizeClasses[$size] . ' ' . $class;
@endphp

<div class="space-y-2">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
            {{ $label }}
            @if ($attributes->has('required'))
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    @php
        $hasName = $attributes->has('name');
        $fieldName = $hasName ? $attributes->get('name') : null;
        $resolvedValue = $hasName ? old($fieldName, $attributes->get('value')) : $attributes->get('value');
    @endphp
    <input type="{{ $type }}" {{ $attributes->except('value')->merge(['class' => $classes]) }}
        value="{{ $resolvedValue }}">

    @if ($help)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if ($error)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
