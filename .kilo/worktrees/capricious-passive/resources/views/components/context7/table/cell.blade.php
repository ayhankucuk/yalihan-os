{{-- ========================================
     CONTEXT7 TABLE CELL COMPONENT
     ========================================
     Context7 Standardı: C7-TABLE-CELL-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'class' => '',
])

@php
    $baseClasses = 'min-w-full divide-y divide-gray-200 dark:divide-gray-700-cell px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100';
    $classes = $baseClasses . ' ' . $class;
@endphp

<td {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</td>
