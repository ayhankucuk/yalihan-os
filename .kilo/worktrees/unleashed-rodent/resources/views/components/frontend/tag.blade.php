@props(['href', 'active' => false])

@php
    $baseClasses = "px-4 py-2 rounded-full backdrop-blur-sm transition-all text-sm font-medium";
    $defaultClasses = "bg-white/10 dark:bg-slate-800/50 hover:bg-white/20 dark:hover:bg-slate-700/50 text-white border border-white/20 dark:border-white/10";
    $activeClasses = "bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 border border-yellow-500/30";

    $classes = $baseClasses . ' ' . ($active ? $activeClasses : $defaultClasses);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
