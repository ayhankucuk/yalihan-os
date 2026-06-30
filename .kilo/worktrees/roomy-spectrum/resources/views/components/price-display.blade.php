{{-- ========================================
     PRICE DISPLAY COMPONENT
     Farklı fiyat görünüm türleri için
     ======================================== --}}

@props([
    'price' => null,
    'currency' => 'TRY',
    'displayType' => 'tam_fiyat',
    'startingPrice' => null,
    'minPrice' => null,
    'maxPrice' => null,
    'rentalType' => null,
    'dailyPrice' => null,
    'weeklyPrice' => null,
    'monthlyPrice' => null,
    'seasonalPrice' => null,
    'showContactButton' => true,
])

<div class="price-display" x-data="priceDisplay()">
    @switch($displayType)
        @case('tam_fiyat')
            {{-- Tam Fiyat Göster --}}
            <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                {{ number_format($price, 0, ',', '.') }} {{ $currency }}
                @if ($rentalType)
                    @switch($rentalType)
                        @case('gunluk')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Gün</span>
                        @break
                        @case('haftalik')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Hafta</span>
                        @break
                        @case('aylik')
                        @case('uzun_donem')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Ay</span>
                        @break
                        @case('sezonluk')
                            <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Sezon</span>
                        @break
                    @endswitch
                @endif
            </div>
        @break

        @case('baslayan_fiyat')
            {{-- Başlayan Fiyat --}}
            <div class="space-y-2">
                <div class="text-lg text-gray-600 dark:text-gray-400">Başlayan Fiyat</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    {{ number_format($startingPrice ?? ($minPrice ?? $price), 0, ',', '.') }} {{ $currency }}
                    @if ($rentalType)
                        @switch($rentalType)
                            @case('gunluk')
                                <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Gün</span>
                            @break
                            @case('haftalik')
                                <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Hafta</span>
                            @break
                            @case('aylik')
                            @case('uzun_donem')
                                <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Ay</span>
                            @break
                            @case('sezonluk')
                                <span class="text-lg font-normal text-gray-600 dark:text-gray-400">/Sezon</span>
                            @break
                        @endswitch
                    @endif
                </div>
                @if ($maxPrice && $maxPrice > ($startingPrice ?? ($minPrice ?? $price)))
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        - {{ number_format($maxPrice, 0, ',', '.') }} {{ $currency }}
                        @if ($rentalType)
                            @switch($rentalType)
                                @case('gunluk')
                                    <span>/Gün</span>
                                @break
                                @case('haftalik')
                                    <span>/Hafta</span>
                                @break
                                @case('aylik')
                                @case('uzun_donem')
                                    <span>/Ay</span>
                                @break
                                @case('sezonluk')
                                    <span>/Sezon</span>
                                @break
                            @endswitch
                        @endif
                    </div>
                @endif
            </div>
        @break

        @case('fiyat_icin_arayin')
            {{-- Fiyat İçin Arayın --}}
            <div class="space-y-3">
                <div class="text-lg text-gray-600">Fiyat İçin Arayın</div>
                @if ($showContactButton)
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button @click="contactForPrice()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>Fiyat İçin Ara</span>
                        </button>
                        <button @click="whatsappForPrice()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488" />
                            </svg>
                            <span>WhatsApp</span>
                        </button>
                    </div>
                @endif
            </div>
        @break

        @case('gizli')
            {{-- Fiyat Gizli --}}
            <div class="space-y-3">
                <div class="text-lg text-gray-600">Fiyat Bilgisi Gizli</div>
                @if ($showContactButton)
                    <button @click="contactForPrice()"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Fiyat İçin İletişime Geçin
                    </button>
                @endif
            </div>
        @break

        @default
            {{-- Varsayılan: Tam Fiyat --}}
            <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                {{ number_format($price, 0, ',', '.') }} {{ $currency }}
            </div>
    @endswitch

    {{-- Kiralama Fiyatları (Eğer kiralama ise ve çoklu fiyat varsa) --}}
    @if ($rentalType && (($dailyPrice && $dailyPrice != $price) || ($weeklyPrice && $weeklyPrice != $price) || ($monthlyPrice && $monthlyPrice != $price) || ($seasonalPrice && $seasonalPrice != $price)))
        <div class="mt-3 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800/30">
            <div class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Tüm Kiralama Fiyatları:
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @if ($dailyPrice && $dailyPrice != $price)
                    <div class="flex justify-between items-center p-2 bg-white dark:bg-slate-900 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">📅 Günlük:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($dailyPrice, 0, ',', '.') }} {{ $currency }}/Gün</span>
                    </div>
                @endif
                @if ($weeklyPrice && $weeklyPrice != $price)
                    <div class="flex justify-between items-center p-2 bg-white dark:bg-slate-900 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">📆 Haftalık:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($weeklyPrice, 0, ',', '.') }} {{ $currency }}/Hafta</span>
                    </div>
                @endif
                @if ($monthlyPrice && $monthlyPrice != $price)
                    <div class="flex justify-between items-center p-2 bg-white dark:bg-slate-900 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">🗓️ Aylık:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($monthlyPrice, 0, ',', '.') }} {{ $currency }}/Ay</span>
                    </div>
                @endif
                @if ($seasonalPrice && $seasonalPrice != $price)
                    <div class="flex justify-between items-center p-2 bg-white dark:bg-slate-900 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">🌅 Sezonluk:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($seasonalPrice, 0, ',', '.') }} {{ $currency }}/Sezon</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
    function priceDisplay() {
        return {
            contactForPrice() {
                // Telefon numarasına yönlendir
                window.location.href = 'tel:+905332090302';
            },

            whatsappForPrice() {
                // WhatsApp'a yönlendir
                const message = encodeURIComponent('Merhaba, bu ilan hakkında fiyat bilgisi almak istiyorum.');
                const phone = '905332090302';
                window.open(`https://wa.me/${phone}?text=${message}`, '_blank');
            },

            // Fiyat dönüştürme (para birimi değişikliği için)
            convertPrice(price, targetCurrency) {
                const rates = {
                    'TRY': 1,
                    'USD': 0.037,
                    'EUR': 0.034,
                    'GBP': 0.029
                };

                if (!rates[targetCurrency]) return price;

                const converted = parseFloat(price) * rates[targetCurrency];
                return new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: targetCurrency
                }).format(converted);
            }
        }
    }
</script>
