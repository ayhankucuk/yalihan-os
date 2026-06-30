@extends('admin.layouts.admin')

@section('title', 'Toplu Kişi Yönetimi')

@push('meta')
    <meta name="description" content="EmlakPro Toplu Kişi Yönetimi - Bulk operations for customer management">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div x-data="bulkKisiManager()" x-init="init()">
        <!-- Header -->
        <div class="content-header mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                        👥 Toplu Kişi Yönetimi
                    </h1>
                    <p class="text-gray-600 mt-2">Çoklu kişi ekleme, düzenleme ve yönetim işlemleri</p>
                </div>
                <div class="flex space-x-3">
                    <button @click="toggleImportModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm touch-target-optimized dark:shadow-none dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                            </path>
                        </svg>
                        CSV Import
                    </button>
                    <button @click="exportData()" :disabled="exporting" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        <span x-text="exporting ? 'Exporting...' : 'Export CSV'"></span>
                    </button>
                    <button @click="toggleBulkCreateModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Toplu Ekleme
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Toplam Kişi</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['total_kisiler']) }}</p>
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
                        <p class="text-sm font-medium text-gray-600">Aktif Kişiler</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['active_kisiler']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pasif Kişiler</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['inactive_kisiler']) }}</p>
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
                        <p class="text-sm font-medium text-gray-600">Bu Hafta</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['recent_additions']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Operations Panel -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 p-6 dark:shadow-none dark:border-slate-700">
            <h2 class="text-xl font-bold text-gray-800 mb-6 dark:text-slate-200">🔧 Toplu İşlemler</h2>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors dark:border-slate-700">
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">📝 Toplu Ekleme</h3>
                    <p class="text-sm text-gray-600 mb-3">Manuel olarak birden fazla kişi bilgisi girişi yapın.</p>
                    <button @click="toggleBulkCreateModal()" class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 w-full touch-target-optimized dark:shadow-none">
                        Başlat
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors dark:border-slate-700">
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">📤 CSV Import</h3>
                    <p class="text-sm text-gray-600 mb-3">CSV dosyasından toplu kişi bilgilerini içe aktarın.</p>
                    <button @click="toggleImportModal()" class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 w-full touch-target-optimized dark:shadow-none">
                        Dosya Seç
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors dark:border-slate-700">
                    <h3 class="font-semibold text-gray-800 mb-2 dark:text-slate-200">🎯 Toplu Güncelleme</h3>
                    <p class="text-sm text-gray-600 mb-3">Seçili kişilerde toplu status ve bilgi güncellemesi.</p>
                    <button @click="toggleBulkUpdateModal()" class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm w-full touch-target-optimized dark:shadow-none dark:text-slate-300">
                        Güncelle
                    </button>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div x-show="processing" class="mt-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="text-blue-700">
                        İşlem devam ediyor... <span x-text="progressMessage"></span>
                    </span>
                </div>
                <div class="mt-2 bg-white rounded-full h-2 dark:bg-slate-900">
                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300"
                        :style="'width: ' + progress + '%'"></div>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div x-show="message" x-transition class="mt-6">
            <div :class="messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                'bg-red-50 border-red-200 text-red-800'"
                class="border rounded-lg p-4">
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

    <!-- Bulk Create Modal -->
    <div x-show="bulkCreateModal" x-cloak
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto dark:bg-slate-900">
            <h3 class="text-lg font-semibold mb-4">👥 Toplu Kişi Ekleme</h3>
            <p class="text-gray-600 mb-6">Aşağıdaki formu kullanarak birden fazla kişi bilgisini tek seferde
                ekleyebilirsiniz.</p>

            <div class="space-y-4">
                <template x-for="(person, index) in bulkPersons" :key="index">
                    <div class="border border-gray-200 rounded-lg p-4 dark:border-slate-700">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-800 dark:text-slate-200">Kişi <span x-text="index + 1"></span></h4>
                            <button @click="removePerson(index)" class="text-red-500 hover:text-red-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Ad *</label>
                                <input x-model="person.ad" type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Soyad *</label>
                                <input x-model="person.soyad" type="text" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Email</label>
                                <input x-model="person.email" type="email" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Telefon</label>
                                <input x-model="person.telefon" type="tel" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Tip *</label>
                                <select style="color-scheme: light dark;" x-model="person.tip" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100" required>
                                    <option value="musteri">Müşteri</option>
                                    <option value="mal_sahibi">Mal Sahibi</option>
                                    <option value="danismani">Danışman</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Durum</label>
                                <select style="color-scheme: light dark;" x-model="person.status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Pasif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-between items-center mt-6">
                <button @click="addPerson()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm touch-target-optimized dark:shadow-none dark:text-slate-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Kişi Ekle
                </button>

                <div class="flex space-x-3">
                    <button @click="closeBulkCreateModal()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm touch-target-optimized dark:shadow-none dark:text-slate-300">İptal</button>
                    <button @click="submitBulkCreate()" :disabled="submitting" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 touch-target-optimized dark:shadow-none">
                        <span x-text="submitting ? 'Kaydediliyor...' : 'Kaydet'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function bulkKisiManager() {
            return {
                processing: false,
                submitting: false,
                exporting: false,
                progress: 0,
                progressMessage: '',
                message: '',
                messageType: 'success',

                // Modal states
                bulkCreateModal: false,
                importModal: false,
                bulkUpdateModal: false,

                // Data
                bulkPersons: [{
                    ad: '',
                    soyad: '',
                    email: '',
                    telefon: '',
                    tip: 'musteri',
                    status: 'active'
                }],

                init() {
                    console.log('Bulk Kisi Manager initialized');
                },

                // Modal controls
                toggleBulkCreateModal() {
                    this.bulkCreateModal = !this.bulkCreateModal;
                },

                toggleImportModal() {
                    this.importModal = !this.importModal;
                },

                toggleBulkUpdateModal() {
                    this.bulkUpdateModal = !this.bulkUpdateModal;
                },

                closeBulkCreateModal() {
                    this.bulkCreateModal = false;
                    this.resetBulkPersons();
                },

                // Person management
                addPerson() {
                    this.bulkPersons.push({
                        ad: '',
                        soyad: '',
                        email: '',
                        telefon: '',
                        tip: 'musteri',
                        status: 'active'
                    });
                },

                removePerson(index) {
                    if (this.bulkPersons.length > 1) {
                        this.bulkPersons.splice(index, 1);
                    }
                },

                resetBulkPersons() {
                    this.bulkPersons = [{
                        ad: '',
                        soyad: '',
                        email: '',
                        telefon: '',
                        tip: 'musteri',
                        status: 'active'
                    }];
                },

                // Operations
                async submitBulkCreate() {
                    this.submitting = true;

                    try {
                        const response = await fetch('/admin/bulk-kisi/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify({
                                kisiler: this.bulkPersons
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.showMessage(result.message, 'success');
                            this.closeBulkCreateModal();
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            this.showMessage('İşlem başarısız: ' + (result.message || 'Bilinmeyen hata'), 'error');
                        }
                    } catch (error) {
                        this.showMessage('Ağ hatası: ' + error.message, 'error');
                    } finally {
                        this.submitting = false;
                    }
                },

                async exportData() {
                    this.exporting = true;

                    try {
                        const response = await fetch('/admin/bulk-kisi/export?format=csv');

                        if (response.ok) {
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'kisiler_export_' + new Date().toISOString().slice(0, 10) + '.csv';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);

                            this.showMessage('Export başarılı!', 'success');
                        } else {
                            this.showMessage('Export başarısız!', 'error');
                        }
                    } catch (error) {
                        this.showMessage('Export hatası: ' + error.message, 'error');
                    } finally {
                        this.exporting = false;
                    }
                },

                // Utility methods
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
