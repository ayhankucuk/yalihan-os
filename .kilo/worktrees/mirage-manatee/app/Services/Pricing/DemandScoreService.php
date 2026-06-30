<?php

namespace App\Services\Pricing;

/**
 * Demand Score Service — MIE v1.2
 *
 * Bölgesel talep gücünü 0–100 arası skorlar.
 * 3 bileşen: velocity (0–40), listing trend (0–30), price drop (0–30).
 *
 * Pure function — side-effect yok, rand() yok, AI yok.
 */
class DemandScoreService
{
    /**
     * Demand skoru hesapla.
     *
     * @param array{avg_days_on_market: float|null, trend_ratio: float|null, drop_ratio: float|null} $marketData
     *
     * @return int 0–100 arası demand skoru
     */
    public function calculate(array $marketData): int
    {
        $velocity = $this->velocityScore($marketData['avg_days_on_market'] ?? null);
        $trend = $this->listingTrendScore($marketData['trend_ratio'] ?? null);
        $priceDrop = $this->priceDropScore($marketData['drop_ratio'] ?? null);

        return (int) min(100, max(0, $velocity + $trend + $priceDrop));
    }

    /**
     * Demand label.
     */
    public function label(int $demandScore): string
    {
        return match (true) {
            $demandScore >= 75 => 'HOT',
            $demandScore >= 50 => 'ACTIVE',
            $demandScore >= 25 => 'SLOW',
            default            => 'WEAK',
        };
    }

    /**
     * Explainable reason string.
     */
    public function reason(array $marketData): string
    {
        $parts = [];

        // Velocity
        $avgDays = $marketData['avg_days_on_market'] ?? null;
        if ($avgDays !== null) {
            $parts[] = "avg " . (int) round($avgDays) . " days";
        } else {
            $parts[] = "no velocity data";
        }

        // Trend
        $trendRatio = $marketData['trend_ratio'] ?? null;
        if ($trendRatio !== null) {
            $parts[] = match (true) {
                $trendRatio > 1.2  => 'rising listings',
                $trendRatio >= 0.9 => 'stable listings',
                $trendRatio >= 0.7 => 'declining listings',
                default            => 'sharp decline',
            };
        } else {
            $parts[] = "no trend data";
        }

        // Price drop
        $dropRatio = $marketData['drop_ratio'] ?? null;
        if ($dropRatio !== null) {
            $parts[] = match (true) {
                $dropRatio < 0.2  => 'low price drops',
                $dropRatio <= 0.4 => 'moderate price drops',
                $dropRatio <= 0.6 => 'high price drops',
                default           => 'heavy price drops',
            };
        } else {
            $parts[] = "no price drop data";
        }

        return implode(', ', $parts);
    }

    /**
     * Velocity Score (0–40).
     *
     * İlanların ortalama kaç günde kapandığı.
     */
    private function velocityScore(?float $avgDays): int
    {
        if ($avgDays === null) {
            return 0;
        }

        return match (true) {
            $avgDays < 15  => 40,
            $avgDays <= 30 => 30,
            $avgDays <= 60 => 20,
            default        => 10,
        };
    }

    /**
     * Listing Trend Score (0–30).
     *
     * trend_ratio = current_30_days_count / previous_30_days_count
     */
    private function listingTrendScore(?float $trendRatio): int
    {
        if ($trendRatio === null) {
            return 0;
        }

        return match (true) {
            $trendRatio > 1.2  => 30,
            $trendRatio >= 0.9 => 20,
            $trendRatio >= 0.7 => 10,
            default            => 0,
        };
    }

    /**
     * Price Drop Score (0–30).
     *
     * drop_ratio = listings_with_price_drop / total_listings
     * Düşük oran = güçlü talep (fiyat düşürmeye gerek yok).
     */
    private function priceDropScore(?float $dropRatio): int
    {
        if ($dropRatio === null) {
            return 0;
        }

        return match (true) {
            $dropRatio < 0.2  => 30,
            $dropRatio <= 0.4 => 20,
            $dropRatio <= 0.6 => 10,
            default           => 0,
        };
    }
}
