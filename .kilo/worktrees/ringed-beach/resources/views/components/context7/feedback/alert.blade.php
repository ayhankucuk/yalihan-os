@props([
    'type' => 'info', // info, success, warning, error
    'title' => null,
    'dismissible' => true,
    'icon' => true,
    'size' => 'md', // sm, md, lg
    'variant' => 'filled', // filled, outlined, subtle
])

@php
    $alertClasses = ['rounded-lg', 'p-4', 'transition-all', 'duration-200'];

    // Type classes
    $typeClasses = [
        'info' => [
            'filled' => 'bg-blue-50 border border-blue-200 text-blue-800',
            'outlined' => 'bg-transparent border-2 border-blue-300 text-blue-800',
            'subtle' => 'bg-blue-25 border border-blue-100 text-blue-700',
        ],
        'success' => [
            'filled' => 'bg-green-50 border border-green-200 text-green-800',
            'outlined' => 'bg-transparent border-2 border-green-300 text-green-800',
            'subtle' => 'bg-green-25 border border-green-100 text-green-700',
        ],
        'warning' => [
            'filled' => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
            'outlined' => 'bg-transparent border-2 border-yellow-300 text-yellow-800',
            'subtle' => 'bg-yellow-25 border border-yellow-100 text-yellow-700',
        ],
        'error' => [
            'filled' => 'bg-red-50 border border-red-200 text-red-800',
            'outlined' => 'bg-transparent border-2 border-red-300 text-red-800',
            'subtle' => 'bg-red-25 border border-red-100 text-red-700',
        ],
    ];

    // Size classes
    $sizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];

    $alertClasses = array_merge($alertClasses, [
        $typeClasses[$type][$variant] ?? $typeClasses['info']['filled'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
    ]);

    // Icon classes
    $iconClasses = [
        'info' => 'text-blue-400',
        'success' => 'text-green-400',
        'warning' => 'text-yellow-400',
        'error' => 'text-red-400',
    ];

    $iconSvg = [
        'info' =>
            '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />',
        'success' =>
            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />',
        'warning' =>
            '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />',
        'error' =>
            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />',
    ];
@endphp

<div {{ $attributes->merge(['class' => implode(' ', $alertClasses)]) }} x-data="{ show: true }" x-show="show"
    x-transition>
    <div class="flex">
        @if ($icon)
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 {{ $iconClasses[$type] ?? $iconClasses['info'] }}" fill="currentColor"
                    viewBox="0 0 20 20">
                    {!! $iconSvg[$type] ?? $iconSvg['info'] !!}
                </svg>
            </div>
        @endif

        <div class="ml-3 flex-1">
            @if ($title)
                <h3 class="text-sm font-medium">
                    {{ $title }}
                </h3>
            @endif

            <div class="{{ $title ? 'mt-2' : '' }}">
                {{ $slot }}
            </div>
        </div>

        @if ($dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button"
                        class="inline-flex rounded-lg p-1.5 hover:bg-opacity-20 hover:bg-current focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-current transition-colors"
                        @click="show = false">
                        <span class="sr-only">Kapat</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
