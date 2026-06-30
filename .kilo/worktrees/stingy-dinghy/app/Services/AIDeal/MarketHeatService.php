<?php

namespace App\Services\AIDeal;

use App\Models\Ilan;
use App\Models\Projections\MarketTrendProjection;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Market Heat Service
 * Determines the market "temperature" for a specific location and property type.
 */
class MarketHeatService
{
    /**
     * Get market heat score for a listing's specific context.
     */
    public function getHeatScore(Ilan $ilan): int
    {
        $projection = MarketTrendProjection::where('city', $ilan->il)
            ->where('district', $ilan->ilce)
            ->where('property_type', $ilan->kategori)
            ->first();

        if (!$projection) {
            // Fallback: District heat if specific property type trend is missing
            $projection = MarketTrendProjection::where('city', $ilan->il)
                ->where('district', $ilan->ilce)
                ->first();
        }

        if (!$projection) {
            return 50; // Neutral fallback
        }

        return $this->calculateHeatScore($projection);
    }

    /**
     * Calculate heat score based on price changes and demand index.
     */
    private function calculateHeatScore(MarketTrendProjection $projection): int
    {
        // Demand Index: 60%
        // Price Change (30d): 40%

        $priceMomentum = (float) $projection->price_change_30d;
        $demandIndex = (int) $projection->demand_index;

        // price_change_30d > 5% is very hot (+20 score)
        $momentumBonus = $priceMomentum > 5 ? 20 : ($priceMomentum > 2 ? 10 : 0);

        $score = ($demandIndex * 0.6) + $momentumBonus;

        return (int) min(100, max(0, $score));
    }
}
