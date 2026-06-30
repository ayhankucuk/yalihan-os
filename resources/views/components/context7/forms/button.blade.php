@props([
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, ghost, link
    'size' => 'md', // xs, sm, md, lg, xl
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'fullWidth' => false,
    'type' => 'button',
])

@php
    $buttonClasses = [
        'inline-flex',
        'items-center',
        'justify-center',
        'font-medium',
        'rounded-lg',
        'transition-all',
        'duration-200',
        'focus:outline-none',
        'focus:ring-2',
        'focus:ring-offset-2',
        'disabled:opacity-50',
        'disabled:cursor-not-allowed',
        'disabled:pointer-events-none',
    ];

    // Variant classes
    $variantClasses = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 shadow-sm hover:shadow-md dark:shadow-none',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 shadow-sm hover:shadow-md dark:shadow-none',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 shadow-sm hover:shadow-md dark:shadow-none',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm hover:shadow-md dark:shadow-none',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500 shadow-sm hover:shadow-md dark:shadow-none',
        'info' => 'bg-cyan-600 text-white hover:bg-cyan-700 focus:ring-cyan-500 shadow-sm hover:shadow-md dark:shadow-none',
        'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500 border border-gray-300 dark:text-slate-300',
        'link' =>
            'bg-transparent text-blue-600 hover:text-blue-800 focus:ring-blue-500 underline-offset-4 hover:underline',
    ];

    // Size classes
    $sizeClasses = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-4 py-2.5 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
        'xl' => 'px-8 py-4 text-lg',
    ];

    $buttonClasses = array_merge($buttonClasses, [
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
    ]);

    if ($fullWidth) {
        $buttonClasses[] = 'w-full';
    }

    if ($loading) {
        $buttonClasses[] = 'cursor-wait';
    }
@endphp

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => implode(' ', $buttonClasses),
        'disabled' => $disabled || $loading,
    ]) }}>
    @if ($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-dynamic-component :component="$icon" class="h-4 w-4 mr-2" />
    @endif

    @if ($loading)
        Yükleniyor...
    @else
        {{ $slot }}
    @endif

    @if ($icon && $iconPosition === 'right' && !$loading)
        <x-dynamic-component :component="$icon" class="h-4 w-4 ml-2" />
    @endif
</button>
