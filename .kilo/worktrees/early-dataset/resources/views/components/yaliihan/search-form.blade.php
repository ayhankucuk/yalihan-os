@props([
    'showAdvanced' => true,
    'showSort' => true,
    'class' => '',
])

<div class="search-form {{ $class }}">
    <!-- Main Search Form -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-2xl border border-gray-100 dark:border-slate-800 transition-all duration-300">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- İlan Türü -->
            <div class="space-y-2">
                <label for="listing_type" class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">İlan Türü</label>
                <select id="listing_type" name="listing_type"
                    class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    style="color-scheme: light dark;"
                    aria-label="İlan türü seçiniz">
                    <option value="">Tümü</option>
                    <option value="sale">Satılık</option>
                    <option value="rent">Kiralık</option>
                </select>
            </div>

            <!-- Emlak Türü -->
            <div class="space-y-2">
                <label for="property_type" class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Emlak Türü</label>
                <select id="property_type" name="property_type"
                    class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    style="color-scheme: light dark;"
                    aria-label="Emlak türü seçiniz">
                    <option value="">Tümü</option>
                    <option value="villa">Villa</option>
                    <option value="apartment">Daire</option>
                    <option value="land">Arsa</option>
                    <option value="commercial">İşyeri</option>
                </select>
            </div>

            <!-- Lokasyon -->
            <div class="space-y-2">
                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Lokasyon</label>
                <input type="text" id="location" name="location" placeholder="Şehir, ilçe veya mahalle"
                    class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    aria-label="Lokasyon giriniz" autocomplete="address-level2">
            </div>

            <!-- Search Button -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-transparent">Ara</label>
                <button type="submit"
                    class="w-full bg-blue-600 dark:bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 active:scale-95 transition-all duration-200 font-semibold flex items-center justify-center gap-2 min-h-[48px] touch-manipulation shadow-lg hover:shadow-xl focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    onclick="performSearch()" aria-label="Emlak ara">
                    <span class="search-icon">🔍</span>
                    <span class="search-text">Ara</span>
                </button>
            </div>
        </div>

        @if ($showAdvanced)
            <!-- Advanced Search Toggle -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <button class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center gap-2 transition-colors duration-200"
                    onclick="toggleAdvancedSearch()" aria-label="Gelişmiş arama">
                    <span>🔧</span>
                    <span>Gelişmiş Arama</span>
                    <span id="advancedToggleIcon">▼</span>
                </button>
            </div>
        @endif
    </div>

    @if ($showAdvanced)
        <!-- Advanced Search Panel -->
        <div id="advancedSearchPanel" class="hidden mt-4 bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-slate-800 transition-all duration-300">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Yatak Odası -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Yatak Odası</label>
                    <select
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                        style="color-scheme: light dark;">
                        <option value="">Herhangi</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                    </select>
                </div>

                <!-- Banyo -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Banyo</label>
                    <select
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                        style="color-scheme: light dark;">
                        <option value="">Herhangi</option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                    </select>
                </div>

                <!-- Min Fiyat -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Min. Fiyat</label>
                    <input type="number" placeholder="0"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
                </div>

                <!-- Max Fiyat -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Max. Fiyat</label>
                    <input type="number" placeholder="Sınırsız"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
                </div>

                <!-- Min Alan -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Min. Alan (m²)</label>
                    <input type="number" placeholder="0"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
                </div>

                <!-- Max Alan -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Max. Alan (m²)</label>
                    <input type="number" placeholder="Sınırsız"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
                </div>

                <!-- Özellikler -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Özellikler</label>
                    <select
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                        style="color-scheme: light dark;">
                        <option value="">Seçiniz</option>
                        <option value="pool">Havuz</option>
                        <option value="garden">Bahçe</option>
                        <option value="garage">Garaj</option>
                        <option value="balcony">Balkon</option>
                    </select>
                </div>

                <!-- Tapu Durumu -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Tapu Durumu</label>
                    <select name="tapu_statusu"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                        style="color-scheme: light dark;">
                        <option value="">Herhangi</option>
                        <option>Hisseli Tapu</option>
                        <option>Müstakil Parsel</option>
                        <option>Tahsis</option>
                        <option>Zilliyet</option>
                        <option>Belirtilmemiş</option>
                        <option>Yabancıdan</option>
                        <option>Tapu yok</option>
                        <option>Kıbrıs Tapulu</option>
                        <option>Kooperatiften Hisseli Tapu</option>
                        <option>Müstakil Tapulu</option>
                        <option>İntifa Hakkı Tesisli</option>
                    </select>
                </div>

                <!-- İmar Durumu -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">İmar Durumu</label>
                    <select name="imar_statusu"
                        class="w-full p-3 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                        style="color-scheme: light dark;">
                        <option value="">Herhangi</option>
                        <option>Konut İmarlı Arsa</option>
                        <option>Ticari İmarlı Arsa</option>
                        <option>Tarla / Bağ / Bahçe</option>
                        <option>Sanayi İmarlı Arsa</option>
                        <option>Turizm İmarlı Arsa</option>
                        <option>İmarlı Ticari + Konut</option>
                    </select>
                </div>

                <!-- Temizle Butonu -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-transparent">Temizle</label>
                    <button
                        class="w-full bg-gray-500 dark:bg-gray-600 text-white p-3 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 active:scale-95 transition-all duration-200 font-medium shadow-md hover:shadow-lg focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:shadow-none"
                        onclick="clearAdvancedSearch()" aria-label="Gelişmiş arama filtrelerini temizle">
                        🗑️ Temizle
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showSort)
        <!-- Sort Section -->
        <div class="mt-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700 dark:text-white dark:text-slate-300">Sırala:</span>
                <select
                    class="p-2 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100"
                    style="color-scheme: light dark;">
                    <option value="default">Varsayılan</option>
                    <option value="price_asc">Fiyat (Düşük → Yüksek)</option>
                    <option value="price_desc">Fiyat (Yüksek → Düşük)</option>
                    <option value="featured">Öne Çıkanlar</option>
                    <option value="date_asc">Tarih (Eski → Yeni)</option>
                    <option value="date_desc">Tarih (Yeni → Eski)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-slate-200">Görünüm:</span>
                <button
                    class="p-2 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-700 dark:text-white hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white dark:hover:text-white active:scale-95 transition-all duration-200 dark:text-slate-300"
                    title="Grid" aria-label="Grid görünümü">
                    ⊞
                </button>
                <button
                    class="p-2 border border-gray-300 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 text-gray-700 dark:text-white hover:bg-blue-500 dark:hover:bg-blue-600 hover:text-white dark:hover:text-white active:scale-95 transition-all duration-200 dark:text-slate-300"
                    title="Liste" aria-label="Liste görünümü">
                    ☰
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Search functionality moved to search-optimizer.js -->
