@extends('admin.layouts.admin')

@section('title', 'Yeni Kişi Ekle')

@section('content_header')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                👤 Yeni Kişi Ekle
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sisteme yeni bir kişi veya müşteri kaydedin</p>
        </div>
        <div>
            <a href="{{ route('admin.kisiler.index') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                ← Geri Dön
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Lütfen aşağıdaki hataları düzeltin:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.kisiler.store') }}" method="POST" x-data="kisiCreateForm()" class="space-y-8">
            @csrf

            <!-- Temel Bilgiler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        👤 Temel Bilgiler
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Ad -->
                        <div class="mb-6">
                            <label for="ad" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Ad *
                            </label>
                            <input type="text" name="ad" id="ad" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="Ad..." x-model="formData.ad">
                        </div>

                        <!-- Soyad -->
                        <div class="mb-6">
                            <label for="soyad" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Soyad *
                            </label>
                            <input type="text" name="soyad" id="soyad" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="Soyad..." x-model="formData.soyad">
                        </div>

                        <!-- Telefon -->
                        <div class="mb-6">
                            <label for="telefon" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Telefon
                            </label>
                            <input type="tel" name="telefon" id="telefon"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="05XX XXX XXXX" x-model="formData.telefon">
                        </div>

                        <!-- E-posta -->
                        <div class="mb-6">
                            <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                E-posta
                            </label>
                            <input type="email" name="email" id="email"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200 dark:text-slate-100"
                                placeholder="ornek@email.com" x-model="formData.email">
                        </div>

                        <!-- Kişi Tipi -->
                        <div class="mb-6">
                            <label for="kisi_tipi" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Kişi Tipi
                            </label>
                            <select style="color-scheme: light dark;" id="kisi_tipi" name="kisi_tipi"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                x-model="formData.kisi_tipi">
                                <option value="">Seçiniz...</option>
                                <option value="alici">Alıcı</option>
                                <option value="satici">Satıcı</option>
                                <option value="ev_sahibi">Ev Sahibi</option>
                                <option value="kiraci">Kiracı</option>
                            </select>
                        </div>

                        <!-- CRM Durumu -->
                        <div class="mb-6">
                            <label for="crm_surec_asamasi"
                                class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                CRM Durumu *
                            </label>
                            <select style="color-scheme: light dark;" id="crm_surec_asamasi" name="crm_surec_asamasi"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                x-model="formData.crm_surec_asamasi">
                                <option value="yeni">Yeni</option>
                                <option value="gorusme">Görüşme</option>
                                <option value="takip">Takip</option>
                                <option value="tamamlandi">Tamamlandı</option>
                                <option value="kaybedildi">Kaybedildi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danışman & Etiketler -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        🤝 Atama ve Gruplandırma
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Danışman -->
                        <div>
                            <label for="danisman_id" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Danışman
                            </label>
                            <select style="color-scheme: light dark;" id="danisman_id" name="danisman_id"
                                x-model="formData.danisman_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200">
                                <option value="">Seçiniz...</option>
                                @foreach ($danismanlar ?? [] as $danisman)
                                    <option value="{{ $danisman->id }}">{{ $danisman->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Etiketler -->
                        <div>
                            <label for="etiketler_ids" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                Etiketler
                            </label>
                            <select id="etiketler_ids" name="etiketler_ids[]" multiple
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                                style="min-height: 100px;">
                                @foreach ($etiketler ?? [] as $etiket)
                                    <option value="{{ $etiket->id }}">{{ $etiket->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adres Bilgileri -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700"
                x-data="locationWizard()" x-init="init()">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        📍 Adres Bilgileri
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- İl --}}
                        <div>
                            <label for="il_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">İl</label>
                            <select name="il_id" id="il_id" x-model="selectedCity"
                                @change="fetchDistricts()"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors">
                                <option value="">İl Seçin</option>
                                @foreach ($iller as $il)
                                    <option value="{{ $il->id }}">{{ $il->il_adi }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- İlçe --}}
                        <div>
                            <label for="ilce_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">İlçe</label>
                            <select name="ilce_id" id="ilce_id" x-model="selectedDistrict"
                                @change="fetchNeighborhoods()" :disabled="!selectedCity || loadingDistricts"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50">
                                <option value="">Önce İl Seçin</option>
                                <template x-for="district in districts" :key="district.id">
                                    <option :value="district.id" x-text="district.name || district.ilce_adi"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Mahalle --}}
                        <div>
                            <label for="mahalle_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Mahalle</label>
                            <select name="mahalle_id" id="mahalle_id" x-model="selectedNeighborhood"
                                :disabled="!selectedDistrict || loadingNeighborhoods"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors disabled:opacity-50">
                                <option value="">Önce İlçe Seçin</option>
                                <template x-for="hood in neighborhoods" :key="hood.id">
                                    <option :value="hood.id" x-text="hood.name || hood.mahalle_adi"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="adres_detay" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Adres Detayı</label>
                        <textarea name="adres_detay" id="adres_detay" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-colors"
                            placeholder="Sokak, bina no, kapı no..." x-model="formData.adres_detay"></textarea>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-yellow-600 mb-6 flex items-center">
                        <span class="bg-yellow-100 text-yellow-600 rounded-full w-7 h-7 flex items-center justify-center text-sm font-bold mr-2">4</span>
                        📝 Notlar
                    </h2>

                    <div>
                        <textarea name="notlar" x-model="formData.notlar" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 transition-colors"
                            placeholder="Kişi hakkında özel notlar, tercihler..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-slate-800">
                <button type="button" @click="resetForm()"
                    class="inline-flex items-center px-6 py-3 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-all duration-200">
                    🔄 Temizle
                </button>

                <div class="flex space-x-3">
                    <button type="submit"
                        class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200"
                        :disabled="loading">
                        <span x-show="!loading">✅ Kişiyi Kaydet</span>
                        <span x-show="loading" x-cloak>⏳ Kaydediliyor...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer />
    <script>
        // Location Wizard Logic
        window.locationWizard = function() {
            return {
                selectedCity: '',
                selectedDistrict: '',
                selectedNeighborhood: '',
                districts: [],
                neighborhoods: [],
                loadingDistricts: false,
                loadingNeighborhoods: false,

                init() {},

                async fetchDistricts() {
                    if (!this.selectedCity) return;
                    this.loadingDistricts = true;
                    try {
                        const response = await fetch(`/api/v1/location/districts/${this.selectedCity}`);
                        const result = await response.json();
                        if (result.success) this.districts = result.data;
                    } catch (error) { console.error(error); }
                    finally { this.loadingDistricts = false; }
                },

                async fetchNeighborhoods() {
                    if (!this.selectedDistrict) return;
                    this.loadingNeighborhoods = true;
                    try {
                        const response = await fetch(`/api/v1/location/neighborhoods/${this.selectedDistrict}`);
                        const result = await response.json();
                        if (result.success) this.neighborhoods = result.data;
                    } catch (error) { console.error(error); }
                    finally { this.loadingNeighborhoods = false; }
                }
            };
        };

        function kisiCreateForm() {
            return {
                loading: false,
                formData: {
                    ad: '{{ old('ad') }}',
                    soyad: '{{ old('soyad') }}',
                    telefon: '{{ old('telefon') }}',
                    email: '{{ old('email') }}',
                    kisi_tipi: '{{ old('kisi_tipi') }}',
                    crm_surec_asamasi: '{{ old('crm_surec_asamasi', 'yeni') }}',
                    danisman_id: '{{ old('danisman_id') }}',
                    adres_detay: '{{ old('adres_detay') }}',
                    notlar: '{{ old('notlar') }}'
                },
                resetForm() {
                    if (confirm('Emin misiniz?')) {
                        this.formData = { ad: '', soyad: '', telefon: '', email: '', kisi_tipi: '', crm_surec_asamasi: 'yeni', danisman_id: '', adres_detay: '', notlar: '' };
                    }
                }
            };
        }
    </script>
@endpush
