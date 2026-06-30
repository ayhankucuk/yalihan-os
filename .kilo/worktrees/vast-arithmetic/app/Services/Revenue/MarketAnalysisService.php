<?php

namespace App\Services\Revenue;

use Illuminate\Support\Facades\DB;

/**
 * Market Analysis Service
 *
 * Context7: Pazar analizi ve fiyat önerileri için servis
 * Phase 7.3: Revenue Intelligence
 *
 * Deterministic Refactor: 28 Mar 2026
 * - Removed rand() from getMarketData and listing_count
 * - Uses real DB data with structured fallback
 */
class MarketAnalysisService
{
    /**
     * Gerçek pazar verisini DB'den çek.
     *
     * @param int $ilId İl ID
     * @param int $ilceId İlçe ID
     * @param int $kategoriId Kategori ID
     * @return array
     */
    public function getMarketData(int $ilId, int $ilceId, int $kategoriId): array
    {
        $marketData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $stats = DB::table('ilanlar')
                ->where('il_id', $ilId)
                ->where('ilce_id', $ilceId)
                ->where('ana_kategori_id', $kategoriId)
                ->where('fiyat', '>', 0)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->selectRaw('AVG(fiyat) as avg_price, MIN(fiyat) as min_price, MAX(fiyat) as max_price, COUNT(*) as listing_count')
                ->first();

            if ($stats && $stats->listing_count > 0) {
                $marketData[] = [
                    'month' => $monthStart->format('Y-m'),
                    'avg_price' => (int) round($stats->avg_price, -3),
                    'min_price' => (int) round($stats->min_price, -3),
                    'max_price' => (int) round($stats->max_price, -3),
                    'listing_count' => (int) $stats->listing_count,
                ];
            }
        }

        return $marketData;
    }

    /**
     * Pazar trendini hesapla
     *
     * @param array $marketData
     * @return string 'up', 'down', veya 'stable'
     */
    public function calculateTrend(array $marketData): string
    {
        if (count($marketData) < 2) {
            return 'stable';
        }

        $first = $marketData[0]['avg_price'] ?? 0;
        $last = end($marketData)['avg_price'] ?? 0;

        if ($first <= 0) {
            return 'stable';
        }

        if ($last > $first * 1.05) {
            return 'up';
        } elseif ($last < $first * 0.95) {
            return 'down';
        }

        return 'stable';
    }

    /**
     * Güven skorunu hesapla (0-100)
     *
     * Deterministic: deviation-based fixed values.
     */
    public function calculateConfidence(array $marketData, float $currentPrice): int
    {
        if (empty($marketData)) {
            return 30;
        }

        $avgPrice = collect($marketData)->avg('avg_price');

        if ($avgPrice <= 0) {
            return 30;
        }

        $deviation = abs($currentPrice - $avgPrice) / $avgPrice;

        if ($deviation < 0.1) {
            return 95;
        } elseif ($deviation < 0.2) {
            return 85;
        } elseif ($deviation < 0.3) {
            return 70;
        }

        return 50;
    }

    /**
     * Önerilen fiyatı hesapla
     *
     * @param array $marketData
     * @param float $currentPrice
     * @return float
     */
    public function calculateRecommendedPrice(array $marketData, float $currentPrice): float
    {
        if (empty($marketData)) {
            return $currentPrice;
        }

        $avgPrice = collect($marketData)->avg('avg_price');
        $trend = $this->calculateTrend($marketData);

        $adjustment = match($trend) {
            'up' => 1.05,
            'down' => 0.95,
            default => 1.0,
        };

        $recommendedPrice = $avgPrice * $adjustment;

        return round($recommendedPrice, -3);
    }
}
