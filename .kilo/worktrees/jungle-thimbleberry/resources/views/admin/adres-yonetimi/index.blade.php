<?php
/**
 * Context7 Compliant Address Management Panel
 * 
 * @version 2.0.0 - Refactored for Context7 compliance
 * @author Yalıhan AI Team
 * 
 * Features:
 * - Pure Tailwind CSS (no Bootstrap/Neo)
 * - Dark mode support (dark: prefix)
 * - On-demand API sync
 * - Geocoding ready coordinates
 * - Minimalist design
 */
?>

@extends('admin.layouts.admin')

@section('title', 'Adres Yönetimi - Context7 Uyumlu')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900 p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Adres Yönetimi</h1>
            <p class="text-gray-600 dark:text-gray-400">Türkiye'nin idari yapılarını yönetin</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ \App\Models\Il::count() }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">İller</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ \App\Models\Ilce::count() }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">İlçeler</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ \App\Models\Mahalle::count() }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Mahalleler</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ \App\Models\Bolge::count() }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Bölgeler</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Sync Controls -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-800 mb-8 dark:shadow-none dark:border-slate-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="İl, ilçe, mahalle ara..." 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100"
                            x-data="{ query: '' }" 
                            x-model="query"
                        >
                        <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        @click="syncAll()" 
                        class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Tümünü Senkronize Et
                    </button>
                    
                    <button 
                        @click="syncFromAPI()" 
                        class="px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        API'den Getir
                    </button>
                </div>
            </div>
        </div>

        <!-- Address Hierarchy -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Provinces -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        İller
                    </h2>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-2" x-data="provincesData()">
                        <template x-for="province in provinces" :key="province.id">
                            <div 
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200 flex justify-between items-center dark:border-slate-700"
                                @click="loadDistricts(province.api_id)"
                            >
                                <span class="text-gray-900 dark:text-white dark:text-slate-100" x-text="province.il_adi"></span>
                                <span class="text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-slate-200 px-2 py-1 rounded dark:bg-slate-900" x-text="province.districts_count"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Districts -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        İlçe
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="'(' + districts.length + ')'" x-show="districts.length > 0"></span>
                    </h2>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-2" x-data="districtsData()">
                        <template x-for="district in districts" :key="district.id">
                            <div 
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200 flex justify-between items-center dark:border-slate-700"
                                @click="loadNeighborhoods(district.api_id)"
                            >
                                <span class="text-gray-900 dark:text-white dark:text-slate-100" x-text="district.ilce_adi"></span>
                                <span class="text-xs bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-slate-200 px-2 py-1 rounded dark:bg-slate-900" x-text="district.neighborhoods_count"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Neighborhoods -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        Mahalleler
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="'(' + neighborhoods.length + ')'" x-show="neighborhoods.length > 0"></span>
                    </h2>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-2" x-data="neighborhoodsData()">
                        <template x-for="neighborhood in neighborhoods" :key="neighborhood.id">
                            <div 
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-200 flex justify-between items-center dark:border-slate-700"
                                @click="showNeighborhoodDetails(neighborhood.id)"
                            >
                                <span class="text-gray-900 dark:text-white dark:text-slate-100" x-text="neighborhood.mahalle_adi"></span>
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="text-xs px-2 py-1 rounded" 
                                        :class="neighborhood.lat ? 'bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400' : 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400'"
                                        x-text="neighborhood.lat ? 'Koordinat: VAR' : 'Koordinat: YOK'"
                                    ></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div 
            x-show="showDetailsModal" 
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
            x-cloak
        >
            <div class="bg-white dark:bg-slate-900 rounded-xl max-w-2xl w-full max-h-96 overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Mahalle Detayları</h3>
                    <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4" x-show="selectedNeighborhood">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Mahalle Adı</label>
                            <p class="text-gray-900 dark:text-white dark:text-slate-100" x-text="selectedNeighborhood.mahalle_adi"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">API ID</label>
                            <p class="text-gray-900 dark:text-white dark:text-slate-100" x-text="selectedNeighborhood.api_id || 'Yok'"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Enlem (Lat)</label>
                            <input 
                                type="text" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                                x-model="selectedNeighborhood.lat"
                                placeholder="Koordinat girin"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Boylam (Lng)</label>
                            <input 
                                type="text" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                                x-model="selectedNeighborhood.lng"
                                placeholder="Koordinat girin"
                            >
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button 
                            @click="updateNeighborhoodCoordinates()" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200"
                        >
                            Koordinatları Güncelle
                        </button>
                        <button 
                            @click="geocodeNeighborhood()" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200"
                        >
                            Geocode ile Bul
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Context7 Compliant Alpine.js Components
    document.addEventListener('alpine:init', () => {
        // Provinces Component
        Alpine.data('provincesData', () => ({
            provinces: [],
            
            async init() {
                try {
                    const response = await fetch('/api/admin/address/provinces');
                    this.provinces = await response.json();
                } catch (error) {
                    console.error('Provinces load error:', error);
                }
            }
        }));
        
        // Districts Component
        Alpine.data('districtsData', () => ({
            districts: [],
            
            async loadDistricts(provinceApiId) {
                try {
                    const response = await fetch(`/api/admin/address/districts/${provinceApiId}`);
                    this.districts = await response.json();
                } catch (error) {
                    console.error('Districts load error:', error);
                }
            }
        }));
        
        // Neighborhoods Component
        Alpine.data('neighborhoodsData', () => ({
            neighborhoods: [],
            showDetailsModal: false,
            selectedNeighborhood: null,
            
            async loadNeighborhoods(districtApiId) {
                try {
                    const response = await fetch(`/api/admin/address/neighborhoods/${districtApiId}`);
                    this.neighborhoods = await response.json();
                } catch (error) {
                    console.error('Neighborhoods load error:', error);
                }
            },
            
            showNeighborhoodDetails(neighborhoodId) {
                const neighborhood = this.neighborhoods.find(n => n.id == neighborhoodId);
                if (neighborhood) {
                    this.selectedNeighborhood = {...neighborhood};
                    this.showDetailsModal = true;
                }
            },
            
            async updateNeighborhoodCoordinates() {
                if (!this.selectedNeighborhood) return;
                
                try {
                    const response = await fetch(`/api/admin/address/neighborhoods/${this.selectedNeighborhood.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            lat: this.selectedNeighborhood.lat,
                            lng: this.selectedNeighborhood.lng
                        })
                    });
                    
                    if (response.ok) {
                        // Update local array
                        const index = this.neighborhoods.findIndex(n => n.id == this.selectedNeighborhood.id);
                        if (index !== -1) {
                            this.neighborhoods[index] = {...this.selectedNeighborhood};
                        }
                        
                        this.showDetailsModal = false;
                        alert('Koordinatlar başarıyla güncellendi!');
                    }
                } catch (error) {
                    console.error('Update error:', error);
                    alert('Güncelleme sırasında hata oluştu!');
                }
            },
            
            async geocodeNeighborhood() {
                if (!this.selectedNeighborhood) return;
                
                // In a real implementation, this would call a geocoding service
                // For now, we'll simulate a response
                alert('Geocoding hizmeti entegrasyonu için API çağrısı yapılacak');
            }
        }));
        
        // Global functions
        window.syncAll = async function() {
            if (!confirm('Tüm verileri API ile senkronize etmek istediğinize emin misiniz?')) return;
            
            try {
                const response = await fetch('/api/admin/address/sync-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                alert(`Senkronizasyon tamamlandı! Oluşturulan: ${result.created}, Güncellenen: ${result.updated}`);
            } catch (error) {
                console.error('Sync error:', error);
                alert('Senkronizasyon sırasında hata oluştu!');
            }
        };
        
        window.syncFromAPI = async function() {
            try {
                const response = await fetch('/api/admin/address/fetch-from-turkiyeapi', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                alert('API verileri başarıyla getirildi!');
            } catch (error) {
                console.error('API fetch error:', error);
                alert('API verileri getirilirken hata oluştu!');
            }
        };
    });
</script>
@endpush
@endsection