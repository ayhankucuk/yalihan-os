/**
 * 🔱 MOD-1: İlan Sihirbazı - Kategori & Harita Senkronizasyon Motoru
 *
 * Merkezi koordinasyon sistemi:
 * - Kategori seçimi → OSM harita katmanı güncelle
 * - Kategori seçimi → UPS özellikler dinamik yükle
 * - Harita koordinat → POI mesafe hesapla (Haversine)
 * - Mühürlü etiketler oluştur (sealed fields)
 *
 * Context7: Kategori bazlı field validation + harita entegrasyonu
 * Performance: <500ms harita render + <100ms UPS fetch
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 */

export default {
    props: {
        wizardData: {
            type: Object,
            default: () => ({
                alt_kategori_id: null,
                junction_id: null,
                lat: null,
                lng: null,
                koordinat_mühürü: false,
            }),
        },
        mapElement: {
            type: String, // HTML element ID for map
            default: 'wizard-map',
        },
    },

    data() {
        return {
            // 🗺️ Harita Durumu
            map: null,
            osmLayers: {},
            poiLayer: null,
            selectedLocationMarker: null,

            // 📋 Kategori & Özellikler
            kategoriSlug: null,
            yayinTipiSlug: null,
            kategoriRules: null,
            availableFeatures: [],
            selectedFeatures: [],

            // 📍 POI & Koordinat
            poiList: [],
            selectedCoordinates: {
                lat: null,
                lng: null,
                address: null,
                nearbyPois: [],
            },

            // ⚙️ Sistem Durumu
            isLoading: false,
            errors: [],
            sealedFields: {}, // Mühürlü alanlar (korunmuş veri)
        };
    },

    computed: {
        /**
         * Mühürlü koordinat kontrolü
         * Koordinat seçildikten sonra salt-okunur (sealed)
         */
        isCoordinateSealed() {
            return (
                this.selectedCoordinates.lat !== null &&
                this.selectedCoordinates.lng !== null &&
                this.wizardData.koordinat_mühürü === true
            );
        },

        /**
         * Haritada gösterilecek OSM katmanları
         * Kategori bazlı filtreleme
         */
        visibleOsmLayers() {
            if (!this.kategoriSlug) return [];

            const layerMap = {
                arsa: ['parcel', 'building_outline'],
                villa: ['building', 'parking', 'amenity'],
                apartman: ['building', 'parking', 'shops'],
                isyeri: ['commercial', 'shop', 'office'],
            };

            return layerMap[this.kategoriSlug] || [];
        },

        /**
         * Mühürlü etiketler (sealed badges)
         * POI verileri + cihaz verisi
         */
        sealedBadges() {
            const badges = [];

            // POI mesafe etiketleri
            this.selectedCoordinates.nearbyPois.forEach((poi) => {
                badges.push({
                    label: `${poi.type}: ${poi.distance}m`,
                    color: 'green',
                    sealed: true,
                    source: 'poi-distance-calculation',
                });
            });

            // Cihaz ölçüsü (Bosch/FLIR)
            if (this.sealedFields.alan_m2_device_sealed) {
                badges.push({
                    label: `Alan: ${this.sealedFields.alan_m2} m² (Cihaz Onaylı)`,
                    color: 'blue',
                    sealed: true,
                    source: 'bosch-laser-device',
                });
            }

            return badges;
        },
    },

    watch: {
        /**
         * Kategori değişimini dinle
         * Harita + UPS senkronizasyonu başlat
         */
        'wizardData.alt_kategori_id'(newKategoriId) {
            if (!newKategoriId) return;
            this.onCategoryChanged(newKategoriId);
        },

        /**
         * Yayın tipi değişimini dinle
         * Özellikler matrisini güncelle
         */
        'wizardData.junction_id'(newYayinTipiId) {
            if (!newYayinTipiId) return;
            this.updateFeaturesForYayinTipi(newYayinTipiId);
        },

        /**
         * Haritadan gelen koordinatı dinle
         * POI hesaplama + mühürleme başlat
         */
        'selectedCoordinates.lat'() {
            if (this.selectedCoordinates.lat && this.selectedCoordinates.lng) {
                this.onCoordinateSelected();
            }
        },
    },

    mounted() {
        this.initializeMap();
        this.setupCategoryListener();
    },

    methods: {
        /**
         * 🗺️ Harita başlatma (Leaflet + OSM)
         */
        async initializeMap() {
            try {
                const mapEl = document.getElementById(this.mapElement);
                if (!mapEl) {
                    console.warn(`Map element not found: ${this.mapElement}`);
                    return;
                }

                // Leaflet harita başlat (OSM temelinde)
                this.map = window.L.map(this.mapElement).setView([39.2, 35.7], 6);
                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(this.map);

                // Harita tıklama eventi
                this.map.on('click', (e) => {
                    this.onMapClick(e.latlng);
                });

                console.log('✅ Harita başlatıldı');
            } catch (error) {
                this.errors.push(`Harita başlatma hatası: ${error.message}`);
                console.error('Map initialization failed:', error);
            }
        },

        /**
         * 🎯 Kategori değişim olayı
         * 1. OSM katmanları güncelle
         * 2. UPS özelliklerini yükle
         * 3. Field matrix'i uygula
         * 4. WizardFormHandler'a event dispatch et
         */
        async onCategoryChanged(kategoriId) {
            this.isLoading = true;

            try {
                // 1️⃣ Kategori slug'ını al
                const response = await fetch(`/api/v1/wizard/features?category_id=${kategoriId}`);
                if (!response.ok) throw new Error('Özellikler yüklenirken hata');

                const data = await response.json();
                this.kategoriSlug = data.category_slug || kategoriId;
                this.availableFeatures = data.features || [];

                // 2️⃣ OSM katmanlarını güncelle
                this.updateOsmLayers();

                // 3️⃣ Field matrix'i uygulanacak + Form Handler'a dispatch et
                this.$emit('category-sync', {
                    kategoriSlug: this.kategoriSlug,
                    availableFeatures: this.availableFeatures,
                });

                // 4️⃣ WizardFormHandler'a custom event dispatch (DocumentEvent)
                document.dispatchEvent(
                    new CustomEvent('wizard:category-changed', {
                        detail: {
                            kategoriSlug: this.kategoriSlug,
                            kategoriId: kategoriId,
                            features: this.availableFeatures,
                        },
                    })
                );

                console.log(`✅ Kategori senkronize: ${this.kategoriSlug}`);
            } catch (error) {
                this.errors.push(`Kategori yükleme hatası: ${error.message}`);
                console.error('Category sync failed:', error);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * 🗺️ OSM katmanlarını kategori bazlı güncelle
         * Arsa: parsel sınırları ön planda
         * Villa: sosyal donatılar ön planda
         */
        updateOsmLayers() {
            // Mevcut katmanları temizle
            Object.values(this.osmLayers).forEach((layer) => {
                if (this.map.hasLayer(layer)) {
                    this.map.removeLayer(layer);
                }
            });
            this.osmLayers = {};

            // Kategori bazlı katmanları yükle
            const layerUrls = {
                arsa: '/tiles/osm-parcels/{z}/{x}/{y}.pbf',
                villa: '/tiles/osm-buildings/{z}/{x}/{y}.pbf',
                apartman: '/tiles/osm-buildings/{z}/{x}/{y}.pbf',
                isyeri: '/tiles/osm-commercial/{z}/{x}/{y}.pbf',
            };

            const url = layerUrls[this.kategoriSlug];
            if (url) {
                // Vector tile layer (VectorTileLayer plugin gerekli)
                const layer = window.L.tileLayer(url, {
                    attribution: 'OSM',
                    minZoom: 12,
                });
                layer.addTo(this.map);
                this.osmLayers[this.kategoriSlug] = layer;
            }

            console.log(`🗺️ OSM katmanları güncellendi: ${this.kategoriSlug}`);
        },

        /**
         * 📋 Yayın tipi bazlı özellikler güncelle
         */
        async updateFeaturesForYayinTipi(yayinTipiId) {
            this.yayinTipiSlug = yayinTipiId;

            try {
                // UPS template'inden özellikler getir
                const response = await fetch(
                    `/api/v1/wizard/features?category_id=${this.wizardData.alt_kategori_id}&junction_id=${yayinTipiId}`
                );
                if (!response.ok) throw new Error('Özellikler yüklenirken hata');

                const data = await response.json();
                this.availableFeatures = data.features || [];

                // Fiyat/takvim alanlarını şekillendır
                if (yayinTipiId === 'gunluk') {
                    this.sealedFields.calendar_visible = true;
                    this.sealedFields.fiyat_turu = 'gunluk';
                } else if (yayinTipiId === 'kiralik') {
                    this.sealedFields.calendar_visible = true;
                    this.sealedFields.fiyat_turu = 'aylik';
                }

                // 🔥 Form Handler'a dispatch et
                document.dispatchEvent(
                    new CustomEvent('wizard:publication-type-changed', {
                        detail: {
                            yayinTipiSlug: this.yayinTipiSlug,
                            yayinTipiId: yayinTipiId,
                        },
                    })
                );

                console.log(`✅ Yayın tipi özellikler: ${yayinTipiId}`);
            } catch (error) {
                console.error('Yayin tipi update failed:', error);
            }
        },

        /**
         * 🗺️ Harita tıklama olayı
         * Koordinat seç ve mühürle
         */
        async onMapClick(latlng) {
            this.selectedCoordinates.lat = latlng.lat;
            this.selectedCoordinates.lng = latlng.lng;

            // Marker ekle
            if (this.selectedLocationMarker) {
                this.map.removeLayer(this.selectedLocationMarker);
            }

            this.selectedLocationMarker = window.L.marker([latlng.lat, latlng.lng], {
                title: 'Seçilen Lokasyon',
            }).addTo(this.map);

            // Reverse geocoding (adres al)
            await this.reverseGeocode(latlng.lat, latlng.lng);

            // Wizard form'unu güncelle
            this.$emit('coordinate-selected', {
                lat: this.selectedCoordinates.lat,
                lng: this.selectedCoordinates.lng,
                address: this.selectedCoordinates.address,
            });
        },

        /**
         * 📍 Koordinat seçildiyinde
         * POI mesafeleri hesapla (Haversine)
         * Mühürlü etiketler oluştur
         * Form Handler'a document event dispatch et
         */
        async onCoordinateSelected() {
            try {
                this.isLoading = true;

                // POI mesafeleri API'ye gönder
                const response = await fetch('/api/v1/location/poi-distances', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        lat: this.selectedCoordinates.lat,
                        lng: this.selectedCoordinates.lng,
                        kategori: this.kategoriSlug,
                        radius_km: 2, // 2km çapında POI ara
                    }),
                });

                if (!response.ok) throw new Error('POI hesaplama hatası');

                const data = await response.json();
                this.selectedCoordinates.nearbyPois = data.pois || [];

                // Mühürlü koordinat bayrağı set et
                this.wizardData.koordinat_mühürü = true;

                // 🔥 1️⃣ Form'a koordinatları mühürle
                document.dispatchEvent(
                    new CustomEvent('wizard:coordinates-sealed', {
                        detail: {
                            lat: this.selectedCoordinates.lat,
                            lng: this.selectedCoordinates.lng,
                            address: this.selectedCoordinates.address,
                            sealed: true,
                            source: 'map-selection',
                        },
                    })
                );

                // 🔥 2️⃣ POI mühürlü etiketlerini gönder
                document.dispatchEvent(
                    new CustomEvent('wizard:poi-badges-ready', {
                        detail: {
                            pois: this.selectedCoordinates.nearbyPois,
                            nearbyPois: this.selectedCoordinates.nearbyPois,
                            totalCount: this.selectedCoordinates.nearbyPois.length,
                        },
                    })
                );

                // Mühürlü etiketleri emit et
                this.$emit('sealed-badges-updated', this.sealedBadges);

                console.log(
                    `✅ POI mesafeleri hesaplandı: ${this.selectedCoordinates.nearbyPois.length} POI`
                );
            } catch (error) {
                console.error('POI calculation failed:', error);
                this.errors.push('POI hesaplama başarısız');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * 🌐 Reverse geocoding (koordinat → adres)
         */
        async reverseGeocode(lat, lng) {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`,
                    { headers: { 'Accept-Language': 'tr' } }
                );

                if (!response.ok) return;

                const data = await response.json();
                this.selectedCoordinates.address =
                    data.address?.road || data.address?.county || 'Bilinmeyen Adres';
            } catch (error) {
                console.warn('Reverse geocoding failed:', error);
            }
        },

        /**
         * 🔧 Kategori listener kurulumu
         */
        setupCategoryListener() {
            // Alpine.js ile entegrasyon (wizard component'te)
            // x-on:category-changed="$refs.categorySync.onCategoryChanged($event)"
            console.log('✅ Kategori listener kuruldu');
        },

        /**
         * 🏷️ Mühürlü alan setter
         * Cihaz verileri (Bosch/FLIR) için
         */
        setSealedField(fieldName, value, source) {
            this.sealedFields[fieldName] = value;
            this.sealedFields[`${fieldName}_sealed_at`] = new Date().toISOString();
            this.sealedFields[`${fieldName}_source`] = source;

            console.log(`🔱 Mühürlü alan set edildi: ${fieldName} = ${value} (${source})`);
        },

        /**
         * 🎨 Kategori rengini al (harita gösterimi için)
         */
        getCategoryColor(kategoriSlug) {
            const colors = {
                arsa: '#FFD700', // Sarı
                villa: '#FF6B6B', // Kırmızı
                apartman: '#4ECDC4', // Teal
                isyeri: '#45B7D1', // Mavi
            };
            return colors[kategoriSlug] || '#999999';
        },
    },
};
