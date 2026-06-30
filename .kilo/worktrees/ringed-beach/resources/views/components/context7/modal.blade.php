{{-- ========================================
     CONTEXT7 MODAL COMPONENT
     ========================================
     Context7 Standardı: C7-MODAL-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'name' => 'modal',
    'size' => 'md', // sm, md, lg, xl, full
    'dismissible' => true,
    'centered' => true,
    'class' => '',
])

@php
    $baseClasses = 'fixed inset-0 z-50 flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto';

    // Size classes
    $sizeClasses = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-7xl',
    ];

    $classes = $baseClasses . ' ' . $class;
    $modalSize = $sizeClasses[$size];
@endphp

<div x-data="{ open: false }" @open-{{ $name }}.window="open = true"
    @close-{{ $name }}.window="open = false" x-show="open" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="{{ $classes }}" style="display: none;">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40 bg-gray-500 bg-opacity-75 transition-opacity"
        @if ($dismissible) @click="open = false" @endif></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full {{ $modalSize }} dark:bg-slate-900">

            <!-- Modal Header -->
            @if (isset($header))
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4-header bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            @if (isset($icon))
                                <div class="flex-shrink-0">
                                    {{ $icon }}
                                </div>
                            @endif
                            <div class="ml-3">
                                {{ $header }}
                            </div>
                        </div>
                        @if ($dismissible)
                            <button type="button"
                                class="rounded-lg bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-slate-900"
                                @click="open = false">
                                <span class="sr-only">Kapat</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Modal Body -->
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4-body bg-white px-4 pt-5 pb-4 sm:p-6 dark:bg-slate-900">
                {{ $slot }}
            </div>

            <!-- Modal Footer -->
            @if (isset($footer))
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center p-4-footer bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:bg-slate-900 dark:border-slate-700">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
