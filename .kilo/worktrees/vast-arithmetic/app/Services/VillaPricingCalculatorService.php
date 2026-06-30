<?php

namespace App\Services;

/**
 * Villa Fiyatlandırma Hesaplama Servisi
 *
 * Context7: C7-VILLA-PRICING-CALCULATOR-2025-11-30
 *
 * Bu servis, villalar için günlük fiyattan haftalık, aylık ve sezonluk
 * fiyat önerileri üretir. Danışmanların hesap makinesiyle uğraşmasını
 * engeller ve fiyatlandırma stratejisini standartlaştırır.
 */
class VillaPricingCalculatorService
{
    /**
     * Haftalık indirim oranı (%5)
     */
    private const WEEKLY_DISCOUNT_RATE = 0.05;

    /**
     * Aylık indirim oranı (%10)
     */
    private const MONTHLY_DISCOUNT_RATE = 0.10;

    /**
     * Kış sezonu indirim oranı (%50)
     */
    private const WINTER_DISCOUNT_RATE = 0.50;

    /**
     * Ara sezon indirim oranı (%30)
     */
    private const MID_SEASON_DISCOUNT_RATE = 0.30;

    /**
     * Günlük fiyattan tüm fiyat önerilerini hesapla
     *
     * @param  float  $dailyPrice  Günlük fiyat (TRY)
     * @param  string  $currency  Para birimi (varsayılan: TRY)
     * @return array
     */
    public function calculateAllPrices(float $dailyPrice, string $currency = 'TRY'): array
    {
        return [
            'daily_price' => [
                'value' => $dailyPrice,
                'currency' => $currency,
                'formatted' => $this->formatPrice($dailyPrice, $currency),
                'description' => 'Günlük fiyat',
            ],
            'weekly_price' => [
                'value' => $this->calculateWeeklyPrice($dailyPrice),
                'currency' => $currency,
                'formatted' => $this->formatPrice($this->calculateWeeklyPrice($dailyPrice), $currency),
                'description' => 'Haftalık fiyat (7 gün × %5 indirim)',
                'savings' => $this->calculateWeeklySavings($dailyPrice),
                'savings_formatted' => $this->formatPrice($this->calculateWeeklySavings($dailyPrice), $currency),
            ],
            'monthly_price' => [
                'value' => $this->calculateMonthlyPrice($dailyPrice),
                'currency' => $currency,
                'formatted' => $this->formatPrice($this->calculateMonthlyPrice($dailyPrice), $currency),
                'description' => 'Aylık fiyat (30 gün × %10 indirim)',
                'savings' => $this->calculateMonthlySavings($dailyPrice),
                'savings_formatted' => $this->formatPrice($this->calculateMonthlySavings($dailyPrice), $currency),
            ],
            'seasonal_prices' => [
                'yaz' => [
                    'value' => $dailyPrice,
                    'currency' => $currency,
                    'formatted' => $this->formatPrice($dailyPrice, $currency),
                    'description' => 'Yaz sezonu (Haziran-Eylül)',
                    'discount' => 0,
                ],
                'ara_sezon' => [
                    'value' => $this->calculateMidSeasonPrice($dailyPrice),
                    'currency' => $currency,
                    'formatted' => $this->formatPrice($this->calculateMidSeasonPrice($dailyPrice), $currency),
                    'description' => 'Ara sezon (Nisan-Mayıs, Ekim) - %30 indirim',
                    'discount' => 30,
                ],
                'kis' => [
                    'value' => $this->calculateWinterPrice($dailyPrice),
                    'currency' => $currency,
                    'formatted' => $this->formatPrice($this->calculateWinterPrice($dailyPrice), $currency),
                    'description' => 'Kış sezonu (Kasım-Mart) - %50 indirim',
                    'discount' => 50,
                ],
            ],
            'recommendations' => $this->generateRecommendations($dailyPrice, $currency),
        ];
    }

    /**
     * Haftalık fiyat hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateWeeklyPrice(float $dailyPrice): float
    {
        // 7 gün × günlük fiyat × (1 - %5 indirim)
        return round(7 * $dailyPrice * (1 - self::WEEKLY_DISCOUNT_RATE), 2);
    }

    /**
     * Aylık fiyat hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateMonthlyPrice(float $dailyPrice): float
    {
        // 30 gün × günlük fiyat × (1 - %10 indirim)
        return round(30 * $dailyPrice * (1 - self::MONTHLY_DISCOUNT_RATE), 2);
    }

    /**
     * Kış sezonu fiyatı hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateWinterPrice(float $dailyPrice): float
    {
        // Günlük fiyat × (1 - %50 indirim)
        return round($dailyPrice * (1 - self::WINTER_DISCOUNT_RATE), 2);
    }

    /**
     * Ara sezon fiyatı hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateMidSeasonPrice(float $dailyPrice): float
    {
        // Günlük fiyat × (1 - %30 indirim)
        return round($dailyPrice * (1 - self::MID_SEASON_DISCOUNT_RATE), 2);
    }

    /**
     * Haftalık tasarruf hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateWeeklySavings(float $dailyPrice): float
    {
        $withoutDiscount = 7 * $dailyPrice;
        $withDiscount = $this->calculateWeeklyPrice($dailyPrice);

        return round($withoutDiscount - $withDiscount, 2);
    }

    /**
     * Aylık tasarruf hesapla
     *
     * @param  float  $dailyPrice
     * @return float
     */
    public function calculateMonthlySavings(float $dailyPrice): float
    {
        $withoutDiscount = 30 * $dailyPrice;
        $withDiscount = $this->calculateMonthlyPrice($dailyPrice);

        return round($withoutDiscount - $withDiscount, 2);
    }

    /**
     * Öneriler oluştur
     *
     * @param  float  $dailyPrice
     * @param  string  $currency
     * @return array
     */
    private function generateRecommendations(float $dailyPrice, string $currency): array
    {
        $weeklyPrice = $this->calculateWeeklyPrice($dailyPrice);
        $winterPrice = $this->calculateWinterPrice($dailyPrice);

        return [
            [
                'type' => 'weekly', // context7-ignore
                'title' => 'Haftalık Kiralama Önerisi',
                'message' => "Haftalık verirsen {$this->formatPrice($weeklyPrice,$currency)} yap",
                'price' => $weeklyPrice,
                'currency' => $currency,
                'savings' => $this->calculateWeeklySavings($dailyPrice),
                'priority' => 'high',
            ],
            [
                'type' => 'winter', // context7-ignore
                'title' => 'Kış Sezonu Önerisi',
                'message' => "Kışın bu villayı {$this->formatPrice($winterPrice,$currency)}'den verebilirsin",
                'price' => $winterPrice,
                'currency' => $currency,
                'discount' => 50,
                'priority' => 'medium',
            ],
            [
                'type' => 'monthly', // context7-ignore
                'title' => 'Aylık Kiralama Önerisi',
                'message' => "Aylık verirsen {$this->formatPrice($this->calculateMonthlyPrice($dailyPrice),$currency)} yap",
                'price' => $this->calculateMonthlyPrice($dailyPrice),
                'currency' => $currency,
                'savings' => $this->calculateMonthlySavings($dailyPrice),
                'priority' => 'low',
            ],
        ];
    }

    /**
     * Fiyatı formatla
     *
     * @param  float  $price
     * @param  string  $currency
     * @return string
     */
    private function formatPrice(float $price, string $currency = 'TRY'): string
    {
        $formatted = number_format($price, 2, ',', '.');

        return match ($currency) {
            'TRY' => "{$formatted} ₺",
            'USD' => "\${$formatted}",
            'EUR' => "€{$formatted}",
            default => "{$formatted} {$currency}",
        };
    }

    /**
     * Özel indirim oranlarıyla hesapla
     *
     * @param  float  $dailyPrice
     * @param  array  $discounts  ['weekly' => 0.05, 'monthly' => 0.10, 'winter' => 0.50]
     * @return array
     */
    public function calculateWithCustomDiscounts(float $dailyPrice, array $discounts = []): array
    {
        $weeklyDiscount = $discounts['weekly'] ?? self::WEEKLY_DISCOUNT_RATE;
        $monthlyDiscount = $discounts['monthly'] ?? self::MONTHLY_DISCOUNT_RATE;
        $winterDiscount = $discounts['winter'] ?? self::WINTER_DISCOUNT_RATE;
        $midSeasonDiscount = $discounts['mid_season'] ?? self::MID_SEASON_DISCOUNT_RATE;

        return [
            'weekly_price' => round(7 * $dailyPrice * (1 - $weeklyDiscount), 2),
            'monthly_price' => round(30 * $dailyPrice * (1 - $monthlyDiscount), 2),
            'winter_price' => round($dailyPrice * (1 - $winterDiscount), 2),
            'mid_season_price' => round($dailyPrice * (1 - $midSeasonDiscount), 2),
        ];
    }
}
