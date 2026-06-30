@extends('admin.layouts.admin')

@section('title', 'Smart Calculator')

@section('content')
    <div class="content-header mb-6">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <h1 class="admin-h1">🧮 Smart Calculator</h1>
                <div class="flex items-center space-x-3">
                    <button onclick="showHistory()" class="admin-button admin-button-secondary touch-target-optimized">
                        <i class="fas fa-history mr-2"></i>
                        Geçmiş
                    </button>
                    <button onclick="showFavorites()" class="admin-button admin-button-secondary touch-target-optimized">
                        <i class="fas fa-star mr-2"></i>
                        Favoriler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" x-data="smartCalculator()">
        <!-- Hesaplama Türü Seçimi - Subtle Vibrant Grid -->
        <div class="container mx-auto">
            <div
                class="sv-card-padding sv-shadow-md rounded-xl border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 mb-8 dark:border-slate-700">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-8 h-8 mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-slate-200">Hesaplama Türü Seçin</h2>
                </div>
                <!-- Basic Theme - Temel Hesaplamalar -->
                <div class="subtle-vibrant-blue sv-header rounded-lg sv-shadow-sm sv-card-hover sv-card-padding mb-6">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-4 flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-5 h-5 mr-2">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        Temel Hesaplamalar
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="(name, type) in getCalculationTypesByTheme('basic')" :key="type">
                            <div class="calculation-type-card sv-card-hover" :class="{ 'selected': selectedType === type }"
                                @click="selectCalculationType(type)">
                                <div class="card-icon">
                                    <i :class="getCalculationIcon(type)"></i>
                                </div>
                                <div class="card-title" x-text="name"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Location Theme - Konum Bazlı Hesaplamalar -->
                <div class="subtle-vibrant-green sv-header rounded-lg sv-shadow-sm sv-card-hover sv-card-padding mb-6">
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-4 flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-5 h-5 mr-2">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        Konum Bazlı Hesaplamalar
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="(name, type) in getCalculationTypesByTheme('location')" :key="type">
                            <div class="calculation-type-card sv-card-hover" :class="{ 'selected': selectedType === type }"
                                @click="selectCalculationType(type)">
                                <div class="card-icon">
                                    <i :class="getCalculationIcon(type)"></i>
                                </div>
                                <div class="card-title" x-text="name"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Features Theme - Özellik Bazlı Hesaplamalar -->
                <div class="subtle-vibrant-purple sv-header rounded-lg sv-shadow-sm sv-card-hover sv-card-padding mb-6">
                    <h3 class="text-lg font-semibold text-purple-800 dark:text-purple-200 mb-4 flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-5 h-5 mr-2">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        Özellik Bazlı Hesaplamalar
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="(name, type) in getCalculationTypesByTheme('features')" :key="type">
                            <div class="calculation-type-card sv-card-hover" :class="{ 'selected': selectedType === type }"
                                @click="selectCalculationType(type)">
                                <div class="card-icon">
                                    <i :class="getCalculationIcon(type)"></i>
                                </div>
                                <div class="card-title" x-text="name"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Media Theme - Gelişmiş Hesaplamalar -->
                <div class="subtle-vibrant-orange sv-header rounded-lg sv-shadow-sm sv-card-hover sv-card-padding mb-6">
                    <h3 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-4 flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-5 h-5 mr-2">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        Gelişmiş Hesaplamalar
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="(name, type) in getCalculationTypesByTheme('media')" :key="type">
                            <div class="calculation-type-card sv-card-hover" :class="{ 'selected': selectedType === type }"
                                @click="selectCalculationType(type)">
                                <div class="card-icon">
                                    <i :class="getCalculationIcon(type)"></i>
                                </div>
                                <div class="card-title" x-text="name"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- System Theme - Sistem Hesaplamaları -->
                <div class="subtle-vibrant-gray sv-header rounded-lg sv-shadow-sm sv-card-hover sv-card-padding">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200 mb-4 flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-5 h-5 mr-2">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        Sistem Hesaplamaları
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <template x-for="(name, type) in getCalculationTypesByTheme('system')" :key="type">
                            <div class="calculation-type-card sv-card-hover"
                                :class="{ 'selected': selectedType === type }" @click="selectCalculationType(type)">
                                <div class="card-icon">
                                    <i :class="getCalculationIcon(type)"></i>
                                </div>
                                <div class="card-title" x-text="name"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hesaplama Formu - Context-Aware Theme -->
        <div x-show="selectedType" class="container mx-auto">
            <div x-bind:class="getFormThemeClass(selectedType)"
                class="sv-header rounded-xl sv-shadow-md sv-card-hover sv-card-padding mb-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center w-8 h-8 mr-3">
                        <i :class="getCalculationIcon(selectedType)" class="w-4 h-4 text-white"></i>
                    </div>
                    <h2 x-bind:class="getFormTitleClass(selectedType)" class="text-xl font-semibold text-gray-800 dark:text-slate-200"
                        x-text="calculationTypes[selectedType]"></h2>
                </div>
                <!-- Metrekare Bazlı Fiyat -->
                <div x-show="selectedType === 'price_per_sqm'" class="sv-form-group">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="sv-form-group">
                            <label class="admin-label">Metrekare <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.metrekare" class="admin-input"
                                placeholder="Metrekare giriniz" min="0" step="0.01">
                        </div>
                        <div class="sv-form-group">
                            <label class="admin-label">Birim Fiyat (TL/m²) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.birim_fiyat" class="admin-input"
                                placeholder="Birim fiyat giriniz" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- Oda Sayısı Bazlı Fiyat -->
                <div x-show="selectedType === 'price_per_room'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">Oda Sayısı <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.oda_sayisi" class="admin-input"
                                placeholder="Oda sayısı giriniz" min="0" step="1">
                        </div>
                        <div>
                            <label class="admin-label">Oda Başına Fiyat (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.oda_basi_fiyat" class="admin-input"
                                placeholder="Oda başına fiyat giriniz" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- Konut Kredisi -->
                <div x-show="selectedType === 'mortgage_loan'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="admin-label">Kredi Tutarı (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.kredi_tutari" class="admin-input"
                                placeholder="Kredi tutarı giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">Vade (Yıl) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.vade" class="admin-input" placeholder="Vade giriniz"
                                min="1" max="30" step="1">
                        </div>
                        <div>
                            <label class="admin-label">Faiz Oranı (%) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.faiz_orani" class="admin-input"
                                placeholder="Faiz oranı giriniz" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- ROI Hesaplama -->
                <div x-show="selectedType === 'roi_calculation'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="admin-label">Yatırım Tutarı (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.yatirim_tutari" class="admin-input"
                                placeholder="Yatırım tutarı giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">Yıllık Gelir (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.yillik_gelir" class="admin-input"
                                placeholder="Yıllık gelir giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">Yıllık Gider (TL)</label>
                            <input type="number" x-model="inputs.yillik_gider" class="admin-input"
                                placeholder="Yıllık gider giriniz" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- KDV Hesaplama -->
                <div x-show="selectedType === 'vat_calculation'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">KDV'siz Fiyat (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.kdvsiz_fiyat" class="admin-input"
                                placeholder="KDV'siz fiyat giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">KDV Oranı (%)</label>
                            <select style="color-scheme: light dark;" x-model="inputs.kdv_orani" class="admin-input transition-all duration-200">
                                <option value="18">%18 (Standart)</option>
                                <option value="8">%8 (İndirimli)</option>
                                <option value="1">%1 (Özel)</option>
                                <option value="0">%0 (Sıfır)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Satış Komisyonu -->
                <div x-show="selectedType === 'sales_commission'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">Satış Fiyatı (TL) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.satis_fiyati" class="admin-input"
                                placeholder="Satış fiyatı giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">Komisyon Oranı (%)</label>
                            <select style="color-scheme: light dark;" x-model="inputs.komisyon_orani" class="admin-input transition-all duration-200">
                                <option value="3">%3 (Standart)</option>
                                <option value="2">%2 (İndirimli)</option>
                                <option value="4">%4 (Premium)</option>
                                <option value="5">%5 (Lüks)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAKS Hesaplama -->
                <div x-show="selectedType === 'taks_calculation'" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">Arsa Alanı (m²) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.arsa_alani" class="admin-input"
                                placeholder="Arsa alanı giriniz" min="0" step="0.01">
                        </div>
                        <div>
                            <label class="admin-label">TAKS Oranı (%) <span class="text-red-500">*</span></label>
                            <input type="number" x-model="inputs.taks_orani" class="admin-input"
                                placeholder="TAKS oranı giriniz" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- Hesapla Butonu -->
                <div class="mt-6">
                    <button @click="calculate()" :disabled="!canCalculate()"
                        x-bind:class="getCalculateButtonClass(selectedType)"
                        class="admin-button admin-button-primary w-full touch-target-optimized">
                        <i class="fas fa-calculator mr-2"></i>
                        🎯 HESAPLA
                    </button>
                </div>
            </div>
        </div>

        <!-- Sonuçlar -->
        <div x-show="result" class="admin-card mb-6">
            <div class="admin-p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h3 class="admin-card-title">
                    <i class="fas fa-chart-line text-green-600 mr-2"></i>
                    📊 HESAPLAMA SONUÇLARI
                </h3>
            </div>
            <div class="admin-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(value, key) in result" :key="key">
                        <div x-show="key.startsWith('formatted_')" class="result-item">
                            <div class="result-label" x-text="getResultLabel(key)"></div>
                            <div class="result-value" x-text="value"></div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button @click="saveToFavorites()" class="admin-button admin-button-success touch-target-optimized">
                        <i class="fas fa-star mr-2"></i>
                        💾 Favorilere Kaydet
                    </button>
                    <button @click="shareResult()" class="btn-neutral sv-button-padding sv-button-hover touch-target-optimized">
                        <i class="fas fa-share mr-2"></i>
                        📤 Paylaş
                    </button>
                    <button @click="resetCalculation()" class="admin-button admin-button-secondary touch-target-optimized">
                        <i class="fas fa-redo mr-2"></i>
                        🔄 Yeniden Hesapla
                    </button>
                </div>
            </div>
        </div>

        <!-- Hesaplama Geçmişi Modal -->
        <div x-show="showHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50"
            @click="showHistoryModal = false">
            <div class="flex items-center justify-center min-h-screen p-4" @click.stop>
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg max-w-4xl w-full max-h-[80vh] overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">
                                <i class="fas fa-history text-blue-600 mr-2"></i>
                                Hesaplama Geçmişi
                            </h3>
                            <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <template x-for="item in history" :key="item.id">
                                <div class="history-item p-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                                x-text="item.calculation_type_name"></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400"
                                                x-text="item.short_description"></div>
                                            <div class="text-xs text-gray-400" x-text="item.formatted_date"></div>
                                        </div>
                                        <button @click="loadFromHistory(item)"
                                            class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:bg-blue-700 dark:hover:bg-blue-800">
                                            <i class="fas fa-arrow-left mr-1"></i>
                                            Yükle
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Favoriler Modal -->
        <div x-show="showFavoritesModal" class="fixed inset-0 bg-black bg-opacity-50 z-50"
            @click="showFavoritesModal = false">
            <div class="flex items-center justify-center min-h-screen p-4" @click.stop>
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg max-w-4xl w-full max-h-[80vh] overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">
                                <i class="fas fa-star text-yellow-600 mr-2"></i>
                                Favori Hesaplamalar
                            </h3>
                            <button @click="showFavoritesModal = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            <template x-for="item in favorites" :key="item.id">
                                <div class="favorite-item p-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                                x-text="item.favorite_name"></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400"
                                                x-text="item.short_description"></div>
                                            <div class="text-xs text-gray-400" x-text="item.formatted_date"></div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button @click="loadFromFavorites(item)"
                                                class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:bg-blue-700 dark:hover:bg-blue-800">
                                                <i class="fas fa-arrow-left mr-1"></i>
                                                Yükle
                                            </button>
                                            <button @click="removeFavorite(item.id)"
                                                class="inline-flex items-center px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 transition-all duration-200 dark:bg-red-700 dark:hover:bg-red-800">
                                                <i class="fas fa-trash mr-1"></i>
                                                Sil
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/admin/smart-calculator.js') }}"></script>
    @endpush
@endsection
