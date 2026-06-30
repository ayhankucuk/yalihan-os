{{-- ========================================
     CONTEXT7 TABLE ROW COMPONENT
     ========================================
     Context7 Standardı: C7-TABLE-ROW-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'hoverable' => true,
    'class' => '',
])

@php
    $baseClasses = 'min-w-full divide-y divide-gray-200 dark:divide-gray-700-row';

    if ($hoverable) {
        $baseClasses .= ' hover:bg-gray-50 transition-colors duration-150';
    }

    $classes = $baseClasses . ' ' . $class;
@endphp

<tr {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</tr>
