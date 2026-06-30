{{-- POI (Point of Interest) Selector Component --}}
{{-- Context7 Standard: C7-POI-SELECTOR-2026-01-07 --}}

<div x-data="poiSelector()" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 mt-6 dark:border-slate-700">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Yakın Çevre / POI</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">İlanınızın yakınındaki önemli noktaları seçin</p>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!loading && pois.length === 0" 
         class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                Bu bölge için çevre verisi bulunamadı. Haritada konum seçtikten sonra POI'ler otomatik yüklenecektir.
            </p>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">POI'ler yükleniyor...</p>
    </div>

    {{-- POI Kategorileri --}}
    <div x-show="!loading && pois.length > 0" class="space-y-6">
        {{-- Kategori Template --}}
        <template x-for="category in poiCategories" :key="category.key">
            <div x-show="getCategoryCount(category.key) > 0">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                    <span x-html="category.icon" class="w-5 h-5"></span>
                    <span x-text="category.label"></span>
                    <span class="ml-2 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-semibold text-gray-700 dark:text-slate-200 dark:bg-slate-900 dark:text-slate-300"
                          x-text="'(' + getCategoryCount(category.key) + ')'"></span>
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <template x-for="poi in getPoisByCategory(category.key)" :key="poi.id">
                        <label class="flex items-center gap-2 p-3 border border-gray-200 dark:border-slate-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors dark:border-slate-700">
                            <input type="checkbox" 
                                   :value="poi.id" 
                                   x-model="selectedPois"
                                   class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                            <span class="text-sm text-gray-900 dark:text-white flex-1 truncate dark:text-slate-100" x-text="poi.poi_adi"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto whitespace-nowrap" x-text="formatDistance(poi.distance_km)"></span>
                        </label>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Dynamic Marketing Badges Preview --}}
    <div x-show="selectedPois.length > 0" class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Pazarlama Badge'leri (Otomatik Oluşturulacak)
        </h4>
        <div class="flex flex-wrap gap-2">
            <template x-for="poiId in selectedPois" :key="poiId">
                <div class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-purple-300 dark:border-purple-700 rounded-lg text-xs font-semibold text-purple-700 dark:text-purple-300">
                    <span x-text="getMarketingBadge(poiId)"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Hidden input for form submission --}}
    <input type="hidden" name="poi_json" :value="JSON.stringify(getPoiJsonData())">
    <input type="hidden" name="poi_metadata" :value="JSON.stringify(getPoiMetadata())">
</div>

<script>
function poiSelector() {
    return {
        pois: [],
        selectedPois: [],
        loading: false,
        
        // ✅ Detaylı POI Kategori Sistemi
        poiCategories: [
            {
                key: 'Ulaşım',
                label: 'Ulaşım',
                icon: '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>'
            },
            {
                key: 'Marketler',
                label: 'Marketler',
                icon: '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>'
            },
            {
                key: 'Sağlık Kurumları',
                label: 'Sağlık Kurumları',
                icon: '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>'
            },
            {
                key: 'Eğitim Kurumları',
                label: 'Eğitim Kurumları',
                icon: '<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>'
            },
            {
                key: 'Kafeler/Restoranlar',
                label: 'Kafeler/Restoranlar',
                icon: '<svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>'
            },
            {
                key: 'Alışveriş Merkezleri',
                label: 'Alışveriş Merkezleri',
                icon: '<svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
            },
            {
                key: 'Eğlence Yerleri',
                label: 'Eğlence Yerleri',
                icon: '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            },
            {
                key: 'Dini Merkezler',
                label: 'Dini Merkezler',
                icon: '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>'
            },
            {
                key: 'Spor Tesisleri',
                label: 'Spor Tesisleri',
                icon: '<svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>'
            },
            {
                key: 'Kültürel Aktiviteler',
                label: 'Kültürel Aktiviteler',
                icon: '<svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>'
            },
            {
                key: 'Tarihi & Turistik Tesisler',
                label: 'Tarihi & Turistik Tesisler',
                icon: '<svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>'
            }
        ],

        init() {
            // ✅ Harita marker hareketi dinle
            document.addEventListener('wizard-map-marker-moved', (e) => {
                if (e.detail && e.detail.lat && e.detail.lng) {
                    this.loadPOIs(e.detail.lat, e.detail.lng);
                }
            });

            // ✅ Mahalle seçimi dinle
            const mahalleSelect = document.getElementById('mahalle_id');
            if (mahalleSelect) {
                mahalleSelect.addEventListener('change', () => {
                    if (mahalleSelect.value) {
                        this.loadPOIsFromMahalle(mahalleSelect.value);
                    }
                });
            }
            
            // ✅ İlçe seçimi dinle (POI'leri önceden yükle)
            const ilceSelect = document.getElementById('ilce_id');
            if (ilceSelect) {
                ilceSelect.addEventListener('change', () => {
                    // İlçe seçildiğinde harita güncellenecek, POI'ler otomatik yüklenecek
                });
            }

            // ✅ Başlangıç koordinatları varsa POI'leri yükle
            const latInput = document.querySelector('[name="enlem"]') || document.querySelector('[name="latitude"]');
            const lngInput = document.querySelector('[name="boylam"]') || document.querySelector('[name="longitude"]');
            if (latInput && lngInput && latInput.value && lngInput.value) {
                this.loadPOIs(parseFloat(latInput.value), parseFloat(lngInput.value));
            } else {
                // ✅ Varsayılan Bodrum koordinatları için POI'leri yükle
                setTimeout(() => {
                    this.loadPOIs(37.0344, 27.4305);
                }, 2000); // Harita yüklendikten sonra
            }
        },

        async loadPOIs(lat, lng) {
            if (!lat || !lng) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/location/poi-distances`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ lat, lng, radius: 5000 })
                });
                
                const data = await response.json();
                if (data.success && data.data) {
                    // ✅ SAB: data.data veya data.data.pois
                    this.pois = data.data.pois || data.data || [];
                } else {
                    this.pois = [];
                }
            } catch (error) {
                console.error('POI yükleme hatası:', error);
                this.pois = [];
            } finally {
                this.loading = false;
            }
        },

        async loadPOIsFromMahalle(mahalleId) {
            if (!mahalleId) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/location/neighborhood/${mahalleId}/coordinates`);
                const data = await response.json();
                
                if (data.success && (data.lat || data.data?.lat) && (data.lng || data.data?.lng)) {
                    const lat = parseFloat(data.lat || data.data.lat);
                    const lng = parseFloat(data.lng || data.data.lng);
                    this.loadPOIs(lat, lng);
                } else {
                    this.error = 'Mahalle koordinatları bulunamadı';
                    this.loading = false;
                }
            } catch (error) {
                console.error('Mahalle koordinat hatası:', error);
                this.error = 'Mahalle koordinatları yüklenemedi';
                this.loading = false;
            }
        },

        getCategoryCount(categoryKey) {
            return this.getPoisByCategory(categoryKey).length;
        },

        getPoisByCategory(categoryKey) {
            // ✅ Detaylı Kategori Mapping: POI türü ve kategorisine göre
            const categoryMapping = {
                'Ulaşım': {
                    types: ['airport', 'bus_station', 'marina', 'transportation'],
                    categories: ['transportation']
                },
                'Marketler': {
                    types: ['market', 'supermarket'],
                    categories: ['shopping_mall']
                },
                'Sağlık Kurumları': {
                    types: ['hospital', 'pharmacy', 'doctor', 'clinic'],
                    categories: ['hospital', 'healthcare']
                },
                'Eğitim Kurumları': {
                    types: ['school', 'university', 'primary_school', 'secondary_school'],
                    categories: ['school', 'education']
                },
                'Kafeler/Restoranlar': {
                    types: ['cafe', 'restaurant', 'food'],
                    categories: ['food']
                },
                'Alışveriş Merkezleri': {
                    types: ['shopping_mall'],
                    categories: ['shopping_mall']
                },
                'Eğlence Yerleri': {
                    types: ['beach', 'beach_club', 'marina'],
                    categories: ['tourist_attraction']
                },
                'Dini Merkezler': {
                    types: ['mosque', 'church', 'monastery'],
                    categories: ['religious']
                },
                'Spor Tesisleri': {
                    types: ['gym', 'stadium', 'sports_center'],
                    categories: ['sports']
                },
                'Kültürel Aktiviteler': {
                    types: ['museum', 'library', 'theater', 'cultural_center'],
                    categories: ['cultural']
                },
                'Tarihi & Turistik Tesisler': {
                    types: ['historical_landmark', 'castle', 'amphitheater', 'monument'],
                    categories: ['historical', 'archaeological', 'tourist_attraction']
                }
            };
            
            const mapping = categoryMapping[categoryKey];
            if (!mapping) return [];
            
            return this.pois.filter(poi => {
                const poiType = (poi.poi_turu || '').toLowerCase();
                const poiCategory = (poi.poi_kategorisi || '').toLowerCase();
                
                // Type kontrolü
                const typeMatch = mapping.types.some(type => 
                    poiType === type.toLowerCase() || poiType.includes(type.toLowerCase())
                );
                
                // Category kontrolü
                const categoryMatch = mapping.categories.some(cat => 
                    poiCategory === cat.toLowerCase() || poiCategory.includes(cat.toLowerCase())
                );
                
                return typeMatch || categoryMatch;
            });
        },

        formatDistance(km) {
            if (!km) return '';
            if (km < 1) return `${Math.round(km * 1000)}m`;
            return `${km.toFixed(1)}km`;
        },

        getMarketingBadge(poiId) {
            const poi = this.pois.find(p => p.id == poiId);
            if (!poi) return '';
            
            const distance = poi.distance_km || 0;
            const distanceText = distance < 1 
                ? `${Math.round(distance * 1000)}m` 
                : `${distance.toFixed(1)}km`;
            
            // Dynamic badge generation based on POI type
            if (poi.poi_turu === 'marina') {
                return `${poi.poi_adi} Sadece ${distanceText}!`;
            } else if (poi.poi_turu === 'beach_club') {
                return `${poi.poi_adi} ${distanceText} Mesafede`;
            } else if (poi.poi_turu === 'airport') {
                return `Havalimanına ${distanceText}`;
            } else {
                return `${poi.poi_adi} ${distanceText}`;
            }
        },

        getPoiJsonData() {
            return this.selectedPois.map(id => {
                const poi = this.pois.find(p => p.id == id);
                if (!poi) return null;
                
                const distance = poi.distance_km || 0;
                const mesafeTipi = distance < 1 ? 'yakın' : distance < 3 ? 'orta' : 'uzak';
                
                // Etki çemberi kontrolü (ek_veri içinde)
                const etkiCemberi = poi.ek_veri?.etki_cemberi_km || 2;
                const marketingBadge = this.getMarketingBadge(id);
                
                return {
                    poi_id: poi.id,
                    poi_adi: poi.poi_adi,
                    poi_turu: poi.poi_turu,
                    mesafe_tipi: mesafeTipi,
                    distance_km: distance,
                    marketing_badge: marketingBadge,
                    etki_cemberi_km: etkiCemberi,
                    bodrum_spesifik: poi.ek_veri?.bodrum_spesifik || false
                };
            }).filter(Boolean);
        },

        getPoiMetadata() {
            const bodrumPois = this.selectedPois.filter(id => {
                const poi = this.pois.find(p => p.id == id);
                return poi?.ek_veri?.bodrum_spesifik === true;
            });
            
            // En yüksek öncelikli POI'nin etki çemberini al
            const maxEtkiCemberi = Math.max(...this.selectedPois.map(id => {
                const poi = this.pois.find(p => p.id == id);
                return poi?.ek_veri?.etki_cemberi_km || 2;
            }), 2);
            
            return {
                etki_cemberi_km: maxEtkiCemberi,
                oncelik_sirasi: bodrumPois.length > 0 ? 1 : 2,
                bodrum_spesifik: bodrumPois.length > 0,
                selected_count: this.selectedPois.length
            };
        }
    }
}
</script>

