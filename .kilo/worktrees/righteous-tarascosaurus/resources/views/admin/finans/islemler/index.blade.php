@extends('admin.layouts.admin')

@section('title', 'Finansal İşlemler')

@section('content')
    <div class="space-y-6" x-data="finansalIslemler()">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Finansal İşlemler</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Finansal işlemlerinizi yönetin ve takip edin</p>
            </div>
            <a href="{{ route('admin.finans.islemler.create') }}"
                class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200 dark:shadow-none">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Yeni İşlem
            </a>
        </div>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
                class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-600" x-text="stats.total || 0">0</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Toplam İşlem</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-yellow-600" x-text="stats.bekliyor || 0">0</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Bekleyen</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-600" x-text="stats.onaylandi || 0">0</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Onaylanan</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-600" x-text="formatCurrency(stats.toplam || 0)">₺0</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Tutar</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                    <select x-model="filters.status" @change="loadIslemler()"
                        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="bekliyor">Bekleyen</option>
                        <option value="onaylandi">Onaylanan</option>
                        <option value="reddedildi">Reddedilen</option>
                        <option value="tamamlandi">Tamamlanan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">İşlem Tipi</label>
                    <select x-model="filters.islem_tipi" @change="loadIslemler()"
                        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                        <option value="">Tümü</option>
                        <option value="komisyon">Komisyon</option>
                        <option value="odeme">Ödeme</option>
                        <option value="masraf">Masraf</option>
                        <option value="gelir">Gelir</option>
                        <option value="gider">Gider</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Başlangıç Tarihi</label>
                    <input type="date" x-model="filters.start_date" @change="loadIslemler()"
                        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Bitiş Tarihi</label>
                    <input type="date" x-model="filters.end_date" @change="loadIslemler()"
                        class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                </div>
            </div>
        </div>

        <!-- İşlemler Tablosu -->
        <div
            class="bg-white dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tarih
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlem Tipi
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Miktar
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Kişi/İlan
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Durum
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex items-center justify-center">
                                        <svg class="animate-spin h-8 w-8 text-blue-600" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span class="ml-3 text-gray-600 dark:text-gray-400">Yükleniyor...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && islemler.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-lg font-medium">Henüz finansal işlem bulunmuyor</p>
                                        <p class="text-sm mt-2">Yeni bir işlem oluşturmak için yukarıdaki butonu kullanın
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="islem in islemler" :key="islem.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100"
                                    x-text="formatDate(islem.tarih)">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': islem
                                                .islem_tipi === 'komisyon',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': islem
                                                .islem_tipi === 'gelir',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': islem
                                                .islem_tipi === 'gider',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': islem
                                                .islem_tipi === 'odeme',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': islem
                                                .islem_tipi === 'masraf'
                                        }"
                                        x-text="getIslemTipiLabel(islem.islem_tipi)">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold"
                                    :class="{
                                        'text-green-600 dark:text-green-400': islem.islem_tipi === 'gelir' || islem
                                            .islem_tipi === 'komisyon',
                                        'text-red-600 dark:text-red-400': islem.islem_tipi === 'gider' || islem
                                            .islem_tipi === 'masraf'
                                    }"
                                    x-text="formatCurrency(islem.miktar, islem.para_birimi)">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    <template x-if="islem.kisi">
                                        <div>
                                            <span x-text="islem.kisi.name || 'İsimsiz'"></span>
                                        </div>
                                    </template>
                                    <template x-if="islem.ilan">
                                        <div class="text-xs text-gray-500 dark:text-gray-500">
                                            İlan: <span x-text="islem.ilan.baslik || 'İlansız'"></span>
                                        </div>
                                    </template>
                                    <template x-if="!islem.kisi && !islem.ilan">
                                        <span class="text-gray-400">-</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': islem
                                                .status === 'bekliyor',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': islem
                                                .status === 'onaylandi',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': islem
                                                .status === 'reddedildi',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': islem
                                                .status === 'tamamlandi'
                                        }"
                                        x-text="getStatusLabel(islem.status)">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a :href="`/admin/finans/islemler/${islem.id}`"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                                            Görüntüle
                                        </a>
                                        <a :href="`/admin/finans/islemler/${islem.id}/edit`"
                                            class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 transition-colors duration-200">
                                            Düzenle
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        <span
                            x-text="`Toplam ${pagination.total} kayıttan ${pagination.from || 0}-${pagination.to || 0} arası gösteriliyor`"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' :
                                'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 transition-colors duration-200 dark:text-slate-300">
                            Önceki
                        </button>
                        <button @click="loadPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' :
                                'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 transition-colors duration-200 dark:text-slate-300">
                            Sonraki
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function finansalIslemler() {
                return {
                    islemler: [],
                    loading: true,
                    filters: {
                        status: '',
                        islem_tipi: '',
                        start_date: '',
                        end_date: ''
                    },
                    stats: {
                        total: 0,
                        bekliyor: 0,
                        onaylandi: 0,
                        toplam: 0
                    },
                    pagination: {
                        current_page: 1,
                        last_page: 1,
                        total: 0,
                        from: 0,
                        to: 0
                    },
                    async init() {
                        await this.loadIslemler();
                    },
                    async loadIslemler(page = 1) {
                        this.loading = true;
                        try {
                            const params = new URLSearchParams({
                                page: page,
                                ...this.filters
                            });
                            const response = await fetch(`/api/admin/finans/islemler?${params}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const result = await response.json();
                            if (result.success) {
                                this.islemler = result.data.data || [];
                                this.pagination = {
                                    current_page: result.data.current_page,
                                    last_page: result.data.last_page,
                                    total: result.data.total,
                                    from: result.data.from,
                                    to: result.data.to
                                };
                                this.calculateStats();
                            }
                        } catch (error) {
                            console.error('İşlemler yüklenemedi:', error);
                            window.toast?.('İşlemler yüklenemedi', 'error');
                        } finally {
                            this.loading = false;
                        }
                    },
                    async loadPage(page) {
                        if (page >= 1 && page <= this.pagination.last_page) {
                            await this.loadIslemler(page);
                        }
                    },
                    calculateStats() {
                        this.stats.total = this.pagination.total;
                        this.stats.bekliyor = this.islemler.filter(i => i.status === 'bekliyor').length;
                        this.stats.onaylandi = this.islemler.filter(i => i.status === 'onaylandi').length;
                        this.stats.toplam = this.islemler.reduce((sum, i) => {
                            const miktar = parseFloat(i.miktar) || 0;
                            return sum + (i.islem_tipi === 'gelir' || i.islem_tipi === 'komisyon' ? miktar : -miktar);
                        }, 0);
                    },
                    formatDate(date) {
                        if (!date) return '-';
                        return new Date(date).toLocaleDateString('tr-TR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    },
                    formatCurrency(amount, currency = 'TRY') {
                        return new Intl.NumberFormat('tr-TR', {
                            style: 'currency',
                            currency: currency
                        }).format(amount || 0);
                    },
                    getIslemTipiLabel(tip) {
                        const labels = {
                            'komisyon': 'Komisyon',
                            'odeme': 'Ödeme',
                            'masraf': 'Masraf',
                            'gelir': 'Gelir',
                            'gider': 'Gider'
                        };
                        return labels[tip] || tip;
                    },
                    getStatusLabel(status) {
                        const labels = {
                            'bekliyor': 'Bekleyen',
                            'onaylandi': 'Onaylanan',
                            'reddedildi': 'Reddedilen',
                            'tamamlandi': 'Tamamlanan'
                        };
                        return labels[status] || status;
                    }
                }
            }
        </script>
    @endpush
@endsection
