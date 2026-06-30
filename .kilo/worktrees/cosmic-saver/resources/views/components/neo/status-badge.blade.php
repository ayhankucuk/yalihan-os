@props(['type' => 'info', 'label'])

@php
    $colors = [
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    ];
    $class = $colors[$type] ?? $colors['info'];
@endphp

<span {{ $attributes->merge(['class' => "px-2.5 py-0.5 rounded-full text-xs font-medium $class"]) }}>
    {{ $label ?? $slot }}
</span>
