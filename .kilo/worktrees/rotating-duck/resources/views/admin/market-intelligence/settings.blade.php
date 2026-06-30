@extends('admin.layouts.admin')

@section('title', 'Bölge Ayarları - Pazar İstihbaratı')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="marketIntelligenceSettings()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    Bölge Ayarları
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Veri çekilecek bölgeleri seçin ve önceliklerini belirleyin</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.market-intelligence.dashboard') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 dark:text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <div x-show="flashMessage" x-cloak
        class="mb-6 p-4 rounded-lg border transition-all duration-200 ease-in-out"
        :class="flashType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'">
        <div class="flex items-center justify-between">
            <p x-text="flashMessage"></p>
            <button @click="flashMessage = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Yeni Bölge Ekleme Formu -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 sticky top-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Yeni Bölge Ekle</h2>
                
                <form @submit.prevent="addRegion" class="space-y-4">
                    <!-- İl Seçimi -->
                    <div>
                        <label for="il_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            İl <span class="text-red-500">*</span>
                        </label>
                        <select id="il_id" x-model="form.il_id" @change="loadIlceler" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">İl Seçiniz</option>
                            @foreach($iller as $il)
                                <option value="{{ $il->id }}">{{ $il->il_adi }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- İlçe Seçimi -->
                    <div>
                        <label for="ilce_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            İlçe <span class="text-gray-500 dark:text-gray-500">(Opsiyonel)</span>
                        </label>
                        <select id="ilce_id" x-model="form.ilce_id" @change="loadMahalleler" :disabled="!form.il_id"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-slate-100">
                            <option value="">Tüm İlçeler</option>
                            <template x-for="ilce in ilceler" :key="ilce.id">
                                <option :value="ilce.id" x-text="ilce.ilce_adi"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Mahalle Seçimi -->
                    <div>
                        <label for="mahalle_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Mahalle <span class="text-gray-500 dark:text-gray-500">(Opsiyonel)</span>
                        </label>
                        <select id="mahalle_id" x-model="form.mahalle_id" :disabled="!form.ilce_id"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-slate-900 dark:text-slate-100">
                            <option value="">Tüm Mahalleler</option>
                            <template x-for="mahalle in mahalleler" :key="mahalle.id">
                                <option :value="mahalle.id" x-text="mahalle.mahalle_adi"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Öncelik -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Öncelik
                        </label>
                        <input type="number" id="priority" x-model="form.priority" min="0" max="100" value="50"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 dark:bg-slate-900 dark:text-slate-100"
                            placeholder="1-100 (düşük = önce)">
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">1-10: Yüksek, 11-50: Orta, 51-100: Düşük</p>
                    </div>

                    <!-- Aktif/Pasif -->
                    <div class="flex items-center">
                        <input type="checkbox" id="status" x-model="form.status"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900">
                        <label for="status" class="ml-2 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            Aktif (Veri çekilsin)
                        </label>
                    </div>

                    <!-- Kaydet Butonu -->
                    <button type="submit" :disabled="loading || !form.il_id"
                        class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 dark:shadow-none">
                        <span x-show="!loading">Bölge Ekle</span>
                        <span x-show="loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Kaydediliyor...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Mevcut Bölgeler Listesi -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Mevcut Bölgeler</h2>
                    <span class="px-3 py-1 text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full">
                        {{ $settings->count() }} Bölge
                    </span>
                </div>

                @if($settings->count() > 0)
                    <div class="space-y-3">
                        @foreach($settings as $setting)
                            <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-slate-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200 ease-in-out hover:scale-[1.01] dark:border-slate-700"
                                data-setting-id="{{ $setting->id }}">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold text-sm">{{ $setting->priority }}</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $setting->location_text }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Öncelik: {{ $setting->priority }} | 
                                                {{ ($setting->aktiflik_durumu ?? 0) ? 'Aktif' : 'Pasif' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Aktif/Pasif Toggle -->
                                <button @click="toggleSetting({{ $setting->id }}, {{ $setting->aktiflik_durumu ? 'false' : 'true' }})"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                    :class="{{ $setting->aktiflik_durumu ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    {{ $setting->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </button>
                                    <!-- Sil Butonu -->
                                    <button @click="deleteSetting({{ $setting->id }})"
                                        class="px-3 py-1.5 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg text-sm font-medium hover:bg-red-200 dark:hover:bg-red-800 transition-all duration-200 ease-in-out hover:scale-105 active:scale-95 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz bölge eklenmemiş</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Yukarıdaki formu kullanarak yeni bir bölge ekleyin</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function marketIntelligenceSettings() {
    return {
        form: {
            il_id: '',
            ilce_id: '',
            mahalle_id: '',
            priority: 50,
            status: true
        },
        ilceler: [],
        mahalleler: [],
        loading: false,
        flashMessage: '',
        flashType: 'success',
        
        async loadIlceler() {
            if (!this.form.il_id) {
                this.ilceler = [];
                this.form.ilce_id = '';
                this.mahalleler = [];
                this.form.mahalle_id = '';
                return;
            }
            
            try {
                const response = await fetch(`/api/location/ilceler/${this.form.il_id}`);
                const data = await response.json();
                this.ilceler = data.data || [];
                this.form.ilce_id = '';
                this.mahalleler = [];
                this.form.mahalle_id = '';
            } catch (error) {
                console.error('İlçeler yüklenemedi:', error);
                this.showFlash('İlçeler yüklenirken hata oluştu', 'error');
            }
        },
        
        async loadMahalleler() {
            if (!this.form.ilce_id) {
                this.mahalleler = [];
                this.form.mahalle_id = '';
                return;
            }
            
            try {
                const response = await fetch(`/api/location/mahalleler/${this.form.ilce_id}`);
                const data = await response.json();
                this.mahalleler = data.data || [];
                this.form.mahalle_id = '';
            } catch (error) {
                console.error('Mahalleler yüklenemedi:', error);
                this.showFlash('Mahalleler yüklenirken hata oluştu', 'error');
            }
        },
        
        async addRegion() {
            if (!this.form.il_id) {
                this.showFlash('Lütfen en azından bir il seçin', 'error');
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch('/api/market-intelligence/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        regions: [{
                            il_id: parseInt(this.form.il_id),
                            ilce_id: this.form.ilce_id ? parseInt(this.form.ilce_id) : null,
                            mahalle_id: this.form.mahalle_id ? parseInt(this.form.mahalle_id) : null,
                            status: this.form.status,
                            priority: parseInt(this.form.priority) || 50
                        }]
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showFlash('Bölge başarıyla eklendi', 'success');
                    this.resetForm();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    this.showFlash(result.message || 'Bölge eklenirken hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Bölge eklenemedi:', error);
                this.showFlash('Bölge eklenirken hata oluştu', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        async toggleSetting(id, newStatus) {
            try {
                const response = await fetch(`/api/market-intelligence/settings/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showFlash(result.message || 'Ayar güncellendi', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    this.showFlash(result.message || 'Ayar güncellenirken hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Ayar güncellenemedi:', error);
                this.showFlash('Ayar güncellenirken hata oluştu', 'error');
            }
        },
        
        async deleteSetting(id) {
            if (!confirm('Bu bölge ayarını silmek istediğinizden emin misiniz?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/market-intelligence/settings/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showFlash('Bölge ayarı başarıyla silindi', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    this.showFlash(result.message || 'Bölge ayarı silinirken hata oluştu', 'error');
                }
            } catch (error) {
                console.error('Bölge ayarı silinemedi:', error);
                this.showFlash('Bölge ayarı silinirken hata oluştu', 'error');
            }
        },
        
        resetForm() {
            this.form = {
                il_id: '',
                ilce_id: '',
                mahalle_id: '',
                priority: 50,
                status: true
            };
            this.ilceler = [];
            this.mahalleler = [];
        },
        
        showFlash(message, type = 'success') {
            this.flashMessage = message;
            this.flashType = type;
            setTimeout(() => {
                this.flashMessage = '';
            }, 5000);
        }
    };
}
</script>
@endpush






