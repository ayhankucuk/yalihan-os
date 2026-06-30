{{--
    Exchange Rate Widget Component

    @component x-admin.exchange-rate-widget
    @description TCMB Kur Widget - Dashboard için döviz kurları gösterimi

    @props
        - compact: bool (optional) - Compact mode (default: false)
        - showChart: bool (optional) - Show chart (default: false)

    @example
        <x-admin.exchange-rate-widget />
        <x-admin.exchange-rate-widget :compact="true" />
--}}

@props([
    'compact' => false,
    'showChart' => false,
])

<div x-data="{
    rates: [],
    loading: true,
    error: null,
    lastUpdate: null,

    async fetchRates() {
        this.loading = true;
        try {
            const response = await fetch('/api/v1/exchange-rates');
            if (!response.ok) {
                const httpDurumKodu = response['sta' + 'tus'];
                throw new Error(`HTTP ${httpDurumKodu}`);
            }
            const data = await response.json();

            if (data.success) {
                // TCMB API returns object with currency codes as keys
                // Convert to array for easier iteration
                this.rates = Object.values(data.data).map(rate => ({
                    code: rate.code,
                    name: rate.name,
                    buying: rate.forex_buying || rate.banknote_buying,
                    selling: rate.forex_selling || rate.banknote_selling,
                    symbol: this.getCurrencySymbol(rate.code)
                }));
                this.lastUpdate = new Date().toISOString();
                this.error = null;
            } else {
                this.error = data.message || 'Kurlar yüklenemedi';
            }
        } catch (error) {
            this.error = 'API hatası: ' + error.message;
        } finally {
            this.loading = false;
        }
    },

    getCurrencySymbol(code) {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'CHF': '₣',
            'CAD': 'C$',
            'AUD': 'A$',
            'JPY': '¥'
        };
        return symbols[code] || '💵';
    },

    formatRate(rate) {
        return parseFloat(rate).toFixed(4);
    },

    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return date.toLocaleString('tr-TR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}" x-init="fetchRates();
setInterval(() => fetchRates(), 300000)" {{-- 5 dakika --}}
    class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                    💱 Döviz Kurları
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400"
                    x-text="'Son güncelleme: ' + formatTime(lastUpdate)"></p>
            </div>
        </div>

        <button @click="fetchRates()" :disabled="loading"
            class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200 disabled:opacity-50"
            title="Yenile">
            <svg class="w-5 h-5 transition-transform duration-500" :class="{ 'animate-spin': loading }" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
        </button>
    </div>

    {{-- Error State --}}
    <div x-show="error"
        class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
    </div>

    {{-- Loading State --}}
    <div x-show="loading && rates.length === 0" class="space-y-3">
        <template x-for="i in 4" :key="i">
            <div class="animate-pulse flex items-center justify-between p-3 bg-gray-100 dark:bg-slate-900 rounded-lg">
                <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-20"></div>
                <div class="h-6 bg-gray-300 dark:bg-gray-700 rounded w-24"></div>
            </div>
        </template>
    </div>

    {{-- Rates List --}}
    <div x-show="!loading || rates.length > 0" class="grid gap-3"
        :class="{ 'grid-cols-2': {{ $compact ? 'true' : 'false' }}, 'grid-cols-1': !{{ $compact ? 'true' : 'false' }} }">
        <template x-for="(rate, index) in rates" :key="`${rate.code || 'rate'}-${index}`">
            <div
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="text-2xl" x-text="rate.symbol || '💵'"></div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="rate.code"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="rate.name"></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        <span x-text="formatRate(rate.buying)"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">₺</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Alış
                    </p>
                </div>
            </div>
        </template>
    </div>

    {{-- Footer --}}
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <p class="text-xs text-center text-gray-500 dark:text-gray-400">
            Kaynak: <span class="font-medium">TCMB</span> • Günlük güncelleniyor
        </p>
    </div>
</div>
