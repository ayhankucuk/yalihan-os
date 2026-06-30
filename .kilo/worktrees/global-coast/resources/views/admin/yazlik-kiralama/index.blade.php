@extends('admin.layouts.admin')

@section('title', 'Yazlık Kiralama Yönetimi')

@push('meta')
    <meta name="description" content="EmlakPro Yazlık Kiralama Yönetimi - Summer rental property management system">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div x-data="yazlikKiralamaManager()" x-init="init()">
        <!-- Header -->
        <div class="content-header mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                        </div>
                        ☀️ Yazlık Kiralama Yönetimi
                    </h1>
                    <p class="text-gray-600 mt-2">Sezonluk kiralama ilanları ve rezervasyon yönetimi</p>
                </div>
                <div class="flex space-x-3">
                    <button @click="toggleReservationModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        Yeni Rezervasyon
                    </button>
                    <button @click="toggleCalendarModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        Müsaitlik Takvimi
                    </button>
                    <button @click="exportReports()" :disabled="exporting" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span x-text="exporting ? 'Rapor Hazırlanıyor...' : 'Rapor İndir'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Toplam Yazlık</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['total_yazlik']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Aktif Reservasyonlar</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['active_reservations']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Bu Ay Gelir</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                            ₺{{ number_format($stats['monthly_revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Doluluk Oranı</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">%{{ number_format($stats['occupancy_rate'], 1) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-8 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">🔍 Arama ve Filtreler</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Lokasyon</label>
                    <select style="color-scheme: light dark;" x-model="filters.location" @change="applyFilters()" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tüm Lokasyonlar</option>
                        <option value="antalya">Antalya</option>
                        <option value="bodrum">Bodrum</option>
                        <option value="cesme">Çeşme</option>
                        <option value="fethiye">Fethiye</option>
                        <option value="kas">Kaş</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Rezervasyon Durumu</label>
                    <select style="color-scheme: light dark;" x-model="filters.status" @change="applyFilters()" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="available">Müsait</option>
                        <option value="reserved">Rezerve</option>
                        <option value="occupied">Dolu</option>
                        <option value="maintenance">Bakımda</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Fiyat Aralığı</label>
                    <select style="color-scheme: light dark;" x-model="filters.price_range" @change="applyFilters()" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tüm Fiyatlar</option>
                        <option value="0-5000">₺0 - ₺5.000</option>
                        <option value="5000-10000">₺5.000 - ₺10.000</option>
                        <option value="10000-20000">₺10.000 - ₺20.000</option>
                        <option value="20000+">₺20.000+</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sezon</label>
                    <select style="color-scheme: light dark;" x-model="filters.season" @change="applyFilters()" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                        <option value="">Tüm Sezonlar</option>
                        <option value="yaz">Yaz Sezonu</option>
                        <option value="kis">Kış Sezonu</option>
                        <option value="ara">Ara Sezon</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4">
                <div class="flex items-center space-x-4">
                    <button @click="resetFilters()" class="text-sm text-gray-500 hover:text-gray-700">
                        🔄 Filtreleri Temizle
                    </button>
                    <span class="text-sm text-gray-500" x-text="`${filteredCount} sonuç gösteriliyor`"></span>
                </div>

                <div class="flex space-x-2">
                    <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-300' dark:text-slate-300"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            </path>
                        </svg>
                    </button>
                    <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-300' dark:text-slate-300"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-lg hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Properties Grid/List -->
        <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <template x-for="property in filteredProperties" :key="property.id">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden hover:shadow-lg transition-all duration-200 dark:shadow-none dark:border-slate-700">
                    <div class="aspect-w-16 aspect-h-9 bg-gray-200">
                        <img :src="property.image || '/images/placeholder-property.jpg'" :alt="property.title"
                            class="w-full h-48 object-cover">
                        <div class="absolute top-2 right-2">
                            <span :class="getStatusBadgeClass(property.status)"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                                x-text="getStatusText(property.status)"></span>
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 mb-2 dark:text-slate-100 dark:text-white" x-text="property.title"></h3>
                        <p class="text-sm text-gray-600 mb-2" x-text="property.location"></p>

                        <div class="flex justify-between items-center mb-3">
                            <span class="text-lg font-bold text-green-600"
                                x-text="`₺${property.price?.toLocaleString()}/gece`"></span>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span x-text="`${property.guests} kişi`"></span>
                            </div>
                        </div>

                        <div class="flex space-x-2">
                            <button @click="viewProperty(property)" class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 dark:bg-slate-900 dark:text-slate-300">
                                📋 Detay
                            </button>
                            <button @click="manageReservation(property)"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md dark:shadow-none">
                                📅 Rezervasyon
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- List View -->
        <div x-show="viewMode === 'list'" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Yazlık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Lokasyon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fiyat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Kapasite</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                        <template x-for="property in filteredProperties" :key="property.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img :src="property.image || '/images/placeholder-property.jpg'"
                                            :alt="property.title" class="w-10 h-10 rounded-lg object-cover mr-3">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white" x-text="property.title"></div>
                                            <div class="text-sm text-gray-500" x-text="`Kod: ${property.code}`"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100 dark:text-white" x-text="property.location">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600"
                                    x-text="`₺${property.price?.toLocaleString()}/gece`"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusBadgeClass(property.status)"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        x-text="getStatusText(property.status)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="`${property.guests} kişi`"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button @click="viewProperty(property)"
                                        class="text-indigo-600 hover:text-indigo-900">Detay</button>
                                    <button @click="manageReservation(property)"
                                        class="text-green-600 hover:text-green-900">Rezervasyon</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex justify-center items-center py-12">
            <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-gray-600">Yazlıklar yükleniyor...</span>
        </div>

        <!-- Success/Error Messages -->
        <div x-show="message" x-transition class="fixed bottom-4 right-4 z-50">
            <div :class="messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                'bg-red-50 border-red-200 text-red-800'"
                class="border rounded-lg p-4 shadow-lg max-w-md">
                <div class="flex">
                    <svg :class="messageType === 'success' ? 'text-green-400' : 'text-red-400'"
                        class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path x-show="messageType === 'success'" fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                        <path x-show="messageType === 'error'" fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <p class="font-medium" x-text="message"></p>
                    </div>
                    <button @click="message = ''" class="ml-auto">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function yazlikKiralamaManager() {
            return {
                loading: false,
                exporting: false,
                message: '',
                messageType: 'success',
                viewMode: 'grid',

                // Modal states
                reservationModal: false,
                calendarModal: false,

                // Filters
                filters: {
                    location: '',
                    status: '',
                    price_range: '',
                    season: ''
                },

                // Data
                properties: @json($yazliklar ?? []),
                filteredProperties: [],
                filteredCount: 0,

                init() {
                    this.applyFilters();
                    console.log('Yazlik Kiralama Manager initialized');
                },

                // Filter methods
                applyFilters() {
                    this.filteredProperties = this.properties.filter(property => {
                        // Location filter
                        if (this.filters.location && property.location?.toLowerCase() !== this.filters.location
                            .toLowerCase()) {
                            return false;
                        }

                        // Status filter
                        if (this.filters.status && property.status !== this.filters.status) {
                            return false;
                        }

                        // Price range filter
                        if (this.filters.price_range && !this.isPriceInRange(property.price, this.filters
                                .price_range)) {
                            return false;
                        }

                        return true;
                    });

                    this.filteredCount = this.filteredProperties.length;
                },

                resetFilters() {
                    this.filters = {
                        location: '',
                        status: '',
                        price_range: '',
                        season: ''
                    };
                    this.applyFilters();
                },

                isPriceInRange(price, range) {
                    const [min, max] = range.split('-').map(val =>
                        val.includes('+') ? Infinity : parseInt(val)
                    );

                    return price >= min && price <= max;
                },

                // Modal controls
                toggleReservationModal() {
                    this.reservationModal = !this.reservationModal;
                },

                toggleCalendarModal() {
                    this.calendarModal = !this.calendarModal;
                },

                // Property actions
                viewProperty(property) {
                    window.location.href = `/admin/yazlik-kiralama/${property.id}`;
                },

                manageReservation(property) {
                    window.location.href = `/admin/yazlik-kiralama/${property.id}/reservations`;
                },

                // Utility methods
                getStatusBadgeClass(status) {
                    const classes = {
                        'available': 'bg-green-100 text-green-800',
                        'reserved': 'bg-yellow-100 text-yellow-800',
                        'occupied': 'bg-red-100 text-red-800',
                        'maintenance': 'bg-gray-100 text-gray-800'
                    };

                    return classes[status] || 'bg-gray-100 text-gray-800';
                },

                getStatusText(status) {
                    const texts = {
                        'available': 'Müsait',
                        'reserved': 'Rezerve',
                        'occupied': 'Dolu',
                        'maintenance': 'Bakımda'
                    };

                    return texts[status] || 'Bilinmiyor';
                },

                // Export functionality
                async exportReports() {
                    this.exporting = true;

                    try {
                        const response = await fetch('/admin/yazlik-kiralama/reports/export', {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        if (response.ok) {
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'yazlik_kiralama_raporu_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            this.showMessage('Rapor başarıyla indirildi!', 'success');
                        } else {
                            this.showMessage('Rapor indirme başarısız!', 'error');
                        }
                    } catch (error) {
                        this.showMessage('Rapor indirme hatası: ' + error.message, 'error');
                    } finally {
                        this.exporting = false;
                    }
                },

                showMessage(text, type) {
                    this.message = text;
                    this.messageType = type;
                    setTimeout(() => {
                        this.message = '';
                    }, 5000);
                }
            };
        }
    </script>
@endpush
