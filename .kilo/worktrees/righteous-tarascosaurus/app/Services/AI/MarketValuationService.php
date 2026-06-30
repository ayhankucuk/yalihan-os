<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AI Market Valuation Engine Service
 *
 * CQRS Read-Model tabanlı, dış kaynak verileriyle (market_listings)
 * otomatik gayrimenkul değerlemesi yapan zeka motoru.
 */
class MarketValuationService
{
    /**
     * Parse query and orchestrate valuation
     * Example: "Bodrum Bitez 1000m2 tarla"
     */
    public function evaluateQuery(array $params): array
    {
        $il = $params['il'] ?? '';
        $ilce = $params['ilce'] ?? '';
        $mahalle = $params['mahalle'] ?? '';
        $assetType = $params['asset_type'] ?? 'Konut';
        $targetM2 = $params['m2'] ?? 0;

        if (!$targetM2) {
            throw new \Exception("M2 bilgisi zorunludur.");
        }

        // 1. Find Comparables
        $comparables = $this->findComparables($il, $ilce, $mahalle, $targetM2);

        if ($comparables->count() < 3) {
            return [
                'is_success' => false,
                'message' => 'Yeterli emsal veri bulunamadı (En az 3 kayıt gerekli).'
            ];
        }

        // 2. Filter Outliers (IQR Method)
        $filteredComparables = $this->filterOutliers($comparables);
        $comparableCount = $filteredComparables->count();

        // 3. && 4. Median Price & Value Estimation
        $medianM2Price = $this->calculateMedian($filteredComparables);
        $estimatedValue = $medianM2Price * $targetM2;

        // 5. Market Range
        $priceRangeLow = $estimatedValue * 0.92;
        $priceRangeHigh = $estimatedValue * 1.08;

        // 6. Market Trend
        $trendPercent = $this->calculateTrend($il, $ilce, $mahalle, $targetM2);

        // 7. Liquidity Score
        $liquidityScore = $this->calculateLiquidity($filteredComparables);

        // 8. Confidence Score
        $confidenceScore = $this->calculateConfidence($comparableCount, $filteredComparables);

        // Define Report Data
        $reportData = [
            'location_il' => $il,
            'location_ilce' => $ilce,
            'location_mahalle' => $mahalle,
            'asset_type' => $assetType,
            'm2' => $targetM2,
            'median_m2_price' => $medianM2Price,
            'estimated_value' => $estimatedValue,
            'price_range_low' => $priceRangeLow,
            'price_range_high' => $priceRangeHigh,
            'market_trend' => $trendPercent,
            'liquidity_score' => $liquidityScore,
            'confidence_score' => $confidenceScore,
            'comparable_count' => $comparableCount,
            'created_at' => now(),
            'updated_at' => now()
        ];

        // CQRS Write to Projection Table
        $reportId = DB::table('market_valuation_reports')->insertGetId($reportData);
        $reportData['id'] = $reportId;

        return [
            'is_success' => true,
            'data' => $reportData
        ];
    }

    /**
     * STEP 1 - Location Match & Size Filtering
     */
    protected function findComparables($il, $ilce, $mahalle, $m2)
    {
        $minM2 = $m2 * 0.8;
        $maxM2 = $m2 * 1.2;

        return DB::table('market_listings') // From yalihan_market
            ->where('is_active', 1) // context7-ignore
            ->where('location_il', $il)
            ->when($ilce, fn($q) => $q->where('location_ilce', $ilce))
            ->when($mahalle, fn($q) => $q->where('location_mahalle', $mahalle))
            ->whereBetween('m2_brut', [(int)$minM2, (int)$maxM2])
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->get();
    }

    /**
     * STEP 2 - IQR Outlier Filtering
     */
    protected function filterOutliers($listings)
    {
        $unitPrices = $listings->map(function ($listing) {
            return $listing->price / max(1, $listing->m2_brut);
        })->sort()->values();

        $count = $unitPrices->count();
        if ($count < 4) return $listings; // IQR needs sufficient data

        $q1Index = floor($count * 0.25);
        $q3Index = floor($count * 0.75);

        $q1 = $unitPrices[$q1Index];
        $q3 = $unitPrices[$q3Index];

        $iqr = $q3 - $q1;
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);

        return $listings->filter(function ($listing) use ($lowerBound, $upperBound) {
            $unitPrice = $listing->price / max(1, $listing->m2_brut);
            return $unitPrice >= $lowerBound && $unitPrice <= $upperBound;
        })->values();
    }

    /**
     * STEP 3 - Median Price Calculation
     */
    protected function calculateMedian($listings): float
    {
        $unitPrices = $listings->map(function ($listing) {
            return $listing->price / max(1, $listing->m2_brut);
        })->sort()->values();

        $count = $unitPrices->count();
        if ($count === 0) return 0;

        $middle = floor($count / 2);

        if ($count % 2 == 0) {
            return ($unitPrices[$middle - 1] + $unitPrices[$middle]) / 2;
        }

        return $unitPrices[$middle];
    }

    /**
     * STEP 6 - Market Trend (Last 30 vs Last 90 days)
     */
    protected function calculateTrend($il, $ilce, $mahalle, $targetM2): float
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $ninetyDaysAgo = $now->copy()->subDays(90);

        $baseQuery = DB::table('market_listings')
            ->where('location_il', $il)
            ->when($ilce, fn($q) => $q->where('location_ilce', $ilce))
            ->when($mahalle, fn($q) => $q->where('location_mahalle', $mahalle))
            ->whereBetween('m2_brut', [(int)($targetM2 * 0.8), (int)($targetM2 * 1.2)])
            ->whereNotNull('price')
            ->where('price', '>', 0);

        $recentComparables = (clone $baseQuery)
            ->where('ilan_tarihi', '>=', $thirtyDaysAgo->toDateString())
            ->get();

        $olderComparables = (clone $baseQuery)
            ->where('ilan_tarihi', '>=', $ninetyDaysAgo->toDateString())
            ->where('ilan_tarihi', '<', $thirtyDaysAgo->toDateString())
            ->get();

        if ($recentComparables->isEmpty() || $olderComparables->isEmpty()) {
            return 0.0; // Not enough data
        }

        $recentMedian = $this->calculateMedian($this->filterOutliers($recentComparables));
        $olderMedian = $this->calculateMedian($this->filterOutliers($olderComparables));

        if ($olderMedian == 0) return 0.0;

        $difference = (($recentMedian - $olderMedian) / $olderMedian) * 100;

        return round($difference, 2);
    }

    /**
     * STEP 7 - Liquidity Score Calculation
     */
    protected function calculateLiquidity($listings): string
    {
        $totalDays = 0;
        $validListings = 0;
        $now = now();

        foreach ($listings as $listing) {
            if (!empty($listing->ilan_tarihi)) {
                $created = Carbon::parse($listing->ilan_tarihi);
                $totalDays += $created->diffInDays($now);
                $validListings++;
            }
        }

        if ($validListings === 0) return 'UNKNOWN';

        $avgDays = $totalDays / $validListings;

        if ($avgDays < 30) return 'HIGH';
        if ($avgDays < 90) return 'MEDIUM';
        return 'LOW';
    }

    /**
     * STEP 8 - Confidence Score
     */
    protected function calculateConfidence(int $comparableCount, $filteredListings): int
    {
        $score = 50; // Starting base

        // Volume factor
        if ($comparableCount > 50) $score += 30;
        elseif ($comparableCount > 20) $score += 20;
        elseif ($comparableCount >= 5) $score += 10;
        else $score -= 20;

        // Variance factor
        if ($filteredListings->count() > 3) {
            $unitPrices = $filteredListings->map(fn($l) => $l->price / max(1, $l->m2_brut));
            $mean = $unitPrices->average();

            // Calc variance
            $sumSquares = 0;
            foreach ($unitPrices as $price) {
                $sumSquares += pow($price - $mean, 2);
            }
            $variance = $sumSquares / $filteredListings->count();
            $stdDev = sqrt($variance);

            $cv = $mean > 0 ? ($stdDev / $mean) : 0; // Coefficient of Variation

            // Lower variance = higher confidence
            if ($cv < 0.1) $score += 20;
            elseif ($cv < 0.2) $score += 10;
            elseif ($cv > 0.4) $score -= 15;
        }

        return min(100, max(0, (int) round($score)));
    }
}
