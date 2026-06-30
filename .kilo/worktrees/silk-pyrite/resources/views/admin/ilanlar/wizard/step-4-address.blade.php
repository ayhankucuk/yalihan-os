@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 4: İLAN ADRESİ --}}
<div class="space-y-6">
    <div class="mb-6">
        <h3
            class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            📍 İlan Adresi
        </h3>
        <p class="{{ FormStandards::help() }} !text-sm">İlanınızın konum bilgilerini ve harita üzerindeki yerini
            belirleyin</p>
    </div>

    <div x-data="{
        locationWizard: null,
        mapInitialized: false,
        initStep4Map() {
            if (this.mapInitialized) return;
    
            const mapStep4 = document.getElementById('map-step4');
            if (!mapStep4) {
                setTimeout(() => this.initStep4Map(), 200);
                return;
            }
    
            if (mapStep4._leaflet_id) {
                this.mapInitialized = true;
                return;
            }
    
            if (typeof window.locationWizard === 'function') {
                this.locationWizard = window.locationWizard();
                this.locationWizard.initMap();
                this.mapInitialized = true;
                console.log('✅ Step 4 harita başlatıldı');
            }
        }
    }" x-init="// İlk yükleme
    setTimeout(() => initStep4Map(), 500);
    
    // Wizard step değişikliğini dinle
    window.addEventListener('wizard-step-changed', (e) => {
        if (e.detail.step === 4 && !mapInitialized) {
            setTimeout(() => initStep4Map(), 300);
        }
    });" class="space-y-6">

        {{-- Konum Bilgileri Formu --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-6 dark:mb-6">
                <div
                    class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center dark:justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3
                    class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    Konum Bilgileri</h3>
            </div>

            <div class="space-y-4 dark:space-y-4">
                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-4 dark:gap-4">
                    {{-- İl --}}
                    <div>
                        <label for="il_id" class="wizard-field-label">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span>İl <span class="text-red-500 dark:text-red-400">*</span></span>
                            </div>
                        </label>
                        <select name="il_id" id="il_id" required
                            onchange="loadIlceler(this.value); if(window.updateMapFromIl) window.updateMapFromIl(this.value);"
                            class="wizard-field">
                            <option value="">İl Seçin</option>
                            @foreach ($iller ?? [] as $il)
                                @if (is_object($il))
                                    <option value="{{ $il->id }}"
                                        {{ old('il_id', 48) == $il->id ? 'selected' : '' }}>
                                        {{ $il->il_adi }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    {{-- İlçe --}}
                    <div>
                        <label for="ilce_id" class="wizard-field-label">
                            İlçe <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <select name="ilce_id" id="ilce_id" required
                            onchange="loadMahalleler(this.value); if(window.updateMapFromIlce) window.updateMapFromIlce(this.value);"
                            disabled class="wizard-field disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Önce İl Seçin</option>
                        </select>
                    </div>

                    {{-- Mahalle --}}
                    <div>
                        <label for="mahalle_id" class="wizard-field-label">
                            Mahalle <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <select name="mahalle_id" id="mahalle_id" required disabled
                            onchange="if(window.updateMapFromLocation) window.updateMapFromLocation()"
                            class="wizard-field disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Önce İlçe Seçin</option>
                        </select>
                    </div>
                </div>

                {{-- Açık Adres --}}
                <div>
                    <label for="adres_detay" class="wizard-field-label">Açık Adres Detayları</label>
                    <textarea name="adres_detay" id="adres_detay" rows="3" class="wizard-field"
                        placeholder="Sokak, Bina No, Kat, Daire, Posta Kodu gibi detayları girin..."></textarea>
                </div>
            </div>
        </div>

        {{-- POI Selector --}}
        @include('admin.ilanlar.wizard.components.poi-selector')

        {{-- Polygon Tools (Arsa/Arazi kategorisi için) --}}
        @include('admin.ilanlar.wizard.components.polygon-tools')

        {{-- Harita --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg overflow-hidden dark:border-slate-700">
            <div
                class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div
                    class="flex items-center justify-between flex-wrap gap-4 dark:gap-4">
                    <div class="flex items-center gap-3 dark:gap-3">
                        <div
                            class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center dark:justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                Harita</h3>
                            <p class="{{ FormStandards::help() }}">Haritaya tıklayarak konum seçin - TKGM verileri
                                otomatik gelecek</p>
                        </div>
                    </div>
                    <div id="map-layer-control" class="flex items-center gap-2 dark:gap-2">
                        <button type="button" id="map-view-button-step4" onclick="switchMapView('map')"
                            class="px-4 py-2 text-sm font-bold rounded-lg bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-transparent dark:border-slate-700 dark:text-slate-300">
                            🗺️ Harita
                        </button>
                        <button type="button" id="satellite-view-button-step4" onclick="switchMapView('satellite')"
                            class="px-4 py-2 text-sm font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-500/20 dark:shadow-none transition-all active:scale-95 border border-transparent">
                            🛰️ Uydu
                        </button>
                    </div>
                </div>
            </div>
            <div id="wizard-map-container-step4" class="relative dark:relative" style="height: 600px;">
                <div id="map-step4" class="w-full h-full z-0 bg-gray-100 dark:bg-slate-900 border-none"></div>

                {{-- Map Search Overlay --}}
                <div
                    class="absolute top-4 left-4 z-[1000] w-72 dark:w-72">
                    <div class="relative dark:relative">
                        <input type="text" id="map-search-input-step4" class="wizard-field shadow-xl"
                            placeholder="Yer ara (Bodrum, Yalıkavak...)">
                        <div id="search-results-step4"
                            class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-lg shadow-2xl border border-gray-100 dark:border-slate-800 hidden dark:hidden">
                        </div>
                    </div>
                </div>

                {{-- Coordinates Display --}}
                <div class="absolute bottom-4 left-4 z-[1000] flex flex-col gap-2">
                    <div
                        class="bg-white dark:bg-slate-900 backdrop-blur-xl px-4 py-3 rounded-xl shadow-xl border border-gray-200 dark:border-slate-800 flex items-center gap-4 text-gray-900 dark:text-white dark:border-slate-700 dark:text-slate-100">
                        <div class="flex items-center gap-2 dark:gap-2">
                            <span
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Enlem</span>
                            <code id="lat-display-step4"
                                class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400">0.000000</code>
                        </div>
                        <div class="w-px h-4 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="flex items-center gap-2 dark:gap-2">
                            <span
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Boylam</span>
                            <code id="lng-display-step4"
                                class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400">0.000000</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
