{{-- 🗺️ Arsa/Arazi Polygon Araçları --}}
{{-- Kategori arsa/arazi olduğunda aktif olur --}}
<div x-data="{
    polygonManager: null,
    isArsaCategory: false,
    hasPolygon: false,
    calculatedArea: 0,
    centroidLat: null,
    centroidLng: null,
    geojsonData: null,
    showUploadModal: false,
    geojsonInput: '',
    uploadError: '',

    init() {
        // Kategori değişimini dinle
        window.addEventListener('wizard:category-changed', (e) => {
                    const slug = (e.detail?.kategoriSlug || '').toLowerCase();
                    const catId = parseInt(e.detail?.kategoriId || 0);
                    // Parent "Arsa & Arazi" (id=3) veya alt kategorileri (id 15-22) this.isArsaCategory=catId===3 ||
    (catId>= 15 && catId <= 22) || slug.includes('arsa') || slug.includes('arazi') || slug.includes('tarla') ||
        slug.includes('zeytin') || slug.includes('bahce') || slug === 'turizm-otel-kamp' || slug === 'turizm-konut' ||
        slug === 'sanayi-ticari-imar'; if (this.isArsaCategory) { this.$nextTick(()=> this.initPolygonTools());
        } else {
        this.disablePolygonTools();
        }
        });

        // Alt kategori select'ten de kontrol et
        const altKatSelect = document.getElementById('alt_kategori_id');
        if (altKatSelect) {
        altKatSelect.addEventListener('change', () => {
        const val = parseInt(altKatSelect.value || 0);
        const text = altKatSelect.options[altKatSelect.selectedIndex]?.text?.toLowerCase() || '';
        // ID veya text bazlı kontrol
        this.isArsaCategory = (val >= 15 && val <= 22) || text.includes('arsa') || text.includes('arazi') ||
            text.includes('tarla') || text.includes('zeytin') || text.includes('bahçe') || text.includes('bahce'); if
            (this.isArsaCategory) { this.$nextTick(()=> this.initPolygonTools());
            } else {
            this.disablePolygonTools();
            }
            });
            }
            },

            initPolygonTools() {
            // Use the globally exposed wizard map
            const map = window.wizardMap;
            if (map) {
            this._setupManager(map);
            return;
            }

            // Fallback: wait for map to initialize
            const checkMap = () => {
            if (window.wizardMap) {
            this._setupManager(window.wizardMap);
            } else {
            setTimeout(checkMap, 500);
            }
            };
            setTimeout(checkMap, 500);
            },

            _setupManager(map) {
            if (this.polygonManager) return;

            this.polygonManager = new MapPolygonManager(map, {
            onGeometryChange: (geojson, centroid) => {
            this.geojsonData = geojson;
            this.hasPolygon = !!geojson;

            if (centroid) {
            this.centroidLat = centroid.lat.toFixed(6);
            this.centroidLng = centroid.lng.toFixed(6);

            // Hidden input'lara yaz
            const latInput = document.getElementById('lat') || document.querySelector('input[name=enlem]');
            const lngInput = document.getElementById('lng') || document.querySelector('input[name=boylam]');
            if (latInput) latInput.value = centroid.lat.toFixed(6);
            if (lngInput) lngInput.value = centroid.lng.toFixed(6);

            // Coordinate display güncelle
            const latDisp = document.getElementById('lat-display-step4');
            const lngDisp = document.getElementById('lng-display-step4');
            if (latDisp) latDisp.textContent = centroid.lat.toFixed(6);
            if (lngDisp) lngDisp.textContent = centroid.lng.toFixed(6);
            }

            // Hidden input: boundary_geojson
            let geojsonInput = document.querySelector('input[name=boundary_geojson]');
            if (!geojsonInput) {
            geojsonInput = document.createElement('input');
            geojsonInput.type = 'hidden';
            geojsonInput.name = 'boundary_geojson';
            document.querySelector('form')?.appendChild(geojsonInput);
            }
            geojsonInput.value = geojson ? JSON.stringify(geojson) : '';

            // Hidden input: geometry_type
            let typeInput = document.querySelector('input[name=geometry_type]');
            if (!typeInput) {
            typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'geometry_type';
            document.querySelector('form')?.appendChild(typeInput);
            }
            typeInput.value = geojson ? 'polygon' : 'point';
            },
            onAreaCalculated: (area) => {
            this.calculatedArea = Math.round(area);

            // Alan m2 alanına yaz
            const alanInput = document.querySelector('input[name=alan_m2]') ||
            document.getElementById('alan_m2');
            if (alanInput && area > 0) {
            alanInput.value = Math.round(area);
            }

            // Hidden input: boundary_area
            let areaInput = document.querySelector('input[name=boundary_area]');
            if (!areaInput) {
            areaInput = document.createElement('input');
            areaInput.type = 'hidden';
            areaInput.name = 'boundary_area';
            document.querySelector('form')?.appendChild(areaInput);
            }
            areaInput.value = Math.round(area);
            },
            });

            this.polygonManager.enableDrawing();
            console.log('✅ Polygon araçları aktif');
            },

            disablePolygonTools() {
            if (this.polygonManager) {
            this.polygonManager.disableDrawing();
            this.polygonManager = null;
            }
            this.hasPolygon = false;
            this.calculatedArea = 0;
            },

            clearPolygon() {
            if (this.polygonManager) {
            this.polygonManager.clearPolygon();
            }
            this.hasPolygon = false;
            this.calculatedArea = 0;
            this.centroidLat = null;
            this.centroidLng = null;
            this.geojsonData = null;
            },

            uploadGeoJSON() {
            this.uploadError = '';
            try {
            const data = JSON.parse(this.geojsonInput);

            // Validate - must be Polygon or Feature with Polygon
            let geometry = data;
            if (data.type === 'Feature') geometry = data.geometry;
            if (data.type === 'FeatureCollection' && data.features?.length) {
            geometry = data.features[0].geometry;
            }

            if (!geometry || geometry.type !== 'Polygon') {
            this.uploadError = 'Geçersiz GeoJSON: Polygon türü olmalı';
            return;
            }

            const result = this.polygonManager.loadGeoJSON(geometry);
            if (result) {
            this.showUploadModal = false;
            this.geojsonInput = '';
            } else {
            this.uploadError = 'GeoJSON yüklenemedi';
            }
            } catch (e) {
            this.uploadError = 'Geçersiz JSON formatı: ' + e.message;
            }
            },

            handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
            this.geojsonInput = e.target.result;
            };
            reader.readAsText(file);
            }
            }" x-show="isArsaCategory" x-transition x-cloak class="space-y-4">

            {{-- Polygon Toolbar --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-900 dark:text-amber-200">Arsa Sınırları</h4>
                            <p class="text-sm text-amber-700 dark:text-amber-400">Haritada polygon çizin veya GeoJSON
                                yükleyin
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- GeoJSON Upload Button --}}
                        <button type="button" @click="showUploadModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            GeoJSON Yükle
                        </button>

                        {{-- Clear Button --}}
                        <button type="button" @click="clearPolygon()" x-show="hasPolygon"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Temizle
                        </button>
                    </div>
                </div>

                {{-- Info: Draw polygon instructions --}}
                <div x-show="!hasPolygon"
                    class="mt-3 flex items-center gap-2 text-sm text-amber-700 dark:text-amber-400">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Haritada sağ üstteki polygon aracını kullanarak arsa sınırlarını çizin, veya GeoJSON dosyası
                    yükleyin.
                </div>

                {{-- Polygon Info --}}
                <div x-show="hasPolygon" x-transition class="mt-3 grid grid-cols-3 gap-4">
                    <div
                        class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-amber-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alan</div>
                        <div class="text-lg font-bold text-amber-900 dark:text-amber-200"
                            x-text="calculatedArea.toLocaleString('tr-TR') + ' m²'"></div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-amber-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Centroid Lat
                        </div>
                        <div class="text-lg font-mono font-bold text-blue-600 dark:text-blue-400"
                            x-text="centroidLat || '—'">
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-amber-200 dark:border-slate-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Centroid Lng
                        </div>
                        <div class="text-lg font-mono font-bold text-blue-600 dark:text-blue-400"
                            x-text="centroidLng || '—'">
                        </div>
                    </div>
                </div>
            </div>

            {{-- GeoJSON Upload Modal --}}
            <div x-show="showUploadModal" x-transition
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
                @click.self="showUploadModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 w-full max-w-lg mx-4 p-6"
                    @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">GeoJSON Yükle</h3>
                        <button type="button" @click="showUploadModal = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- File Upload --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dosya Seç
                            (.geojson,
                            .json)</label>
                        <input type="file" accept=".geojson,.json" @change="handleFileUpload($event)"
                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-semibold
                        file:bg-amber-50 file:text-amber-700
                        dark:file:bg-amber-900/30 dark:file:text-amber-400
                        hover:file:bg-amber-100 dark:hover:file:bg-amber-900/50" />
                    </div>

                    {{-- Or paste --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">veya GeoJSON
                            yapıştır</label>
                        <textarea x-model="geojsonInput" rows="8"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white resize-y focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            placeholder='{"type": "Polygon", "coordinates": [[[27.42, 37.05], ...]]}'></textarea>
                    </div>

                    {{-- Error --}}
                    <div x-show="uploadError" x-transition
                        class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400"
                        x-text="uploadError"></div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                            İptal
                        </button>
                        <button type="button" @click="uploadGeoJSON()"
                            class="px-4 py-2 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-sm">
                            Yükle & Çiz
                        </button>
                    </div>
                </div>
            </div>
</div>
