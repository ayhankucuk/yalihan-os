<?php

namespace App\Services\Ranking;

use App\Models\Ilan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ranking Service (Phase 19)
 *
 * Deterministic scoring engine for Listings.
 * Range: 0 - 10,000
 */
class RankingService
{
    const MAX_SCORE = 10000;

    /**
     * Calculate and update visibility score using deterministic factors.
     * This method avoids Observer loops by using saveQuietly().
     *
     * @param Ilan $ilan
     * @return int Calculated Score
     */
    public function updateListingScore(Ilan $ilan): int
    {
        // Pasif ilanlar için skor 0 (veya filtreleme kolaylığı için düşük bir değer)
        if (!$ilan->yayindami) {
            $ilan->visibility_score = 0;
            $ilan->saveQuietly();
            return 0;
        }

        $score = 0;

        // 1. Completeness (Doluluk) - Max 3000
        $score += $this->calculateCompletenessScore($ilan);

        // 2. Freshness (Güncellik) - Max 2500
        $score += $this->calculateFreshnessScore($ilan);

        // 3. Interaction (Etkileşim) - Max 2000
        $score += $this->calculateInteractionScore($ilan);

        // 4. Boosters (Öne Çıkarma) - Max 2500
        $score += $this->calculateBoosterScore($ilan);

        // Final Cap
        $finalScore = min(self::MAX_SCORE, $score);

        // Only save if changed to reduce DB writes
        if ($ilan->visibility_score !== $finalScore) {
            $ilan->visibility_score = $finalScore;
            $ilan->saveQuietly();

            // Log for debugging (optional, can be removed later)
            // Log::info("Ranking Updated: Ilan #{$ilan->id} -> Score: {$finalScore}");
        }

        return $finalScore;
    }

    /**
     * 1. Completeness Score (0 - 3000)
     */
    protected function calculateCompletenessScore(Ilan $ilan): int
    {
        $score = 0;

        // Fotoğraf Sayısı (Max 1000)
        // 1-5 photos: 100 per photo
        // 5-10 photos: 50 per photo
        // 10+ photos: 1000 cap
        $photoCount = $ilan->fotograflar_count ?? $ilan->fotograflar()->count();
        if ($photoCount > 0) {
            $score += min(500, $photoCount * 100);
            if ($photoCount > 5) {
                $score += min(500, ($photoCount - 5) * 100);
            }
        }
        $score = min(1000, $score);

        // Açıklama Uzunluğu (Max 500)
        // > 300 chars implies detailed description
        $descLen = strlen(strip_tags($ilan->aciklama ?? ''));
        if ($descLen > 100) $score += 200;
        if ($descLen > 300) $score += 300;

        // Lokasyon (Lat/Lng) (Max 500)
        if ($ilan->lat && $ilan->lng) {
            $score += 500;
        }

        // Fiyat Bilgisi (Max 500)
        if ($ilan->fiyat > 0) {
            $score += 500;
        }

        // Ekstra: Video/3D Tur (Max 500)
        if ($ilan->youtube_video_url || $ilan->sanal_tur_url) {
            $score += 500;
        }

        // AI Quality (Max 500)
        // Check if AI-generated description exists
        if ($ilan->metinler()->where('yapay_zeka_durumu', true)->exists()) {
            $score += 500;
        }

        return min(3000, $score);
    }

    /**
     * 2. Freshness Score (0 - 2500)
     * Linear decay over 30 days.
     */
    protected function calculateFreshnessScore(Ilan $ilan): int
    {
        if (!$ilan->updated_at) return 0;

        $daysSinceUpdate = abs(now()->diffInDays($ilan->updated_at));

        // 0-1 gün: 2500
        // 30 gün: 0
        // Formül: 2500 - (days * 83.3)

        $decay = $daysSinceUpdate * 83; // Approx 2500 / 30
        $score = max(0, 2500 - $decay);

        return (int) $score;
    }

    /**
     * 3. Interaction Score (0 - 2000)
     * Based on views and favorites.
     */
    protected function calculateInteractionScore(Ilan $ilan): int
    {
        $score = 0;
        $views = $ilan->goruntulenme ?? 0;

        // Logarithmic scale for views
        // 100 views -> ~300 pts
        // 1000 views -> ~600 pts
        // 10000 views -> ~1000 pts
        if ($views > 0) {
            $score += min(1500, log($views + 1) * 150);
        }

        // Favorites (Assume weighted higher)
        // $favorites = $ilan->favori_sayisi ?? 0;
        // if ($favorites > 0) {
        //     $score += min(500, $favorites * 50);
        // }

        return min(2000, (int) $score);
    }

    /**
     * 4. Booster Score (0 - 2500)
     * Paid boosters, verified status, etc.
     */
    protected function calculateBoosterScore(Ilan $ilan): int
    {
        $score = 0;

        // Öne Çıkan (Featured)
        if ($ilan->one_cikan) {
            $score += 1500;
        }

        // Kurumsal Danışman / Verified
        // Assuming danisman_id implies verified advisor vs unknown
        if ($ilan->danisman_id) {
            $score += 500;
        }

        return min(2500, $score);
    }
}
