<?php

namespace App\Services\AIMatch;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\Projections\TalepMatchProjection;
use App\Models\Projections\BuyerIntentProjection;
use Illuminate\Support\Collection;

/**
 * ️ SAB SEALED
 * Buyer Match Detection Service
 *
 * Pools candidates from projections and runs them through the scoring logic.
 */
class BuyerMatchDetectionService
{
    public function __construct(
        private BuyerMatchScoringService $scoringService
    ) {}

    /**
     * Find top matches for a given listing.
     */
    public function detectForListing(Ilan $ilan, int $limit = 10): Collection
    {
        // 1. Initial Filtering via Projections (Read Models)
        $candidates = $this->getInitialCandidates($ilan);

        // 2. Full Scoring
        $matches = $candidates->map(function ($candidate) use ($ilan) {
            $buyer = $candidate->buyer;
            $talep = $candidate instanceof TalepMatchProjection ? $candidate->talep : null;

            $scoreData = $this->scoringService->calculateMatch($ilan, $buyer, $talep);

            return [
                'buyer' => $buyer,
                'talep_id' => $talep?->id,
                'score' => $scoreData,
            ];
        });

        // 3. Sort & Limit
        return $matches->sortByDesc(fn($m) => $m['score']['total'])->take($limit);
    }

    /**
     * Basic pool filtering using projections.
     */
    private function getInitialCandidates(Ilan $ilan): Collection
    {
        $price = $ilan->fiyat;
        $tolerance = 0.2; // 20% budget spread

        // Strategy A: Direct Talep matches
        $talepCandidates = TalepMatchProjection::with(['buyer', 'talep'])
            ->where('property_type', $ilan->emlak_tipi)
            ->where('city', $ilan->il?->il_adi)
            ->where(function ($q) use ($price, $tolerance) {
                $maxAcceptableMin = $price * (1 + $tolerance);
                $minAcceptableMax = $price * (1 - $tolerance);

                $q->where('min_price', '<=', $maxAcceptableMin)
                  ->where('max_price', '>=', $minAcceptableMax);
            })
            ->get();

        // Strategy B: General Intent matches (for those without specific Talep yet)
        $intentCandidates = BuyerIntentProjection::with('buyer')
            ->whereJsonContains('property_types', $ilan->emlak_tipi)
            ->where('preferred_city', $ilan->il?->il_adi)
            ->where(function ($q) use ($price, $tolerance) {
                $maxAcceptableMin = $price * (1 + $tolerance);
                $minAcceptableMax = $price * (1 - $tolerance);

                $q->where('min_budget', '<=', $maxAcceptableMin)
                  ->where('max_budget', '>=', $minAcceptableMax);
            })
            ->get();

        return $talepCandidates->concat($intentCandidates)->unique('buyer_id');
    }
}
