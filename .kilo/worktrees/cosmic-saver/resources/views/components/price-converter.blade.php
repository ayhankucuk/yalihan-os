{{-- Price Converter Component --}}
@props(['price', 'currency' => 'TRY', 'showConverter' => true, 'rentalType' => null])

@php
    $currencyService = app(\App\Services\CurrencyService::class);
    $conversions = $showConverter ? $currencyService->convertPrice($price, $currency) : [];
    $formattedPrice = $currencyService->formatCurrency($price, $currency);
@endphp

<div class="price-converter-wrapper" x-data="{ showConverter: false }">
    {{-- Ana fiyat --}}
    <div class="flex items-center gap-2">
        <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
            {{ $formattedPrice }}
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
        </span>

        @if ($showConverter)
            <button @click="showConverter = !showConverter"
                class="flex items-center gap-1 px-2 py-1 text-xs bg-gray-100 hover:bg-primary-100 text-gray-600 hover:text-primary-600 rounded-lg transition-colors dark:bg-slate-900"
                title="Döviz çevirici">
                <span class="material-symbols-outlined">swap_horiz</span>
                <span>Döviz</span>
            </button>
        @endif
    </div>

    @if ($showConverter)
        {{-- Döviz çevirici dropdown --}}
        <div x-show="showConverter" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95" @click.away="showConverter = false"
            class="absolute z-50 mt-2 bg-white rounded-lg shadow-xl border border-gray-200 p-4 min-w-48 dark:bg-slate-900 dark:border-slate-700">

            <div class="text-sm font-medium text-gray-700 mb-3 dark:text-slate-300">Diğer Para Birimleri:</div>

            <div class="space-y-2">
                @foreach ($conversions as $curr => $amount)
                    @if ($curr !== $currency)
                        <div class="flex justify-between items-center py-1">
                            <span class="text-gray-600 text-sm">{{ $curr }}:</span>
                            <span class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $currencyService->formatCurrency($amount, $curr) }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="text-xs text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-slate-800">
                Güncel kurlar - {{ now()->format('d.m.Y H:i') }}
            </div>
        </div>
    @endif
</div>

<style>
    .price-converter-wrapper {
        position: relative;
        display: inline-block;
    }
</style>
