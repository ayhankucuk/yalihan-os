@props([
    'variant' => 'default',
    'size' => 'md',
    'class' => ''
])

@php
    $baseClasses = 'inline-flex items-center font-medium rounded-full';

    $variantClasses = [
        'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        'primary' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'info' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
        'light' => 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        'dark' => 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
    ];

    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-1 text-sm',
        'md' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-4 py-2 text-base'
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size] . ' ' . $class;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
