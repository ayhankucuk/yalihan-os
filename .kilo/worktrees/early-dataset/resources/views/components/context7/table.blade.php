{{-- ========================================
     CONTEXT7 TABLE COMPONENT
     ========================================
     Context7 Standardı: C7-TABLE-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'striped' => false,
    'hoverable' => true,
    'responsive' => true,
    'class' => '',
])

@php
    $baseClasses = 'min-w-full divide-y divide-gray-200 dark:divide-gray-700 min-w-full divide-y divide-gray-200 bg-white shadow-sm rounded-lg overflow-hidden dark:bg-slate-900 dark:shadow-none';

    if ($striped) {
        $baseClasses .= ' table-striped';
    }

    if ($hoverable) {
        $baseClasses .= ' hoverable';
    }

    $classes = $baseClasses . ' ' . $class;
@endphp

@if ($responsive)
    <div class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-responsive overflow-x-auto">
@endif

<table {{ $attributes->merge(['class' => $classes]) }}>
    @if (isset($header))
        <thead class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-header bg-gray-50 dark:bg-slate-900">
            {{ $header }}
        </thead>
    @endif

    <tbody class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-body bg-white dark:bg-slate-900">
        {{ $slot }}
    </tbody>

    @if (isset($footer))
        <tfoot class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-footer bg-gray-50 dark:bg-slate-900">
            {{ $footer }}
        </tfoot>
    @endif
</table>

@if ($responsive)
    </div>
@endif
