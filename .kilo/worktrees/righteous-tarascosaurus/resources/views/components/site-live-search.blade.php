{{-- Site/Apartman Live Search Component --}}
{{-- Context7 Standard: C7-SITE-LIVE-SEARCH-COMPONENT-2025-10-17 --}}

@props([
    'name' => 'site_adi',
    'placeholder' => 'Site/Apartman adı yazın...',
    'required' => false,
    'label' => 'Site/Apartman',
    'help' => 'Site veya apartman adını yazmaya başlayın',
    'showCreateNew' => true,
])

<div class="space-y-2">
    {{-- Label --}}
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
        {{ $label }}
        @if ($required)
            <span class="text-red-500 ml-1">*</span>
        @endif
    </label>

    {{-- Live Search Container --}}
    <div x-data="siteApartmanLiveSearch()" x-init="init()" class="relative"
        @site-selected="console.log('Site selected:', $event.detail.site)" @site-cleared="console.log('Site cleared')">
        {{-- Search Input --}}
        <div class="relative">
            <input type="text" :name="{{ json_encode($name) }}" x-model="query" @keydown="handleKeydown($event)"
                @focus="showResults = hasResults()" :class="getStatusClass()" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 pr-10 dark:text-slate-100"
                placeholder="{{ $placeholder }}" autocomplete="off" {{ $required ? 'required' : '' }}>

            {{-- Status Icon --}}
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <i :class="getStatusIcon()" class="w-4 h-4"></i>
            </div>
        </div>

        {{-- Hidden Fields --}}
        <input type="hidden" name="site_id" x-model="selectedSite ? selectedSite.id : ''">
        <input type="hidden" name="site_adresi" x-model="selectedSite ? selectedSite.adres : ''">
        <input type="hidden" name="toplam_daire_sayisi" x-model="selectedSite ? selectedSite.daire_sayisi : ''">

        {{-- Search Results Dropdown --}}
        <div x-show="showResults && hasResults()" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-1 transform scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-1 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto dark:bg-slate-900"
            style="display: none;">
            <template x-for="(site, index) in results" :key="site.id">
                <div @click="selectSite(site)"
                    :class="site.active ? 'bg-blue-50 border-l-4 border-l-blue-500' : 'hover:bg-gray-50'"
                    class="px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            {{-- Site Name --}}
                            <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-html="highlightMatch(site.name, query)"></div>

                            {{-- Site Address --}}
                            <div class="text-sm text-gray-600 mt-1" x-show="site.adres">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <span x-text="site.adres"></span>
                            </div>

                            {{-- Apartment Count --}}
                            <div class="text-xs text-blue-600 mt-1" x-show="site.daire_sayisi">
                                <i class="fas fa-building mr-1"></i>
                                <span x-text="site.daire_sayisi + ' daire'"></span>
                            </div>
                        </div>

                        {{-- Selection Indicator --}}
                        <div class="ml-3 flex-shrink-0" x-show="isSelected(site)">
                            <i class="fas fa-check text-green-500"></i>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- No Results Message --}}
        <div x-show="query.length >= minQueryLength && !loading && !hasResults()"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-500 dark:bg-slate-900"
            style="display: none;">
            <i class="fas fa-search mb-2 text-gray-400"></i>
            <div class="text-sm">
                "<span x-text="query"></span>" için sonuç bulunamadı
            </div>

            @if ($showCreateNew)
                <button type="button" @click="$dispatch('create-new-site', { name: query })"
                    class="mt-2 text-xs text-blue-600 hover:text-blue-700 underline">
                    <i class="fas fa-plus mr-1"></i>
                    Yeni site/apartman ekle
                </button>
            @endif
        </div>

        {{-- Loading Indicator --}}
        <div x-show="loading"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center dark:bg-slate-900"
            style="display: none;">
            <i class="fas fa-spinner fa-spin text-blue-500 mr-2"></i>
            <span class="text-sm text-gray-600">Aranıyor...</span>
        </div>

        {{-- Selected Site Display --}}
        <div x-show="selectedSite" class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg"
            style="display: none;">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="font-medium text-green-900">
                        <i class="fas fa-building mr-2"></i>
                        <span x-text="selectedSite ? selectedSite.name : ''"></span>
                    </div>
                    <div class="text-sm text-green-700 mt-1" x-show="selectedSite && selectedSite.adres">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <span x-text="selectedSite ? selectedSite.adres : ''"></span>
                    </div>
                    <div class="text-xs text-green-600 mt-1" x-show="selectedSite && selectedSite.daire_sayisi">
                        <i class="fas fa-home mr-1"></i>
                        <span x-text="selectedSite ? (selectedSite.daire_sayisi + ' daire') : ''"></span>
                    </div>
                </div>
                <button type="button" @click="clearSelection()" class="ml-3 text-green-600 hover:text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Help Text --}}
    @if ($help)
        <p class="text-xs text-gray-500 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            {{ $help }}
        </p>
    @endif
</div>
