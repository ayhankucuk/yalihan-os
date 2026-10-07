{{-- resources/views/components/admin/data-table/index.blade.php --}}
@props([
    'columns' => [],
    'data' => collect([]),
    'sortable' => false,
    'filterable' => false,
    'bulkActions' => false,
    'pagination' => null,
    'emptyMessage' => 'No items found',
    'emptyAction' => null,
    'emptyActionText' => 'Add New',
    'searchPlaceholder' => 'Search...',
    'selectAllName' => 'select-all',
    'rowCheckboxName' => 'selected[]',
    'striped' => true,
])

@php
    $hasData = $data->isNotEmpty();
    $currentSort = request('sort');
    $currentDirection = request('direction', 'asc');

    // Sort URL generation
    $sortUrl = function($column) use ($currentSort, $currentDirection) {
        $params = request()->except(['sort', 'direction']);
        if ($currentSort === $column['key']) {
            $direction = $currentDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $direction = 'asc';
        }
        $params = array_merge($params, ['sort' => $column['key'], 'direction' => $direction]);
        return url()->current() . '?' . http_build_query($params);
    };
@endphp

<div x-data="{
    selected: [],
    viewMode: 'table',
    searchQuery: new URLSearchParams(window.location.search).get('q') || '',
    get selectedCount() { return this.selected.length },
    toggleAll(source) {
        if (source.target.checked) {
            this.selected = @json($data->pluck('id')->toArray());
        } else {
            this.selected = [];
        }
    },
    updateUrl() {
        const params = new URLSearchParams(window.location.search);
        if (this.searchQuery) {
            params.set('q', this.searchQuery);
        } else {
            params.delete('q');
        }
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }
}" class="space-y-4">

    {{-- Toolbar: Search & Actions --}}
    @if($filterable || $bulkActions)
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        {{-- Search --}}
        @if($filterable)
        <x-admin.data-table.search
            :placeholder="$searchPlaceholder"
            x-model="searchQuery"
            @keyup.enter="updateUrl()"
        />
        @endif

        <div class="flex items-center gap-3">
            {{-- View Toggle (Mobile Cards) --}}
            <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg">
                <button
                    @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
                    class="p-2 rounded-md transition-all"
                    title="Table view"
                >
                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <button
                    @click="viewMode = 'cards'"
                    :class="viewMode === 'cards' ? 'bg-white dark:bg-slate-700 shadow-sm' : ''"
                    class="p-2 rounded-md transition-all"
                    title="Card view"
                >
                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                </button>
            </div>

            {{-- Item Count --}}
            @if($pagination)
            <span class="text-sm text-slate-500 dark:text-slate-400">
                {{ $pagination->total() }} items
            </span>
            @endif
        </div>
    </div>
    @endif

    {{-- Bulk Actions Bar --}}
    @if($bulkActions)
    <x-admin.data-table.bulk-actions-bar />
    @endif

    {{-- Desktop Table View --}}
    <div x-show="viewMode === 'table'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="overflow-hidden">
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        {{-- Bulk Select Checkbox --}}
                        @if($bulkActions && $hasData)
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox"
                                class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-800"
                                @change="toggleAll($event)"
                                :checked="selected.length === @json($data->count()) && selected.length > 0"
                                :indeterminate="selected.length > 0 && selected.length < @json($data->count())"
                            >
                        </th>
                        @endif

                        {{-- Column Headers --}}
                        @foreach($columns as $column)
                        <x-admin.data-table.table-header
                            :column="$column"
                            :sortable="$sortable"
                            :current-sort="$currentSort"
                            :current-direction="$currentDirection"
                            :sort-url="$sortUrl($column)"
                        />
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 {{ $striped ? 'odd:bg-white even:bg-slate-50/50 dark:odd:bg-slate-900 dark:even:bg-slate-800/30' : 'bg-white dark:bg-slate-900' }}">
                    @forelse($data as $row)
                        <x-admin.data-table.table-row
                            :row="$row"
                            :columns="$columns"
                            :bulk-actions="$bulkActions"
                            :checkbox-name="$rowCheckboxName"
                        >
                            {{-- Custom cell slots --}}
                            @foreach($columns as $column)
                                @if(isset($column['key']))
                                    @php $slotName = 'cell:' . $column['key']; @endphp
                                    @if(isset($slots[$slotName]) || View::hasSlot($slotName))
                                        <x-slot name="{{ $slotName }}" :row="$row" :column="$column"></x-slot>
                                    @endif
                                @endif
                            @endforeach
                        </x-admin.data-table.table-row>
                    @empty
                        <x-admin.data-table.table-empty
                            :message="$emptyMessage"
                            :action="$emptyAction"
                            :action-text="$emptyActionText"
                            :columns-count="count($columns) + ($bulkActions ? 1 : 0)"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards View --}}
    <div x-show="viewMode === 'cards'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="md:hidden space-y-3">
        @forelse($data as $row)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    {{-- Mobile card content via slots --}}
                    @foreach($columns as $column)
                        @if(!in_array($column['key'], ['actions', '']))
                        <div class="mb-2">
                            <dt class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                {{ $column['label'] ?? '' }}
                            </dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white">
                                @if(isset($slots['cell:' . $column['key']]))
                                    {{ $slots['cell:' . $column['key']] }}
                                @else
                                    {{ $row->{$column['key']} ?? '' }}
                                @endif
                            </dd>
                        </div>
                        @endif
                    @endforeach
                </div>

                {{-- Card Checkbox --}}
                @if($bulkActions)
                <input type="checkbox"
                    class="mt-1 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"
                    x-model="selected"
                    value="{{ $row->id }}"
                >
                @endif
            </div>

            {{-- Card Actions --}}
            @php $actionsColumn = collect($columns)->firstWhere('key', 'actions') @endphp
            @if($actionsColumn)
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                @if(isset($slots['cell:actions']))
                    {{ $slots['cell:actions'] }}
                @endif
            </div>
            @endif
        </div>
        @empty
        <x-admin.data-table.table-empty
            :message="$emptyMessage"
            :action="$emptyAction"
            :action-text="$emptyActionText"
            :columns-count="1"
        />
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($pagination && $pagination->hasPages())
    <x-admin.data-table.pagination :pagination="$pagination" />
    @endif

    {{-- Selected items form input --}}
    @if($bulkActions)
    <input type="hidden" name="selected_ids" x-model="selected.join(',')">
    @endif
</div>
