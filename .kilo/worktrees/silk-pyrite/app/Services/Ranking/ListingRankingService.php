<?php

namespace App\Services\Ranking;

use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * Listing Ranking Service (DAP Protocol v2)
 *
 * SSOT for Listing Visibility Score.
 * Range: 0 - 10,000 (Deterministic)
 *
 * Formula:
 * Final Score = (Quality * 0.4) + (SEO * 0.2) + (Engagement * 0.2) + (Recency * 0.2)
 */
class ListingRankingService
{
    const MAX_SCORE = 10000;
    
    /**
     * Calculate and persist the final visibility score.
     * 
     * @param Ilan $ilan
     * @return int
     */
    public function recalculateAndPersist(Ilan $ilan): int
    {
        $score = $this->calculateScore($ilan);
        
        $ilan->visibility_score = $score;
        $ilan->saveQuietly();
        
        return $score;
    }

    /**
     * Calculate and return the final visibility score.
     * Does NOT persist. Persistence is the job of the caller (Job/Command).
     *
     * @param Ilan $ilan
     * @return int 0-10000
     */
    public function calculateScore(Ilan $ilan): int
    {
        // 0. Base Constraint: Passive listings are 0
        if (!$ilan->yayindami) {
            return 0;
        }

        // 1. Calculate Component Scores (0-10000 basis for internal precision)
        $qualityScore = $this->calculateQualityScore($ilan);
        $seoScore = $this->calculateSeoScore($ilan);
        $engagementScore = $this->calculateEngagementScore($ilan);
        $recencyScore = $this->calculateRecencyScore($ilan);

        // 2. Apply Weighted Formula
        // (Quality * 0.4) + (SEO * 0.2) + (Engagement * 0.2) + (Recency * 0.2)
        $finalScore = (
            ($qualityScore * 0.4) +
            ($seoScore * 0.2) +
            ($engagementScore * 0.2) +
            ($recencyScore * 0.2)
        );

        // 3. Clamp and Cast
        return $this->clamp((int) round($finalScore), 0, self::MAX_SCORE);
    }

    /**
     * Quality Score (40% Weight)
     * - Completeness (Attributes, Location, Price)
     * - Media (Photos, Video)
     * - AI Status (Description)
     */
    public function calculateQualityScore(Ilan $ilan): int
    {
        $score = 0;

        // Completeness (Max 4000)
        if (!empty($ilan->aciklama) && strlen($ilan->aciklama) > 100) $score += 1000;
        if ($ilan->fiyat > 0) $score += 1000;
        if ($ilan->ilce_id && $ilan->mahalle_id) $score += 1000;
        if ($ilan->lat && $ilan->lng) $score += 1000;

        // Media (Max 4000)
        $photoCount = $ilan->fotograflar_count ?? $ilan->fotograflar()->count();
        $score += min(3000, $photoCount * 300); // 10 photos = 3000
        if ($ilan->youtube_video_url || $ilan->sanal_tur_url) $score += 1000;

        // AI Bonus (Max 2000)
        // Check if AI description exists (using legacy relation or field check)
        $hasAi = $ilan->metinler()->where('yapay_zeka_durumu', true)->exists();
        if ($hasAi) $score += 2000;

        return min(10000, $score);
    }

    /**
     * SEO Score (20% Weight)
     * - Title Length (Ideal: 30-60 chars)
     * - Description Length (Ideal: 120-160 chars)
     * - Slug Sanity
     */
    public function calculateSeoScore(Ilan $ilan): int
    {
        $score = 0;
        $titleLen = mb_strlen($ilan->baslik ?? '');
        $descLen = mb_strlen(strip_tags($ilan->aciklama ?? ''));

        // Title (Max 4000)
        if ($titleLen >= 20 && $titleLen <= 70) $score += 4000;
        elseif ($titleLen > 5) $score += 1000;

        // Description (Max 4000)
        if ($descLen >= 100 && $descLen <= 300) $score += 4000;
        elseif ($descLen > 50) $score += 1000;

        // Slug (Max 2000)
        if (!empty($ilan->slug) && !str_contains($ilan->slug, 'temp-')) {
            $score += 2000;
        }

        return min(10000, $score);
    }

    /**
     * Engagement Score (20% Weight)
     * - Views (Logarithmic)
     * - Favorites (Weighted)
     */
    public function calculateEngagementScore(Ilan $ilan): int
    {
        $score = 0;
        $views = $ilan->goruntulenme ?? 0;

        // Views (Max 7000)
        // log10(10) = 1 * 1500 = 1500
        // log10(100) = 2 * 1500 = 3000
        // log10(1000) = 3 * 1500 = 4500
        // log10(10000) = 4 * 1500 = 6000
        if ($views > 0) {
            $score += min(7000, log10($views + 1) * 1750);
        }

        // Favorites (Max 3000)
        // Assuming favorite_count attribute exists or relationship count
        $favorites = $ilan->favorite_count ?? 0; // optimized attribute or relation
        $score += min(3000, $favorites * 300); // 10 favs = 3000

        return min(10000, (int) $score);
    }

    /**
     * Recency Score (20% Weight)
     * - Decay over 30 days
     */
    public function calculateRecencyScore(Ilan $ilan): int
    {
        if (!$ilan->updated_at) return 0;

        $days = now()->diffInDays($ilan->updated_at);

        // Decay logic: 10000 start, -333 per day -> 0 at 30 days
        $score = 10000 - ($days * 333);

        return max(0, $score);
    }

    /**
     * Clamp helper
     */
    protected function clamp(int $val, int $min, int $max): int
    {
        return max($min, min($max, $val));
    }
}
