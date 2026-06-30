<?php

namespace App\Services\AIDeal;

use App\Models\Ilan;
use App\Models\Projections\MarketTrendProjection;
use App\Services\AI\OpportunityScoringService;
use App\Services\AIMatch\BuyerMatchScoringService;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Deal Scoring Service
 * Implements the multi-dimensional weighted scoring algorithm for listings.
 */
class DealScoringService
{
    protected ListingVelocityService $velocityService;
    protected MarketHeatService $marketHeatService;
    protected OpportunityScoringService $opportunityScoring;
    protected BuyerMatchScoringService $buyerMatchScoring;

    public function __construct(
        ListingVelocityService $velocityService,
        MarketHeatService $marketHeatService,
        OpportunityScoringService $opportunityScoring,
        BuyerMatchScoringService $buyerMatchScoring
    ) {
        $this->velocityService = $velocityService;
        $this->marketHeatService = $marketHeatService;
        $this->opportunityScoring = $opportunityScoring;
        $this->buyerMatchScoring = $buyerMatchScoring;
    }

    /**
     * Calculate all scores for a listing.
     */
    public function calculateAll(Ilan $ilan): array
    {
        // 1. Market Heat (0-100)
        $marketHeat = $this->marketHeatService->getHeatScore($ilan);

        // 2. Listing Velocity (0-100)
        $velocityProjection = $this->velocityService->syncVelocity($ilan);
        $velocityScore = $velocityProjection->activity_score;

        // 3. Price Accuracy (0-100)
        $priceAccuracy = $this->calculatePriceAccuracy($ilan);

        // 4. Buyer Interest (0-100)
        // Simulation/Placeholder for this phase: tied to velocity + buyer matches
        $buyerInterest = (int) ($velocityScore * 0.7 + $marketHeat * 0.3);

        // 5. Aggregate: Sale Probability (0-100)
        $saleProbability = $this->calculateSaleProbability($marketHeat, $velocityScore, $priceAccuracy);

        // 6. Aggregate: Deal Quality (0-100)
        $dealQuality = (int) (($saleProbability * 0.4) + ($priceAccuracy * 0.3) + ($marketHeat * 0.3));

        // 7. Estimated Days to Sell
        $daysToSell = $this->estimateDaysToSell($saleProbability, $marketHeat);

        return [
            'sale_probability' => $saleProbability,
            'estimated_days_to_sell' => $daysToSell,
            'price_accuracy_score' => $priceAccuracy,
            'market_heat_score' => $marketHeat,
            'buyer_interest_score' => $buyerInterest,
            'deal_quality_score' => $dealQuality,
            'velocity_score' => $velocityScore,
        ];
    }

    /**
     * Calculate price accuracy score (0-100).
     */
    protected function calculatePriceAccuracy(Ilan $ilan): int
    {
        $projection = MarketTrendProjection::where('city', $ilan->il)
            ->where('district', $ilan->ilce)
            ->where('property_type', $ilan->kategori)
            ->first();

        if (!$projection || $projection->avg_price <= 0) {
            return 70; // High uncertainty fallback
        }

        $price = (float) $ilan->fiyat;
        $avgPrice = (float) $projection->avg_price;

        $diff = abs($price - $avgPrice) / $avgPrice;

        // Perfect price = within 5% of market avg
        if ($diff <= 0.05) return 95;
        if ($diff <= 0.15) return 85;
        if ($diff <= 0.30) return 60;

        return 40;
    }

    /**
     * Sale Probability Algorithm (0-100).
     */
    protected function calculateSaleProbability(int $heat, int $velocity, int $priceAcc): int
    {
        // Weights: Price(50%), Velocity(30%), Heat(20%)
        return (int) (($priceAcc * 0.5) + ($velocity * 0.3) + ($heat * 0.2));
    }

    /**
     * Estimation Logic (Days).
     */
    protected function estimateDaysToSell(int $probability, int $heat): int
    {
        // Base: 90 days
        // Scale: 15-180 days

        $baseDays = 90;

        if ($probability > 80 && $heat > 80) return 15;
        if ($probability > 70) return 30;
        if ($probability > 50) return 60;
        if ($probability < 30) return 150;

        return $baseDays;
    }
}
