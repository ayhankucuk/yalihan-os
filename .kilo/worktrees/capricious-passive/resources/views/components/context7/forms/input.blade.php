@props([
    'label' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'size' => 'md', // sm, md, lg
    'variant' => 'default', // default, error, success, warning
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'type' => 'text',
])

@php
    $inputId = $attributes->get('id') ?? 'input-' . Str::random(8);

    $inputClasses = [
        'block',
        'w-full',
        'border',
        'border-gray-300',
        'rounded-lg',
        'shadow-sm dark:shadow-none',
        'focus:ring-2',
        'focus:ring-blue-500',
        'focus:border-blue-500',
        'transition-colors',
        'duration-200',
        'disabled:bg-gray-100',
        'disabled:text-gray-500',
        'disabled:cursor-not-allowed',
        'readonly:bg-white',
        'readonly:cursor-default',
    ];

    // Size classes
    $sizeClasses = [
        'sm' => 'px-4 py-2.5 text-sm',
        'md' => 'px-4 py-3 text-base',
        'lg' => 'px-5 py-4 text-lg',
    ];

    // Variant classes
    $variantClasses = [
        'default' => 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
        'error' => 'border-red-300 focus:border-red-500 focus:ring-red-500',
        'success' => 'border-green-300 focus:border-green-500 focus:ring-green-500',
        'warning' => 'border-yellow-300 focus:border-yellow-500 focus:ring-yellow-500',
    ];

    $inputClasses = array_merge($inputClasses, [
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $variantClasses[$variant] ?? $variantClasses['default'],
    ]);

    if ($icon) {
        $inputClasses[] = $iconPosition === 'left' ? 'pl-10' : 'pr-10';
    }
@endphp

<div class="space-y-1">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
            {{ $label }}
            @if ($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if ($icon && $iconPosition === 'left')
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <x-dynamic-component :component="$icon" class="h-5 w-5 text-gray-400" />
            </div>
        @endif

        <input
            {{ $attributes->merge([
                'id' => $inputId,
                'type' => $type,
                'class' => implode(' ', $inputClasses),
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
            ]) }} />

        @if ($icon && $iconPosition === 'right')
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <x-dynamic-component :component="$icon" class="h-5 w-5 text-gray-400" />
            </div>
        @endif
    </div>

    @if ($help && !$error)
        <p class="text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if ($error)
        <p class="text-sm text-red-600 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            {{ $error }}
        </p>
    @endif
</div>
