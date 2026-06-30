/**
 * Location Wizard Logic (Context7 Compliant)
 * Handles: Address Selects, Leaflet Map, POI Sync
 */

window.locationWizard = function () {
    return {
        selectedCity: '',
        selectedDistrict: '',
        selectedNeighborhood: '',

        districts: [],
        neighborhoods: [],

        loadingDistricts: false,
        loadingNeighborhoods: false,

        lat: 37.0344, // Default: Bodrum
        lng: 27.4305,

        map: null,
        marker: null,

        init() {
            // Leaflet CSS'in yüklendiğinden emin olun (layout dosyasında olmalı)
            this.initMap();

            // Bodrum-First Strategy: Muğla (ID: 48) otomatik seçili gelsin
            const ilSelect = document.getElementById('il_id');
            if (ilSelect) {
                // Eğer edit moddaysa (values doluysa) kullan, yoksa Muğla'yı seç
                if (ilSelect.value) {
                    this.selectedCity = ilSelect.value;
                } else {
                    // Muğla (ID: 48) otomatik seç
                    this.selectedCity = '48';
                    ilSelect.value = '48';
                    // Change event'i tetikle
                    ilSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
                this.fetchDistricts();
            }
        },

        initMap() {
            // Leaflet global check
            if (typeof L === 'undefined') {
                console.error('❌ Leaflet JS bulunamadı!');
                const fallback =
                    document.getElementById('map') ||
                    document.getElementById('map-step1') ||
                    document.getElementById('map-step2') ||
                    document.getElementById('map-step4');
                if (fallback) {
                    fallback.innerHTML = `
                        <div class="flex items-center justify-center h-full bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-300 dark:border-red-700 p-8">
                            <div class="text-center">
                                <div class="text-6xl mb-4">🗺️</div>
                                <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-2">Harita Yüklenemedi</h3>
                                <p class="text-sm text-red-500 dark:text-red-300">Leaflet kütüphanesi bulunamadı. Sayfayı yenileyin.</p>
                            </div>
                        </div>
                    `;
                }
                return;
            }

            // Harita container (Step1: #map-step1, Step2: #map-step2, Step4: #map-step4, fallback: #map)
            const mapElement =
                document.getElementById('map-step1') ||
                document.getElementById('map-step2') ||
                document.getElementById('map-step4') ||
                document.getElementById('map');
            if (!mapElement) {
                console.warn(
                    '⚠️ Harita container bulunamadı (map-step1, map-step2, map-step4, map)'
                );
                return;
            }

            // Map initialization - Eğer zaten init edilmişse, mevcut map'i kullan
            if (mapElement._leaflet_id) {
                // Mevcut map instance'ını al
                const existingMap = L.Map.getInstance(mapElement);
                if (existingMap) {
                    this.map = existingMap;
                    // Marker'ı kontrol et
                    if (!this.marker) {
                        this.marker = L.marker([this.lat, this.lng], {
                            draggable: true,
                            icon: L.icon({
                                iconUrl:
                                    'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                                iconRetinaUrl:
                                    'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                                shadowUrl:
                                    'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41],
                            }),
                        }).addTo(this.map);
                    }
                    // Event listener'ları ekle (eğer yoksa)
                    this.setupMapEvents();
                    return;
                }
                mapElement._leaflet_id = null; // Fix for SPA/Alpine re-renders
            }

            // ✅ Koordinatları Step 1'den al (eğer varsa) veya default Bodrum
            const step1LatDisplay = document.getElementById('lat-display');
            const step1LngDisplay = document.getElementById('lng-display');

            if (step1LatDisplay && step1LngDisplay) {
                const step1Lat = parseFloat(step1LatDisplay.textContent) || this.lat;
                const step1Lng = parseFloat(step1LngDisplay.textContent) || this.lng;

                // Eğer Step 1'de gerçek koordinatlar varsa (0.000000 değilse) kullan
                if (step1Lat !== 0 && step1Lng !== 0) {
                    this.lat = step1Lat;
                    this.lng = step1Lng;
                }
            }

            // ✅ NaN Guard: Ensure coordinates are valid numbers
            let safeLat = parseFloat(this.lat);
            let safeLng = parseFloat(this.lng);
            if (isNaN(safeLat) || isNaN(safeLng)) {
                console.warn(
                    `⚠️ Invalid init coordinates: ${this.lat}, ${this.lng}. Defaulting to Bodrum.`
                );
                safeLat = 37.0344;
                safeLng = 27.4305;
            }

            // ✅ Bodrum-First Strategy: Varsayılan Bodrum koordinatları
            this.map = L.map(mapElement).setView([safeLat, safeLng], 12); // Zoom 12 (Bodrum için ideal)

            // ✅ Varsayılan Uydu Görünümü (Esri World Imagery)
            const satelliteLayer = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                {
                    attribution:
                        '© Esri, Maxar, GeoEye, Earthstar Geographics, CNES/Airbus DS, USDA, USGS, AeroGRID, IGN, IGP, and the GIS User Community',
                    maxZoom: 19,
                }
            );

            // Harita görünümü (alternatif)
            const mapLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
            });

            // ✅ Varsayılan olarak uydu görünümünü ekle
            satelliteLayer.addTo(this.map);

            // Layer'ları sakla (switchMapView için)
            this.mapLayers = {
                satellite: satelliteLayer,
                map: mapLayer,
                current: 'satellite',
            };

            // Global window'a kaydet (switchMapView için)
            window.wizardMap = this.map;
            window.wizardMapLayers = this.mapLayers;
            window.wizardMarker = this.marker;

            // Marker
            this.marker = L.marker([this.lat, this.lng], {
                draggable: true,
                icon: L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41],
                }),
            }).addTo(this.map);

            // Global marker'ı güncelle
            window.wizardMarker = this.marker;

            // Event listener'ları kur
            this.setupMapEvents();

            // ✅ Koordinat display'leri güncelle
            this.updateCoordinateDisplays();

            // Fix map resize issues when tab changes
            setTimeout(() => {
                this.map.invalidateSize();
            }, 500);
        },

        setupMapEvents() {
            if (!this.map || !this.marker) return;

            // Mevcut event listener'ları temizle (duplicate önleme)
            this.marker.off('dragend');
            this.map.off('click');

            // Drag event
            this.marker.on('dragend', (event) => {
                const position = event.target.getLatLng();
                this.updateCoordinates(position.lat, position.lng);

                // ✅ POI widget'ı güncelle
                document.dispatchEvent(
                    new CustomEvent('wizard-map-marker-moved', {
                        detail: { lat: position.lat, lng: position.lng },
                    })
                );
            });

            // Map click event
            this.map.on('click', (e) => {
                this.marker.setLatLng(e.latlng);
                this.updateCoordinates(e.latlng.lat, e.latlng.lng);

                // ✅ POI widget'ı güncelle
                document.dispatchEvent(
                    new CustomEvent('wizard-map-marker-moved', {
                        detail: { lat: e.latlng.lat, lng: e.latlng.lng },
                    })
                );
            });
        },

        updateCoordinateDisplays() {
            // Step 1, Step 2 ve Step 4 için display'leri güncelle
            const latDisplays = [
                document.getElementById('lat-display'),
                document.getElementById('lat-display-step2'),
                document.getElementById('lat-display-step4'),
            ].filter(Boolean);

            const lngDisplays = [
                document.getElementById('lng-display'),
                document.getElementById('lng-display-step2'),
                document.getElementById('lng-display-step4'),
            ].filter(Boolean);

            latDisplays.forEach((display) => {
                display.textContent = this.lat.toFixed(6);
            });

            lngDisplays.forEach((display) => {
                display.textContent = this.lng.toFixed(6);
            });
        },

        updateCoordinates(lat, lng) {
            this.lat = lat;
            this.lng = lng;

            // ✅ Koordinat display'leri güncelle
            this.updateCoordinateDisplays();

            // ✅ REFACTOR: Hidden input'ları güncelle - Context7 canonical: lat/lng öncelikli
            // Not: enlem/boylam backward compatibility için korunuyor ama lat/lng tercih edilmeli
            const latInputs = [
                document.querySelector('[name="lat"]'),
                document.getElementById('lat'),
                document.getElementById('lat-input'),
                // Backward compatibility (deprecated):
                document.querySelector('[name="enlem"]'),
                document.querySelector('[name="latitude"]'),
            ].filter(Boolean);

            const lngInputs = [
                document.querySelector('[name="lng"]'),
                document.getElementById('lng'),
                document.getElementById('lng-input'),
                // Backward compatibility (deprecated):
                document.querySelector('[name="boylam"]'),
                document.querySelector('[name="longitude"]'),
            ].filter(Boolean);

            latInputs.forEach((input) => (input.value = lat));
            lngInputs.forEach((input) => (input.value = lng));

            // ✅ Alpine store listing.location ile senkronizasyon
            try {
                if (window.Alpine && typeof Alpine.store === 'function') {
                    const listingStore = Alpine.store('listing');
                    if (listingStore) {
                        if (!listingStore.location) {
                            listingStore.location = {};
                        }
                        listingStore.location.lat = lat;
                        listingStore.location.lng = lng;
                    }
                }
            } catch (e) {
                console.warn('Listing store konum senkronizasyonu sırasında hata:', e);
            }

            // ✅ POI widget'ı güncelle
            document.dispatchEvent(
                new CustomEvent('wizard-map-marker-moved', {
                    detail: { lat, lng },
                })
            );

            // ✅ POI görselleştirme (haritada yakındaki POI'leri göster)
            this.fetchAndShowPois(lat, lng);
        },

        /**
         * Haritada POI noktalarını göster (2km radius)
         */
        poiMarkers: [],
        radiusCircle: null,

        async fetchAndShowPois(lat, lng) {
            if (!this.map) return;

            // Önceki POI marker'larını temizle
            this.poiMarkers.forEach((m) => this.map.removeLayer(m));
            this.poiMarkers = [];
            if (this.radiusCircle) {
                this.map.removeLayer(this.radiusCircle);
                this.radiusCircle = null;
            }

            try {
                const response = await fetch('/api/v1/location/poi-distances', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ lat, lng, radius_km: 2 }),
                });

                if (!response.ok) return;
                const data = await response.json();
                const pois = data.pois || data.data || [];

                if (pois.length === 0) return;

                // Radius circle
                this.radiusCircle = L.circle([lat, lng], {
                    radius: 2000,
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.04,
                    weight: 1,
                    dashArray: '6, 8',
                }).addTo(this.map);

                // POI icons
                const poiColors = {
                    education: '#10b981',
                    health: '#ef4444',
                    food_social: '#8b5cf6',
                    shopping: '#f59e0b',
                    transport: '#3b82f6',
                    green_leisure: '#22c55e',
                    daily_need: '#6366f1',
                };
                const poiEmojis = {
                    education: '🏫',
                    health: '🏥',
                    food_social: '🍽️',
                    shopping: '🛒',
                    transport: '🚌',
                    green_leisure: '🌳',
                    daily_need: '🏪',
                };

                pois.forEach((poi) => {
                    const cat = poi.poi_kategorisi || poi.kategori || '';
                    const color = poiColors[cat] || '#6b7280';
                    const emoji = poiEmojis[cat] || '📍';

                    const icon = L.divIcon({
                        className: 'poi-map-marker',
                        html: `<div style="background:${color};width:24px;height:24px;border-radius:50%;border:2px solid white;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:0 2px 4px rgba(0,0,0,0.3);cursor:pointer;">${emoji}</div>`,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12],
                    });

                    const m = L.marker([poi.lat, poi.lng], { icon }).addTo(this.map);
                    const dist = poi.distance_km
                        ? `${(poi.distance_km * 1000).toFixed(0)}m`
                        : poi.mesafe_m
                          ? `${poi.mesafe_m}m`
                          : '';

                    m.bindPopup(
                        `<div class="p-1 text-xs"><strong>${poi.poi_adi || poi.name || ''}</strong><br><span class="text-gray-500">${cat}</span>${dist ? ` · <span class="text-blue-600">${dist}</span>` : ''}</div>`
                    );

                    this.poiMarkers.push(m);
                });

                console.log(`✅ ${pois.length} POI haritada gösteriliyor`);
            } catch (e) {
                console.warn('POI görselleştirme hatası:', e);
            }
        },

        async fetchDistricts() {
            if (!this.selectedCity) {
                this.districts = [];
                return;
            }

            this.loadingDistricts = true;
            this.districts = [];
            this.selectedDistrict = '';

            try {
                const response = await fetch(`/api/v1/location/districts/${this.selectedCity}`);
                const result = await response.json();

                if (result.success) {
                    this.districts = result.data;

                    // ✅ Dropdown'ı güncelle
                    const ilceSelect = document.getElementById('ilce_id');
                    if (ilceSelect) {
                        ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';
                        if (Array.isArray(this.districts)) {
                            this.districts.forEach((district) => {
                                const opt = document.createElement('option');
                                opt.value = district.id;
                                opt.textContent = district.name || district.ilce_adi;
                                ilceSelect.appendChild(opt);
                            });
                        }
                        ilceSelect.disabled = false;
                    }

                    // ✅ Haritayı il koordinatlarına güncelle
                    if (window.updateMapFromIl) {
                        window.updateMapFromIl(this.selectedCity);
                    }
                }
            } catch (error) {
                console.error('İlçeler yüklenemedi:', error);
                alert('İlçeler yüklenirken bir hata oluştu.');
            } finally {
                this.loadingDistricts = false;
            }
        },

        async fetchNeighborhoods() {
            if (!this.selectedDistrict) {
                this.neighborhoods = [];
                return;
            }

            this.loadingNeighborhoods = true;
            this.neighborhoods = [];
            this.selectedNeighborhood = '';

            try {
                const response = await fetch(
                    `/api/v1/location/neighborhoods/${this.selectedDistrict}`
                );
                const result = await response.json();

                if (result.success) {
                    this.neighborhoods = result.data;

                    // ✅ Dropdown'ı güncelle
                    const mahalleSelect = document.getElementById('mahalle_id');
                    if (mahalleSelect) {
                        mahalleSelect.innerHTML = '<option value="">Mahalle Seçin</option>';
                        if (Array.isArray(this.neighborhoods)) {
                            this.neighborhoods.forEach((neighborhood) => {
                                const opt = document.createElement('option');
                                opt.value = neighborhood.id;
                                opt.textContent = neighborhood.name || neighborhood.mahalle_adi;
                                if (neighborhood.lat && neighborhood.lng) {
                                    opt.dataset.lat = neighborhood.lat;
                                    opt.dataset.lng = neighborhood.lng;
                                }
                                mahalleSelect.appendChild(opt);
                            });
                        }
                        mahalleSelect.disabled = false;
                    }

                    // ✅ Haritayı ilçe koordinatlarına güncelle
                    if (window.updateMapFromIlce) {
                        window.updateMapFromIlce(this.selectedDistrict);
                    }
                }
            } catch (error) {
                console.error('Mahalleler yüklenemedi:', error);
            } finally {
                this.loadingNeighborhoods = false;
            }
        },

        // ✅ Safe Map Utils
        _toNumber(v) {
            if (v === null || v === undefined || v === '') return null;
            const n = parseFloat(v);
            return Number.isFinite(n) ? n : null;
        },

        _isValidLatLng(lat, lng) {
            const l = this._toNumber(lat);
            const g = this._toNumber(lng);
            return l !== null && g !== null && l >= -90 && l <= 90 && g >= -180 && g <= 180;
        },

        focusNeighborhood() {
            if (!this.selectedNeighborhood) return;

            // Find lat/lng from neighborhoods array if available locally
            const hood = this.neighborhoods.find((h) => h.id == this.selectedNeighborhood);

            if (hood && (hood.lat || hood.latitude) && (hood.lng || hood.longitude)) {
                const lat = hood.lat || hood.latitude;
                const lng = hood.lng || hood.longitude;
                this.flyToLocation(lat, lng, 16); // Zoom 16 (mahalle için ideal)
            } else {
                // Fetch details from API
                // Debounce prevent?
                fetch(`/api/v1/location/neighborhood/${this.selectedNeighborhood}/coordinates`)
                    .then((r) => r.json())
                    .then((result) => {
                        if (
                            result.success &&
                            (result.lat || result.data?.lat) &&
                            (result.lng || result.data?.lng)
                        ) {
                            const lat = result.lat || result.data.lat;
                            const lng = result.lng || result.data.lng;
                            this.flyToLocation(lat, lng, 16);
                        }
                    })
                    .catch((e) => console.error('Mahalle koordinatı alınamadı', e));
            }
        },

        flyToLocation(lat, lng, zoom = 13) {
            if (!this.map || !this.marker) return;

            // ✅ NaN Guard
            if (!this._isValidLatLng(lat, lng)) {
                console.warn(`⚠️ Invalid flyTo coordinates: ${lat}, ${lng}. Defaulting to Bodrum.`);
                // Fallback to Bodrum
                lat = 37.0344;
                lng = 27.4305;
                zoom = 12;
            }

            const newLatLng = new L.LatLng(lat, lng);
            this.marker.setLatLng(newLatLng);
            this.map.flyTo(newLatLng, zoom);
            this.updateCoordinates(lat, lng);
        },

        resetView() {
            // ✅ Bodrum-First Strategy: Varsayılan Bodrum koordinatları
            this.flyToLocation(37.0344, 27.4305, 12); // Bodrum
        },

        locateUser() {
            if (!navigator.geolocation) {
                alert('Tarayıcınız konum servisini desteklemiyor.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.flyToLocation(position.coords.latitude, position.coords.longitude, 16);
                },
                () => {
                    alert('Konumunuz alınamadı.');
                }
            );
        },
    };
};

// ✅ Global switchMapView fonksiyonu (wizard için)
window.switchMapView = function (viewType) {
    if (!window.wizardMap || !window.wizardMapLayers) {
        // Alternatif: locationWizard component'inden al
        const locationWizard = window.locationWizard();
        if (locationWizard && locationWizard.map && locationWizard.mapLayers) {
            window.wizardMap = locationWizard.map;
            window.wizardMapLayers = locationWizard.mapLayers;
        } else {
            console.warn('⚠️ Harita henüz başlatılmadı');
            return;
        }
    }

    const map = window.wizardMap;
    const layers = window.wizardMapLayers;

    // Mevcut layer'ı kaldır
    if (layers.current === 'map') {
        map.removeLayer(layers.map);
    } else if (layers.current === 'satellite') {
        map.removeLayer(layers.satellite);
    }

    // Yeni layer'ı ekle
    if (viewType === 'map') {
        layers.map.addTo(map);
        layers.current = 'map';
    } else {
        layers.satellite.addTo(map);
        layers.current = 'satellite';
    }

    // Buton durumlarını güncelle
    const mapBtn = document.getElementById('map-view-btn');
    const satBtn = document.getElementById('satellite-view-btn');

    if (viewType === 'map') {
        if (mapBtn) {
            mapBtn.classList.add('bg-blue-600', 'text-white', 'shadow-md dark:shadow-none');
            mapBtn.classList.remove(
                'bg-gray-200',
                'dark:bg-gray-700',
                'text-gray-700 dark:text-slate-300',
                'dark:text-gray-300'
            );
        }
        if (satBtn) {
            satBtn.classList.remove('bg-blue-600', 'text-white', 'shadow-md dark:shadow-none');
            satBtn.classList.add(
                'bg-gray-200',
                'dark:bg-gray-700',
                'text-gray-700 dark:text-slate-300',
                'dark:text-gray-300'
            );
        }
    } else {
        if (satBtn) {
            satBtn.classList.add('bg-blue-600', 'text-white', 'shadow-md dark:shadow-none');
            satBtn.classList.remove(
                'bg-gray-200',
                'dark:bg-gray-700',
                'text-gray-700 dark:text-slate-300',
                'dark:text-gray-300'
            );
        }
        if (mapBtn) {
            mapBtn.classList.remove('bg-blue-600', 'text-white', 'shadow-md dark:shadow-none');
            mapBtn.classList.add(
                'bg-gray-200',
                'dark:bg-gray-700',
                'text-gray-700 dark:text-slate-300',
                'dark:text-gray-300'
            );
        }
    }
};

// ✅ Global konum → harita köprüsü (Alpine store'dan gelen güncellemeler için)
window.updateMapFromStore = function (lat, lng) {
    if (!window.wizardMap || !window.wizardMarker || typeof L === 'undefined') {
        return;
    }

    // ✅ NaN Guard
    const safeLat = parseFloat(lat);
    const safeLng = parseFloat(lng);
    if (isNaN(safeLat) || isNaN(safeLng)) {
        console.warn(
            `⚠️ updateMapFromStore: Invalid coordinates [${lat}, ${lng}], skipping update.`
        );
        return;
    }

    const newLatLng = new L.LatLng(safeLat, safeLng);
    window.wizardMarker.setLatLng(newLatLng);
    const currentZoom = window.wizardMap.getZoom() || 13;
    window.wizardMap.flyTo(newLatLng, currentZoom);
};
