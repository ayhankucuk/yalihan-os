@props([
    'variant' => 'default',
    'class' => '',
    'header' => null,
    'footer' => null
])

@php
    $baseClasses = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden dark:shadow-none';

    $variantClasses = [
        'default' => 'shadow-sm dark:shadow-none',
        'elevated' => 'shadow-lg hover:shadow-xl transition-shadow duration-200',
        'outlined' => 'border-2 border-gray-300 dark:border-gray-600',
        'gradient' => 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 border-blue-200 dark:border-gray-700',
        'glass' => 'bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-white/20 dark:border-gray-700/50'
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $class;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($header)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
            {{ $header }}
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if($footer)
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
            {{ $footer }}
        </div>
    @endif
</div>
