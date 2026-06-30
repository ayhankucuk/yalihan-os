{{--
    Neo Design System Loading Component
    Context7 uyumlu loading göstergeleri

    Kullanım:
    <x-admin.animate-spin />  <!-- Varsayılan spinner -->
    <x-admin.animate-spin type="dots" />  <!-- Dots loader -->
    <x-admin.animate-spin type="bar" />  <!-- Progress bar -->
    <x-admin.animate-spin overlay="true" />  <!-- Full overlay -->

    @context7-compliant true
    @space-y-4 true
--}}

@props([
    'type' => 'spinner',
    'size' => 'md',
    'color' => 'primary',
    'overlay' => false,
    'message' => 'Yükleniyor...',
    'fullscreen' => false,
])

@php
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
        'xl' => 'w-12 h-12',
    ];

    $colorClasses = [
        'primary' => 'text-blue-600 dark:text-blue-400',
        'success' => 'text-green-600 dark:text-green-400',
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'danger' => 'text-red-600 dark:text-red-400',
        'gray' => 'text-gray-600 dark:text-gray-400',
    ];
@endphp

{{-- Full Screen Overlay --}}
@if ($fullscreen)
    <div class="fixed inset-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm z-[9999] flex items-center justify-center dark:bg-slate-900/90"
        role="presentation" aria-live="polite">
        <div class="text-center">
            @include('components.admin.partials.loading-' . $type, [
                'size' => 'xl',
                'color' => $color,
            ])
            <p class="mt-4 text-lg font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                {{ $message }}
            </p>
        </div>
    </div>
@elseif($overlay)
    {{-- Overlay Loading --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center" role="presentation" aria-live="polite">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-8 shadow-2xl"
            @include('components.admin.partials.loading-' . $type, [
                'size' => $size,
                'color' => $color,
            ])
            @if ($message)
                <p class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    {{ $message }}
                </p>
            @endif
        </div>
    </div>
@else
    {{-- Inline Loading --}}
    <div class="inline-flex items-center gap-2" role="presentation" aria-live="polite">
        @if ($type === 'spinner')
            <svg class="animate-spin {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} $colorClasses[$color] $colorClasses['primary']"
                fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        @elseif($type === 'dots')
            <div class="flex space-x-2" aria-hidden="true">
                <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: currentColor; animation-delay: 0ms"></div>
                <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: currentColor; animation-delay: 150ms"></div>
                <div class="w-2 h-2 rounded-full animate-bounce" style="background-color: currentColor; animation-delay: 300ms"></div>
            </div>
        @elseif($type === 'bar')
            <div class="w-full h-1 bg-gray-200 dark:bg-slate-900 rounded-full overflow-hidden" aria-hidden="true">
                <div class="h-full {{ $colorClasses[$color] ?? $colorClasses['primary'] }} animate-pulse" style="width: 100%; animation: progress 1.5s ease-in-out infinite;"></div>
            </div>
        @elseif($type === 'pulse')
            <div class="bg-gray-200 dark:bg-gray-700 animate-pulse {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} rounded-full"
                aria-hidden="true"></div>
        @endif

        @if ($message && !$overlay && !$fullscreen)
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $message }}</span>
        @endif

        <span class="sr-only">{{ $message }}</span>
    </div>
@endif
