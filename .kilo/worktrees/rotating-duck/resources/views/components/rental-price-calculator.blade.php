{{-- ========================================
     RENTAL PRICE CALCULATOR WIDGET
     Check-in/Check-out tarih seçici ile otomatik fiyat hesaplama
     ======================================== --}}

@props([
    'ilan' => null,
    'dailyPrice' => null,
    'weeklyPrice' => null,
    'monthlyPrice' => null,
    'seasonalPrice' => null,
    'currency' => 'TRY',
    'showComparison' => true,
])

@php
    $dailyPrice = $dailyPrice ?? $ilan->gunluk_fiyat ?? 0;
    $weeklyPrice = $weeklyPrice ?? $ilan->haftalik_fiyat ?? 0;
    $monthlyPrice = $monthlyPrice ?? $ilan->aylik_fiyat ?? 0;
    $seasonalPrice = $seasonalPrice ?? $ilan->sezonluk_fiyat ?? 0;
    $currency = $currency ?? $ilan->para_birimi ?? 'TRY';
@endphp

<div class="rental-price-calculator bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800/30 p-6" x-data="rentalPriceCalculator({
    dailyPrice: {{ $dailyPrice }},
    weeklyPrice: {{ $weeklyPrice }},
    monthlyPrice: {{ $monthlyPrice }},
    seasonalPrice: {{ $seasonalPrice }},
    currency: '{{ $currency }}'
})">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Fiyat Hesaplayıcı</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Tarih seçerek toplam fiyatı hesaplayın</p>
        </div>
    </div>

    {{-- Tarih Seçici --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                Giriş Tarihi (Check-in)
            </label>
            <input type="date"
                   x-model="checkInDate"
                   @change="calculatePrice()"
                   :min="today"
                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                Çıkış Tarihi (Check-out)
            </label>
            <input type="date"
                   x-model="checkOutDate"
                   @change="calculatePrice()"
                   :min="checkInDate || today"
                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 dark:text-slate-100">
        </div>
    </div>

    {{-- Hesaplanan Fiyat --}}
    <div x-show="calculatedPrice > 0" x-transition class="mb-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border-2 border-blue-500 dark:border-blue-400">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Konaklama Süresi</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        <span x-text="nights"></span> Gece
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Fiyat</div>
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        <span x-text="formatPrice(calculatedPrice)"></span>
                    </div>
                </div>
            </div>

            {{-- Fiyat Detayları --}}
            <div class="pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Fiyat Detayları:</div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Günlük Fiyat:</span>
                        <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100" x-text="formatPrice(dailyPrice)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Toplam Gece:</span>
                        <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100" x-text="nights"></span>
                    </div>
                    <div class="flex justify-between text-sm font-semibold pt-2 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <span class="text-gray-900 dark:text-white dark:text-slate-100">Toplam:</span>
                        <span class="text-blue-600 dark:text-blue-400" x-text="formatPrice(calculatedPrice)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fiyat Karşılaştırması --}}
    @if($showComparison && ($weeklyPrice > 0 || $monthlyPrice > 0))
    <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Fiyat Karşılaştırması:</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @if($weeklyPrice > 0)
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg dark:bg-slate-900">
                <div class="text-xs text-gray-600 dark:text-gray-400">Haftalık</div>
                <div class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    {{ number_format($weeklyPrice, 0, ',', '.') }} {{ $currency }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    ~{{ number_format($weeklyPrice / 7, 0, ',', '.') }} {{ $currency }}/gün
                </div>
            </div>
            @endif

            @if($monthlyPrice > 0)
            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg dark:bg-slate-900">
                <div class="text-xs text-gray-600 dark:text-gray-400">Aylık</div>
                <div class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    {{ number_format($monthlyPrice, 0, ',', '.') }} {{ $currency }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    ~{{ number_format($monthlyPrice / 30, 0, ',', '.') }} {{ $currency }}/gün
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<script>
function rentalPriceCalculator(config) {
    return {
        dailyPrice: config.dailyPrice || 0,
        weeklyPrice: config.weeklyPrice || 0,
        monthlyPrice: config.monthlyPrice || 0,
        seasonalPrice: config.seasonalPrice || 0,
        currency: config.currency || 'TRY',
        checkInDate: '',
        checkOutDate: '',
        calculatedPrice: 0,
        nights: 0,

        get today() {
            return new Date().toISOString().split('T')[0];
        },

        calculatePrice() {
            if (!this.checkInDate || !this.checkOutDate) {
                this.calculatedPrice = 0;
                this.nights = 0;
                return;
            }

            const checkIn = new Date(this.checkInDate);
            const checkOut = new Date(this.checkOutDate);

            if (checkOut <= checkIn) {
                this.calculatedPrice = 0;
                this.nights = 0;
                return;
            }

            const diffTime = checkOut - checkIn;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            this.nights = diffDays;

            // Hesaplama mantığı: Önce aylık, sonra haftalık, sonra günlük
            if (diffDays >= 30 && this.monthlyPrice > 0) {
                const months = Math.floor(diffDays / 30);
                const remainingDays = diffDays % 30;
                this.calculatedPrice = (months * this.monthlyPrice) + (remainingDays * this.dailyPrice);
            } else if (diffDays >= 7 && this.weeklyPrice > 0) {
                const weeks = Math.floor(diffDays / 7);
                const remainingDays = diffDays % 7;
                this.calculatedPrice = (weeks * this.weeklyPrice) + (remainingDays * this.dailyPrice);
            } else {
                this.calculatedPrice = diffDays * this.dailyPrice;
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: this.currency === 'TRY' ? 'TRY' : this.currency,
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price).replace('TRY', '₺');
        }
    }
}
</script>
