{{-- Season Pricing Manager Component --}}
{{-- Pure Tailwind + Alpine.js --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

<div x-data="seasonPricingManager({{ json_encode($ilan->id ?? null) }})"
     x-init="init()"
     class="bg-white dark:bg-slate-900 rounded-xl border-2 border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                🏖️ Sezonluk Fiyatlandırma
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Yaz, kış ve ara sezon için farklı fiyatlar belirleyin
            </p>
        </div>
        <button
            type="button"
            @click="addSeason()"
            class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg font-semibold hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-105">
            ➕ Sezon Ekle
        </button>
    </div>

    {{-- Seasons List --}}
    <div class="space-y-4 mb-6">
        <template x-for="(season, index) in seasons" :key="season.id || index">
            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:shadow-lg"
                 :class="getSeasonColor(season.season_type)">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl" x-text="getSeasonIcon(season.season_type)"></span>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="getSeasonName(season.season_type)"></h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="formatDate(season.start_date)"></span> - <span x-text="formatDate(season.end_date)"></span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="removeSeason(index)"
                        class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-semibold text-sm">
                        ✖ Sil
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {{-- Season Type --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Sezon Tipi
                        </label>
                        <select
                            x-model="season.season_type"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="yaz">☀️ Yaz Sezonu (Yüksek Sezon)</option>
                            <option value="kis">❄️ Kış Sezonu (Düşük Sezon)</option>
                            <option value="ara_sezon">🍂 Ara Sezon (Normal)</option>
                        </select>
                    </div>

                    {{-- Date Range --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Başlangıç Tarihi *
                        </label>
                        <input
                            type="date"
                            x-model="season.start_date"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Bitiş Tarihi *
                        </label>
                        <input
                            type="date"
                            x-model="season.end_date"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Status (using is_active from DB - TODO: migrate to status) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Durum
                        </label>
                        <select
                            x-model="season.is_active"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option :value="1">✅ Aktif</option>
                            <option :value="0">⏸️ Pasif</option>
                        </select>
                    </div>

                    {{-- Prices --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            💰 Günlük Fiyat (₺)
                        </label>
                        <input
                            type="number"
                            x-model.number="season.daily_price"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            💰 Haftalık Fiyat (₺)
                        </label>
                        <input
                            type="number"
                            x-model.number="season.weekly_price"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            💰 Aylık Fiyat (₺)
                        </label>
                        <input
                            type="number"
                            x-model.number="season.monthly_price"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Min/Max Stay --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            📅 Min. Konaklama (gece)
                        </label>
                        <input
                            type="number"
                            x-model.number="season.minimum_stay"
                            min="1"
                            max="30"
                            placeholder="3"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            📅 Max. Konaklama (gece)
                        </label>
                        <input
                            type="number"
                            x-model.number="season.maximum_stay"
                            min="1"
                            max="365"
                            placeholder="30"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Summary --}}
    <div x-show="seasons.length > 0" class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-2 border-blue-200 dark:border-blue-700 rounded-xl p-5">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">📊 Sezon Özeti</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Sezon</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="seasons.length"></p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Aktif Sezon</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="seasons.filter(s => s.is_active === 1).length"></p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ort. Günlük Fiyat</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="formatPrice(averageDailyPrice)"></p>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="seasons.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl">
        <div class="text-4xl mb-3">🏖️</div>
        <p class="text-sm">Henüz sezon eklenmedi</p>
        <p class="text-xs mt-2">Yaz, kış ve ara sezon fiyatlarını ekleyin</p>
    </div>

    {{-- Hidden Input (JSON data for Laravel) --}}
    <input
        type="hidden"
        name="seasons_data"
        :value="JSON.stringify(seasons)">
</div>

@push('scripts')
<script>
function seasonPricingManager(ilanId = null) {
    return {
        ilanId: ilanId,
        seasons: [],

        get averageDailyPrice() {
            if (this.seasons.length === 0) return 0;
            const total = this.seasons.reduce((sum, s) => sum + (s.daily_price || 0), 0);
            return total / this.seasons.length;
        },

        async init() {
            if (this.ilanId) {
                await this.loadSeasons();
            }
        },

        async loadSeasons() {
            try {
                const response = await fetch(`/api/admin/ilanlar/${this.ilanId}/seasons`);
                if (response.ok) {
                    const data = await response.json();
                    this.seasons = data.seasons || [];
                }
            } catch (error) {
                console.error('Sezonlar yüklenemedi:', error);
            }
        },

        addSeason() {
            this.seasons.push({
                id: null,
                season_type: 'yaz',
                name: 'Yaz Sezonu',
                start_date: '',
                end_date: '',
                daily_price: 0,
                weekly_price: 0,
                monthly_price: 0,
                minimum_stay: 3,
                maximum_stay: 30,
                is_active: 1
            });
        },

        async removeSeason(index) {
            const season = this.seasons[index];

            // Delete from server if exists
            if (this.ilanId && season.id) {
                try {
                    await fetch(`/api/admin/seasons/${season.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                } catch (error) {
                    console.error('Delete error:', error);
                }
            }

            this.seasons.splice(index, 1);
            window.toast?.('Sezon silindi', 'success');
        },

        getSeasonColor(type) {
            const colors = {
                'yaz': 'border-orange-400 dark:border-orange-500 bg-orange-50 dark:bg-orange-900/20',
                'kis': 'border-blue-400 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20',
                'ara_sezon': 'border-green-400 dark:border-green-500 bg-green-50 dark:bg-green-900/20'
            };
            return colors[type] || 'border-gray-300 dark:border-gray-600';
        },

        getSeasonIcon(type) {
            const icons = {
                'yaz': '☀️',
                'kis': '❄️',
                'ara_sezon': '🍂'
            };
            return icons[type] || '🏖️';
        },

        getSeasonName(type) {
            const names = {
                'yaz': 'Yaz Sezonu',
                'kis': 'Kış Sezonu',
                'ara_sezon': 'Ara Sezon'
            };
            return names[type] || 'Sezon';
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('tr-TR', {
                day: 'numeric',
                month: 'short'
            });
        },

        formatPrice(price) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY',
                minimumFractionDigits: 0
            }).format(price);
        }
    }
}
</script>
@endpush
