<?php

namespace App\Services\Ranking;

use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * Visibility Score Service (DAP Protocol Phase 19)
 *
 * SSOT for Deterministic Visibility Scoring.
 * Range: 0 - 10,000
 *
 * Factors:
 * 1. Completeness (40%)
 * 2. UPS / Wizard Quality (30%)
 * 3. Market / Engagement (20%)
 * 4. Boosters (10%)
 */
class VisibilityScoreService
{
    public const MAX_SCORE = 10000;

    /**
     * Calculate and return the score without saving.
     */
    public function calculate(Ilan $ilan): int
    {
        if (!$ilan->yayindami) {
            return 0;
        }

        $completeness = $this->calculateCompleteness($ilan); // Max 4000
        $quality      = $this->calculateQuality($ilan);      // Max 3000
        $engagement   = $this->calculateEngagement($ilan);   // Max 2000
        $boosters     = $this->calculateBoosters($ilan);     // Max 1000

        $total = $completeness + $quality + $engagement + $boosters;

        return min(self::MAX_SCORE, max(0, $total));
    }

    /**
     * Calculate and persist the score.
     * Uses saveQuietly() to avoid observer loops.
     */
    public function updateScore(Ilan $ilan): int
    {
        $score = $this->calculate($ilan);

        if ($ilan->visibility_score !== $score) {
            $ilan->visibility_score = $score;
            $ilan->saveQuietly();
        }

        return $score;
    }

    /**
     * 1. Completeness (Max 4000)
     * - Photos, Description, Price, Location, Features
     */
    private function calculateCompleteness(Ilan $ilan): int
    {
        $score = 0;

        // Photos (Max 1500)
        $photoCount = $ilan->fotograflar_count ?? $ilan->fotograflar()->count();
        $score += min(1500, $photoCount * 150);

        // Description (Max 500)
        if (!empty($ilan->aciklama) && strlen($ilan->aciklama) > 200) {
            $score += 500;
        }

        // Price & Currency (Max 500)
        if ($ilan->fiyat > 0 && !empty($ilan->para_birimi)) {
            $score += 500;
        }

        // Location (Lat/Lng) (Max 1000)
        if ($ilan->lat && $ilan->lng) {
            $score += 1000;
        }

        // Features (Max 500)
        // Assuming rudimentary check if relation loaded or count
        // $score += 500; // Placeholder until feature count is standard

        return min(4000, $score);
    }

    /**
     * 2. UPS / Wizard Quality (Max 3000)
     * - AI Quality Score, Structured Data, AI Metadata
     */
    private function calculateQuality(Ilan $ilan): int
    {
        $score = 0;

        // Existing AI Quality Score (Float 0-10 or 0-100?)
        // Assuming 0-10 scale based on typical AI scores, scale to 1000
        $quality = $ilan->quality_score ?? 0;
        if ($quality > 0) {
            // Normalize to 1000. If quote is 9.5 -> 950
            $score += min(1000, $quality * 100);
        }

        // AI Metadata Presence (Max 1000)
        if (!empty($ilan->ai_metadata)) {
            $score += 1000;
        }

        // Structured Data (Max 1000)
        if (!empty($ilan->structured_data)) {
            $score += 1000;
        }

        return min(3000, $score);
    }

    /**
     * 3. Market / Engagement (Max 2000)
     * - Recency, Views, Favorites
     */
    private function calculateEngagement(Ilan $ilan): int
    {
        $score = 0;

        // Recency (Max 1000) - Decay over 30 days
        if ($ilan->updated_at) {
            $days = now()->diffInDays($ilan->updated_at);
            $recency = max(0, 1000 - ($days * 33));
            $score += $recency;
        }

        // Views (Max 500) - Logarithmic
        $views = $ilan->goruntulenme ?? 0;
        if ($views > 0) {
            $score += min(500, log($views + 1) * 100);
        }

        // Favorites (Max 500)
        $favorites = $ilan->favorite_count ?? 0;
        if ($favorites > 0) {
            $score += min(500, $favorites * 100);
        }

        return min(2000, $score);
    }

    /**
     * 4. Boosters (Max 1000)
     * - Featured, Verified Advisor
     */
    private function calculateBoosters(Ilan $ilan): int
    {
        $score = 0;

        // Featured (Max 500)
        if ($ilan->one_cikan) {
            $score += 500;
        }

        // Advisor Assigned (Max 500)
        if ($ilan->danisman_id) {
            $score += 500;
        }

        return min(1000, $score);
    }
}
