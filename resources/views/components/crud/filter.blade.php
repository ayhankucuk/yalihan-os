@props([
    'route',
    'filters' => [],
    'searchPlaceholder' => 'Ara...',
    'showSearchInput' => true,
    'resetText' => 'Filtreleri Temizle',
    'searchText' => 'Ara',
    'advancedFilters' => false,
])

<div {{ $attributes->merge(['class' => 'mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700 dark:shadow-none']) }}>
    <form method="GET" action="{{ route($route) }}" class="space-y-4">
        @if($showSearchInput)
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $searchPlaceholder }}" class="pl-10 w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white dark:shadow-none">
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                        <span class="material-symbols-outlined mr-2">search</span> {{ $searchText }}
                    </button>

                    @if(request()->anyFilled(array_merge(['search'], array_column($filters, 'name'))))
                        <a href="{{ route($route) }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800 transition-colors duration-200 dark:text-slate-300">
                            <span class="material-symbols-outlined mr-2">close</span> {{ $resetText }}
                        </a>
                    @endif

                    @if($advancedFilters && count($filters) > 0)
                        <button type="button" id="toggle-filters" class="px-4 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-200">
                            <span class="material-symbols-outlined mr-2">filter_list</span> Filtreler
                        </button>
                    @endif
                </div>
            </div>
        @endif

        @if(count($filters) > 0)
            <div id="filters-container" class="{{ $advancedFilters ? 'hidden' : '' }} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                @foreach($filters as $filter)
                    @php
                        $filterName = $filter['name'];
                        $filterType = $filter['type'] ?? 'select';
                        $filterLabel = $filter['label'] ?? ucfirst($filterName);
                        $filterOptions = $filter['options'] ?? [];
                        $filterValue = request($filterName);
                    @endphp

                    <div>
                        <label for="filter_{{ $filterName }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                            {{ $filterLabel }}
                        </label>

                        @if($filterType === 'select')
                            <select id="filter_{{ $filterName }}" name="{{ $filterName }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white dark:shadow-none">
                                <option value="">Tümü</option>
                                @foreach($filterOptions as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}" {{ $filterValue == $optionValue ? 'selected' : '' }}>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($filterType === 'date')
                            <input type="date" id="filter_{{ $filterName }}" name="{{ $filterName }}" value="{{ $filterValue }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white dark:shadow-none">
                        @elseif($filterType === 'daterange')
                            <div class="flex space-x-2">
                                <input type="date" id="filter_{{ $filterName }}_start" name="{{ $filterName }}_start" value="{{ request($filterName.'_start') }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white dark:shadow-none" placeholder="Başlangıç">
                                <input type="date" id="filter_{{ $filterName }}_end" name="{{ $filterName }}_end" value="{{ request($filterName.'_end') }}" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-slate-900 dark:text-white dark:shadow-none" placeholder="Bitiş">
                            </div>
                        @elseif($filterType === 'checkbox')
                            <div class="flex items-center">
                                <input type="checkbox" id="filter_{{ $filterName }}" name="{{ $filterName }}" value="1" {{ $filterValue ? 'checked' : '' }} class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded dark:bg-slate-900">
                                <label for="filter_{{ $filterName }}" class="ml-2 block text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    {{ $filter['checkboxLabel'] ?? 'Evet' }}
                                </label>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </form>
</div>

@if($advancedFilters && count($filters) > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle-filters');
            const filtersContainer = document.getElementById('filters-container');

            if (toggleButton && filtersContainer) {
                toggleButton.addEventListener('click', function() {
                    filtersContainer.classList.toggle('hidden');

                    // İkon değiştirme
                    const icon = toggleButton.querySelector('i');
                    if (icon) {
                        if (filtersContainer.classList.contains('hidden')) {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-filter');
                        } else {
                            icon.classList.remove('fa-filter');
                            icon.classList.add('fa-times');
                        }
                    }
                });
            }
        });
    </script>
@endif
