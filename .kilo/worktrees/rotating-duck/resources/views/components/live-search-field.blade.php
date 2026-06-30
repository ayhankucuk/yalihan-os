{{--
    Live Search Field Component

    Merkezi live search component'i - Tüm formlarda kullanılabilir

    Kullanım:
    <x-live-search-field
        type="kisiler"
        name="kisi_id"
        label="Kişi"
        required
    />

    @version 1.0.0
    @since 2025-12-06
--}}

@props([
    'type' => 'kisiler', // kisiler, ilanlar, danismanlar
    'name' => 'search_field',
    'label' => '',
    'placeholder' => '',
    'required' => false,
    'value' => old($name . '_search', request($name . '_search', '')),
    'selectedId' => old($name, request($name, '')),
    'minQueryLength' => 2,
    'maxResults' => 10,
    'showNoResults' => true,
    'showLoadingIndicator' => true,
    'enableKeyboardNavigation' => true,
    'noResultsText' => 'Sonuç bulunamadı',
    'filters' => null, // JSON string veya array
    'autoSubmit' => false, // Seçim yapıldığında form submit et
    'formId' => null, // Form ID (autoSubmit için)
    'class' => '',
])

@php
    // Placeholder'lar
$placeholders = [
    'kisiler' => 'Kişi ara (ad, soyad, telefon)...',
    'ilanlar' => 'İlan ara (başlık, referans no)...',
    'danismanlar' => 'Danışman ara (ad, email)...',
    'sites' => 'Site/Apartman ara (ad, adres)...',
    'site-apartman' => 'Site/Apartman ara (ad, adres)...',
];

$placeholder = $placeholder ?: $placeholders[$type] ?? 'Arama yapın...';

// Label
$label = $label ?: ucfirst($type);

// Field ID
$fieldId = $name . '_' . uniqid();
@endphp

@php
    // Filters'i parse et
$filtersArray = null;
if ($filters) {
    if (is_string($filters)) {
        $filtersArray = json_decode($filters, true);
    } elseif (is_array($filters)) {
        $filtersArray = $filters;
    }
}

// Form submit için parent form'u bul
    $autoSubmit = $autoSubmit ?? false;
@endphp

<div class="relative {{ $class }}" x-data="liveSearch('{{ $type }}', '{{ $name }}', {
    minQueryLength: {{ $minQueryLength }},
    maxResults: {{ $maxResults }},
    showNoResults: {{ $showNoResults ? 'true' : 'false' }},
    showLoadingIndicator: {{ $showLoadingIndicator ? 'true' : 'false' }},
    enableKeyboardNavigation: {{ $enableKeyboardNavigation ? 'true' : 'false' }},
    noResultsText: '{{ addslashes($noResultsText) }}',
    filters: @js($filtersArray),
    @if ($autoSubmit) onSelect: (item) => {
        const form = document.getElementById('{{ $formId ?? 'kisiler-filter-form' }}') ||
                     this.$el.closest('form');
        if (form) {
            form.submit();
        }
    } @endif
})">
    {{-- Label --}}
    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Search Input --}}
    <div class="relative">
        <input type="text" id="{{ $fieldId }}" name="{{ $name }}_search" x-model="searchQuery"
            @input.debounce.300ms="search()" @focus="showDropdown = true" @blur="closeDropdown()"
            @keydown="handleKeydown($event)" placeholder="{{ $placeholder }}" autocomplete="off"
            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
            :class="{ 'border-red-500': error, 'opacity-50': loading }"
            @if ($required) required @endif>

        {{-- Loading Indicator --}}
        <div x-show="loading && showLoadingIndicator" x-cloak class="absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        {{-- Hidden Field --}}
        <input type="hidden" name="{{ $name }}" :value="selectedItem ? selectedItem.id : ''"
            value="{{ $selectedId }}">
    </div>

    {{-- Dropdown Results --}}
    <div x-show="showDropdown && results.length > 0" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-lg shadow-xl max-h-60 overflow-y-auto dark:border-slate-700"
        @click.away="showDropdown = false">

        <template x-for="(item, index) in results" :key="item.id">
            <div @click="selectItem(item)"
                class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150 border-b border-gray-100 dark:border-slate-800 last:border-b-0"
                :class="{ 'bg-blue-50 dark:bg-blue-900/30': highlightedIndex === index }">
                <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100" x-text="getDisplayText(item)"></div>
                <div class="text-sm text-gray-500 dark:text-gray-400"
                    x-show="item.telefon || item.email || item.referans_no">
                    <span x-show="item.telefon" x-text="'Tel: ' + item.telefon"></span>
                    <span x-show="item.email" x-text="'Email: ' + item.email"></span>
                    <span x-show="item.referans_no" x-text="'Ref: ' + item.referans_no"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- No Results --}}
    <div x-show="showDropdown && showNoResults && results.length === 0 && searchQuery.length >= minQueryLength && !loading"
        x-cloak
        class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-lg shadow-xl p-4 dark:border-slate-700">
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center"
            x-text="noResultsText || 'Sonuç bulunamadı'"></p>
    </div>

    {{-- Error Message --}}
    <div x-show="error" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="error"></div>

    {{-- Validation Error --}}
    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
