@extends('admin.layouts.admin')

@section('title', 'Komisyonlar')

@section('content')
<div class="space-y-6" x-data="komisyonlarApp()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Komisyonlar</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Danışman komisyonlarını yönetin ve takip edin</p>
        </div>
        <a href="{{ route('admin.finans.komisyonlar.create') }}"
            class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg shadow-md hover:bg-orange-700 hover:scale-105 hover:shadow-lg active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:outline-none transition-all duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Yeni Komisyon
        </a>
    </div>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="stats.toplam || 0">0</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Komisyon</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" x-text="stats.hesaplandi || 0">0</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Hesaplandı</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="stats.onaylandi || 0">0</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Onaylandı</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg border-2 border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-200 p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="stats.odendi || 0">0</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ödendi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap gap-4">
            <select x-model="filters.odeme_statusu" @change="loadKomisyonlar(1)"
                class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Tüm Durumlar</option>
                <option value="hesaplandi">Hesaplandı</option>
                <option value="onaylandi">Onaylandı</option>
                <option value="odendi">Ödendi</option>
            </select>

            <select x-model="filters.komisyon_tipi" @change="loadKomisyonlar(1)"
                class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Tüm Tipler</option>
                <option value="satis">Satış</option>
                <option value="kiralama">Kiralama</option>
                <option value="danismanlik">Danışmanlık</option>
            </select>

            <input type="text" x-model="filters.search" @keydown.enter="loadKomisyonlar(1)" placeholder="Danışman veya ilan ara..."
                class="flex-1 min-w-[200px] px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

            <button @click="loadKomisyonlar(1)"
                class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200">
                Ara
            </button>

            <button @click="clearFilters()"
                class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-200 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 active:scale-95 transition-all duration-200">
                Temizle
            </button>
        </div>
    </div>

    <!-- Yükleme Durumu -->
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Tablo -->
    <div x-show="!loading" class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">İlan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Müşteri</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Danışman</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Tip</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Tutar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                    <template x-if="komisyonlar.length === 0">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-slate-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-sm">Komisyon bulunamadı</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="komisyon in komisyonlar" :key="komisyon.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-200" x-text="komisyon.id"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                <template x-if="komisyon.ilan">
                                    <div>
                                        <span class="font-medium" x-text="komisyon.ilan.baslik || 'İlan #' + komisyon.ilan.id"></span>
                                        <span class="text-xs text-gray-400 block" x-text="formatCurrency(komisyon.ilan.fiyat, komisyon.ilan.para_birimi)"></span>
                                    </div>
                                </template>
                                <template x-if="!komisyon.ilan">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                <template x-if="komisyon.kisi">
                                    <span x-text="(komisyon.kisi.ad || '') + ' ' + (komisyon.kisi.soyad || '')"></span>
                                </template>
                                <template x-if="!komisyon.kisi">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                <template x-if="komisyon.danisman">
                                    <span x-text="komisyon.danisman.name || komisyon.danisman.email || 'Danışman #' + komisyon.danisman_id"></span>
                                </template>
                                <template x-if="!komisyon.danisman">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': komisyon.komisyon_tipi === 'satis',
                                        'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200': komisyon.komisyon_tipi === 'kiralama',
                                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': komisyon.komisyon_tipi === 'danismanlik'
                                    }"
                                    x-text="getTipLabel(komisyon.komisyon_tipi)">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-slate-200">
                                <span x-text="formatCurrency(komisyon.komisyon_tutari, komisyon.para_birimi)"></span>
                                <span class="text-xs text-gray-500 block" x-text="'%' + komisyon.komisyon_orani"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': komisyon.odeme_statusu === 'hesaplandi',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': komisyon.odeme_statusu === 'onaylandi',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': komisyon.odeme_statusu === 'odendi'
                                    }"
                                    x-text="getStatusLabel(komisyon.odeme_statusu)">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-3">
                                    <a :href="`/admin/komisyonlar/${komisyon.id}`"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                                        Görüntüle
                                    </a>
                                    <template x-if="komisyon.odeme_statusu === 'hesaplandi'">
                                        <button @click="approveKomisyon(komisyon.id)"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200">
                                            Onayla
                                        </button>
                                    </template>
                                    <template x-if="komisyon.odeme_statusu === 'onaylandi'">
                                        <button @click="payKomisyon(komisyon.id)"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 transition-colors duration-200">
                                            Öde
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sayfalama -->
    <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-slate-200">
                <span x-text="`Toplam ${pagination.total} kayıttan ${pagination.from || 0}-${pagination.to || 0} arası`"></span>
            </div>
            <div class="flex gap-2">
                <button @click="loadPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    :class="pagination.current_page === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-slate-700'"
                    class="px-4 py-2 border-2 border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 transition-colors duration-200">
                    Önceki
                </button>
                <button @click="loadPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    :class="pagination.current_page === pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50 dark:hover:bg-slate-700'"
                    class="px-4 py-2 border-2 border-gray-300 dark:border-slate-600 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 transition-colors duration-200">
                    Sonraki
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function komisyonlarApp() {
    return {
        komisyonlar: [],
        loading: true,
        filters: {
            odeme_statusu: '',
            komisyon_tipi: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0
        },
        stats: {
            toplam: 0,
            hesaplandi: 0,
            onaylandi: 0,
            odendi: 0
        },

        init() {
            this.loadKomisyonlar(1);
        },

        async loadKomisyonlar(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 20,
                    ...this.filters
                });

                const response = await fetch(`/api/v1/admin/komisyonlar?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) throw new Error('Veriler yüklenemedi');

                const result = await response.json();
                this.komisyonlar = result.data || result;
                this.pagination = {
                    current_page: result.current_page || 1,
                    last_page: result.last_page || 1,
                    total: result.total || 0,
                    from: result.from || 0,
                    to: result.to || 0
                };
                this.calculateStats();
            } catch (error) {
                console.error('Komisyonlar yüklenemedi:', error);
                window.toast?.('Komisyonlar yüklenemedi', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                await this.loadKomisyonlar(page);
            }
        },

        async approveKomisyon(id) {
            if (!confirm('Bu komisyonu onaylamak istediğinizden emin misiniz?')) return;

            try {
                const response = await fetch(`/api/v1/admin/komisyonlar/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) throw new Error('Onaylama başarısız');

                window.toast?.('Komisyon onaylandı', 'success');
                this.loadKomisyonlar(this.pagination.current_page);
            } catch (error) {
                console.error('Onaylama hatası:', error);
                window.toast?.('Komisyon onaylanamadı', 'error');
            }
        },

        async payKomisyon(id) {
            if (!confirm('Bu komisyonu ödendi olarak işaretlemek istediğinizden emin misiniz?')) return;

            try {
                const response = await fetch(`/api/v1/admin/komisyonlar/${id}/pay`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) throw new Error('Ödeme başarısız');

                window.toast?.('Komisyon ödendi olarak işaretlendi', 'success');
                this.loadKomisyonlar(this.pagination.current_page);
            } catch (error) {
                console.error('Ödeme hatası:', error);
                window.toast?.('Komisyon ödenemedi', 'error');
            }
        },

        calculateStats() {
            this.stats.toplam = this.pagination.total;
            this.stats.hesaplandi = this.komisyonlar.filter(k => k.odeme_statusu === 'hesaplandi').length;
            this.stats.onaylandi = this.komisyonlar.filter(k => k.odeme_statusu === 'onaylandi').length;
            this.stats.odendi = this.komisyonlar.filter(k => k.odeme_statusu === 'odendi').length;
        },

        clearFilters() {
            this.filters = { odeme_statusu: '', komisyon_tipi: '', search: '' };
            this.loadKomisyonlar(1);
        },

        formatCurrency(amount, currency = 'TRY') {
            if (!amount) return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: currency || 'TRY'
            }).format(0);
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: currency || 'TRY'
            }).format(amount);
        },

        getTipLabel(tip) {
            const labels = {
                'satis': 'Satış',
                'kiralama': 'Kiralama',
                'danismanlik': 'Danışmanlık'
            };
            return labels[tip] || tip || '-';
        },

        getStatusLabel(status) {
            const labels = {
                'hesaplandi': 'Hesaplandı',
                'onaylandi': 'Onaylandı',
                'odendi': 'Ödendi'
            };
            return labels[status] || status || '-';
        }
    };
}
</script>
@endpush
