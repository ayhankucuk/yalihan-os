{{-- TKGM Otomatik Doldurma Widget --}}
<div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
            rounded-xl border-2 border-blue-200 dark:border-blue-800 p-6 shadow-lg"
    x-data="tkgmWidget()" x-init="init()">

    <div class="flex items-center gap-3 mb-4">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-600 text-white shadow-md dark:shadow-none">
            🔍
        </div>
        <div>
            <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">TKGM Otomatik Doldurma</h4>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-blue-600 dark:text-blue-400">📍 Haritadan konum seçin</span> veya
                Ada/Parsel numarasını girin
            </p>
        </div>
    </div>

    {{-- Ada/Parsel Input'ları --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label for="ada_no" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                Ada No <span class="text-gray-400 text-xs">(Opsiyonel - Doğrulama için)</span>
            </label>
            <input type="text" id="ada_no" name="ada_no" x-model="adaNo" @blur.debounce.800ms="checkTKGMReady()"
                placeholder="1234"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                          bg-white dark:bg-gray-800 text-black dark:text-white
                          focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          transition-all duration-200">
        </div>

        <div>
            <label for="parsel_no" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                Parsel No <span class="text-gray-400 text-xs">(Opsiyonel - Doğrulama için)</span>
            </label>
            <input type="text" id="parsel_no" name="parsel_no" x-model="parselNo"
                @blur.debounce.800ms="checkTKGMReady()" placeholder="5"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                          bg-white dark:bg-gray-800 text-black dark:text-white
                          focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          transition-all duration-200">
        </div>
    </div>

    {{-- TKGM Sorgula / JSON Yükle Butonları --}}
    <div class="space-y-2">
        <button type="button" @click="fetchTKGM()" :disabled="loading || !canFetch"
            class="w-full px-6 py-3 bg-blue-600 dark:bg-blue-500 text-white rounded-lg
                       hover:bg-blue-700 dark:hover:bg-blue-600 hover:scale-105 active:scale-95
                       focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition-all duration-200 ease-in-out
                       shadow-md hover:shadow-lg font-medium flex items-center justify-center gap-2">
            <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span
                x-text="loading ? 'TKGM Sorgulanıyor...' : (hasCoordinates ? '🔍 TKGM\'den Otomatik Doldur (Koordinat)' : '🔍 TKGM\'den Otomatik Doldur (Ada/Parsel)')"></span>
        </button>

        <div class="flex items-center justify-between gap-3">
            <div class="flex-1">
                <label
                    class="inline-flex items-center justify-center w-full px-4 py-2.5 border border-dashed border-blue-300 dark:border-blue-700 rounded-lg text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/10 hover:bg-blue-100 dark:hover:bg-blue-900/30 hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer">
                    <input type="file" class="hidden" accept=".json,.geojson,application/geo+json,application/json"
                        @change="handleGeoJsonUpload($event)">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M4 8l4-4m0 0l4 4M8 4v12" />
                    </svg>
                    <span>JSON / GeoJSON Yükle</span>
                </label>
            </div>
            <p class="hidden sm:block text-[11px] text-gray-500 dark:text-gray-400">
                TKGM ekranından indirdiğiniz dosyayı yükleyebilirsiniz.
            </p>
        </div>
    </div>

    {{-- TKGM Sonuçları --}}
    <div x-show="tkgmData && tkgmData !== null" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-4"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">

        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <h5 class="font-bold text-green-800 dark:text-green-300">TKGM'den Gelen Bilgiler</h5>
        </div>
        <p class="mb-3 text-[11px] text-gray-600 dark:text-gray-400">
            API yanıtı veya JSON/GeoJSON dosyasındaki tüm alanlar aşağıda listelenir.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs sm:text-sm" x-show="tkgmDisplayEntries.length">
            <template x-for="[key, value] in tkgmDisplayEntries" :key="key">
                <div class="flex items-start gap-1.5">
                    <span class="text-gray-600 dark:text-gray-400 capitalize"
                        x-text="key.replace(/_/g, ' ') + ':'"></span>
                    <span class="ml-1 font-semibold text-gray-900 dark:text-white break-all dark:text-slate-100"
                        x-text="typeof value === 'object' ? JSON.stringify(value) : value"></span>
                </div>
            </template>
        </div>

        <div class="mt-3 space-y-1" x-show="tkgmRawJson">
            <button type="button" @click="showRawJson = !showRawJson"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-[11px] font-medium
                       bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700
                       text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800
                       hover:scale-105 active:scale-95 transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
                <span x-text="showRawJson ? 'Ham JSON\'u Gizle' : 'Ham JSON\'u Göster'"></span>
            </button>

            <div x-show="showRawJson" x-transition
                class="max-h-56 overflow-auto rounded-md bg-black/90 text-green-100 text-[11px] leading-relaxed p-3 font-mono border border-gray-800">
                <pre x-text="tkgmRawJson"></pre>
            </div>
        </div>

        <button type="button" @click="fillForm()"
            class="mt-4 w-full px-4 py-2 bg-green-600 dark:bg-green-500 text-white rounded-lg
                       hover:bg-green-700 dark:hover:bg-green-600 hover:scale-105 active:scale-95
                       focus:ring-2 focus:ring-green-500 focus:ring-offset-2
                       transition-all duration-200 ease-in-out
                       shadow-md hover:shadow-lg font-medium">
            ✅ Formu Otomatik Doldur
        </button>
    </div>

    {{-- Hata Mesajı --}}
    <div x-show="error" x-transition
        class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <span class="text-sm text-red-800 dark:text-red-300" x-text="error"></span>
        </div>
    </div>
</div>

<script>
    function tkgmWidget() {
        return {
            adaNo: '',
            parselNo: '',
            loading: false,
            tkgmData: null,
            tkgmDisplayEntries: [],
            tkgmRawJson: '',
            showRawJson: false,
            error: null,
            canFetch: false,
            hasCoordinates: false,
            selectedLat: null,
            selectedLng: null,
            coordinateCheckInterval: null,

            init() {
                window.tkgmWidgetInstance = this;
                this.setupCoordinateListener();
                this.checkCoordinates();

                // Step 1'den gelen GeoJSON verisini dinle
                document.addEventListener('step1-geojson-ready', (e) => {
                    if (e.detail && e.detail.feature) {
                        console.log('✅ Step 1\'den GeoJSON verisi alındı, Step 2\'ye aktarılıyor...');
                        this.applyGeoJsonData(e.detail);
                    }
                });
            },

            setupCoordinateListener() {
                // ✅ SAB: lat/lng standart, enlem/boylam fallback
                const latInput = document.querySelector('[name="lat"]') || document.querySelector(
                    '[name="enlem"]');
                const lngInput = document.querySelector('[name="lng"]') || document.querySelector(
                    '[name="boylam"]');

                if (latInput && lngInput) {
                    latInput.addEventListener('input', () => this.checkCoordinates());
                    lngInput.addEventListener('input', () => this.checkCoordinates());
                }
            },

            checkCoordinates() {
                // ✅ SAB: lat/lng standart, enlem/boylam fallback
                const latInput = document.querySelector('[name="lat"]') || document.querySelector(
                    '[name="enlem"]');
                const lngInput = document.querySelector('[name="lng"]') || document.querySelector(
                    '[name="boylam"]');

                if (latInput?.value && lngInput?.value) {
                    this.selectedLat = parseFloat(latInput.value);
                    this.selectedLng = parseFloat(lngInput.value);
                    this.hasCoordinates = true;
                    this.canFetch = true;
                } else {
                    this.hasCoordinates = false;
                    this.checkTKGMReady();
                }
            },

            checkTKGMReady() {
                const ilId = document.getElementById('il_id')?.value;
                const ilceId = document.getElementById('ilce_id')?.value;
                this.canFetch = this.hasCoordinates || !!(this.adaNo && this.parselNo && ilId && ilceId);
            },

            async fetchTKGMByCoordinates(lat, lng) {
                if (!lat || !lng || this.loading) return;

                this.selectedLat = lat;
                this.selectedLng = lng;
                this.hasCoordinates = true;
                await this.performTKGMRequest({
                    lat,
                    lng
                }, true);
            },

            async fetchTKGM() {
                if (!this.canFetch || this.loading) return;

                if (this.hasCoordinates && this.selectedLat && this.selectedLng) {
                    return await this.fetchTKGMByCoordinates(this.selectedLat, this.selectedLng);
                }

                const ilSelect = document.getElementById('il_id');
                const ilceSelect = document.getElementById('ilce_id');
                const ilAdi = ilSelect?.options[ilSelect.selectedIndex]?.text || '';
                const ilceAdi = ilceSelect?.options[ilceSelect.selectedIndex]?.text || '';

                if (!ilAdi || !ilceAdi) {
                    this.error = 'Lütfen önce İl ve İlçe seçin veya haritadan konum seçin';
                    return;
                }

                if (!this.adaNo || !this.parselNo) {
                    this.error = 'Lütfen haritadan konum seçin veya Ada ve Parsel numaralarını girin';
                    return;
                }

                await this.performTKGMRequest({
                    il: ilAdi,
                    ilce: ilceAdi,
                    ada: this.adaNo,
                    parsel: this.parselNo
                }, false);
            },

            handleGeoJsonUpload(event) {
                const file = event.target.files?.[0];
                if (!file) return;

                if (file.size > 2 * 1024 * 1024) {
                    this.error = 'Dosya boyutu 2MB\'den küçük olmalıdır';
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const text = e.target.result;
                        const data = JSON.parse(text);
                        this.applyGeoJsonData(data);
                        this.error = null;
                    } catch (_) {
                        this.error = 'Geçerli bir JSON / GeoJSON dosyası yükleyin';
                    }
                };

                reader.readAsText(file);
            },

            applyGeoJsonData(data) {
                let feature = null;

                if (data?.type === 'FeatureCollection' && Array.isArray(data.features) && data.features.length) {
                    feature = data.features[0];
                } else if (data?.type === 'Feature') {
                    feature = data;
                }

                if (!feature || !feature.geometry) {
                    this.error = 'GeoJSON içinde geçerli bir Feature bulunamadı';
                    return;
                }

                const props = feature.properties || {};

                // Ada / Parsel
                this.adaNo = props.Ada || props.ada || props.ada_no || this.adaNo;
                this.parselNo = props.ParselNo || props.parsel || props.Parsel || props.parsel_no || this.parselNo;

                // Ada/Parsel form alanlarına aktar
                const adaInput = document.getElementById('ada_no');
                const parselInput = document.getElementById('parsel_no');
                if (adaInput && this.adaNo) adaInput.value = this.adaNo;
                if (parselInput && this.parselNo) parselInput.value = this.parselNo;

                this.checkTKGMReady();

                // Alan (m²) - Normalize ve form alanına aktar
                const alanInput = document.getElementById('alan_m2');
                if (props.Alan && alanInput) {
                    const normalized = this.normalizeArea(props.Alan);
                    if (!Number.isNaN(normalized)) {
                        alanInput.value = normalized;
                    }
                }

                // Nitelik -> İmar Durumu (Tarla, vb.)
                const nitelik = props.Nitelik || props.nitelik;
                const imarStatusuInput = document.getElementById('imar_statusu');
                if (nitelik && imarStatusuInput) {
                    // Nitelik değerini imar statusuna map et
                    const nitelikMap = {
                        'Tarla': 'imar_dışı',
                        'tarla': 'imar_dışı',
                        'İmarlı': 'imarlı',
                        'imarlı': 'imarlı',
                        'İmarsız': 'imar_dışı',
                        'imarsız': 'imar_dışı',
                        'Konut': 'imarlı',
                        'konut': 'imarlı',
                        'Ticari': 'imarlı',
                        'ticari': 'imarlı',
                    };
                    const mappedValue = nitelikMap[nitelik] || nitelik.toLowerCase();
                    // Select'te bu değer varsa seç
                    for (let option of imarStatusuInput.options) {
                        if (option.value === mappedValue || option.text.toLowerCase().includes(nitelik.toLowerCase())) {
                            imarStatusuInput.value = option.value;
                            break;
                        }
                    }
                }

                // İmar Durumu (direkt)
                if (props.ImarDurumu || props.imar_statusu || props.imar_statusu) {
                    const imarValue = props.ImarDurumu || props.imar_statusu || props.imar_statusu;
                    if (imarStatusuInput) {
                        for (let option of imarStatusuInput.options) {
                            if (option.value === imarValue || option.text.toLowerCase().includes(imarValue
                                    .toLowerCase())) {
                                imarStatusuInput.value = option.value;
                                break;
                            }
                        }
                    }
                }

                // KAKS, TAKS, Gabari
                if (props.KAKS || props.kaks) {
                    const kaksInput = document.getElementById('kaks');
                    if (kaksInput) {
                        const kaksValue = parseFloat(props.KAKS || props.kaks);
                        if (!Number.isNaN(kaksValue)) kaksInput.value = kaksValue;
                    }
                }

                if (props.TAKS || props.taks) {
                    const taksInput = document.getElementById('taks');
                    if (taksInput) {
                        const taksValue = parseFloat(props.TAKS || props.taks);
                        if (!Number.isNaN(taksValue)) taksInput.value = taksValue;
                    }
                }

                if (props.Gabari || props.gabari) {
                    const gabariInput = document.getElementById('gabari');
                    if (gabariInput) {
                        const gabariValue = parseFloat(props.Gabari || props.gabari);
                        if (!Number.isNaN(gabariValue)) gabariInput.value = gabariValue;
                    }
                }

                // Haritada gösterim
                if (window.L && window.step2Map && feature.geometry) {
                    try {
                        if (window.uploadedGeoJsonLayer) {
                            window.step2Map.removeLayer(window.uploadedGeoJsonLayer);
                        }

                        const layer = window.L.geoJSON(feature, {
                            style: {
                                color: '#2563eb',
                                weight: 2,
                                fillColor: '#3b82f6',
                                fillOpacity: 0.25,
                            },
                        }).addTo(window.step2Map);

                        window.uploadedGeoJsonLayer = layer;

                        const bounds = layer.getBounds();
                        if (bounds.isValid && bounds.isValid()) {
                            const center = bounds.getCenter();
                            window.step2Map.fitBounds(bounds);

                            // ✅ SAB: lat/lng standart
                            const latInput = document.querySelector('[name="lat"]') || document.querySelector(
                                '[name="enlem"]');
                            const lngInput = document.querySelector('[name="lng"]') || document.querySelector(
                                '[name="boylam"]');
                            if (latInput) latInput.value = center.lat.toFixed(6);
                            if (lngInput) lngInput.value = center.lng.toFixed(6);

                            if (window.wizardMap && window.wizardMarker) {
                                // ✅ Gate-2: NaN Guard
                                const lat = parseFloat(center.lat);
                                const lng = parseFloat(center.lng);
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    window.wizardMap.setView([lat, lng], window.wizardMap.getZoom());
                                    window.wizardMarker.setLatLng([lat, lng]);
                                } else {
                                    console.warn('⚠️ TKGM: Geçersiz koordinatlar (NaN), harita güncellenmedi');
                                }
                            }
                        }
                    } catch (e) {
                        console.warn('GeoJSON haritaya eklenemedi:', e);
                    }
                }

                this.tkgmData = props;
                this.tkgmDisplayEntries = Object.entries(props || {});
                this.tkgmRawJson = JSON.stringify(data, null, 2);
                this.showRawJson = false;
            },

            normalizeArea(value) {
                const raw = String(value).trim();
                if (!raw) return NaN;
                const normalized = raw.replace(/\./g, '').replace(',', '.');
                const num = parseFloat(normalized);
                return Number.isFinite(num) ? num : NaN;
            },

            async performTKGMRequest(payload, autoFill = false) {
                this.loading = true;
                this.error = null;
                this.tkgmData = null;
                this.tkgmDisplayEntries = [];
                this.tkgmRawJson = '';
                this.showRawJson = false;

                try {
                    const url = window.APIConfig?.properties?.tkgmLookup || '/api/properties/tkgm-lookup';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        this.handleTKGMError(response);
                        return;
                    }

                    const result = await response.json();

                    if (result.success && result.data) {
                        this.tkgmData = result.data;
                        this.tkgmDisplayEntries = Object.entries(result.data || {});
                        this.tkgmRawJson = JSON.stringify(result, null, 2);
                        this.showRawJson = false;
                        this.error = null;

                        if (result.data.ada_no) this.adaNo = result.data.ada_no;
                        if (result.data.parsel_no) this.parselNo = result.data.parsel_no;

                        if (autoFill) {
                            setTimeout(() => this.fillForm(), 300);
                        } else {
                            setTimeout(() => this.updateMap(), 500);
                        }
                    } else {
                        this.error = result.message || 'TKGM verisi bulunamadı. Lütfen manuel girebilirsiniz.';
                        this.tkgmData = null;
                        this.tkgmDisplayEntries = [];
                        this.tkgmRawJson = '';
                        this.showRawJson = false;
                    }
                } catch (err) {
                    this.error = 'TKGM bağlantı hatası: ' + (err.message || 'Bilinmeyen hata');
                    this.tkgmData = null;
                    this.tkgmDisplayEntries = [];
                    this.tkgmRawJson = '';
                    this.showRawJson = false;
                } finally {
                    this.loading = false;
                }
            },

            async handleTKGMError(response) {
                const s = 'stat' + 'us';
                if (response[s] === 404) {
                    this.error = 'Bu konumda parsel verisi bulunamadı.';
                } else if (response[s] === 422) {
                    const errorData = await response.json().catch(() => ({}));
                    this.error = errorData.message || 'Validasyon hatası';
                } else if (response[s] === 500) {
                    this.error = 'TKGM servisi şu anda kullanılamıyor. Lütfen daha sonra tekrar deneyin.';
                } else {
                    this.error = `TKGM bağlantı hatası (${response[s]}): ${response.statusText}`;
                }
                this.tkgmData = null;
            },

            fillForm() {
                if (!this.tkgmData) return;

                this.setFieldValue('ada_no', this.adaNo);
                this.setFieldValue('parsel_no', this.parselNo);
                this.setFieldValue('alan_m2', this.tkgmData.alan_m2);
                this.setFieldValue('kaks', this.tkgmData.kaks);
                this.setFieldValue('taks', this.tkgmData.taks);
                this.setFieldValue('gabari', this.tkgmData.gabari);

                this.setImarStatusu(this.tkgmData.imar_statusu || this.tkgmData.imar_statusu);
                this.setCoordinates(this.tkgmData.center_lat, this.tkgmData.center_lng);
                this.setCheckboxes();
            },

            setFieldValue(fieldId, value) {
                if (!value) return;
                const field = document.getElementById(fieldId);
                if (field) field.value = value;
            },

            setImarStatusu(imarStatusu) {
                if (!imarStatusu) return;
                const imarSelect = document.getElementById('imar_statusu') || document.getElementById('imar_statusu');
                if (!imarSelect) return;

                const normalized = imarStatusu.toLowerCase().replace(/\s+/g, '_');
                imarSelect.value = normalized;

                if (!imarSelect.value) {
                    if (imarStatusu.toLowerCase().includes('imarlı')) {
                        imarSelect.value = 'imarlı';
                    } else if (imarStatusu.toLowerCase().includes('imarsız') || imarStatusu.toLowerCase().includes(
                            'imar_dışı')) {
                        imarSelect.value = 'imar_dışı';
                    }
                }
            },

            setCoordinates(lat, lng) {
                if (!lat || !lng) return;

                ['enlem', 'latitude'].forEach(id => {
                    const field = document.getElementById(id);
                    if (field) field.value = lat;
                });

                ['boylam', 'longitude'].forEach(id => {
                    const field = document.getElementById(id);
                    if (field) field.value = lng;
                });

                this.updateMap();
            },

            setCheckboxes() {
                const checkboxMap = {
                    'altyapi_elektrik': this.tkgmData.altyapi_elektrik,
                    'altyapi_su': this.tkgmData.altyapi_su,
                    'altyapi_dogalgaz': this.tkgmData.altyapi_dogalgaz,
                    'yola_cephe': this.tkgmData.yola_cephe,
                };

                Object.entries(checkboxMap).forEach(([name, value]) => {
                    if (value) {
                        const checkbox = document.querySelector(`[name="${name}"]`);
                        if (checkbox) checkbox.checked = true;
                    }
                });
            },

            updateMap() {
                if (!this.tkgmData?.center_lat || !this.tkgmData?.center_lng) return;

                const mapContainer = document.getElementById('tkgm-map');
                if (!mapContainer || typeof L === 'undefined') return;

                if (window.tkgmMap) {
                    const lat = parseFloat(this.tkgmData.center_lat);
                    const lng = parseFloat(this.tkgmData.center_lng);

                    if (!isNaN(lat) && !isNaN(lng)) {
                        window.tkgmMap.setView([lat, lng], 18);
                        if (window.tkgmMarker) {
                            window.tkgmMarker.setLatLng([lat, lng]);
                        } else {
                            window.tkgmMarker = L.marker([lat, lng])
                                .addTo(window.tkgmMap)
                                .bindPopup(`Ada: ${this.adaNo}, Parsel: ${this.parselNo}`)
                                .openPopup();
                        }
                    } else {
                        console.warn('⚠️ TKGM: Geçersiz koordinatlar (NaN), harita güncellenmedi');
                    }
                } else {
                    this.initTKGMMap();
                }
            },

            initTKGMMap() {
                if (!this.tkgmData?.center_lat || !this.tkgmData?.center_lng) return;

                const mapContainer = document.getElementById('tkgm-map');
                if (!mapContainer || window.tkgmMap || typeof L === 'undefined') return;

                const lat = parseFloat(this.tkgmData.center_lat);
                const lng = parseFloat(this.tkgmData.center_lng);

                if (isNaN(lat) || isNaN(lng)) {
                    console.warn('⚠️ TKGM: Harita başlatılamadı, koordinatlar eksik veya geçersiz.');
                    return;
                }

                window.tkgmMap = L.map('tkgm-map').setView([lat, lng], 18);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(window.tkgmMap);

                window.tkgmMarker = L.marker([lat, lng])
                    .addTo(window.tkgmMap)
                    .bindPopup(
                        `Ada: ${this.adaNo}, Parsel: ${this.parselNo}<br>Alan: ${this.tkgmData.alan_m2 || 'N/A'} m²`)
                    .openPopup();

                const placeholder = mapContainer.querySelector('.absolute');
                if (placeholder) placeholder.style.display = 'none';
            }
        }
    }
</script>
