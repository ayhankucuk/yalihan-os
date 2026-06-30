@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2';
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:bg-blue-700 dark:hover:bg-blue-800 dark:shadow-none',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 hover:scale-105 active:scale-95 focus:ring-red-500 shadow-md hover:shadow-lg dark:bg-red-700 dark:hover:bg-red-800 dark:shadow-none',
        'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100 hover:scale-105 active:scale-95 focus:ring-gray-400 dark:text-gray-300 dark:hover:bg-gray-800',
    ];
    $class = trim($base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($attributes['class'] ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </button>
@endif
