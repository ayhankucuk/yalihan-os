@props([
    'color' => 'indigo',
])
@php
    $map = [
        'indigo' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        'green' => 'bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-300',
        'red' => 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        'yellow' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    ];
    $classes = $map[$color] ?? $map['indigo'];
@endphp
<span
    {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $classes]) }}>
    {{ $slot }}
</span>
