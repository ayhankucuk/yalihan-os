@extends('admin.layouts.admin')

@section('title', 'Yeni Komisyon Oluştur')

@section('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="space-y-6" x-data="komisyonForm">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm mb-3">
                    <a href="{{ route('admin.dashboard') }}"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </a>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <a href="{{ route('admin.finans.komisyonlar.index') }}"
                        class="text-gray-500 hover:text-orange-600 dark:text-gray-400 dark:hover:text-orange-400 transition-colors duration-200 font-medium">Komisyonlar</a>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-700 dark:text-slate-200 font-medium dark:text-slate-300">Yeni Komisyon</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Komisyon</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Yeni bir komisyon kaydı oluşturun</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div
                class="bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-lg flex items-center shadow-sm dark:shadow-none">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div
                class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-lg flex items-center shadow-sm dark:shadow-none">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('admin.finans.komisyonlar.store') }}" class="space-y-6"
            @submit.prevent="submitForm">
            @csrf

            <!-- Temel Bilgiler -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Temel Bilgiler</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Komisyon Tipi -->
                    <div>
                        <label for="komisyon_tipi" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Komisyon Tipi <span class="text-red-500">*</span>
                        </label>
                        <select id="komisyon_tipi" name="komisyon_tipi" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                            x-model="formData.komisyon_tipi" @change="updateKomisyonOrani">
                            <option value="">Seçin...</option>
                            <option value="satis">Satış Komisyonu</option>
                            <option value="kiralama">Kiralama Komisyonu</option>
                            <option value="danismanlik">Danışmanlık Komisyonu</option>
                        </select>
                        @error('komisyon_tipi')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- İlan Fiyatı -->
                    <div>
                        <label for="ilan_fiyati" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            İlan Fiyatı <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="ilan_fiyati" name="ilan_fiyati" required step="0.01" min="0"
                            value="{{ old('ilan_fiyati') }}" placeholder="0.00"
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                            x-model="formData.ilan_fiyati" @input="calculateKomisyon">
                        @error('ilan_fiyati')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Para Birimi -->
                    <div>
                        <label for="para_birimi" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Para Birimi <span class="text-red-500">*</span>
                        </label>
                        <select id="para_birimi" name="para_birimi" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                            x-model="formData.para_birimi">
                            <option value="TRY">TRY (₺)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                        </select>
                        @error('para_birimi')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Komisyon Oranı (Manuel) -->
                    <div>
                        <label for="komisyon_orani" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Komisyon Oranı (%) <span class="text-xs text-gray-500">(Opsiyonel - Boş bırakılırsa otomatik
                                hesaplanır)</span>
                        </label>
                        <input type="number" id="komisyon_orani" name="komisyon_orani" step="0.01" min="0"
                            max="100" value="{{ old('komisyon_orani') }}" placeholder="Otomatik"
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                            x-model="formData.komisyon_orani" @input="calculateKomisyon">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Varsayılan: Satış %3, Kiralama %1, Danışmanlık %2
                        </p>
                        @error('komisyon_orani')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Hesaplanan Komisyon Tutarı -->
                <div x-show="formData.komisyon_tutari > 0" x-cloak
                    class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-200">Hesaplanan Komisyon
                            Tutarı:</span>
                        <span class="text-lg font-bold text-blue-900 dark:text-blue-200"
                            x-text="formatCurrency(formData.komisyon_tutari, formData.para_birimi)"></span>
                    </div>
                </div>
            </div>

            <!-- İlişkiler -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">İlişkiler</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- İlan -->
                    <x-live-search-field type="ilanlar" name="ilan_id" label="İlan" required />

                    <!-- Kişi -->
                    <x-live-search-field type="kisiler" name="kisi_id" label="Kişi" required />

                    <!-- Danışman -->
                    <x-live-search-field type="danismanlar" name="danisman_id" label="Danışman" required />
                </div>
            </div>

            <!-- Split Commission (Opsiyonel) -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Bölünmüş Komisyon (Opsiyonel)</h2>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="enableSplitCommission"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900">
                        <span class="ml-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">Bölünmüş komisyon kullan</span>
                    </label>
                </div>

                <div x-show="enableSplitCommission" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Satıcı Danışman -->
                    <div>
                        <label for="satici_danisman_id"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Satıcı Danışman
                        </label>
                        <div class="relative">
                            <input type="text" id="satici_danisman_search" name="satici_danisman_search"
                                placeholder="Satıcı danışman ara..." autocomplete="off"
                                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                x-model="saticiDanismanSearch"
                                @input.debounce.300ms="searchDanisman($event.target.value, 'satici')"
                                @focus="showSaticiDanismanDropdown = true"
                                @blur="setTimeout(() => { showSaticiDanismanDropdown = false }, 200)">
                            <input type="hidden" id="satici_danisman_id" name="satici_danisman_id"
                                x-model="formData.satici_danisman_id">
                            <div x-show="showSaticiDanismanDropdown && saticiDanismanResults.length > 0" x-cloak
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-lg shadow-xl max-h-60 overflow-y-auto dark:border-slate-700">
                                <template x-for="danisman in saticiDanismanResults" :key="danisman.id">
                                    <div @click="selectSaticiDanisman(danisman)"
                                        class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150 border-b border-gray-100 dark:border-slate-800 last:border-b-0">
                                        <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="danisman.name || 'İsimsiz'"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"
                                            x-text="danisman.email || ''">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Alıcı Danışman -->
                    <div>
                        <label for="alici_danisman_id"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Alıcı Danışman
                        </label>
                        <div class="relative">
                            <input type="text" id="alici_danisman_search" name="alici_danisman_search"
                                placeholder="Alıcı danışman ara..." autocomplete="off"
                                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                x-model="aliciDanismanSearch"
                                @input.debounce.300ms="searchDanisman($event.target.value, 'alici')"
                                @focus="showAliciDanismanDropdown = true"
                                @blur="setTimeout(() => { showAliciDanismanDropdown = false }, 200)">
                            <input type="hidden" id="alici_danisman_id" name="alici_danisman_id"
                                x-model="formData.alici_danisman_id">
                            <div x-show="showAliciDanismanDropdown && aliciDanismanResults.length > 0" x-cloak
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border-2 border-gray-200 dark:border-slate-800 rounded-lg shadow-xl max-h-60 overflow-y-auto dark:border-slate-700">
                                <template x-for="danisman in aliciDanismanResults" :key="danisman.id">
                                    <div @click="selectAliciDanisman(danisman)"
                                        class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150 border-b border-gray-100 dark:border-slate-800 last:border-b-0">
                                        <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="danisman.name || 'İsimsiz'"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400"
                                            x-text="danisman.email || ''">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Split Ratio -->
                    <div>
                        <label for="split_ratio" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Bölünme Oranı (örn: 50-50)
                        </label>
                        <input type="text" id="split_ratio" name="split_ratio" value="{{ old('split_ratio') }}"
                            placeholder="50-50" pattern="^\d{1,3}-\d{1,3}$"
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Format: Satıcı-Alıcı (örn: 60-40)
                        </p>
                        @error('split_ratio')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Notlar</h2>
                <textarea id="notlar" name="notlar" rows="4" placeholder="Ek notlar..."
                    class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-none dark:text-slate-100"
                    x-model="formData.notlar">{{ old('notlar') }}</textarea>
                @error('notlar')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.finans.komisyonlar.index') }}"
                    class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-slate-200 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 focus:outline-none transition-all duration-200 dark:text-slate-300">
                    İptal
                </a>
                <button type="submit" :disabled="loading"
                    class="px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:outline-none transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none">
                    <span x-show="!loading">Kaydet</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Kaydediliyor...
                    </span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('komisyonForm', () => ({
                    loading: false,
                    enableSplitCommission: false,
                    formData: {
                        komisyon_tipi: '{{ old('komisyon_tipi', '') }}',
                        ilan_fiyati: '{{ old('ilan_fiyati', '') }}',
                        para_birimi: '{{ old('para_birimi', 'TRY') }}',
                        komisyon_orani: '{{ old('komisyon_orani', '') }}',
                        komisyon_tutari: 0,
                        ilan_id: '{{ old('ilan_id', '') }}',
                        kisi_id: '{{ old('kisi_id', '') }}',
                        danisman_id: '{{ old('danisman_id', '') }}',
                        satici_danisman_id: '{{ old('satici_danisman_id', '') }}',
                        alici_danisman_id: '{{ old('alici_danisman_id', '') }}',
                        split_ratio: '{{ old('split_ratio', '') }}',
                        notlar: '{{ old('notlar', '') }}'
                    },
                    saticiDanismanSearch: '',
                    saticiDanismanResults: [],
                    showSaticiDanismanDropdown: false,
                    aliciDanismanSearch: '',
                    aliciDanismanResults: [],
                    showAliciDanismanDropdown: false,
                    init() {
                        console.log('✅ Komisyon Form initialized');
                        this.calculateKomisyon();
                    },
                    updateKomisyonOrani() {
                        const defaultRates = {
                            'satis': 3.0,
                            'kiralama': 1.0,
                            'danismanlik': 2.0
                        };
                        if (!this.formData.komisyon_orani && defaultRates[this.formData.komisyon_tipi]) {
                            this.formData.komisyon_orani = defaultRates[this.formData.komisyon_tipi];
                        }
                        this.calculateKomisyon();
                    },
                    calculateKomisyon() {
                        const fiyat = parseFloat(this.formData.ilan_fiyati) || 0;
                        const oran = parseFloat(this.formData.komisyon_orani) || 0;
                        if (fiyat > 0 && oran > 0) {
                            this.formData.komisyon_tutari = fiyat * (oran / 100);
                        } else {
                            this.formData.komisyon_tutari = 0;
                        }
                    },
                    formatCurrency(amount, currency = 'TRY') {
                        return new Intl.NumberFormat('tr-TR', {
                            style: 'currency',
                            currency: currency
                        }).format(amount || 0);
                    },
                    async searchDanisman(query, type = 'main') {
                        if (!query || query.length < 2) {
                            if (type === 'satici') {
                                this.saticiDanismanResults = [];
                                this.showSaticiDanismanDropdown = false;
                            } else if (type === 'alici') {
                                this.aliciDanismanResults = [];
                                this.showAliciDanismanDropdown = false;
                            } else {
                                this.danismanResults = [];
                                this.showDanismanDropdown = false;
                            }
                            return;
                        }
                        try {
                            const response = await fetch(
                                `/api/v1/users/search?q=${encodeURIComponent(query)}&limit=10`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]')?.getAttribute(
                                            'content') || ''
                                    }
                                });
                            const result = await response.json();
                            if (result.success && result.data) {
                                const results = Array.isArray(result.data) ? result.data : [];
                                if (type === 'satici') {
                                    this.saticiDanismanResults = results;
                                    this.showSaticiDanismanDropdown = results.length > 0;
                                } else if (type === 'alici') {
                                    this.aliciDanismanResults = results;
                                    this.showAliciDanismanDropdown = results.length > 0;
                                } else {
                                    this.danismanResults = results;
                                    this.showDanismanDropdown = results.length > 0;
                                }
                            } else {
                                if (type === 'satici') {
                                    this.saticiDanismanResults = [];
                                    this.showSaticiDanismanDropdown = false;
                                } else if (type === 'alici') {
                                    this.aliciDanismanResults = [];
                                    this.showAliciDanismanDropdown = false;
                                } else {
                                    this.danismanResults = [];
                                    this.showDanismanDropdown = false;
                                }
                            }
                        } catch (error) {
                            console.error('Danışman arama hatası:', error);
                            if (type === 'satici') {
                                this.saticiDanismanResults = [];
                                this.showSaticiDanismanDropdown = false;
                            } else if (type === 'alici') {
                                this.aliciDanismanResults = [];
                                this.showAliciDanismanDropdown = false;
                            } else {
                                this.danismanResults = [];
                                this.showDanismanDropdown = false;
                            }
                        }
                    },
                    selectDanisman(danisman) {
                        if (!danisman || !danisman.id) return;
                        this.formData.danisman_id = danisman.id;
                        this.danismanSearch = danisman.name || 'Danışman #' + danisman.id;
                        this.showDanismanDropdown = false;
                        this.danismanResults = [];
                    },
                    selectSaticiDanisman(danisman) {
                        if (!danisman || !danisman.id) return;
                        this.formData.satici_danisman_id = danisman.id;
                        this.saticiDanismanSearch = danisman.name || 'Danışman #' + danisman.id;
                        this.showSaticiDanismanDropdown = false;
                        this.saticiDanismanResults = [];
                    },
                    selectAliciDanisman(danisman) {
                        if (!danisman || !danisman.id) return;
                        this.formData.alici_danisman_id = danisman.id;
                        this.aliciDanismanSearch = danisman.name || 'Danışman #' + danisman.id;
                        this.showAliciDanismanDropdown = false;
                        this.aliciDanismanResults = [];
                    },
                    async submitForm() {
                        this.loading = true;
                        const form = this.$el.querySelector('form');
                        if (form) {
                            form.submit();
                        }
                    }
                }));
            });
        </script>
    @endpush
@endsection
