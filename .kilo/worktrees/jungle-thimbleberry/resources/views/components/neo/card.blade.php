@props([
    'padding' => 'default',
    'shadow dark:shadow-none' => 'default',
    'rounded' => 'default'
])

@php
$paddingClasses = [
    'none' => '',
    'sm' => 'p-4',
    'default' => 'p-6',
    'lg' => 'p-8',
    'xl' => 'p-10'
];

$shadowClasses = [
    'none' => '',
    'sm' => 'shadow-sm dark:shadow-none',
    'default' => 'shadow dark:shadow-none',
    'md' => 'shadow-md dark:shadow-none',
    'lg' => 'shadow-lg',
    'xl' => 'shadow-xl'
];

$roundedClasses = [
    'none' => '',
    'sm' => 'rounded-sm',
    'default' => 'rounded-lg',
    'md' => 'rounded-lg',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    '2xl' => 'rounded-2xl',
    'full' => 'rounded-full'
];

$selectedPadding = $paddingClasses[$padding] ?? $paddingClasses['default'];
$selectedShadow = $shadowClasses[$shadow] ?? $shadowClasses['default'];
$selectedRounded = $roundedClasses[$rounded] ?? $roundedClasses['default'];

$baseClasses = 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
$classes = "{$baseClasses} {$selectedPadding} {$selectedShadow} {$selectedRounded}";
@endphp

<div class="{{ $classes }}" {{ $attributes }}>
    @if(isset($header))
        <div class="mb-6 pb-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            {{ $header }}
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            {{ $footer }}
        </div>
    @endif
</div>
