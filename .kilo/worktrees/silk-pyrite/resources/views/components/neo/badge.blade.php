@props([
    'variant' => 'secondary', // success, info, warning, danger, secondary
    'pill' => true,
    'size' => 'sm', // sm, md
])
@php
    $base = 'inline-flex items-center font-medium ' . ($pill ? 'rounded-full' : 'rounded-lg');
    $sizeClass = $size === 'md' ? ' px-3 py-1 text-sm' : ' px-2.5 py-0.5 text-xs';
    $variants = [
        'success' => ' bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'info' => ' bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'warning' => ' bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'danger' => ' bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'secondary' => ' bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    ];
    $classes = $base . $sizeClass . ($variants[$variant] ?? $variants['secondary']);
@endphp
<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
