@props([
    'variant' => 'default', // default, danger, success, warning
    'icon' => null,
    'href' => null,
])

@php
    $baseClasses = 'flex items-center w-full px-4 py-2 text-sm transition-colors duration-150';

    $variantClasses = [
        'default' => 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300',
        'danger' => 'text-red-600 hover:bg-red-50 hover:text-red-700',
        'success' => 'text-green-600 hover:bg-green-50 hover:text-green-700',
        'warning' => 'text-yellow-600 hover:bg-yellow-50 hover:text-yellow-700',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="mr-3">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <span class="mr-3">{!! $icon !!}</span>
        @endif
        {{ $slot }}
    </button>
@endif
