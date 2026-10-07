{{-- resources/views/components/admin/data-table/table-row.blade.php --}}
@props([
    'row' => null,
    'columns' => [],
    'bulkActions' => false,
    'checkboxName' => 'selected[]',
])

@php
    $rowId = $row->id ?? null;
@endphp

<tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
    {{-- Bulk Select Checkbox --}}
    @if($bulkActions)
    <td class="w-12 px-4 py-4">
        <input type="checkbox"
            name="{{ $checkboxName }}"
            value="{{ $rowId }}"
            x-model="selected"
            class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800"
        >
    </td>
    @endif

    {{-- Data Cells --}}
    @foreach($columns as $column)
    <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300 {{ $column['class'] ?? '' }}">
        @php $slotName = 'cell:' . ($column['key'] ?? '') @endphp
        @if(View::hasSlot($slotName))
            @stack($slotName)
        @else
            {{ $row->{$column['key']} ?? '' }}
        @endif
    </td>
    @endforeach
</tr>
