{{-- Kiralık Kategorisi Özel Alanlar --}}
<div x-show="selectedKategoriSlug && (selectedKategoriSlug.includes('kiralik') || selectedKategoriSlug.includes('rental') || selectedKategoriSlug.includes('yazlik'))"
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
    class="space-y-4 mb-4">

    {{-- Kiralık Kategorisi Bilgilendirme --}}
    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="font-semibold text-green-900 dark:text-green-100">Kiralık Kategorisi Seçildi</span>
        </div>
        <p class="text-sm text-green-700 dark:text-green-300">
            Kiralık kategorisine özel alanlar (gunluk_fiyat, min_konaklama, havuz, sezon_baslangic, vb.) aktif edildi.
        </p>
    </div>

    {{-- Yazlık Özel Alanlar (Sadece Yazlık kategorisi için) --}}
    <div x-show="selectedKategoriSlug && selectedKategoriSlug.includes('yazlik')"
        x-data="{
            calculating: false,
            selectedSeason: null,
            seasons: @js(
    config('yali_options.sezon_tipleri', [
        'yaz' => ['label' => 'Yaz Sezonu', 'color' => 'yellow', 'icon' => '☀️'],
        'ara_sezon' => ['label' => 'Ara Sezon', 'color' => 'orange', 'icon' => '🍂'],
        'kis' => ['label' => 'Kış Sezonu', 'color' => 'blue', 'icon' => '❄️'],
    ]),
),
            calculatePrices() {
                const gunlukFiyatInput = document.getElementById('field_gunluk_fiyat') ||
                    document.getElementById('gunluk_fiyat') ||
                    document.querySelector('[name="gunluk_fiyat"]') ||
                    document.querySelector('[name="features[gunluk-fiyat]"]');

                if (!gunlukFiyatInput || !gunlukFiyatInput.value || parseFloat(gunlukFiyatInput.value) <= 0) {
                    this.showToast('Lütfen önce günlük fiyatı giriniz.', 'error');
                    return;
                }

                this.calculating = true;
                const gunlukFiyat = parseFloat(gunlukFiyatInput.value);

                // ✅ SAB: Merkezi API config kullan
                const endpoint = window.APIConfig?.ai?.calculateSeasonalPrice || '/api/v1/ai/calculate-seasonal-price';
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ gunluk_fiyat: gunlukFiyat })
                })
                .then(response => response.json())
        .then(data => {
        this.calculating = false;
        if (data.success && data.data) {
        // Haftalık fiyat
        const haftalikInput = document.getElementById('field_haftalik_fiyat') ||
        document.getElementById('haftalik_fiyat') ||
        document.querySelector('[name="haftalik_fiyat"]') ||
        document.querySelector('[name="features[haftalik-fiyat]"]');
        if (haftalikInput && typeof data.data.haftalik_fiyat === 'number') {
        haftalikInput.value = Math.round(data.data.haftalik_fiyat);
        this.flashInput(haftalikInput);
        }

        // Aylık fiyat
        const aylikInput = document.getElementById('field_aylik_fiyat') ||
        document.getElementById('aylik_fiyat') ||
        document.querySelector('[name="aylik_fiyat"]') ||
        document.querySelector('[name="features[aylik-fiyat]"]');
        if (aylikInput && typeof data.data.aylik_fiyat === 'number') {
        aylikInput.value = Math.round(data.data.aylik_fiyat);
        this.flashInput(aylikInput);
        }

        // Sezonluk günlük fiyatlar - yeni API yapısı
        const sezonluk = data.data.sezonluk_fiyatlar || {};

        const yazGunlukDegeri = (typeof data.data.yaz_sezonu_gunluk === 'number') ? data.data.yaz_sezonu_gunluk : (sezonluk.yaz && sezonluk.yaz.gunluk);
        if (yazGunlukDegeri) {
        const yazGunlukInput = document.querySelector('[name*="yaz_gunluk"]') ||
        document.querySelector('[name*="yaz_sezonu_gunluk"]') ||
        document.querySelector('[name*="features[yaz-gunluk]"]');
        if (yazGunlukInput) {
        yazGunlukInput.value = Math.round(yazGunlukDegeri);
        this.flashInput(yazGunlukInput);
        }
        }

        const araGunlukDegeri = (typeof data.data.ara_sezon_gunluk === 'number') ? data.data.ara_sezon_gunluk : (sezonluk.ara_sezon && sezonluk.ara_sezon.gunluk);
        if (araGunlukDegeri) {
        const araSezonGunlukInput = document.querySelector('[name*="ara_sezon_gunluk"]') ||
        document.querySelector('[name*="features[ara-sezon-gunluk]"]');
        if (araSezonGunlukInput) {
        araSezonGunlukInput.value = Math.round(araGunlukDegeri);
        this.flashInput(araSezonGunlukInput);
        }
        }

        const kisGunlukDegeri = (typeof data.data.kis_sezonu_gunluk === 'number') ? data.data.kis_sezonu_gunluk : (sezonluk.kis && sezonluk.kis.gunluk);
        if (kisGunlukDegeri) {
        const kisGunlukInput = document.querySelector('[name*="kis_gunluk"]') ||
        document.querySelector('[name*="kis_sezonu_gunluk"]') ||
        document.querySelector('[name*="features[kis-gunluk]"]');
        if (kisGunlukInput) {
        kisGunlukInput.value = Math.round(kisGunlukDegeri);
        this.flashInput(kisGunlukInput);
        }
        }

        this.showToast('Fiyatlandırma hesaplandı!', 'success');
        } else {
        this.showToast(data.message || 'Fiyatlandırma hesaplanamadı.', 'error');
        }
        })
        .catch(error => {
        this.calculating = false;
        console.error('Yazlık Fiyatlandırma Hatası:', error);
        this.showToast('Fiyatlandırma hesaplanırken bir hata oluştu.', 'error');
        });
        },
        flashInput(input) {
        input.classList.add('bg-green-100', 'dark:bg-green-900/30', 'transition-colors', 'duration-300');
        setTimeout(() => {
        input.classList.remove('bg-green-100', 'dark:bg-green-900/30');
        }, 1000);
        },
        showToast(message, type) {
        if (window.showToast && typeof window.showToast === 'function') {
        window.showToast(message, type);
        } else if (window.showNotification && typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
        }
        },
        getSeasonColor(seasonKey) {
        const season = this.seasons[seasonKey];
        if (!season) return 'gray';
        return season.color || 'gray';
        },
        getSeasonIcon(seasonKey) {
        const season = this.seasons[seasonKey];
        if (!season) return '';
        return season.icon || '';
        },
        getSeasonLabel(seasonKey) {
        const season = this.seasons[seasonKey];
        if (!season) return seasonKey;
        return season.label || seasonKey;
        }
        }"
        x-init="// Günlük fiyat input'una buton ekle
        $watch('selectedKategoriSlug', (value) => {
                    if (value && value.includes('yazlik')) {
                        setTimeout(() => {
                                    const gunlukFiyatInput = document.getElementById('field_gunluk_fiyat') ||
                                        document.getElementById('gunluk_fiyat') ||
                                        document.querySelector('[name="gunluk_fiyat"]') ||
        document.querySelector('[name="features[gunluk-fiyat]"]');

        if (gunlukFiyatInput && !gunlukFiyatInput.parentElement.querySelector('.auto-calculate-btn')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        gunlukFiyatInput.parentNode.insertBefore(wrapper, gunlukFiyatInput);
        wrapper.appendChild(gunlukFiyatInput);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className='auto-calculate-btn absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none';
        btn.innerHTML = '<span>⚡</span><span>Hesapla</span>';
        btn.onclick = () => {
        if (!this.calculating) {
        this.calculatePrices();
        }
        };
        wrapper.appendChild(btn);
        }
        }, 500);
        }
        });
        "
        class="space-y-4">

        {{-- Sezon Tipi Renkli Select --}}
        <div
            class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl">
            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                <span>🌍</span>
                <span>Sezon Tipi</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <template x-for="(season, key) in seasons" :key="key">
                    <label class="relative cursor-pointer">
                        <input type="radio" :name="'sezon_tipi'" :value="key" x-model="selectedSeason"
                            class="peer sr-only"
                            @change="
                                const input = document.querySelector('[name=\"features[sezon-tipi]\"]') ||
                                    document.querySelector('[name=\"sezon_tipi\"]');
                                if (input) input.value = key;
                            ">
                        <div
                            class="flex items-center gap-3 p-4 rounded-lg border-2 transition-all duration-200
                            peer-checked:border-2 peer-checked:shadow-lg
                            bg-white dark:bg-gray-800
                            border-gray-200 dark:border-gray-700
                            peer-checked:border-yellow-400 dark:peer-checked:border-yellow-500
                            hover:border-yellow-300 dark:hover:border-yellow-600
                            hover:shadow-md">
                            <div class="flex-shrink-0 text-2xl" x-text="getSeasonIcon(key)"></div>
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="getSeasonLabel(key)">
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    <template x-if="key === 'yaz'">
                                        <span>Haziran - Ağustos</span>
                                    </template>
                                    <template x-if="key === 'ara_sezon'">
                                        <span>Eylül-Ekim / Nisan-Mayıs</span>
                                    </template>
                                    <template x-if="key === 'kis'">
                                        <span>Kasım - Mart</span>
                                    </template>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div
                                    class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600
                                    peer-checked:border-yellow-500 dark:peer-checked:border-yellow-400
                                    peer-checked:bg-yellow-500 dark:peer-checked:bg-yellow-400
                                    flex items-center justify-center transition-all duration-200">
                                    <div
                                        class="w-2 h-2 rounded-full bg-white dark:bg-gray-800
                                        peer-checked:opacity-100 opacity-0 transition-opacity duration-200">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                </template>
            </div>
        </div>

        {{-- Yazlık Otomatik Fiyatlandırma Kartı --}}
        <div
            class="p-6 bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 dark:shadow-none">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-100 mb-1 flex items-center gap-2">
                        ⚡ Yazlık Otomatik Fiyatlandırma
                    </h3>
                    <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-4">
                        Günlük fiyatı girdikten sonra "⚡ Hesapla" butonuna tıklayın. Sistem haftalık, aylık ve sezonluk
                        fiyatları otomatik hesaplayacaktır.
                    </p>
                    <div
                        class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-indigo-600 dark:text-indigo-400 mb-4">
                        <div class="flex items-center gap-1">
                            <span>📅</span>
                            <span>Haftalık: %5 indirimli</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>📆</span>
                            <span>Aylık: %15 indirimli</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>❄️</span>
                            <span>Kış: %50 indirimli</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span>🍂</span>
                            <span>Ara Sezon: %30 indirimli</span>
                        </div>
                    </div>
                    <button type="button" @click="calculatePrices()" :disabled="calculating"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-75 dark:shadow-none">
                        <svg x-show="!calculating" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <svg x-show="calculating" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="calculating ? 'Hesaplanıyor...' : 'Fiyatları Hesapla'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Auto-Fill Butonları --}}
        <div
            class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                <span>🤖</span>
                <span>AI Otomatik Doldurma</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Denize Uzaklık Auto-Fill --}}
                <div x-data="{
                    calculatingDistance: false,
                    geocodingAddress: false,
                    async calculateDistanceToSea() {
                        const latInput = document.querySelector('[name="lat"]') ||
                            document.querySelector('[name="enlem"]') ||
                            document.querySelector('[id="lat"]');
                        const lngInput = document.querySelector('[name="lng"]') ||
                            document.querySelector('[name="boylam"]') ||
                            document.querySelector('[id="lng"]');

                        // Koordinat yoksa adres bilgisinden çekmeyi dene
                        if (!latInput || !lngInput || !latInput.value || !lngInput.value) {
                            const ilInput = document.querySelector('[name="il_id"]');
                            const ilceInput = document.querySelector('[name="ilce_id"]');
                            const mahalleInput = document.querySelector('[name="mahalle_id"]');
                            const sokakInput = document.querySelector('[name="sokak"]');
                            const adresInput = document.querySelector('[name="adres"]');

                            if (ilInput && ilceInput && ilInput.value && ilceInput.value) {
                                // Adres bilgisinden koordinat çekmeyi dene
                                const shouldGeocode = confirm('Koordinatlar bulunamadı. Adres bilgilerinden otomatik koordinat çekmek ister misiniz?');
                                if (shouldGeocode) {
                                    await this.geocodeFromAddress(ilInput, ilceInput, mahalleInput, sokakInput, adresInput, latInput, lngInput);
                                    // Koordinat çekildikten sonra devam et
                                    if (!latInput.value || !lngInput.value) {
                                        this.showToast('Koordinat çekilemedi. Lütfen harita üzerinden konum seçiniz.', 'error');
                                        return;
                                    }
                                } else {
                                    this.showToast('Koordinatlar gerekli. Harita üzerinden konum seçebilir veya adres bilgilerinden koordinat çekebilirsiniz.', 'error');
                                    return;
                                }
                            } else {
                                this.showToast('Koordinatlar gerekli. Lütfen harita üzerinden konum seçiniz veya il/ilçe bilgilerini giriniz.', 'error');
                                return;
                            }
                        }

                        this.calculatingDistance = true;
                        const lat = parseFloat(latInput.value);
                        const lng = parseFloat(lngInput.value);
                        const endpoint = window.APIConfig?.ai?.calculateDistanceToSea || '/api/v1/ai/calculate-distance-to-sea';
                        fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ lat, lng })
                        })
                        .then(response => response.json())
                    .then(data => {
                    this.calculatingDistance = false;
                    if (data.success && data.data) {
                    const denizeUzaklikInput = document.getElementById('field_denize_uzaklik') ||
                    document.getElementById('denize_uzaklik') ||
                    document.querySelector('[name="denize_uzaklik"]') ||
                    document.querySelector('[name="features[denize-uzaklik]"]');

                    if (denizeUzaklikInput) {
                    // Metre cinsinden kaydet (database'de metre olarak saklanıyor)
                    denizeUzaklikInput.value = Math.round(data.data.distance_m || (data.data.distance_km * 1000));
                    this.flashInput(denizeUzaklikInput);

                    // Kullanıcıya km cinsinden göster
                    const distanceKm = data.data.distance_km || (data.data.distance_m / 1000).toFixed(2);
                    const walkingMinutes = data.data.walking_minutes || Math.round((data.data.distance_m || distanceKm *
                    1000) / 80);
                    const location = data.data.location || 'Deniz';
                    this.showToast(`Denize uzaklık: ${distanceKm} km (${walkingMinutes} dk yürüme) - ${location}`,
                    'success');
                    } else {
                    // Input bulunamadıysa sadece bilgi mesajı göster
                    const distanceKm = data.data.distance_km || (data.data.distance_m / 1000).toFixed(2);
                    this.showToast(`Denize uzaklık hesaplandı: ${distanceKm} km. Lütfen 'denize_uzaklik' alanını manuel
                    olarak doldurun.`, 'info');
                    }
                    } else {
                    this.showToast(data.message || 'Denize uzaklık hesaplanamadı.', 'error');
                    }
                    })
                    .catch(error => {
                    this.calculatingDistance = false;
                    console.error('Denize Uzaklık Hatası:', error);

                    // Detaylı hata mesajı
                    let errorMessage = 'Denize uzaklık hesaplanırken bir hata oluştu.';
                    if (error.message) {
                    errorMessage += ` (${error.message})`;
                    } else if (error.response && error.response.status === 404) {
                    errorMessage = 'Denize yakın bir nokta bulunamadı. Koordinatları kontrol ediniz.';
                    } else if (error.response && error.response.status === 500) {
                    errorMessage = 'Sunucu hatası. Lütfen daha sonra tekrar deneyiniz.';
                    } else if (!navigator.onLine) {
                    errorMessage = 'İnternet bağlantınızı kontrol ediniz.';
                    }

                    this.showToast(errorMessage, 'error');
                    });
                    },
                    async geocodeFromAddress(ilInput, ilceInput, mahalleInput, sokakInput, adresInput, latInput,
                    lngInput) {
                    this.geocodingAddress = true;

                    try {
                    // Adres string'i oluştur
                    const addressParts = [];
                    if (sokakInput && sokakInput.value) addressParts.push(sokakInput.value);
                    if (mahalleInput && mahalleInput.value) {
                    const mahalleText = mahalleInput.options ? mahalleInput.options[mahalleInput.selectedIndex]?.text :
                    mahalleInput.value;
                    addressParts.push(mahalleText);
                    }
                    if (ilceInput && ilceInput.value) {
                    const ilceText = ilceInput.options ? ilceInput.options[ilceInput.selectedIndex]?.text :
                    ilceInput.value;
                    addressParts.push(ilceText);
                    }
                    if (ilInput && ilInput.value) {
                    const ilText = ilInput.options ? ilInput.options[ilInput.selectedIndex]?.text : ilInput.value;
                    addressParts.push(ilText);
                    }
                    if (adresInput && adresInput.value) addressParts.push(adresInput.value);

                    const address = addressParts.join(', ') || 'Türkiye';
                    const geocodeEndpoint = window.APIConfig?.location?.geocode || '/api/v1/location/geocode';

                    const response = await fetch(geocodeEndpoint, {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                    address: address,
                    il_id: ilInput?.value || null,
                    ilce_id: ilceInput?.value || null
                    })
                    });

                    const data = await response.json();

                    if (data.success && data.data && data.data.data) {
                    const coords = data.data.data;
                    if (latInput) latInput.value = coords.latitude;
                    if (lngInput) lngInput.value = coords.longitude;

                    // Input'ları güncelle
                    this.flashInput(latInput);
                    this.flashInput(lngInput);

                    this.showToast(`Koordinatlar çekildi: ${coords.latitude.toFixed(6)},
                    ${coords.longitude.toFixed(6)}`, 'success');
                    } else {
                    throw new Error(data.message || 'Koordinat çekilemedi');
                    }
                    } catch (error) {
                    console.error('Geocoding Hatası:', error);
                    this.showToast('Adres bilgilerinden koordinat çekilemedi. Lütfen harita üzerinden konum seçiniz.',
                    'error');
                    } finally {
                    this.geocodingAddress = false;
                    }
                    },
                    flashInput(input) {
                    input.classList.add('bg-green-100', 'dark:bg-green-900/30', 'transition-colors', 'duration-300');
                    setTimeout(() => {
                    input.classList.remove('bg-green-100', 'dark:bg-green-900/30');
                    }, 1000);
                    },
                    showToast(message, type) {
                    if (window.showToast && typeof window.showToast === 'function') {
                    window.showToast(message, type);
                    } else if (window.showNotification && typeof window.showNotification === 'function') {
                    window.showNotification(message, type);
                    } else {
                    console.log(`[${type.toUpperCase()}] ${message}`);
                    }
                    }
                    }">
                    <button type="button" @click="calculateDistanceToSea()"
                        :disabled="calculatingDistance || geocodingAddress"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-75 dark:shadow-none">
                        <svg x-show="!calculatingDistance && !geocodingAddress" class="w-5 h-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg x-show="calculatingDistance || geocodingAddress" class="w-5 h-5 animate-spin"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span
                            x-text="geocodingAddress ? 'Koordinat çekiliyor...' : (calculatingDistance ? 'Hesaplanıyor...' : '🌊 Denize Uzaklık')"></span>
                    </button>
                </div>

                {{-- Havuz Tespiti Auto-Fill --}}
                <div x-data="{
                    detectingPool: false,
                    detectPool() {
                        const aciklamaInput = document.querySelector('[name="aciklama"]') ||
                            document.querySelector('[name="ilan_aciklama"]') ||
                            document.querySelector('[id="aciklama"]') ||
                            document.querySelector('[id="ilan_aciklama"]') ||
                            document.querySelector('textarea[name*="aciklama"]');

                        if (!aciklamaInput || !aciklamaInput.value || aciklamaInput.value.length < 10) {
                            this.showToast('Lütfen önce ilan açıklamasını giriniz (en az 10 karakter).', 'error');
                            return;
                        }

                        this.detectingPool = true;
                        const aciklama = aciklamaInput.value;
                        const endpoint = window.APIConfig?.ai?.detectPool || '/api/v1/ai/detect-pool';
                        fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ aciklama })
                        })
                        .then(response => response.json())
                    .then(data => {
                    this.detectingPool = false;
                    if (data.success && data.data) {
                    // Havuz checkbox/input
                    const havuzInput = document.getElementById('field_havuz') ||
                    document.getElementById('havuz') ||
                    document.querySelector('[name="havuz"]') ||
                    document.querySelector('[name="features[havuz]"]');

                    if (havuzInput) {
                    if (havuzInput.type === 'checkbox') {
                    havuzInput.checked = data.data.has_pool;
                    } else {
                    havuzInput.value = data.data.has_pool ? '1' : '0';
                    }
                    this.flashInput(havuzInput);
                    }

                    // Havuz türü (pool_type varsa)
                    if (data.data.has_pool && data.data.pool_type) {
                    const havuzTuruInput = document.getElementById('field_havuz_turu') ||
                    document.getElementById('havuz_turu') ||
                    document.querySelector('[name="havuz_turu"]') ||
                    document.querySelector('[name="features[havuz-turu]"]');

                    if (havuzTuruInput) {
                    // Select ise option'ı seç, text ise değeri yaz
                    if (havuzTuruInput.tagName === 'SELECT') {
                    const option = Array.from(havuzTuruInput.options).find(
                    opt => opt.value === data.data.pool_type ||
                    opt.value.toLowerCase() === data.data.pool_type.toLowerCase()
                    );
                    if (option) {
                    havuzTuruInput.value = option.value;
                    this.flashInput(havuzTuruInput);
                    }
                    } else {
                    havuzTuruInput.value = data.data.pool_type;
                    this.flashInput(havuzTuruInput);
                    }
                    }
                    }

                    // Havuz boyutu (pool_size varsa)
                    if (data.data.has_pool && data.data.pool_size) {
                    const havuzBoyutInput = document.getElementById('field_havuz_boyut') ||
                    document.getElementById('havuz_boyut') ||
                    document.querySelector('[name="havuz_boyut"]') ||
                    document.querySelector('[name="features[havuz-boyut]"]');

                    if (havuzBoyutInput) {
                    havuzBoyutInput.value = data.data.pool_size;
                    this.flashInput(havuzBoyutInput);
                    }
                    }

                    // Havuz derinliği (pool_depth varsa)
                    if (data.data.has_pool && data.data.pool_depth) {
                    const havuzDerinlikInput = document.getElementById('field_havuz_derinlik') ||
                    document.getElementById('havuz_derinlik') ||
                    document.querySelector('[name="havuz_derinlik"]') ||
                    document.querySelector('[name="features[havuz-derinlik]"]');

                    if (havuzDerinlikInput) {
                    havuzDerinlikInput.value = data.data.pool_depth;
                    this.flashInput(havuzDerinlikInput);
                    }
                    }

                    const confidence = (data.data.confidence * 100).toFixed(0);
                    let message = `Havuz tespiti: ${data.data.has_pool ? 'Var' : 'Yok'} (Güven: %${confidence})`;
                    if (data.data.has_pool) {
                    const details = [];
                    if (data.data.pool_type) details.push(`Tür: ${data.data.pool_type}`);
                    if (data.data.pool_size) details.push(`Boyut: ${data.data.pool_size}`);
                    if (data.data.pool_depth) details.push(`Derinlik: ${data.data.pool_depth}`);
                    if (details.length > 0) {
                    message += ` - ${details.join(', ')}`;
                    }
                    }
                    this.showToast(message, 'success');
                    } else {
                    this.showToast(data.message || 'Havuz tespiti yapılamadı.', 'error');
                    }
                    })
                    .catch(error => {
                    this.detectingPool = false;
                    console.error('Havuz Tespiti Hatası:', error);

                    // Detaylı hata mesajı
                    let errorMessage = 'Havuz tespiti yapılırken bir hata oluştu.';
                    if (error.message) {
                    errorMessage += ` (${error.message})`;
                    } else if (error.response && error.response.status === 400) {
                    errorMessage = 'Açıklama çok kısa. Lütfen en az 10 karakter giriniz.';
                    } else if (error.response && error.response.status === 500) {
                    errorMessage = 'Sunucu hatası. Lütfen daha sonra tekrar deneyiniz.';
                    } else if (!navigator.onLine) {
                    errorMessage = 'İnternet bağlantınızı kontrol ediniz.';
                    }

                    this.showToast(errorMessage, 'error');
                    });
                    },
                    flashInput(input) {
                    input.classList.add('bg-green-100', 'dark:bg-green-900/30', 'transition-colors', 'duration-300');
                    setTimeout(() => {
                    input.classList.remove('bg-green-100', 'dark:bg-green-900/30');
                    }, 1000);
                    },
                    showToast(message, type) {
                    if (window.showToast && typeof window.showToast === 'function') {
                    window.showToast(message, type);
                    } else if (window.showNotification && typeof window.showNotification === 'function') {
                    window.showNotification(message, type);
                    } else {
                    console.log(`[${type.toUpperCase()}] ${message}`);
                    }
                    }
                    }">
                    <button type="button" @click="detectPool()" :disabled="detectingPool"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-cyan-600 hover:bg-cyan-700 disabled:bg-cyan-400 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-75 dark:shadow-none">
                        <svg x-show="!detectingPool" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <svg x-show="detectingPool" class="w-5 h-5 animate-spin" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="detectingPool ? 'Tespit ediliyor...' : '🏊 Havuz Tespiti'"></span>
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-3">
                💡 <strong>Denize Uzaklık:</strong> Koordinatlar (lat, lng) gerekli. <strong>Havuz Tespiti:</strong>
                İlan açıklaması gerekli.
            </p>
        </div>
    </div>
</div>
