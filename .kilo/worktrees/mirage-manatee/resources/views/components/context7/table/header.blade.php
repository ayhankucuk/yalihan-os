{{-- ========================================
     CONTEXT7 TABLE HEADER COMPONENT
     ========================================
     Context7 Standardı: C7-TABLE-HEADER-COMPONENT
     Versiyon: 4.0.0 | Tarih: 15 Eylül 2025
     ======================================== --}}

@props([
    'sortable' => false,
    'sortDirection' => null,
    'class' => '',
])

@php
    $baseClasses =
        'min-w-full divide-y divide-gray-200 dark:divide-gray-700-header-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';

    if ($sortable) {
        $baseClasses .= ' cursor-pointer hover:bg-gray-100 select-none';
    }

    $classes = $baseClasses . ' ' . $class;
@endphp

<th {{ $attributes->merge(['class' => $classes]) }}
    @if ($sortable) @click="sortTable('{{ $attributes->get('data-sort') }}')" @endif>
    <div class="flex items-center space-x-1">
        <span>{{ $slot }}</span>

        @if ($sortable)
            <div class="flex flex-col">
                @if ($sortDirection === 'asc')
                    <svg class="w-3 h-3 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                @elseif($sortDirection === 'desc')
                    <svg class="w-3 h-3 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                @else
                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                @endif
            </div>
        @endif
    </div>
</th>
