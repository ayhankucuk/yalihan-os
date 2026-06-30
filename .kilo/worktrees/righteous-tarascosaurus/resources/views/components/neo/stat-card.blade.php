@props([
    'title' => '',
    'value' => 0,
    'icon' => 'chart-bar',
    'color' => 'blue',
    'href' => null,
    'trend' => null,
    'trendValue' => null
])

@php
$colorClasses = [
    'blue' => 'from-blue-500 to-blue-600 bg-blue-50 border-blue-200 text-blue-800',
    'green' => 'from-green-500 to-green-600 bg-green-50 border-green-200 text-green-800',
    'purple' => 'from-purple-500 to-purple-600 bg-purple-50 border-purple-200 text-purple-800',
    'orange' => 'from-orange-500 to-orange-600 bg-orange-50 border-orange-200 text-orange-800',
    'red' => 'from-red-500 to-red-600 bg-red-50 border-red-200 text-red-800',
    'indigo' => 'from-indigo-500 to-indigo-600 bg-indigo-50 border-indigo-200 text-indigo-800',
];

$iconClasses = [
    'chart-bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    'check-circle' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'eye' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
    'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
    'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'currency-dollar' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'
];

$selectedColor = $colorClasses[$color] ?? $colorClasses['blue'];
$selectedIcon = $iconClasses[$icon] ?? $iconClasses['chart-bar'];
@endphp

<div class="bg-gradient-to-r {{ $selectedColor }} rounded-xl border shadow-sm p-6 transition-all duration-200 hover:shadow-md hover:scale-105 dark:shadow-none">
    @if($href)
        <a href="{{ $href }}" class="flex items-center group">
    @else
        <div class="flex items-center">
    @endif
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-gradient-to-r {{ $selectedColor }} rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $selectedIcon }}" />
                </svg>
            </div>
        </div>
        <div class="ml-4 flex-1">
            <h4 class="text-sm font-medium {{ $selectedColor }}">{{ $title }}</h4>
            <div class="flex items-center space-x-2">
                <p class="text-2xl font-bold {{ $selectedColor }}">{{ number_format($value) }}</p>
                @if($trend && $trendValue)
                    <div class="flex items-center space-x-1">
                        @if($trend === 'up')
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                            <span class="text-sm text-green-600">+{{ $trendValue }}%</span>
                        @elseif($trend === 'down')
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                            <span class="text-sm text-red-600">-{{ $trendValue }}%</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @if($href)
        </a>
    @else
        </div>
    @endif
</div>
