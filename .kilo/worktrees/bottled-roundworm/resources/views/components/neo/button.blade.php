@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'href' => null,
    'target' => null,
    'icon' => null,
    'iconPosition' => 'left'
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = [
    'primary' => 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-sm focus:ring-blue-500/20 dark:shadow-none',
    'secondary' => 'bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white shadow-sm focus:ring-gray-500/20 dark:shadow-none',
    'success' => 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white shadow-sm focus:ring-green-500/20 dark:shadow-none',
    'danger' => 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white shadow-sm focus:ring-red-500/20 dark:shadow-none',
    'warning' => 'bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white shadow-sm focus:ring-yellow-500/20 dark:shadow-none',
    'info' => 'bg-gradient-to-r from-cyan-500 to-cyan-600 hover:from-cyan-600 hover:to-cyan-700 text-white shadow-sm focus:ring-cyan-500/20 dark:shadow-none',
    'ghost' => 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 focus:ring-gray-500/20',
    'link' => 'bg-transparent hover:bg-transparent text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline-offset-4 hover:underline focus:ring-blue-500/20'
];

$sizeClasses = [
    'xs' => 'px-2.5 py-1.5 text-xs',
    'sm' => 'px-4 py-2.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
    'xl' => 'px-8 py-4 text-lg'
];

$selectedVariant = $variantClasses[$variant] ?? $variantClasses['primary'];
$selectedSize = $sizeClasses[$size] ?? $sizeClasses['md'];
$classes = "{$baseClasses} {$selectedVariant} {$selectedSize}";

// Icon SVG paths
$iconPaths = [
    'plus' => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    'edit' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    'trash' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    'eye' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    'download' => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'upload' => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
    'search' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'filter' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z',
    'check' => 'M5 13l4 4L19 7',
    'x' => 'M6 18L18 6M6 6l12 12',
    'arrow-right' => 'M9 5l7 7-7 7',
    'arrow-left' => 'M15 19l-7-7 7-7',
    'external-link' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
    'copy' => 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z',
    'save' => 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4'
];

$iconPath = $iconPaths[$icon] ?? null;
@endphp

@if($href)
    <a href="{{ $href }}"
       @if($target) target="{{ $target }}" @endif
       class="{{ $classes }}"
       {{ $attributes->except(['href', 'target']) }}>
@else
    <button type="{{ $type }}"
            {{ $disabled ? 'disabled' : '' }}
            class="{{ $classes }}"
            {{ $attributes->except(['type', 'disabled']) }}>
@endif

    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Yükleniyor...
    @else
        @if($icon && $iconPosition === 'left')
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
            </svg>
        @endif

        {{ $slot }}

        @if($icon && $iconPosition === 'right')
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
            </svg>
        @endif
    @endif

@if($href)
    </a>
@else
    </button>
@endif
