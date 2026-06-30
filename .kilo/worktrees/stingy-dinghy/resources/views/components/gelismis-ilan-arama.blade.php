{{--
    Gelişmiş İlan Arama Component
    Context7 Standardı: C7-ADVANCED-SEARCH-2025-10-11

    Özellikler:
    - Referans no ile arama (YE-SAT-001234)
    - Telefon ile arama (05551234567)
    - Portal ID ile arama (123456789)
    - Site/Apartman adı ile arama
    - Real-time sonuçlar
    - Otomatik tip tespiti

    Kullanım:
    <x-gelismis-ilan-arama />
--}}

<div x-data="gelismisIlanArama()" x-init="init()" class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none dark:border-slate-700">
    {{-- Arama Input --}}
    <div class="relative">
        <label for="advanced-search" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
            🔍 Gelişmiş Arama
        </label>

        <div class="relative">
            <input type="text"
                   id="advanced-search"
                   x-model="searchTerm"
                   @input.debounce.300ms="search()"
                   class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 pl-12 pr-32 dark:text-slate-100"
                   placeholder="Referans no, telefon, portal ID veya site adı...">

            {{-- Search Icon --}}
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            {{-- Search Type Badge --}}
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <span x-show="detectedType"
                      class="text-xs px-2 py-1 rounded-full"
                      :class="{
                          'bg-blue-100 text-blue-700': detectedType === 'referans',
                          'bg-green-100 text-green-700': detectedType === 'telefon',
                          'bg-purple-100 text-purple-700': detectedType === 'portal_id',
                          'bg-gray-100 text-gray-700 dark:text-slate-300': detectedType === 'text'
                      }"
                      x-text="typeLabels[detectedType]">
                </span>
            </div>
        </div>

        {{-- Quick Search Tips --}}
        <div class="mt-2 flex gap-2 flex-wrap">
            <button @click="searchTerm = 'YE-SAT-'; $el.nextElementSibling?.focus()"
                    type="button"
                    class="text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded hover:bg-blue-100">
                💡 Referans No
            </button>
            <button @click="searchTerm = '0555'; $el.previousElementSibling?.previousElementSibling?.focus()"
                    type="button"
                    class="text-xs px-2 py-1 bg-green-50 text-green-700 rounded hover:bg-green-100">
                📱 Telefon
            </button>
            <button @click="searchTerm = ''; detectedType = 'text'"
                    type="button"
                    class="text-xs px-2 py-1 bg-gray-50 text-gray-700 rounded hover:bg-gray-100 dark:bg-slate-900 dark:text-slate-300">
                🏢 Site Adı
            </button>
        </div>

        {{-- Loading State --}}
        <div x-show="searching" class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm flex items-center justify-center rounded-lg">
            <div class="flex items-center gap-2 text-blue-600">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Aranıyor...</span>
            </div>
        </div>
    </div>

    {{-- Search Results --}}
    <div x-show="results.length > 0"
         x-transition
         class="mt-4 bg-gray-50 dark:bg-slate-900 rounded-lg p-4 max-h-96 overflow-y-auto">

        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                📊 Sonuçlar (<span x-text="results.length"></span>)
            </h3>
            <button @click="clearResults()" class="text-xs text-gray-500 hover:text-gray-700">
                ✖ Temizle
            </button>
        </div>

        {{-- Result List --}}
        <div class="space-y-2">
            <template x-for="ilan in results" :key="ilan.id">
                <div class="bg-white dark:bg-slate-900 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                     @click="selectIlan(ilan)">

                    {{-- Referans No & Başlık --}}
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400"
                                      x-text="ilan.referans_no"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      :class="statusColors[ilan.status]"
                                      x-text="ilan.status"></span>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm dark:text-slate-100"
                                x-text="ilan.baslik"></h4>
                        </div>

                        <div class="text-right">
                            <div class="text-lg font-bold text-green-600 dark:text-green-400"
                                 x-text="formatPrice(ilan.fiyat)"></div>
                        </div>
                    </div>

                    {{-- Detaylar --}}
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <div>📍 <span x-text="ilan.lokasyon"></span></div>
                        <div>🏢 <span x-text="ilan.site || 'Site yok'"></span></div>
                        <div>🏷️ <span x-text="ilan.kategori"></span></div>
                        <div>👤 <span x-text="ilan.mal_sahibi || 'Belirtilmemiş'"></span></div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-3 flex gap-2">
                        <a :href="ilan.url"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-xs bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg">
                            👁️ Görüntüle
                        </a>
                        <a :href="ilan.edit_url"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-xs border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                            ✏️ Düzenle
                        </a>
                        <button @click.stop="copyReferans(ilan.referans_no)"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-xs border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                            📋 Ref No Kopyala
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- No Results --}}
    <div x-show="searched && results.length === 0"
         x-transition
         class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
        <div class="text-yellow-800 dark:text-yellow-300">
            <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="font-medium">Sonuç bulunamadı</p>
            <p class="text-sm mt-1">Farklı bir arama terimi deneyin</p>
        </div>
    </div>

    {{-- Search Examples --}}
    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">
            💡 Arama Örnekleri:
        </h4>
        <ul class="text-xs text-blue-800 dark:text-blue-400 space-y-1">
            <li><strong>Referans No:</strong> YE-SAT-YALKVK-DAİRE-001234</li>
            <li><strong>Telefon:</strong> 0555 123 45 67 veya 05551234567</li>
            <li><strong>Portal ID:</strong> 123456789 (sahibinden.com ID)</li>
            <li><strong>Site Adı:</strong> Ülkerler Sitesi</li>
            <li><strong>Başlık:</strong> Deniz manzaralı daire</li>
        </ul>
    </div>
</div>

<script>
function gelismisIlanArama() {
    return {
        searchTerm: '',
        results: [],
        searching: false,
        searched: false,
        detectedType: '',

        typeLabels: {
            'referans': '🏷️ Referans No',
            'telefon': '📱 Telefon',
            'portal_id': '🌐 Portal ID',
            'text': '📝 Metin'
        },

        statusColors: {
            'Aktif': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'Pasif': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
            'Satıldı': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'Taslak': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
        },

        init() {
            // URL'den arama terimi al (varsa)
            const urlParams = new URLSearchParams(window.location.search);
            const q = urlParams.get('q');
            if (q) {
                this.searchTerm = q;
                this.search();
            }
        },

        async search() {
            if (this.searchTerm.trim().length < 2) {
                this.results = [];
                this.searched = false;
                this.detectedType = '';
                return;
            }

            this.searching = true;
            this.detectedType = this.detectType(this.searchTerm);

            try {
                const response = await fetch(`/api/ilanlar/search?q=${encodeURIComponent(this.searchTerm)}&limit=20`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.results = data.results || [];
                    this.searched = true;
                } else {
                    this.results = [];
                    this.searched = true;
                    window.toast.error('Arama başarısız');
                }
            } catch (error) {
                console.error('Search error:', error);
                window.toast.error('Arama sırasında hata oluştu');
                this.results = [];
            } finally {
                this.searching = false;
            }
        },

        detectType(term) {
            term = term.trim().toUpperCase();

            // Referans no (YE- ile başlıyor)
            if (term.startsWith('YE-')) {
                return 'referans';
            }

            // Telefon (sadece rakam)
            if (/^[0-9+\s()-]+$/.test(term)) {
                return 'telefon';
            }

            // Portal ID (8+ haneli sayı)
            if (/^\d{8,}$/.test(term)) {
                return 'portal_id';
            }

            return 'text';
        },

        selectIlan(ilan) {
            // İlanı yeni sekmede aç
            window.open(ilan.url, '_blank');
        },

        copyReferans(referansNo) {
            navigator.clipboard.writeText(referansNo).then(() => {
                window.toast.success('📋 Referans no kopyalandı: ' + referansNo);
            }).catch(() => {
                window.toast.error('Kopyalama başarısız');
            });
        },

        formatPrice(price) {
            if (!price) return '-';
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY',
                minimumFractionDigits: 0
            }).format(price);
        },

        clearResults() {
            this.results = [];
            this.searchTerm = '';
            this.searched = false;
            this.detectedType = '';
        }
    };
}
</script>

<style>
.inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2-xs {
    @apply px-2 py-1 text-xs;
}
</style>
