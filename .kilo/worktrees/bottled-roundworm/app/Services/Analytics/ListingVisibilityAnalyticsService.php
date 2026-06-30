<?php

namespace App\Services\Analytics;

use App\Models\Ilan;
use App\Models\IlanGoruntulenmeGunluk;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Listing Visibility Analytics Service
 *
 * PHASE 19.3: Visibility Metrics
 * Bridges Ranking Engine scores with real-world traffic data.
 */
class ListingVisibilityAnalyticsService
{
    /**
     * Get Visibility Efficiency Score
     * Formula (SSOT): goruntulenme_gunluk / visibility_score
     */
    public function getEfficiencyScore(int $ilanId, int $days = 30): array
    {
        $ilan = Ilan::findOrFail($ilanId);
        $score = $ilan->visibility_score ?: 1;

        $views = IlanGoruntulenmeGunluk::where('ilan_id', $ilanId)
            ->where('tarih', '>=', now()->subDays($days))
            ->sum('adet');

        $dailyAvg = $views / ($days ?: 1);
        $efficiency = $dailyAvg / $score;

        return [
            'ilan_id' => $ilanId,
            'total_views' => (int) $views,
            'daily_avg' => round($dailyAvg, 2),
            'visibility_score' => (int) $score,
            'visibility_efficiency' => round($efficiency, 6),
            'rating' => $this->getEfficiencyRating($efficiency, $score, $dailyAvg),
            'period_days' => $days,
            'is_anomaly' => ($score < 2000 && $dailyAvg > 10),
            'low_performance_alert' => ($score > 8000 && $dailyAvg < 5) // High score but < 5 views/day
        ];
    }

    /**
     * Get Visibility History (Daily Trend)
     */
    public function getVisibilityTrend(int $ilanId, int $days = 30): array
    {
        $trend = IlanGoruntulenmeGunluk::where('ilan_id', $ilanId)
            ->where('tarih', '>=', now()->subDays($days))
            ->orderBy('tarih') // context7-ignore
            ->get(['tarih', 'adet'])
            ->map(fn($item) => [
                'date' => is_string($item->tarih) ? substr($item->tarih, 0, 10) : $item->tarih->format('Y-m-d'),
                'views' => $item->adet
            ]);

        return [
            'ilan_id' => $ilanId,
            'period' => $days . ' days',
            'data' => $trend
        ];
    }

    /**
     * Traffic Gain Prediction
     * Predicted Views = Current Efficiency * (10000 / Current Score) * Current Views
     */
    public function getTrafficGainPrediction(int $ilanId): array
    {
        $ilan = Ilan::findOrFail($ilanId);
        $currentScore = $ilan->visibility_score ?: 1;

        $currentViewsAvg = IlanGoruntulenmeGunluk::where('ilan_id', $ilanId)
            ->where('tarih', '>=', now()->subDays(30))
            ->avg('adet') ?: 0;

        $potentialFactor = 10000 / $currentScore;
        $predictedDailyViews = $currentViewsAvg * $potentialFactor;

        return [
            'ilan_id' => $ilanId,
            'current_daily_avg' => round($currentViewsAvg, 2),
            'potential_daily_avg' => round($predictedDailyViews, 2),
            'expected_increase' => round($predictedDailyViews - $currentViewsAvg, 2),
            'boost_factor' => round($potentialFactor, 2)
        ];
    }

    /**
     * Get Efficiency Rating Label
     */
    protected function getEfficiencyRating(float $efficiency, int $score, float $dailyAvg): string
    {
        if ($score < 2000 && $dailyAvg > 10) return 'Organic Anomaly';
        if ($efficiency > 0.01) return 'Exceptional';
        if ($efficiency > 0.005) return 'Good';
        if ($efficiency > 0.002) return 'Moderate';
        return 'Low Impact';
    }
}
