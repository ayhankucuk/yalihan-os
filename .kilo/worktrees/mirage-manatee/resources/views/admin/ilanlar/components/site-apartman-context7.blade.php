{{--
    🎨 Site/Apartman Seçimi - Context7 Live Search (Tailwind Modernized)
    C7-SITE-APARTMAN-LIVE-SEARCH-2025-10-30
    "Kişi Seçimi" ile aynı mantık: Live search + Yeni ekle butonu
--}}

<div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-8 hover:shadow-2xl transition-shadow duration-300 dark:border-slate-700">
    <!-- Section Header -->
    @if(!($wizardMode ?? false))
    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/50 font-bold text-lg">
            7
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Site/Apartman Seçimi
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Konum tipi ve site/apartman bilgileri (Context7 Live Search)</p>
        </div>
    </div>
    @endif

    <div class="space-y-6">
        {{-- Konum Tipi Seçimi --}}
        <div>
            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                Konum Tipi <span class="text-red-500 font-bold">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <label class="relative flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                              has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20
                              has-[:not(:checked)]:border-gray-300 dark:has-[:not(:checked)]:border-gray-600
                              hover:border-gray-400 dark:hover:border-gray-500
                              has-[:checked]:shadow-lg has-[:checked]:shadow-green-500/20">
                    <input type="radio" name="konum_tipi" value="site"
                           {{ old('konum_tipi') == 'site' ? 'checked' : '' }}
                           data-context7-field="konum_tipi"
                           onchange="toggleSiteApartmanSelection(this.value)"
                           class="sr-only">
                    <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Site İçi
                    </span>
                </label>

                <label class="relative flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                              has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20
                              has-[:not(:checked)]:border-gray-300 dark:has-[:not(:checked)]:border-gray-600
                              hover:border-gray-400 dark:hover:border-gray-500
                              has-[:checked]:shadow-lg has-[:checked]:shadow-green-500/20">
                    <input type="radio" name="konum_tipi" value="apartman"
                           {{ old('konum_tipi') == 'apartman' ? 'checked' : '' }}
                           data-context7-field="konum_tipi"
                           onchange="toggleSiteApartmanSelection(this.value)"
                           class="sr-only">
                    <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Apartman
                    </span>
                </label>

                <label class="relative flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                              has-[:checked]:border-green-500 has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/20
                              has-[:not(:checked)]:border-gray-300 dark:has-[:not(:checked)]:border-gray-600
                              hover:border-gray-400 dark:hover:border-gray-500
                              has-[:checked]:shadow-lg has-[:checked]:shadow-green-500/20">
                    <input type="radio" name="konum_tipi" value="mustakil"
                           {{ old('konum_tipi', 'mustakil') == 'mustakil' ? 'checked' : '' }}
                           data-context7-field="konum_tipi"
                           onchange="toggleSiteApartmanSelection(this.value)"
                           class="sr-only">
                    <span class="flex items-center gap-2 font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-3a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 001 1h3a1 1 0 001-1V10M9 21h6"/>
                        </svg>
                        Müstakil
                    </span>
                </label>
            </div>
        </div>

        {{-- Site/Apartman Live Search (Context7 Standardı) --}}
        <div id="site-apartman-search-container"
             class="hidden space-y-6 p-6 rounded-xl bg-gradient-to-br from-green-50/50 to-emerald-50/50 dark:from-green-900/10 dark:to-emerald-900/10 border border-green-200 dark:border-green-800/30"
             data-visible-for="site,apartman">

            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">
                    <span id="site-apartman-label">Site/Apartman Seçin</span>
                    <span class="text-red-500 font-bold ml-1">*</span>
                </label>

                {{-- Context7 Live Search (Kişi Seçimi ile aynı yapı) --}}
                <div class="context7-live-search relative"
                     data-search-type="site-apartman"
                     data-placeholder="Site/Apartman adı yazın..."
                     data-max-results="10"
                     data-creatable="true">

                    <input type="hidden" name="site_apartman_id" id="site_apartman_id" value="{{ old('site_apartman_id') }}">

                    <input type="text"
                           id="site_apartman_search"
                           class="w-full px-4 py-2.5
                                  border-2 border-gray-300 dark:border-gray-600
                                  rounded-xl
                                  bg-white dark:bg-gray-800
                                  text-black dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-green-500 dark:focus:border-green-400
                                  transition-all duration-200
                                  hover:border-gray-400 dark:hover:border-gray-500
                                  shadow-sm hover:shadow-md focus:shadow-lg"
                           placeholder="Site/Apartman adı yazın (min 2 karakter)..."
                           autocomplete="off">

                    {{-- Arama Sonuçları Dropdown --}}
                    <div class="context7-search-results absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border-2 border-green-300 dark:border-green-600 rounded-xl shadow-2xl hidden max-h-60 overflow-y-auto">
                        {{-- JavaScript ile doldurulacak --}}
                    </div>

                    {{-- Seçilen Site/Apartman Gösterimi --}}
                    <div id="selected-site-display" class="hidden mt-4 p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-lg">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div class="font-bold text-green-900 dark:text-green-100" id="selected-site-name"></div>
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300 mb-1" id="selected-site-adres"></div>
                                <div class="text-xs text-green-600 dark:text-green-400" id="selected-site-info"></div>
                            </div>
                            <button type="button"
                                    onclick="clearSiteSelection()"
                                    class="flex items-center justify-center w-8 h-8 rounded-lg
                                           text-red-500 hover:text-white hover:bg-red-500
                                           dark:text-red-400 dark:hover:bg-red-600
                                           transition-all duration-200 font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Yeni Ekle Butonu --}}
                    <button type="button"
                            onclick="openAddSiteModal()"
                            class="mt-3 flex items-center gap-2 text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 font-medium transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Listede yoksa yeni site/apartman ekle
                    </button>
                </div>

                @error('site_apartman_id')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Site Özellikleri (Dinamik - SiteOzellik model) --}}
            <div id="site-ozellikler-container" class="hidden">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Site Özellikleri
                </h4>
                <div id="site-ozellikler-grid" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    {{-- JavaScript ile yüklenecek --}}
                </div>
                <div id="site-ozellikler-loading" class="flex items-center justify-center py-6 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm">Özellikler yükleniyor...</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Context7 Site/Apartman Live Search System
// C7-SITE-APARTMAN-LIVE-SEARCH-2025-10-30 (Tailwind Modernized)

let siteSearchTimeout = null;
let currentKonumTipi = 'mustakil';
let siteOzellikleri = [];

// Konum tipi değiştiğinde
function toggleSiteApartmanSelection(type) {
    currentKonumTipi = type;
    const container = document.getElementById('site-apartman-search-container');
    const label = document.getElementById('site-apartman-label');

    if (type === 'site' || type === 'apartman') {
        container.classList.remove('hidden');
        label.textContent = type === 'site' ? 'Site Seçin' : 'Apartman Seçin';

        // Site özellikleri yükle
        loadSiteOzellikleri();
    } else {
        container.classList.add('hidden');
        clearSiteSelection();
    }
}

// Site/Apartman Arama (Context7 Live Search)
function initSiteApartmanSearch() {
    const searchInput = document.getElementById('site_apartman_search');
    const resultsContainer = searchInput?.nextElementSibling;

    if (!searchInput || !resultsContainer) return;

    // Input event (debounce 300ms)
    searchInput.addEventListener('input', function() {
        clearTimeout(siteSearchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            resultsContainer.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }

        siteSearchTimeout = setTimeout(() => {
            searchSiteApartman(query, currentKonumTipi);
        }, 300);
    });

    // Focus: Sonuçları göster
    searchInput.addEventListener('focus', function() {
        if (resultsContainer.children.length > 0) {
            resultsContainer.classList.remove('hidden');
        }
    });

    // Blur: Sonuçları gizle (200ms delay)
    searchInput.addEventListener('blur', function() {
        setTimeout(() => {
            resultsContainer.classList.add('hidden');
        }, 200);
    });
}

// API Arama Fonksiyonu
async function searchSiteApartman(query, type) {
    const resultsContainer = document.querySelector('.context7-search-results');

    try {
        const response = await fetch(`/api/v1/site-apartman/search?q=${encodeURIComponent(query)}&type=${type}`, {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error('Arama başarısız');

        const data = await response.json();
        const results = data.results || data.data || [];

        // Sonuçları göster
        displaySiteResults(results);

    } catch (error) {
        console.error('Site/Apartman arama hatası:', error);
        resultsContainer.innerHTML = `
            <div class="p-4 text-center text-red-600 dark:text-red-400 text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Arama yapılırken hata oluştu
            </div>
        `;
        resultsContainer.classList.remove('hidden');
    }
}

// Sonuçları Göster
function displaySiteResults(results) {
    const resultsContainer = document.querySelector('.context7-search-results');

    if (results.length === 0) {
        resultsContainer.innerHTML = `
            <div class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Sonuç bulunamadı
            </div>
        `;
    } else {
        resultsContainer.innerHTML = results.map(site => `
            <div class="px-4 py-2.5 hover:bg-green-50 dark:hover:bg-green-900/30 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0 transition-colors duration-150 dark:border-slate-700"
                 onclick="selectSite(${site.id}, '${escapeHtml(site.name)}', '${escapeHtml(site.adres || '')}', ${site.toplam_daire_sayisi || 0})">
                <div class="font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">${site.name}</div>
                ${site.adres ? `<div class="text-sm text-gray-600 dark:text-gray-400 mb-1">${site.adres}</div>` : ''}
                <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    ${site.toplam_daire_sayisi || 0} daire
                </div>
            </div>
        `).join('');
    }

    resultsContainer.classList.remove('hidden');
}

// Site Seç
function selectSite(id, name, adres, daireCount) {
    document.getElementById('site_apartman_id').value = id;
    document.getElementById('site_apartman_search').value = name;

    // Seçilen site'yi göster
    const display = document.getElementById('selected-site-display');
    document.getElementById('selected-site-name').textContent = name;
    document.getElementById('selected-site-adres').textContent = adres || '';
    document.getElementById('selected-site-info').textContent = `${daireCount} daire`;
    display.classList.remove('hidden');

    // Dropdown'u kapat
    document.querySelector('.context7-search-results').classList.add('hidden');

    // Site özelliklerini göster
    showSiteOzellikler();

    console.log('✅ Site seçildi:', { id, name });
}

// Seçimi Temizle
function clearSiteSelection() {
    document.getElementById('site_apartman_id').value = '';
    document.getElementById('site_apartman_search').value = '';
    document.getElementById('selected-site-display').classList.add('hidden');
    document.getElementById('site-ozellikler-container').classList.add('hidden');
}

// Site Özellikleri Yükle (Dinamik - SiteOzellik model)
async function loadSiteOzellikleri() {
    const container = document.getElementById('site-ozellikler-container');
    const grid = document.getElementById('site-ozellikler-grid');
    const loading = document.getElementById('site-ozellikler-loading');

    loading.classList.remove('hidden');
    grid.innerHTML = '';

    try {
        const response = await fetch('/api/v1/admin/site-ozellikleri/active', {
            headers: { 'Accept': 'application/json' }
        });

        if (!response.ok) throw new Error('Özellikler yüklenemedi');

        const data = await response.json();
        siteOzellikleri = data.data || [];

        console.log('✅ Site özellikleri yüklendi:', siteOzellikleri.length);

    } catch (error) {
        console.error('Site özellikleri yükleme hatası:', error);
    } finally {
        loading.classList.add('hidden');
    }
}

// Site Özellikleri Göster
function showSiteOzellikler() {
    const container = document.getElementById('site-ozellikler-container');
    const grid = document.getElementById('site-ozellikler-grid');

    if (siteOzellikleri.length === 0) {
        container.classList.add('hidden');
        return;
    }

    grid.innerHTML = siteOzellikleri.map(ozellik => `
        <label class="flex items-center gap-2 p-3 rounded-lg
                      border border-gray-200 dark:border-gray-700
                      hover:bg-gray-50 dark:hover:bg-gray-700
                      cursor-pointer transition-all duration-150
                      has-[:checked]:bg-green-50 dark:has-[:checked]:bg-green-900/30
                      has-[:checked]:border-green-500 dark:has-[:checked]:border-green-600">
            <input type="checkbox"
                   name="site_ozellikleri[${ozellik.id}]"
                   value="${ozellik.id}"
                   class="w-4 h-4 rounded text-green-600 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-2">
            <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">${ozellik.name}</span>
        </label>
    `).join('');

    container.classList.remove('hidden');
}

// Yeni Site/Apartman Ekle Modal
function openAddSiteModal() {
    alert('🚧 Yeni Site/Apartman Ekleme Modal - Yakında eklenecek!\n\nŞimdilik Site Yönetimi sayfasından ekleyebilirsiniz.');
    // TODO: Modal implementation
}

// HTML Escape (XSS Protection)
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    initSiteApartmanSearch();

    // Mevcut konum tipini kontrol et
    const selectedType = document.querySelector('input[name="konum_tipi"]:checked');
    if (selectedType) {
        toggleSiteApartmanSelection(selectedType.value);
    }

    console.log('✅ SAB Site/Apartman Live Search initialized (Tailwind)');
});
</script>
@endpush
