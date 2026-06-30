@extends('admin.layouts.admin')

@section('title', 'Özellik Paketleri')

@section('content')
<div class="px-4 py-6" x-data="featurePackManager()">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm dark:shadow-none">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 dark:text-white">Özellik Paketleri (UPS)</h3>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors flex items-center shadow-sm dark:shadow-none" @click="openCreateModal()">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Yeni Paket Oluştur
                </button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($packs as $pack)
                <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 hover:shadow-md dark:hover:shadow-indigo-900/10 transition-shadow flex flex-col justify-between h-full bg-gray-50/50 dark:bg-gray-800/50 dark:border-slate-700">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-md font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $pack->name }}</h4>
                            @if(Str::contains(strtolower($pack->name), 'premium') || Str::contains(strtolower($pack->slug), 'luxury'))
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Premium</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-slate-200 dark:bg-slate-900">Standart</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $pack->description ?? 'Açıklama belirtilmemiş.' }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-500 mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="font-bold text-gray-700 dark:text-slate-200 mr-1 dark:text-slate-300">{{ $pack->features_count ?? 0 }}</span> özellik içeriyor.
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100 dark:border-slate-800">
                        <button @click="deletePack({{ $pack->id }}, '{{ $pack->name }}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                            Paketi Sil
                        </button>
                        <button @click="openManager({{ $pack->id }})" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors flex items-center">
                            Özellikleri Yönet
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                    Henüz bir özellik paketi tanımlanmamış.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Create Pack Modal -->
    <div x-show="showCreateModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-black/60 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showCreateModal = false"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-slate-900 w-full max-w-lg rounded-xl shadow-2xl dark:shadow-indigo-950/50 transform transition-all flex flex-col" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-900 rounded-t-xl dark:border-slate-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Paket Oluştur</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500 p-2 rounded-full transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Paket Adı</label>
                        <input type="text" x-model="newPack.name" placeholder="Örn: Premium Konut Özellikleri" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none dark:bg-slate-900 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Açıklama</label>
                        <textarea x-model="newPack.description" rows="3" placeholder="Paket hakkında kısa bilgi..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none dark:bg-slate-900 dark:text-slate-100"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-xl flex justify-end gap-3 dark:border-slate-700">
                    <button @click="showCreateModal = false" class="px-4 py-2 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium dark:text-slate-300">İptal</button>
                    <button @click="createPack()" :disabled="saving || !newPack.name" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50 flex items-center shadow-sm dark:shadow-none">
                        <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Oluştur</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Manager Modal -->
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-black/60 dark:bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

        <!-- Modal Panel -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-slate-900 w-full max-w-4xl rounded-xl shadow-2xl dark:shadow-indigo-950/50 transform transition-all flex flex-col max-h-[90vh]"
                 @click.stop>
                
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-900 rounded-t-xl dark:border-slate-700">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100" x-text="currentPack?.name ? 'Yönet: ' + currentPack.name : 'Yükleniyor...'"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Bu pakete dahil edilecek özellikleri seçin.</p>
                    </div>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-full transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 relative bg-white dark:bg-slate-900">
                    <!-- Loading State -->
                    <div x-show="loading" class="absolute inset-0 bg-white/80 dark:bg-slate-900/80 flex flex-col items-center justify-center z-10 backdrop-blur-sm dark:backdrop-blur-md">
                        <svg class="animate-spin h-12 w-12 text-blue-600 dark:text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-slate-200 font-medium">Özellikler yükleniyor...</span>
                    </div>

                    <div x-show="!loading && categories.length > 0">
                        <div class="mb-4">
                            <input type="text" placeholder="Özelliklerde ara..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition-shadow shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100" @input="filterFeatures($event.target.value)">
                        </div>

                        <template x-for="category in filteredCategories" :key="category.id">
                            <div class="mb-8 last:mb-0">
                                <h4 class="text-md font-bold text-gray-900 dark:text-white mb-3 pb-2 border-b border-gray-100 dark:border-slate-800 sticky top-0 bg-white dark:bg-slate-900 z-10 flex items-center justify-between dark:text-slate-100">
                                    <span x-text="category.name"></span>
                                    <span class="text-xs font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full dark:bg-slate-900" x-text="category.features.length"></span>
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <template x-for="feature in category.features" :key="feature.id">
                                        <label class="flex items-start p-3 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors group border border-transparent hover:border-blue-100 dark:hover:border-blue-800">
                                            <div class="flex items-center h-5">
                                                <input type="checkbox" 
                                                       :value="feature.id" 
                                                       x-model="selectedFeatureIds"
                                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 transition-colors">
                                            </div>
                                            <div class="ml-3 text-sm select-none">
                                                <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300 shadow-sm dark:shadow-none">
                                                    <span class="font-medium text-gray-700 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors dark:text-slate-300" x-text="feature.name"></span>
                                                </div>
                                                <span class="block text-xs text-gray-500" x-text="feature.slug"></span>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-xl flex justify-between items-center dark:border-slate-700">
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                        <span class="inline-flex items-center justify-center bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 font-bold px-2 py-0.5 rounded mr-2" x-text="selectedFeatureIds.length"></span>
                        özellik seçili
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModal()" class="px-4 py-2 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors dark:text-slate-300">
                            İptal
                        </button>
                        <button @click="saveChanges()" 
                                :disabled="saving"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-sm dark:shadow-none transition-all flex items-center disabled:opacity-50">
                            <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="saving ? 'Kaydediliyor...' : 'Özellikleri Kaydet'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function featurePackManager() {
    return {
        showModal: false,
        showCreateModal: false,
        loading: false,
        saving: false,
        currentPack: null,
        categories: [],
        filteredCategories: [],
        selectedFeatureIds: [],
        filterText: '',
        newPack: {
            name: '',
            description: ''
        },

        openCreateModal() {
            this.newPack = { name: '', description: '' };
            this.showCreateModal = true;
        },

        createPack() {
            if(!this.newPack.name) return;
            this.saving = true;
            fetch('/admin/ups/packs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.newPack)
            })
            .then(res => res.json())
            .then(data => {
                this.saving = false;
                if(data.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => {
                this.saving = false;
                alert('Hata: ' + err.message);
            });
        },

        deletePack(id, name) {
            if(!confirm(`'${name}' paketini silmek istediğinizden emin misiniz?`)) return;
            
            fetch(`/admin/ups/packs/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => alert('Hata: ' + err.message));
        },

        openManager(packId) {
            this.showModal = true;
            this.loading = true;
            this.currentPack = null;
            this.filterText = '';
            
            fetch(`/admin/ups/packs/${packId}/features`)
                .then(res => res.json())
                .then(data => {
                    this.currentPack = data.pack;
                    this.categories = data.categories;
                    this.filteredCategories = this.categories;
                    this.selectedFeatureIds = data.assigned_ids.map(String);
                    this.loading = false;
                })
                .catch(err => {
                    alert('Hata: ' + err.message);
                    this.loading = false;
                    this.showModal = false;
                });
        },

        filterFeatures(query) {
            this.filterText = query.toLowerCase();
            if (this.filterText === '') {
                this.filteredCategories = this.categories;
                return;
            }
            
            this.filteredCategories = this.categories.map(cat => {
                const matchingFeatures = cat.features.filter(f => 
                    f.name.toLowerCase().includes(this.filterText) || 
                    f.slug.toLowerCase().includes(this.filterText)
                );
                
                if (matchingFeatures.length > 0) {
                    return { ...cat, features: matchingFeatures };
                }
                return null;
            }).filter(cat => cat !== null);
        },

        saveChanges() {
            if(!this.currentPack) return;
            this.saving = true;
            
            fetch(`/admin/ups/packs/${this.currentPack.id}/features`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    feature_ids: this.selectedFeatureIds
                })
            })
            .then(res => res.json())
            .then(data => {
                this.saving = false;
                if(data.success) {
                    window.location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            })
            .catch(err => {
                this.saving = false;
                alert('Hata: ' + err.message);
            });
        },

        closeModal() {
            this.showModal = false;
            setTimeout(() => {
                this.categories = [];
                this.filteredCategories = [];
                this.selectedFeatureIds = [];
            }, 300);
        }
    }
}
</script>
@endsection
