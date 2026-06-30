<?php

namespace App\Services\Visibility;

use App\Models\Ilan;

class ListingRankingService
{
    /**
     * Calculate total visibility score (0-10000)
     */
    public function calculateVisibilityScore(Ilan $ilan): int
    {
        $qualityScore = $this->calculateQualityScore($ilan);
        $seoScore = $this->calculateSeoScore($ilan);
        $recencyScore = $this->calculateRecencyScore($ilan);

        // Formula: 40% Quality + 30% SEO + 30% Recency
        // This is a simplified formula for Phase 19
        $totalScore = ($qualityScore * 0.4) + ($seoScore * 0.3) + ($recencyScore * 0.3);

        return $this->clamp($totalScore);
    }

    /**
     * Calculate content quality score (0-10000)
     */
    public function calculateQualityScore(Ilan $ilan): int
    {
        $score = 0;

        // Title length check (ideal 30-60 chars)
        $titleLen = mb_strlen($ilan->baslik ?? '');
        if ($titleLen >= 30 && $titleLen <= 60) {
            $score += 2000;
        } elseif ($titleLen > 10) {
            $score += 1000;
        }

        // Description length check (ideal > 300 chars)
        $descLen = mb_strlen($ilan->aciklama ?? '');
        if ($descLen > 500) {
            $score += 3000;
        } elseif ($descLen > 200) {
            $score += 1500;
        }

        // Photo count check (ideal > 5)
        // Assuming fotograflar_count is available or relation loaded
        $photoCount = $ilan->fotograflar_count ?? $ilan->fotograflar()->count();
        if ($photoCount >= 10) {
            $score += 3000;
        } elseif ($photoCount >= 5) {
            $score += 1500;
        } elseif ($photoCount > 0) {
            $score += 500;
        }

        // Location check
        if (!empty($ilan->lat) && !empty($ilan->lng)) {
            $score += 2000;
        }

        return $this->clamp($score);
    }

    /**
     * Calculate SEO score (0-10000)
     */
    public function calculateSeoScore(Ilan $ilan): int
    {
        $score = 0;

        // Meta presence check
        if (!empty($ilan->seo_meta)) {
            $meta = is_string($ilan->seo_meta) ? json_decode($ilan->seo_meta, true) : $ilan->seo_meta;

            if (!empty($meta['title'])) $score += 2500;
            if (!empty($meta['description'])) $score += 2500;
            if (!empty($meta['keywords'])) $score += 2500;
        }

        // Slug check
        if (!empty($ilan->slug)) {
            $score += 2500;
        }

        return $this->clamp($score);
    }

    /**
     * Calculate Recency score (0-10000)
     * Decay over time
     */
    public function calculateRecencyScore(Ilan $ilan): int
    {
        $updatedAt = $ilan->updated_at ?? now();
        $daysDiff = now()->diffInDays($updatedAt);

        // Exponential decay or linear? Using linear for simplicity
        // 0 days = 10000
        // 30 days = 0

        if ($daysDiff >= 30) {
            return 0;
        }

        $score = 10000 - ($daysDiff * (10000 / 30));

        return $this->clamp((int) $score);
    }

    private function clamp(float|int $value): int
    {
        return (int) max(0, min(10000, $value));
    }
}
