<?php

namespace App\Services\Analysis;

use App\Models\Ilan;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Market Analysis Service
 *
 * Phase 7.1: AI Pricing Optimization - Market Trend Analysis
 * Analyzes regional price trends using real listing data.
 *
 * Context7: Uses 'fiyat' (Turkish), 'market_data' (JSON), 'ai_price_recommendation' (decimal)
 *
 * Deterministic Refactor: 28 Mar 2026
 * - Removed all rand() calls
 * - Replaced simulated data with real DB queries
 * - Confidence is now formula-based (deviation + sample size)
 */
class MarketAnalysisService
{
    use GuardsAgentWrites;

    /**
     * Analyze market trend for a listing
     *
     * @param int $ilanId
     * @return array ['trend' => string, 'confidence' => int, 'recommended_price' => float, 'market_data' => array]
     */
    public function analyzeMarketTrend(int $ilanId): array
    {
        $ilan = Ilan::with(['il', 'ilce', 'anaKategori'])->findOrFail($ilanId);

        $currentPrice = (float) $ilan->fiyat;

        $marketData = $this->getHistoricalMarketData(
            (int) $ilan->il_id,
            (int) $ilan->ilce_id,
            (int) $ilan->ana_kategori_id
        );

        $trend = $this->calculateTrend($marketData);
        $confidence = $this->calculateConfidence($marketData, $currentPrice);
        $recommendedPrice = $this->calculateRecommendedPrice($marketData, $currentPrice);

        Log::info('Market analysis completed', [
            'ilan_id' => $ilanId,
            'current_price' => $currentPrice,
            'recommended_price' => $recommendedPrice,
            'confidence' => $confidence,
            'trend' => $trend,
            'sample_months' => count($marketData),
        ]);

        return [
            'trend' => $trend,
            'confidence' => $confidence,
            'recommended_price' => $recommendedPrice,
            'market_data' => $marketData,
            'analysis_date' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get real historical market data from listing records.
     *
     * Queries ilanlar table grouped by month for the same il/ilce/kategori.
     * Returns empty array if no data — never fabricates values.
     */
    private function getHistoricalMarketData(int $ilId, int $ilceId, int $kategoriId): array
    {
        $months = [];

        for ($i = 6; $i >= 1; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $stats = DB::table('ilanlar')
                ->where('il_id', $ilId)
                ->where('ilce_id', $ilceId)
                ->where('ana_kategori_id', $kategoriId)
                ->where('fiyat', '>', 0)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->selectRaw('AVG(fiyat) as avg_price, COUNT(*) as listings_count')
                ->first();

            if ($stats && $stats->listings_count > 0) {
                $months[] = [
                    'month' => $monthStart->format('Y-m'),
                    'avg_price' => (int) round($stats->avg_price),
                    'listings_count' => (int) $stats->listings_count,
                ];
            }
        }

        return $months;
    }

    /**
     * Calculate market trend from historical data.
     */
    private function calculateTrend(array $marketData): string
    {
        if (count($marketData) < 2) {
            return 'stable';
        }

        $firstPrice = $marketData[0]['avg_price'];
        $lastPrice = end($marketData)['avg_price'];

        if ($firstPrice <= 0) {
            return 'stable';
        }

        $change = (($lastPrice - $firstPrice) / $firstPrice) * 100;

        if ($change > 5) {
            return 'rising';
        } elseif ($change < -5) {
            return 'falling';
        }

        return 'stable';
    }

    /**
     * Calculate confidence score (0-100) deterministically.
     *
     * Formula: deviation_score (0-70) + sample_bonus (0-30)
     * - deviation_score = max(0, 70 - (deviation * 300))
     * - sample_bonus = min(30, total_listings * 2)
     */
    private function calculateConfidence(array $marketData, float $currentPrice): int
    {
        if (empty($marketData)) {
            return 20;
        }

        $avgPrice = collect($marketData)->avg('avg_price');

        if ($avgPrice <= 0) {
            return 20;
        }

        $deviation = abs(($currentPrice - $avgPrice) / $avgPrice);
        $sampleCount = collect($marketData)->sum('listings_count');

        $deviationScore = max(0, 70 - ($deviation * 300));
        $sampleBonus = min(30, $sampleCount * 2);

        return (int) round(min(100, max(20, $deviationScore + $sampleBonus)));
    }

    /**
     * Calculate recommended price based on market average and trend.
     */
    private function calculateRecommendedPrice(array $marketData, float $currentPrice): float
    {
        if (empty($marketData)) {
            return $currentPrice;
        }

        $avgPrice = collect($marketData)->avg('avg_price');
        $trend = $this->calculateTrend($marketData);

        $adjustment = match($trend) {
            'rising' => 1.03,
            'falling' => 0.97,
            default => 1.0,
        };

        $recommended = $avgPrice * $adjustment;

        // Don't recommend more than 15% change from current price
        $maxChange = $currentPrice * 0.15;
        if (abs($recommended - $currentPrice) > $maxChange) {
            $recommended = $currentPrice + ($recommended > $currentPrice ? $maxChange : -$maxChange);
        }

        return round($recommended, -3);
    }

    /**
     * Update listing with deterministic recommendation.
     *
     * Only writes if confidence >= 30 (minimum viable data).
     */
    public function updateListingRecommendation(int $ilanId): bool
    {
        $this->blockAgentWrite(__FUNCTION__);

        $analysis = $this->analyzeMarketTrend($ilanId);

        if ($analysis['confidence'] < 30) {
            Log::info('Market recommendation skipped: insufficient confidence', [
                'ilan_id' => $ilanId,
                'confidence' => $analysis['confidence'],
            ]);
            return false;
        }

        return DB::table('ilanlar')
            ->where('id', $ilanId)
            ->update([
                'ai_price_recommendation' => $analysis['recommended_price'],
                'market_confidence_score' => $analysis['confidence'],
                'market_data' => json_encode($analysis['market_data']),
                'updated_at' => now(),
            ]);
    }
}
