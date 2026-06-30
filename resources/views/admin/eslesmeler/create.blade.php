@extends('admin.layouts.admin')

@section('title', 'Yeni Eşleştirme Oluştur')
@section('meta_description', 'Müşteri talepleri ile ilanları eşleştirin - AI destekli akıllı eşleştirme sistemi ile müşteri memnuniyetini artırın.')
@section('meta_keywords', 'eşleştirme oluştur, talep ilan eşleştirme, müşteri talep, emlak eşleştirme, ai eşleştirme')

@section('content')
    <!-- Eşleşme form bileşeni -->
    <div class="prose max-w-none p-6" x-data="eslesmeForm()">

        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Eşleştirme Oluştur</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Müşteri talepleri ile uygun ilanları eşleştirin</p>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="{{ route('admin.eslesmeler.store') }}" class="space-y-6" @submit="loading = true">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sol Kolon -->
                <div class="space-y-6">
                    <!-- Müşteri Seçimi -->
                    <div class="bg-white dark:bg-gray-800
                                rounded-2xl shadow-xl
                                border border-gray-100 dark:border-gray-700
                                transition-all duration-300 ease-in-out
                                hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700
                                    bg-gradient-to-r from-purple-50 to-pink-50
                                    dark:from-gray-800 dark:to-gray-700
                                    rounded-t-2xl">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">👤 Müşteri Bilgileri</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <!-- Live Search: Müşteri -->
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 required dark:text-slate-300">Müşteri</label>
                                <div class="context7-live-search"
                                     data-endpoint="/api/admin/kisiler/search"
                                     data-target-input="kisi_id"
                                     data-placeholder="Ad, soyad veya telefon ile ara..."
                                     data-min-chars="2">
                                    <input type="text" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100" placeholder="Müşteri ara...">
                                    <input type="hidden" name="kisi_id" id="kisi_id" x-model="form.kisi_id" required>
                                </div>
                                <button type="button" @click="clearKisi()"
                                    x-show="form.kisi_id"
                                    class="mt-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                                    🗑️ Temizle
                                </button>
                                @error('kisi_id')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Danışman Seçimi -->
                            <div class="space-y-2 relative">
                                <label for="danisman_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Danışman</label>
                                <select style="color-scheme: light dark;" name="danisman_id" id="danisman_id" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100" x-model="form.danisman_id">
                                    <option value="">Danışman seçin...</option>
                                    @foreach($danismanlar ?? [] as $danisman)
                                        <option value="{{ $danisman->id }}">{{ $danisman->name }}</option>
                                    @endforeach
                                </select>
                                @error('danisman_id')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Talep Seçimi -->
                    <div class="bg-white dark:bg-gray-800
                                rounded-2xl shadow-xl
                                border border-gray-100 dark:border-gray-700
                                transition-all duration-300 ease-in-out
                                hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700
                                    bg-gradient-to-r from-blue-50 to-indigo-50
                                    dark:from-gray-800 dark:to-gray-700
                                    rounded-t-2xl">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">🎯 Talep Bilgileri</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <!-- Live Search: Talep -->
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Talep (İsteğe Bağlı)</label>
                                <div class="context7-live-search"
                                     data-endpoint="/api/admin/talepler/search"
                                     data-target-input="talep_id"
                                     data-placeholder="Talep başlığı veya lokasyon ile ara..."
                                     data-min-chars="2">
                                    <input type="text" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100" placeholder="Talep ara...">
                                    <input type="hidden" name="talep_id" id="talep_id" x-model="form.talep_id">
                                </div>
                                <button type="button" @click="clearTalep()"
                                    x-show="form.talep_id"
                                    class="mt-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                                    🗑️ Temizle
                                </button>
                                @error('talep_id')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="space-y-6">
                    <!-- İlan Seçimi -->
                    <div class="bg-white dark:bg-gray-800
                                rounded-2xl shadow-xl
                                border border-gray-100 dark:border-gray-700
                                transition-all duration-300 ease-in-out
                                hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700
                                    bg-gradient-to-r from-green-50 to-emerald-50
                                    dark:from-gray-800 dark:to-gray-700
                                    rounded-t-2xl">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">🏠 İlan Bilgileri</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <!-- Live Search: İlan -->
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 required dark:text-slate-300">İlan</label>
                                <div class="context7-live-search"
                                     data-endpoint="/api/admin/ilanlar/search"
                                     data-target-input="ilan_id"
                                     data-placeholder="İlan başlığı veya lokasyon ile ara..."
                                     data-min-chars="2">
                                    <input type="text" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100" placeholder="İlan ara...">
                                    <input type="hidden" name="ilan_id" id="ilan_id" x-model="form.ilan_id" required>
                                </div>
                                <button type="button" @click="clearIlan()"
                                    x-show="form.ilan_id"
                                    class="mt-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                                    🗑️ Temizle
                                </button>
                                @error('ilan_id')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Eşleştirme Detayları -->
                    <div class="bg-white dark:bg-gray-800
                                rounded-2xl shadow-xl
                                border border-gray-100 dark:border-gray-700
                                transition-all duration-300 ease-in-out
                                hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700
                                    bg-gradient-to-r from-amber-50 to-orange-50
                                    dark:from-gray-800 dark:to-gray-700
                                    rounded-t-2xl">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">⚙️ Eşleştirme Detayları</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="space-y-2 relative">
                                <label for="eslesme_durumu" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 required dark:text-slate-300">Durum</label>
                                <select style="color-scheme: light dark;" name="eslesme_durumu" id="eslesme_durumu"
                                        class="w-full px-4 py-3
                                               border-transparent bg-gray-50 dark:bg-gray-900/50
                                               rounded-xl
                                               text-gray-900 dark:text-white
                                               focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-gray-800
                                               transition-all shadow-sm"
                                        required x-model="form.eslesme_durumu">
                                    <option value="">Durum seçin...</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Beklemede">Beklemede</option>
                                    <option value="İptal">İptal</option>
                                    <option value="Tamamlandı">Tamamlandı</option>
                                </select>
                                @error('eslesme_durumu')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 relative">
                                <label class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer-wrapper">
                                    <input type="checkbox" name="one_cikan" value="1" class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer" x-model="form.one_cikan">
                                    <span class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer-label">Öne Çıkan Eşleştirme</span>
                                </label>
                            </div>

                            <div class="space-y-2 relative">
                                <label for="eslesme_tarihi" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Eşleştirme Tarihi</label>
                                <input type="datetime-local" name="eslesme_tarihi" id="eslesme_tarihi" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100"
                                    x-model="form.eslesme_tarihi">
                                @error('eslesme_tarihi')
                                    <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notlar Bölümü -->
            <div class="bg-white dark:bg-gray-800
                        rounded-2xl shadow-xl
                        border border-gray-100 dark:border-gray-700
                        transition-all duration-300 ease-in-out
                        hover:shadow-2xl">
                <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-700
                            bg-gradient-to-r from-gray-50 to-gray-100
                            dark:from-gray-800 dark:to-gray-700
                            rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">📝 Notlar ve Açıklamalar</h3>
                </div>
                <div class="p-8">
                    <div class="space-y-2 relative">
                        <label for="notlar" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Eşleştirme Notları</label>
                        <textarea name="notlar" id="notlar" rows="4" class="w-full px-4 py-3 border-transparent bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:bg-white dark:focus:bg-gray-800 transition-all shadow-sm resize-vertical dark:shadow-none dark:bg-slate-900 dark:text-slate-100" x-model="form.notlar"
                            placeholder="Bu eşleştirme hakkında notlarınızı buraya yazabilirsiniz..."></textarea>
                        @error('notlar')
                            <p class="text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <button type="submit" :disabled="loading"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50 dark:shadow-none" aria-label="Eşleştirmeyi kaydet">
                    <svg x-show="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg x-show="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="!loading">Eşleştirmeyi Kaydet</span>
                    <span x-show="loading">Kaydediliyor...</span>
                </button>

                <button type="button" @click="resetForm()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Formu Temizle
                </button>

                <a href="{{ route('admin.eslesmeler.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Geri Dön
                </a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <!-- Live Search System -->
    <script src="{{ asset('js/context7-live-search.js') }}"></script>

    <!-- Eşleşme form mantığı (Vanilla JS + Alpine.js) -->
    <script>
        function eslesmeForm() {
            return {
                loading: false,
                form: {
                    kisi_id: '',
                    ilan_id: '',
                    talep_id: '',
                    danisman_id: '',
                    eslesme_durumu: 'Aktif',
                    one_cikan: false,
                    eslesme_tarihi: '{{ now()->format("Y-m-d\TH:i") }}',
                    notlar: ''
                },

                init() {
                    console.log('✅ Eşleşme formu hazır');
                    // Live search bileşenleri otomatik başlar
                },

                clearKisi() {
                    this.form.kisi_id = '';
                    const searchInput = document.querySelector('[data-target-input="kisi_id"] input[type="text"]');
                    if (searchInput) {
                        searchInput.value = '';
                    }
                },

                clearIlan() {
                    this.form.ilan_id = '';
                    const searchInput = document.querySelector('[data-target-input="ilan_id"] input[type="text"]');
                    if (searchInput) {
                        searchInput.value = '';
                    }
                },

                clearTalep() {
                    this.form.talep_id = '';
                    const searchInput = document.querySelector('[data-target-input="talep_id"] input[type="text"]');
                    if (searchInput) {
                        searchInput.value = '';
                    }
                },

                resetForm() {
                    if (confirm('Formu temizlemek istediğinizden emin misiniz?')) {
                        this.form = {
                            kisi_id: '',
                            ilan_id: '',
                            talep_id: '',
                            danisman_id: '',
                            eslesme_durumu: 'Aktif',
                            one_cikan: false,
                            eslesme_tarihi: '{{ now()->format("Y-m-d\TH:i") }}',
                            notlar: ''
                        };

                        // Tüm live search inputlarını temizle
                        document.querySelectorAll('.context7-live-search input[type="text"]').forEach(input => {
                            input.value = '';
                        });

                        console.log('🔄 Form temizlendi');
                    }
                }
            }
        }

        // Sayfa hazır olduğunda başlat
        document.addEventListener('DOMContentLoaded', () => {
            console.log('✅ Eşleşme oluşturma sayfası yüklendi');
        });
    </script>
@endpush

@push('styles')
    <style>
        .accent-blue-600 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700-wrapper {
            @apply flex items-center gap-2 cursor-pointer;
        }

        .accent-blue-600 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 {
            @apply w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2;
        }

        .accent-blue-600 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700-label {
            @apply text-sm font-medium text-gray-900 dark:text-white;
        }

        .required::after {
            content: ' *';
            @apply text-red-500;
        }
    </style>
@endpush
