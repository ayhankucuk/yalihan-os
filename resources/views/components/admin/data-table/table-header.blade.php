{{-- resources/views/components/admin/data-table/table-header.blade.php --}}
@props([
    'column' => [],
    'sortable' => false,
    'currentSort' => null,
    'currentDirection' => 'asc',
    'sortUrl' => '#',
])

@php
    $isSortable = $sortable && ($column['sortable'] ?? false);
    $isActive = $currentSort === $column['key'];
    $direction = $isActive ? $currentDirection : 'asc';
@endphp

<th @if($isSortable) class="cursor-pointer select-none hover:bg-slate-100 dark:hover:bg-slate-700" @endif
    {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 ' . ($column['class'] ?? '')]) }}>
    @if($isSortable)
    <a href="{{ $sortUrl }}"
       class="inline-flex items-center gap-1.5 group">
        <span>{{ $column['label'] ?? '' }}</span>
        <span class="flex flex-col">
            <svg class="w-3 h-3 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300
                {{ $isActive && $direction === 'asc' ? 'text-indigo-600 dark:text-indigo-400' : '' }}"
                fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 5l7 7H5z"/>
            </svg>
            <svg class="w-3 h-3 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300
                {{ $isActive && $direction === 'desc' ? 'text-indigo-600 dark:text-indigo-400' : '' }}"
                fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 19l-7-7h14z"/>
            </svg>
        </span>
    </a>
    @else
    <span>{{ $column['label'] ?? '' }}</span>
    @endif
</th>
