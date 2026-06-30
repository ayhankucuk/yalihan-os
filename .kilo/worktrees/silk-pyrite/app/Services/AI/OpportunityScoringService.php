<?php

namespace App\Services\AI;

use App\Models\Projections\ListingSearchProjection;

class OpportunityScoringService
{
    /**
     * Calculate opportunity score (0-100) for a given projection.
     */
    public function calculateScore(ListingSearchProjection $projection): array
    {
        $score = 0;
        $reasons = [];

        // 1. Price Advantage (Placeholder logic: compare to a fictitious median)
        // In production, this would query average prices for the district
        $medianPrice = 15000000;
        if ($projection->price < $medianPrice * 0.8) {
            $score += 40;
            $reasons[] = "Market ortalamasının %20 altında";
        } elseif ($projection->price < $medianPrice * 0.9) {
            $score += 20;
            $reasons[] = "Cazip fiyat avantajı";
        }

        // 2. Featured / Quality
        if (str_contains(strtolower($projection->property_type), 'villa')) {
            $score += 10;
            $reasons[] = "Yüksek yatırım potansiyeli (Villa)";
        }

        // 3. Features richness
        $featureCount = count($projection->features ?? []);
        if ($featureCount > 5) {
            $score += 20;
            $reasons[] = "Zengin donanım ve özellikler";
        }

        // Normalize score
        $finalScore = min(100, $score);

        return [
            'score' => $finalScore,
            'reason' => $reasons[0] ?? 'Standart ilan',
            'all_reasons' => $reasons
        ];
    }
}
