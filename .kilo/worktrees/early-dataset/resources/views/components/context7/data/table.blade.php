@props([
    'data' => [],
    'columns' => [],
    'sortable' => true,
    'searchable' => true,
    'filterable' => true,
    'pagination' => true,
    'striped' => true,
    'hover' => true,
    'bordered' => true,
    'size' => 'md', // sm, md, lg
    'emptyMessage' => 'Veri bulunamadı',
    'loading' => false,
])

@php
    $tableClasses = ['min-w-full', 'divide-y', 'divide-gray-200', 'bg-white dark:bg-slate-900'];

    if ($bordered) {
        $tableClasses[] = 'border border-gray-200 dark:border-slate-700';
    }

    $tableClasses[] = 'rounded-lg overflow-hidden';

    $theadClasses = ['bg-gray-50 dark:bg-slate-900'];
    $tbodyClasses = ['bg-white divide-y divide-gray-200 dark:bg-slate-900'];

    if ($striped) {
        $tbodyClasses[] = 'divide-y divide-gray-200';
    }

    $trClasses = [];
    if ($hover) {
        $trClasses[] = 'hover:bg-gray-50 transition-colors duration-150';
    }

    // Size classes
    $sizeClasses = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];
@endphp

<div class="space-y-4">
    @if ($searchable || $filterable)
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            @if ($searchable)
                <div class="w-full sm:w-64">
                    <x-context7.forms.input type="search" placeholder="Ara..." class="search-input" />
                </div>
            @endif

            @if ($filterable)
                <div class="flex gap-2">
                    <select class="filter-select rounded-lg border-gray-300 text-sm">
                        <option value="">Tümü</option>
                        <!-- Filter options will be added dynamically -->
                    </select>
                </div>
            @endif
        </div>
    @endif

    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => implode(' ', $tableClasses)]) }}>
            <thead class="{{ implode(' ', $theadClasses) }}">
                <tr>
                    @foreach ($columns as $column)
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider {{ $sizeClasses[$size] ?? $sizeClasses['md'] }}">
                            @if ($sortable && isset($column['sortable']) && $column['sortable'])
                                <button class="flex items-center space-x-1 hover:text-gray-700 transition-colors">
                                    <span>{{ $column['label'] ?? $column }}</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                    </svg>
                                </button>
                            @else
                                {{ $column['label'] ?? $column }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="{{ implode(' ', $tbodyClasses) }}">
                @if ($loading)
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center space-y-2">
                                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Yükleniyor...</span>
                            </div>
                        </td>
                    </tr>
                @elseif(empty($data))
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center space-y-2">
                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <span>{{ $emptyMessage }}</span>
                            </div>
                        </td>
                    </tr>
                @else
                    @foreach ($data as $row)
                        <tr class="{{ implode(' ', $trClasses) }}">
                            @foreach ($columns as $column)
                                <td
                                    class="px-6 py-4 whitespace-nowrap {{ $sizeClasses[$size] ?? $sizeClasses['md'] }} text-gray-900 dark:text-slate-100 dark:text-white">
                                    @if (isset($column['render']) && is_callable($column['render']))
                                        {!! $column['render']($row) !!}
                                    @elseif(isset($column['key']))
                                        {{ data_get($row, $column['key']) }}
                                    @else
                                        {{ $row[$column] ?? '' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if ($pagination && !$loading)
        <div class="flex items-center justify-between px-6 py-3 bg-white border-t border-gray-200 dark:bg-slate-900 dark:border-slate-700">
            <div class="flex items-center text-sm text-gray-700 dark:text-slate-300">
                <span>Toplam {{ count($data) }} kayıt</span>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Pagination controls will be added here -->
                <button
                    class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Önceki
                </button>
                <button
                    class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Sonraki
                </button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // Sort functionality
            const sortButtons = document.querySelectorAll('th button');
            sortButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Sort logic will be implemented here
                    console.log('Sort clicked');
                });
            });
        });
    </script>
@endpush
