<?php

namespace App\Services\AIDeal;

use App\Models\Ilan;
use App\Models\Projections\ListingVelocityProjection;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Listing Velocity Service
 * Calculates activity scores based on engagement metrics (Views, Favorites, Inquiries).
 */
class ListingVelocityService
{
    /**
     * Calculate and sync velocity for a listing.
     */
    public function syncVelocity(Ilan $ilan): ListingVelocityProjection
    {
        $projection = ListingVelocityProjection::firstOrCreate(
            ['listing_id' => $ilan->id]
        );

        // Simulation: In a real system, these would come from Analytics/Logs
        // For this phase, we use existing projection data or initial state
        $score = $this->calculateActivityScore($projection);

        $projection->update([
            'activity_score' => $score,
            'last_activity_at' => now(),
        ]);

        return $projection;
    }

    /**
     * Calculate weighted activity score (0-100).
     */
    private function calculateActivityScore(ListingVelocityProjection $projection): int
    {
        $viewWeight = 0.1;
        $favoriteWeight = 0.3;
        $inquiryWeight = 0.5;
        $shareWeight = 0.1;

        $rawScore = ($projection->view_count * $viewWeight) +
                    ($projection->favorite_count * $favoriteWeight) +
                    ($projection->inquiry_count * $inquiryWeight) +
                    ($projection->share_count * $shareWeight);

        // Normalization (Cap at 100)
        return (int) min(100, $rawScore);
    }
}
