@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="wikimapiaManager()">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                    </div>
                    WikiMapia Site/Apartman Arama
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Site bilgilerini WikiMapia'dan çekin ve ilanlarınıza
                    ekleyin</p>
            </div>
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 dark:text-slate-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Map & Search --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Map --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                    <div
                        class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            İnteraktif Harita
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Haritaya tıklayarak konum seçin</p>
                    </div>
                    <div id="map" class="w-full relative" style="height: 500px; min-height: 500px; z-index: 1;"></div>
                    <div class="px-6 py-3 bg-gray-50 dark:bg-slate-900 text-xs text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Haritada tıkladığınız nokta için yakındaki yerler otomatik aranır</span>
                        </div>
                    </div>
                </div>

                {{-- Search Form --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">🔍 Arama Kriterleri</h2>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="searchPlaces" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                        Site/Apartman Adı
                                    </label>
                                    <input type="text" x-model="searchQuery" placeholder="Örn: Bahçeşehir Sitesi"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all duration-200 dark:text-slate-100">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                        Arama Yarıçapı
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="range" x-model="searchRadius" min="0.01" max="2"
                                            step="0.1" class="flex-1">
                                        <span
                                            class="text-sm font-mono text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg min-w-[80px] text-center dark:bg-slate-900 dark:text-slate-300"
                                            x-text="(searchRadius * 100).toFixed(0) + ' km'"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Latitude</label>
                                    <input type="number" step="0.000001" x-model="searchLat"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all duration-200 font-mono text-sm dark:text-slate-100">
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Longitude</label>
                                    <input type="number" step="0.000001" x-model="searchLon"
                                        class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all duration-200 font-mono text-sm dark:text-slate-100">
                                </div>
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="submit" :disabled="searching"
                                    class="flex-1 px-6 py-3 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 text-white hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium flex items-center justify-center gap-2 disabled:opacity-50 dark:shadow-none">
                                    <svg x-show="!searching" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <div x-show="searching"
                                        class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full">
                                    </div>
                                    <span x-text="searching ? 'Aranıyor...' : 'Site/Apartman Ara'"></span>
                                </button>

                                <button type="button" @click="searchNearby" :disabled="searching"
                                    class="px-6 py-3 rounded-lg border-2 border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all duration-200 font-medium flex items-center gap-2 disabled:opacity-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Yakındakiler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Site/Apartman Listesi Modal --}}
                <div x-show="showSitesModal && filteredResults.length > 0" x-cloak
                    class="fixed inset-0 z-[100] overflow-y-auto" @keydown.escape.window="showSitesModal = false"
                    style="z-index: 9999;">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity z-[9998]"
                        @click="showSitesModal = false"></div>
                    <div class="flex min-h-screen items-center justify-center p-4 relative z-[9999]">
                        <div @click.stop
                            class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-800 w-full max-w-4xl max-h-[80vh] overflow-hidden z-[10000] dark:border-slate-700">
                            <div
                                class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                        🏢 Bulunan Site/Apartmanlar (<span x-text="filteredResults.length"></span>)
                                    </h3>
                                    <button @click="showSitesModal = false"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-white/50 dark:hover:bg-slate-800/50 dark:hover:bg-gray-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-6 overflow-y-auto max-h-[60vh] relative" style="z-index: 10001;">
                                <div class="space-y-3 relative" style="z-index: 10001;">
                                    <template x-for="(place, index) in filteredResults" :key="place.id">
                                        <div class="group bg-gradient-to-r from-white to-gray-50 dark:from-gray-800 dark:to-gray-800 rounded-xl border-2 border-gray-200 dark:border-slate-800 hover:border-purple-500 dark:hover:border-purple-400 transition-all duration-200 overflow-hidden relative dark:border-slate-700"
                                            style="z-index: 10002;">
                                            <div class="p-4">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="flex-1">
                                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors dark:text-slate-100"
                                                            x-text="place.title"></h3>
                                                        <p x-show="place.description"
                                                            class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2"
                                                            x-text="place.description"></p>
                                                        <div class="flex flex-wrap gap-2 mb-3">
                                                            <span x-show="place.location"
                                                                class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-full text-xs font-medium">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                </svg>
                                                                <span
                                                                    x-text="`${formatCoordinate(place.location?.latitude)}, ${formatCoordinate(place.location?.longitude)}`"></span>
                                                            </span>
                                                            <span
                                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-full text-xs font-medium"
                                                                x-text="getSourceLabel(place.source || dataSource)"></span>
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <button type="button" @click="saveSite(place)"
                                                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg transition-all duration-200 text-sm font-medium flex items-center gap-2 shadow-md hover:shadow-lg dark:shadow-none">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                💾 Veritabanına Kaydet
                                                            </button>
                                                            <button type="button"
                                                                @click="showPlaceDetail(place); showSitesModal = false"
                                                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium flex items-center gap-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                </svg>
                                                                Detay
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div
                                class="border-t border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-slate-900 flex items-center justify-end gap-3 dark:border-slate-700">
                                <button @click="showSitesModal = false"
                                    class="px-6 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 hover:bg-white dark:hover:bg-gray-700 transition-colors duration-200 font-medium dark:text-slate-300">
                                    Kapat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Results (Liste görünümü - opsiyonel) --}}
                <div x-show="results.length > 0 && !showSitesModal" x-cloak
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">📋 Site/Apartman Listesi</h2>
                            <span class="text-sm text-gray-600 dark:text-gray-400"
                                x-text="`${results.length} site/apartman bulundu`"></span>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <template x-for="(place, index) in results" :key="place.id">
                            <div
                                class="group bg-gradient-to-r from-white to-gray-50 dark:from-gray-800 dark:to-gray-800 rounded-xl border-2 border-gray-200 dark:border-slate-800 hover:border-purple-500 dark:hover:border-purple-400 transition-all duration-200 overflow-hidden dark:border-slate-700">
                                <div class="p-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors dark:text-slate-100"
                                                x-text="place.title"></h3>
                                            <p x-show="place.description"
                                                class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2"
                                                x-text="place.description"></p>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                <span x-show="place.location"
                                                    class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-full text-xs font-medium">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    </svg>
                                                    <span
                                                        x-text="`${formatCoordinate(place.location?.latitude)}, ${formatCoordinate(place.location?.longitude)}`"></span>
                                                </span>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="button" @click="saveSite(place)"
                                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    💾 Kaydet
                                                </button>
                                                <button type="button" @click="showPlaceDetail(place)"
                                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Detay
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Empty State --}}
                <div x-show="!searching && results.length === 0"
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-12 text-center dark:shadow-none dark:border-slate-700">
                    <div class="text-6xl mb-4">🗺️</div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Aramaya Başlayın</h3>
                    <p class="text-gray-600 dark:text-gray-400">Haritada bir konum seçin veya arama yapın</p>
                </div>
            </div>

            {{-- Right: Info & Stats --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">📊 İstatistikler</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span>Toplam Arama</span>
                            <span class="text-2xl font-bold" x-text="stats.totalSearches"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Son Arama Sonucu</span>
                            <span class="text-2xl font-bold" x-text="stats.lastSearchResults"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Seçilen Site</span>
                            <span class="text-2xl font-bold" x-text="stats.selectedSites"></span>
                        </div>
                    </div>
                </div>

                {{-- Selected Coordinates --}}
                <div x-show="searchLat && searchLon"
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📍 Seçili Koordinat</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Latitude:</dt>
                            <dd class="font-mono text-gray-900 dark:text-white dark:text-slate-100" x-text="formatCoordinate(searchLat)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Longitude:</dt>
                            <dd class="font-mono text-gray-900 dark:text-white dark:text-slate-100" x-text="formatCoordinate(searchLon)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Yarıçap:</dt>
                            <dd class="font-mono text-gray-900 dark:text-white dark:text-slate-100"
                                x-text="(searchRadius * 100).toFixed(0) + ' km'"></dd>
                        </div>
                    </dl>
                </div>

                {{-- Help --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Nasıl Kullanılır?
                    </h3>
                    <ol class="space-y-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        <li class="flex gap-2">
                            <span class="font-bold">1.</span>
                            <span>Haritada tıklayarak konum seçin</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">2.</span>
                            <span>Site adı yazın ve arama yapın</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">3.</span>
                            <span>"Detay" butonuna tıklayarak site bilgilerini görüntüleyin</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">4.</span>
                            <span>"Seç" butonu ile site'yi ilana ekleyin ve kaydedin</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Place Detail Modal --}}
        <div x-show="selectedPlace" x-cloak class="fixed inset-0 z-[100] overflow-y-auto"
            @keydown.escape.window="selectedPlace = null" style="z-index: 10001;">

            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="selectedPlace = null">
            </div>

            {{-- Modal --}}
            <div class="flex min-h-screen items-center justify-center p-4">
                <div @click.stop
                    class="relative bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-800 w-full max-w-2xl transform transition-all dark:border-slate-700">

                    {{-- Header --}}
                    <div
                        class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="selectedPlace?.title">
                            </h3>
                            <button @click="selectedPlace = null"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-white/50 dark:hover:bg-slate-800/50 dark:hover:bg-gray-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-4">
                            {{-- Description --}}
                            <div x-show="selectedPlace?.description">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">📝 Açıklama</h4>
                                <p class="text-gray-700 dark:text-slate-200 text-sm dark:text-slate-300" x-text="selectedPlace?.description">
                                </p>
                            </div>

                            {{-- Location Info --}}
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">📍 Konum Bilgileri</h4>
                                <dl class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <dt class="text-gray-600 dark:text-gray-400 mb-1">Latitude</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="formatCoordinate(selectedPlace?.location?.latitude)"></dd>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                                        <dt class="text-gray-600 dark:text-gray-400 mb-1">Longitude</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="formatCoordinate(selectedPlace?.location?.longitude)"></dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- WikiMapia ID --}}
                            <div
                                class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs text-purple-600 dark:text-purple-400 font-medium mb-1">
                                            WikiMapia Place ID</div>
                                        <div class="text-2xl font-bold text-purple-700 dark:text-purple-300"
                                            x-text="selectedPlace?.id"></div>
                                    </div>
                                    <svg class="w-12 h-12 text-purple-300 dark:text-purple-700" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        class="border-t border-gray-200 dark:border-slate-800 px-6 py-4 bg-gray-50 dark:bg-slate-900 flex items-center justify-end gap-3 dark:border-slate-700">
                        <button @click="selectedPlace = null"
                            class="px-6 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 hover:bg-white dark:hover:bg-gray-700 transition-colors duration-200 font-medium dark:text-slate-300">
                            Kapat
                        </button>
                        <a x-show="selectedPlace?.url" :href="selectedPlace?.url" target="_blank"
                            class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200 font-medium flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            WikiMapia'da Aç
                        </a>
                        <button @click="selectSite(selectedPlace); selectedPlace = null"
                            class="px-6 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white transition-all duration-200 shadow-md hover:shadow-lg font-medium flex items-center gap-2 dark:shadow-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Site Olarak Seç
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <style>
            [x-cloak] {
                display: none !important;
            }

            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>
    @endpush

    @push('scripts')
        <x-csp-script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" />
        <script>
            function wikimapiaManager() {
                return {
                    searchQuery: '',
                    searchLat: 37.0345,
                    searchLon: 27.4305,
                    searchRadius: 0.5,
                    searching: false,
                    results: [],
                    filteredResults: [],
                    selectedPlace: null,
                    showSitesModal: false,
                    map: null,
                    marker: null,
                    resultMarkers: [],
                    savedSiteMarkers: [],
                    stats: {
                        totalSearches: 0,
                        totalPlaces: 0,
                        lastSearchResults: 0,
                        selectedSites: 0,
                        savedSites: 0
                    },
                    dataSource: 'unknown',
                    dataQuality: 'unknown',

                    // Get human-readable source label
                    getSourceLabel(source) {
                        const labels = {
                            'wikimapia': '🗺️ WikiMapia',
                            'openstreetmap': '🌍 OpenStreetMap',
                            'wikimapia_test': '⚠️ Test Data',
                            'unknown': '❓ Bilinmeyen'
                        };
                        return labels[source] || labels.unknown;
                    },

                    // Get quality badge color
                    getQualityColor(quality) {
                        const colors = {
                            'verified': 'bg-green-500',
                            'free_alternative': 'bg-blue-500',
                            'test_data': 'bg-yellow-500',
                            'unknown': 'bg-gray-500'
                        };
                        return colors[quality] || colors.unknown;
                    },

                    // Toast helper - Component-local (guaranteed available)
                    toast(type, message) {
                        let container = document.getElementById('toast-container');
                        if (!container) {
                            container = document.createElement('div');
                            container.id = 'toast-container';
                            container.className = 'fixed top-4 right-4 z-50 space-y-2';
                            document.body.appendChild(container);
                        }
                        const colors = {
                            success: 'bg-green-500',
                            error: 'bg-red-500',
                            warning: 'bg-yellow-500',
                            info: 'bg-blue-500'
                        };
                        const toastEl = document.createElement('div');
                        toastEl.className = (colors[type] || colors.info) +
                            ' text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 transition-all duration-300';
                        toastEl.innerHTML = '<span>' + message +
                            '</span><button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-2 font-bold">✕</button>';
                        container.appendChild(toastEl);
                        setTimeout(function() {
                            toastEl.style.opacity = '0';
                            toastEl.style.transform = 'translateX(100%)';
                            setTimeout(function() {
                                toastEl.remove();
                            }, 300);
                        }, 3000);
                    },

                    // Format coordinate helper - Consistent format everywhere
                    formatCoordinate(coord) {
                        if (!coord) return '0.000000';
                        return parseFloat(coord).toFixed(6);
                    },

                    // Site/Apartman filtreleme (sadece residential complexes)
                    filterSitesApartments(places) {
                        if (!places || !Array.isArray(places)) return [];

                        return places.filter(place => {
                            const title = (place.title || '').toLowerCase();
                            const description = (place.description || '').toLowerCase();
                            const type = (place.type || '').toLowerCase();
                            const category = (place.category || '').toLowerCase();

                            // Site/Apartman anahtar kelimeleri
                            const siteKeywords = ['site', 'sitesi', 'apartman', 'rezidans', 'residential', 'complex',
                                'building', 'apartment'
                            ];

                            // Kontrol et
                            const matchesTitle = siteKeywords.some(keyword => title.includes(keyword));
                            const matchesDescription = siteKeywords.some(keyword => description.includes(keyword));
                            const matchesType = type.includes('apartment') || type.includes('residential') || type
                                .includes('building') || category.includes('building');

                            return matchesTitle || matchesDescription || matchesType;
                        });
                    },

                    init() {
                        // Load stats from localStorage
                        const savedStats = localStorage.getItem('wikimapia_stats');
                        if (savedStats) {
                            try {
                                this.stats = {
                                    ...this.stats,
                                    ...JSON.parse(savedStats)
                                };
                            } catch (e) {
                                console.error('Stats load error:', e);
                            }
                        }

                        // Alpine.js init'ten sonra harita başlat (DOM hazır olduğundan emin ol)
                        this.$nextTick(() => {
                            // Kısa bir gecikme ile harita başlat (container render edilmiş olsun)
                            setTimeout(() => {
                                this.initMap();

                                // Harita yüklendikten sonra kaydedilen siteleri yükle
                                setTimeout(() => {
                                    if (this.map) {
                                        this.loadSavedSites();
                                    }
                                }, 500);
                            }, 100);
                        });
                    },

                    initMap() {
                        // Map container kontrolü
                        const mapContainer = document.getElementById('map');
                        if (!mapContainer) {
                            console.error('Map container not found!');
                            return;
                        }

                        // Harita container'ına düşük z-index (modal kartlarının üstünde görünmemesi için)
                        mapContainer.style.position = 'relative';
                        mapContainer.style.zIndex = '1';

                        // Initialize Leaflet map - Tüm animasyonlar kapalı (project/null hatası önleme)
                        this.map = L.map('map', {
                            zoomAnimation: false,
                            fadeAnimation: false,
                            markerZoomAnimation: false,
                            zoomControl: true,
                            doubleClickZoom: true,
                            scrollWheelZoom: true,
                            preferCanvas: false // Canvas render engine sorunları önleme
                        });

                        // View set et (animasyon olmadan)
                        this.map.setView([this.searchLat, this.searchLon], 13, {
                            animate: false,
                            reset: false
                        });

                        // Base layers (Street + Satellite)
                        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors',
                            maxZoom: 19
                        });

                        const satelliteLayer = L.tileLayer(
                            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                                attribution: '© Esri, Maxar, Earthstar Geographics',
                                maxZoom: 19
                            });

                        // Add default layer
                        streetLayer.addTo(this.map);

                        // Layer control
                        const baseMaps = {
                            "🗺️ Sokak Haritası": streetLayer,
                            "🛰️ Uydu Görünümü": satelliteLayer
                        };

                        L.control.layers(baseMaps, null, {
                            position: 'topright'
                        }).addTo(this.map);

                        // Container boyutunu güncelle (responsive için)
                        setTimeout(() => {
                            if (this.map) {
                                this.map.invalidateSize();
                            }
                        }, 200);

                        // Add click event
                        this.map.on('click', (e) => {
                            this.searchLat = e.latlng.lat.toFixed(6);
                            this.searchLon = e.latlng.lng.toFixed(6);

                            // Update marker
                            if (this.marker) {
                                this.marker.setLatLng(e.latlng);
                            } else {
                                this.marker = L.marker(e.latlng, {
                                    icon: L.icon({
                                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                        iconSize: [25, 41],
                                        iconAnchor: [12, 41],
                                        popupAnchor: [1, -34],
                                        shadowSize: [41, 41]
                                    })
                                }).addTo(this.map);
                            }

                            // Auto search nearby site/apartman
                            this.searchNearby();

                            // ✅ SAB: TurkiyeAPI'den lokasyon bilgisi getir
                            this.getLocationFromCoordinates(e.latlng.lat, e.latlng.lng);
                        });
                    },

                    // Kaydedilen siteleri yükle ve haritada göster
                    async loadSavedSites() {
                        // Harita kontrolü
                        if (!this.map) {
                            console.warn('Map not initialized yet, skipping saved sites load');
                            return;
                        }

                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                            const response = await fetch('{{ route('admin.wikimapia-search.saved-sites') }}', {
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken || '',
                                    'Accept': 'application/json'
                                }
                            });

                            if (!response.ok) {
                                console.warn('Failed to load saved sites:', response.status);
                                // 500 hatası sessizce devam et
                                return;
                            }

                            // Response tipini kontrol et
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                console.warn('Non-JSON response from saved-sites');
                                return;
                            }

                            const data = await response.json();
                            if (data.success && data.data && Array.isArray(data.data)) {
                                // Eski marker'ları temizle
                                this.savedSiteMarkers.forEach(marker => {
                                    if (this.map && this.map.hasLayer(marker)) {
                                        this.map.removeLayer(marker);
                                    }
                                });
                                this.savedSiteMarkers = [];

                                // Yeni marker'ları ekle
                                data.data.forEach(site => {
                                    if (!site.latitude || !site.longitude) return;

                                    try {
                                        const marker = L.marker([site.latitude, site.longitude], {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        }).addTo(this.map);

                                        marker.bindPopup(`
                                <div class="p-2">
                                    <h3 class="font-bold text-sm mb-1">${site.name || 'İsimsiz'}</h3>
                                    <p class="text-xs text-gray-600">${site.tip || 'site'}</p>
                                    ${site.address ? '<p class="text-xs text-gray-500 mt-1">' + site.address + '</p>' : ''}
                                </div>
                            `);

                                        this.savedSiteMarkers.push(marker);
                                    } catch (markerError) {
                                        console.error('Marker creation error:', markerError, site);
                                    }
                                });

                                this.stats.savedSites = data.data.length;
                            }
                        } catch (error) {
                            console.error('Saved sites load error:', error);
                            // Hata statusunda sessizce devam et (kullanıcıyı rahatsız etme)
                        }
                    },

                    async searchPlaces() {
                        if (!this.searchQuery) {
                            this.toast('warning', 'Lütfen arama terimi girin');
                            return;
                        }

                        this.searching = true;
                        this.stats.totalSearches++;
                        localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                        try {
                            const response = await fetch('{{ route('admin.wikimapia-search.search') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                },
                                body: JSON.stringify({
                                    query: this.searchQuery,
                                    lat: parseFloat(this.searchLat),
                                    lon: parseFloat(this.searchLon),
                                    radius: parseFloat(this.searchRadius)
                                })
                            });

                            const data = await response.json();

                            if (data.success && data.data) {
                                this.results = data.data.places || [];
                                this.stats.lastSearchResults = this.results.length;
                                this.stats.totalPlaces += this.results.length;
                                localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                                this.toast('success', this.results.length + ' yer bulundu!');
                            } else {
                                this.toast('error', 'Sonuç bulunamadı');
                            }
                        } catch (error) {
                            console.error('Search error:', error);
                            this.toast('error', 'Arama hatası oluştu');
                        } finally {
                            this.searching = false;
                        }
                    },

                    async searchNearby() {
                        this.searching = true;
                        this.stats.totalSearches++;
                        localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                        try {
                            const response = await fetch('{{ route('admin.wikimapia-search.nearby') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                },
                                body: JSON.stringify({
                                    lat: parseFloat(this.searchLat),
                                    lon: parseFloat(this.searchLon),
                                    radius: parseFloat(this.searchRadius)
                                })
                            });

                            const data = await response.json();

                            if (data.success && data.data) {
                                const allPlaces = data.data.places || [];

                                // Site/Apartman filtreleme
                                this.filteredResults = this.filterSitesApartments(allPlaces);
                                this.results = this.filteredResults;

                                // Eski marker'ları temizle
                                this.resultMarkers.forEach(marker => this.map.removeLayer(marker));
                                this.resultMarkers = [];

                                // Yeni marker'ları ekle (sadece site/apartman)
                                this.filteredResults.forEach(place => {
                                    if (place.location && place.location.latitude && place.location.longitude) {
                                        const marker = L.marker([place.location.latitude, place.location
                                        .longitude], {
                                            icon: L.icon({
                                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                                iconSize: [25, 41],
                                                iconAnchor: [12, 41],
                                                popupAnchor: [1, -34],
                                                shadowSize: [41, 41]
                                            })
                                        }).addTo(this.map);

                                        const popupContent = document.createElement('div');
                                        popupContent.className = 'p-2';
                                        popupContent.innerHTML = `
                                <h3 class="font-bold text-sm mb-1">${place.title || 'İsimsiz'}</h3>
                                ${place.description ? '<p class="text-xs text-gray-600 mb-2">' + place.description.substring(0, 100) + '...</p>' : ''}
                                <button class="marker-save-btn mt-2 px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700" data-place-id="${place.id}">
                                    💾 Kaydet
                                </button>
                            `;

                                        const saveBtn = popupContent.querySelector('.marker-save-btn');
                                        saveBtn.addEventListener('click', (e) => {
                                            e.stopPropagation();
                                            this.saveSite(place);
                                            marker.closePopup();
                                        });

                                        marker.bindPopup(popupContent);

                                        marker.on('click', () => {
                                            this.showPlaceDetail(place);
                                        });

                                        this.resultMarkers.push(marker);
                                    }
                                });

                                this.stats.lastSearchResults = this.results.length;
                                this.stats.totalPlaces += this.results.length;

                                // Store source info in results
                                this.dataSource = data.source || 'unknown';
                                this.dataQuality = data.quality || 'unknown';

                                localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                                // Show data source in toast
                                const sourceLabel = this.getSourceLabel(data.source);
                                if (this.results.length > 0) {
                                    this.toast('success', this.results.length + ' site/apartman bulundu! (' + sourceLabel +
                                        ')');
                                    this.showSitesModal = true;
                                } else {
                                    this.toast('warning',
                                        'Yakınlarda site/apartman bulunamadı. Arama yarıçapını artırabilirsiniz.');
                                }
                            }
                        } catch (error) {
                            console.error('Nearby search error:', error);
                            this.toast('error', 'Yakındaki yerler bulunamadı');
                        } finally {
                            this.searching = false;
                        }
                    },

                    showPlaceDetail(place) {
                        this.selectedPlace = place;
                    },

                    selectSite(place) {
                        // LocalStorage'a kaydet
                        localStorage.setItem('selectedWikimapiaSite', JSON.stringify({
                            wikimapia_id: place.id,
                            name: place.title,
                            description: place.description,
                            latitude: place.location?.latitude,
                            longitude: place.location?.longitude,
                            url: place.url
                        }));

                        // Update stats counter
                        this.stats.selectedSites++;

                        // Save stats to localStorage (persistent)
                        localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                        this.toast('success', '✅ ' + place.title + ' seçildi! İlan formunda kullanılabilir.');

                        // Eğer iframe içindeyse parent'a mesaj gönder
                        if (window.parent !== window) {
                            window.parent.postMessage({
                                type: 'wikimapia_site_selected',
                                site: {
                                    wikimapia_id: place.id,
                                    name: place.title,
                                    latitude: place.location?.latitude,
                                    longitude: place.location?.longitude
                                }
                            }, '*');
                        }
                    },

                    // Site/Apartman'ı veritabanına kaydet
                    async saveSite(place) {
                        if (!place || !place.title) {
                            this.toast('error', 'Site adı eksik!');
                            return;
                        }

                        if (!place.location || !place.location.latitude || !place.location.longitude) {
                            this.toast('error', 'Site koordinatları eksik!');
                            return;
                        }

                        // Koordinat validasyonu
                        const lat = parseFloat(place.location.latitude);
                        const lng = parseFloat(place.location.longitude);

                        if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                            this.toast('error', 'Geçersiz koordinatlar!');
                            return;
                        }

                        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                            this.toast('error', 'Koordinatlar geçerli aralıkta değil!');
                            return;
                        }

                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                            if (!csrfToken) {
                                this.toast('error', 'CSRF token bulunamadı! Sayfayı yenileyin.');
                                return;
                            }

                            const response = await fetch('{{ route('admin.wikimapia-search.save-site') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    name: place.title.trim(),
                                    latitude: lat,
                                    longitude: lng,
                                    description: (place.description || '').substring(0, 1000),
                                    address: (place.address || place.description || '').substring(0, 500),
                                    tip: (place.title || '').toLowerCase().includes('apartman') ?
                                        'apartman' : 'site',
                                    wikimapia_id: place.id ? String(place.id) : null,
                                    source: place.source || this.dataSource || 'unknown'
                                })
                            });

                            // Response tipini kontrol et
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                console.error('Non-JSON response:', text.substring(0, 200));
                                this.toast('error', 'Sunucu hatası! Lütfen tekrar deneyin.');
                                return;
                            }

                            let data;
                            try {
                                data = await response.json();
                            } catch (jsonError) {
                                const text = await response.text();
                                console.error('JSON parse error:', text.substring(0, 500));
                                this.toast('error', 'Sunucu yanıtı geçersiz! Status: ' + response.status);
                                return;
                            }

                            if (data.success) {
                                this.toast('success', '✅ ' + place.title + ' veritabanına kaydedildi!');
                                this.stats.savedSites++;
                                localStorage.setItem('wikimapia_stats', JSON.stringify(this.stats));

                                // Kaydedilen siteleri yeniden yükle
                                await this.loadSavedSites();
                            } else {
                                // Validation error detaylarını göster
                                let errorMsg = data.message || 'Kaydetme başarısız!';
                                if (data.errors) {
                                    const errors = Object.values(data.errors).flat();
                                    errorMsg = errors.join(', ');
                                    console.error('Save site validation errors:', data.errors);
                                    console.error('Full error response:', JSON.stringify(data, null, 2));
                                }
                                console.error('Save site error response:', JSON.stringify(data, null, 2));
                                console.error('Full error object:', data);
                                this.toast('error', errorMsg);
                            }
                        } catch (error) {
                            console.error('Save site error:', error);
                            if (error instanceof SyntaxError) {
                                this.toast('error', 'Sunucu yanıtı geçersiz! Lütfen tekrar deneyin.');
                            } else {
                                this.toast('error', 'Kaydetme sırasında hata oluştu: ' + error.message);
                            }
                        }
                    },

                    /**
                     * TurkiyeAPI'den koordinatlardan lokasyon bilgisi getir
                     * Context7: Harita sistemi için TurkiyeAPI entegrasyonu
                     */
                    async getLocationFromCoordinates(lat, lon) {
                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                            const response = await fetch(
                            '{{ route('admin.wikimapia-search.location-from-coordinates') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken || '',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    lat: parseFloat(lat),
                                    lon: parseFloat(lon)
                                })
                            });

                            if (!response.ok) {
                                console.warn('TurkiyeAPI lokasyon bilgisi alınamadı:', response.status);
                                return;
                            }

                            const data = await response.json();

                            if (data.success && data.data) {
                                const locationInfo = data.data;

                                // Marker popup'a lokasyon bilgisi ekle
                                if (this.marker && locationInfo.mahalle) {
                                    const popupContent = `
                            <div class="p-2">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">📍 Lokasyon Bilgisi</h3>
                                ${locationInfo.mahalle ? `<p class="text-sm text-gray-700 dark:text-slate-300"><strong>Mahalle:</strong> ${locationInfo.mahalle.name}</p>` : ''}
                                ${locationInfo.ilce ? `<p class="text-sm text-gray-700 dark:text-slate-300"><strong>İlçe:</strong> ${locationInfo.ilce.name}</p>` : ''}
                                ${locationInfo.il ? `<p class="text-sm text-gray-700 dark:text-slate-300"><strong>İl:</strong> ${locationInfo.il.name}</p>` : ''}
                                ${locationInfo.mahalle && locationInfo.mahalle.distance ? `<p class="text-xs text-gray-500 mt-1">Mesafe: ${locationInfo.mahalle.distance}m</p>` : ''}
                                <p class="text-xs text-gray-400 mt-2">Kaynak: TurkiyeAPI</p>
                            </div>
                        `;

                                    this.marker.bindPopup(popupContent).openPopup();
                                }

                                // Console'a log
                                console.log('✅ TurkiyeAPI Lokasyon:', locationInfo);
                            }
                        } catch (error) {
                            console.error('TurkiyeAPI lokasyon hatası:', error);
                        }
                    },

                    /**
                     * TurkiyeAPI'den lokasyon verilerini getir
                     * Context7: Harita sistemi için lokasyon dropdown'ları
                     */
                    async getLocationData(type, provinceId = null, districtId = null) {
                        try {
                            const params = new URLSearchParams({
                                type
                            });
                            if (provinceId) params.append('province_id', provinceId);
                            if (districtId) params.append('district_id', districtId);

                            const response = await fetch(
                                `{{ route('admin.wikimapia-search.location-data') }}?${params.toString()}`, {
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                });

                            if (!response.ok) {
                                console.warn('TurkiyeAPI lokasyon verisi alınamadı:', response.status);
                                return null;
                            }

                            const data = await response.json();

                            if (data.success) {
                                return data.data;
                            }

                            return null;
                        } catch (error) {
                            console.error('TurkiyeAPI lokasyon verisi hatası:', error);
                            return null;
                        }
                    }
                };
            }
        </script>
    @endpush
@endsection
