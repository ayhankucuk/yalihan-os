<?php

namespace App\Services\Price;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Döviz Kuru Servisi
 *
 * Context7 Standardı: C7-CURRENCY-RATE-SERVICE-2025-10-11
 *
 * Real-time döviz kurları için Exchange Rate API entegrasyonu
 * Cache ile performans optimizasyonu
 * Fallback mekanizması
 */
class CurrencyRateService
{
    /**
     * Exchange Rate API URL
     */
    const API_URL = 'https://api.exchangerate-api.com/v4/latest/TRY';

    /**
     * Cache süresi (1 saat)
     */
    const CACHE_TTL = 3600;

    /**
     * Fallback kurlar (API çalışmazsa)
     */
    const FALLBACK_RATES = [
        'TRY' => 1,
        'USD' => 34.50,
        'EUR' => 37.20,
        'GBP' => 43.80,
    ];

    /**
     * Güncel döviz kurlarını al
     */
    public function getRates(): array
    {
        return Cache::remember('currency_rates', self::CACHE_TTL, function () {
            try {
                $response = Http::timeout(5)->get(self::API_URL);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['rates'])) {
                        return [
                            'rates' => $data['rates'],
                            'last_updated' => now()->toIso8601String(),
                            'source' => 'exchangerate-api.com',
                            'base_currency' => 'TRY',
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Currency rate API failed, using fallback rates', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback
            return [
                'rates' => self::FALLBACK_RATES,
                'last_updated' => now()->toIso8601String(),
                'source' => 'fallback',
                'base_currency' => 'TRY',
            ];
        });
    }

    /**
     * Belirli bir para birimi çifti için kur al
     */
    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rates = $this->getRates()['rates'];

        // TRY to X
        if ($from === 'TRY' && isset($rates[$to])) {
            return 1 / $rates[$to];
        }

        // X to TRY
        if ($to === 'TRY' && isset($rates[$from])) {
            return $rates[$from];
        }

        // X to Y (cross rate)
        if (isset($rates[$from]) && isset($rates[$to])) {
            $inTRY = $rates[$from];

            return $inTRY / $rates[$to];
        }

        return 1.0;
    }

    /**
     * Para birimi çevir
     */
    public function convert(float $amount, string $from, string $to): float
    {
        $rate = $this->getRate($from, $to);

        return round($amount * $rate, 2);
    }

    /**
     * Formatlanmış fiyat string'i
     */
    public function format(float $amount, string $currency = 'TRY'): string
    {
        $symbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $formatted = number_format($amount, 2, ',', '.');
        $symbol = $symbols[$currency] ?? $currency;

        return $formatted.' '.$symbol;
    }

    /**
     * Cache'i temizle ve yenile
     */
    public function refresh(): array
    {
        Cache::forget('currency_rates');

        return $this->getRates();
    }

    /**
     * Desteklenen para birimleri
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'TRY' => ['name' => 'Türk Lirası', 'symbol' => '₺'],
            'USD' => ['name' => 'Amerikan Doları', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
            'GBP' => ['name' => 'İngiliz Sterlini', 'symbol' => '£'],
        ];
    }

    /**
     * Multi-currency fiyat listesi
     */
    public function convertToAllCurrencies(float $amount, string $baseCurrency = 'TRY'): array
    {
        $result = [];

        foreach ($this->getSupportedCurrencies() as $currency => $info) {
            $converted = $this->convert($amount, $baseCurrency, $currency);
            $result[$currency] = [
                'amount' => $converted,
                'formatted' => $this->format($converted, $currency),
                'symbol' => $info['symbol'],
                'name' => $info['name'],
            ];
        }

        return $result;
    }
}
