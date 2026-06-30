{{--
🎨 ELEGANT FORM WRAPPER - Modern Form Design System
Context7: %100, Tailwind CSS ONLY
Version: 2.0 - Ultra Modern Edition
--}}

@props([
    'sectionId' => '',
    'title' => '',
    'subtitle' => '',
    'icon' => '',
    'badgeNumber' => '',
    'badgeColor' => 'blue', // blue, green, purple, orange, pink
    'glassEffect' => false,
])

@php
$colorMap = [
    'blue' => 'from-blue-500 to-indigo-600',
    'green' => 'from-green-500 to-emerald-600',
    'purple' => 'from-purple-500 to-pink-600',
    'orange' => 'from-orange-500 to-amber-600',
    'pink' => 'from-pink-500 to-rose-600',
    'cyan' => 'from-cyan-500 to-blue-600',
    'red' => 'from-red-500 to-rose-600',
];

$gradientClass = $colorMap[$badgeColor] ?? $colorMap['blue'];
@endphp

<div id="{{ $sectionId }}"
     class="group relative
            {{ $glassEffect ? 'bg-white/60 dark:bg-gray-900/60 backdrop-blur-xl' : 'bg-white dark:bg-gray-900' }}
            rounded-2xl
            border border-gray-200/50 dark:border-gray-700/50
            shadow-sm hover:shadow-xl
            transition-all duration-500 ease-out
            hover:scale-[1.01]
            overflow-hidden">

    {{-- Decorative Gradient Border (Top) --}}
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $gradientClass }} opacity-75"></div>

    {{-- Section Header --}}
    <div class="relative px-6 py-5 border-b border-gray-200/50 dark:border-gray-700/50">
        {{-- Background Glow Effect --}}
        <div class="absolute inset-0 bg-gradient-to-r {{ $gradientClass }} opacity-5"></div>

        <div class="relative flex items-center gap-4">
            {{-- Badge Number --}}
            @if($badgeNumber)
            <div class="flex items-center justify-center w-12 h-12
                        rounded-xl bg-gradient-to-br {{ $gradientClass }}
                        text-white shadow-lg shadow-{{ $badgeColor }}-500/30
                        font-bold text-lg
                        transform transition-transform duration-300
                        group-hover:scale-110 group-hover:rotate-3">
                {{ $badgeNumber }}
            </div>
            @endif

            {{-- Title & Subtitle --}}
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white
                           flex items-center gap-3
                           transition-colors duration-300">
                    @if($icon)
                    <span class="text-{{ $badgeColor }}-600 dark:text-{{ $badgeColor }}-400">
                        {!! $icon !!}
                    </span>
                    @endif
                    {{ $title }}
                </h2>
                @if($subtitle)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 font-medium">
                    {{ $subtitle }}
                </p>
                @endif
            </div>

            {{-- Optional Actions Slot --}}
            {{ $actions ?? '' }}
        </div>
    </div>

    {{-- Content Area --}}
    <div class="p-6 space-y-6">
        {{ $slot }}
    </div>
</div>

