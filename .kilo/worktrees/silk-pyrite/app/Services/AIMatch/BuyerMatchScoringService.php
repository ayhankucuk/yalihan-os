<?php

namespace App\Services\AIMatch;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\Projections\BuyerIntentProjection;
use App\Services\Intelligence\ActionScoreService;
use App\Services\AI\KisiChurnService;
use Illuminate\Support\Facades\Log;

/**
 * ️ SAB SEALED
 * Buyer Match Scoring Service
 *
 * Implements the 100-point weighted matching algorithm.
 */
class BuyerMatchScoringService
{
    // Weights (Total 100)
    private const WEIGHT_PRICE = 20;
    private const WEIGHT_LOCATION = 20;
    private const WEIGHT_FEATURES = 15;
    private const WEIGHT_ROOMS = 10;
    private const WEIGHT_TYPE = 10;
    private const WEIGHT_INTENT = 10;
    private const WEIGHT_ACTIVITY = 5;
    private const WEIGHT_ACTION = 5;
    private const WEIGHT_CHURN = 5;

    public function __construct(
        private ActionScoreService $actionScoreService,
        private KisiChurnService $churnService
    ) {}

    /**
     * Calculate a full match score between a listing and a buyer.
     */
    public function calculateMatch(Ilan $ilan, Kisi $buyer, ?Talep $talep = null): array
    {
        $scores = [
            'price' => $this->scorePrice($ilan, $buyer, $talep),
            'location' => $this->scoreLocation($ilan, $buyer, $talep),
            'features' => $this->scoreFeatures($ilan, $buyer, $talep),
            'rooms' => $this->scoreRooms($ilan, $buyer, $talep),
            'type' => $this->scoreType($ilan, $buyer, $talep), // context7-ignore
            'intent' => $this->scoreIntent($buyer, $talep),
            'activity' => $this->scoreActivity($buyer),
            'action' => $this->scoreAction($buyer),
            'churn' => $this->scoreChurn($buyer),
        ];

        $totalScore = array_sum($scores);

        return [
            'total' => round($totalScore, 2),
            'breakdown' => $scores,
        ];
    }

    private function scorePrice(Ilan $ilan, Kisi $buyer, ?Talep $talep): float
    {
        $min = $talep?->min_fiyat ?: 0;
        $max = $talep?->max_fiyat ?: PHP_INT_MAX;
        $price = $ilan->fiyat;

        if ($price >= $min && $price <= $max) return self::WEIGHT_PRICE;

        // Tolerance calculation (up to 10% flexibility for partial points)
        $diff = 0;
        if ($price < $min) $diff = ($min - $price) / $min;
        if ($price > $max) $diff = ($price - $max) / $max;

        if ($diff < 0.1) return self::WEIGHT_PRICE * 0.5;
        if ($diff < 0.2) return self::WEIGHT_PRICE * 0.2;

        return 0;
    }

    private function scoreLocation(Ilan $ilan, Kisi $buyer, ?Talep $talep): float
    {
        // Exact match (District/Mahalle)
        if ($talep && $ilan->ilce_id === $talep->ilce_id) {
            if ($ilan->mahalle_id === $talep->mahalle_id) return self::WEIGHT_LOCATION;
            return self::WEIGHT_LOCATION * 0.8;
        }

        // City match
        if ($talep && $ilan->il_id === $talep->il_id) return self::WEIGHT_LOCATION * 0.4;

        return 0;
    }

    private function scoreFeatures(Ilan $ilan, Kisi $buyer, ?Talep $talep): float
    {
        $aranan = $talep?->aranan_ozellikler_json ?? [];
        if (empty($aranan)) return self::WEIGHT_FEATURES * 0.5; // Neutral

        $ilanOzellikleri = $ilan->ozellikler->pluck('slug')->toArray();
        $matches = count(array_intersect($aranan, $ilanOzellikleri));
        $total = count($aranan);

        return ($matches / $total) * self::WEIGHT_FEATURES;
    }

    private function scoreRooms(Ilan $ilan, Kisi $buyer, ?Talep $talep): float
    {
        if (!$talep || !$talep->oda_sayisi) return self::WEIGHT_ROOMS * 0.5;
        return $ilan->oda_sayisi === $talep->oda_sayisi ? self::WEIGHT_ROOMS : 0;
    }

    private function scoreType(Ilan $ilan, Kisi $buyer, ?Talep $talep): float
    {
        if (!$talep || !$talep->emlak_tipi) return self::WEIGHT_TYPE * 0.5;
        return $ilan->emlak_tipi === $talep->emlak_tipi ? self::WEIGHT_TYPE : 0;
    }

    private function scoreIntent(Kisi $buyer, ?Talep $talep): float
    {
        $urgency = $talep?->oncelik === 'Acil' ? 1 : 0.5;
        return self::WEIGHT_INTENT * $urgency;
    }

    private function scoreActivity(Kisi $buyer): float
    {
        // Simple activity proxy: days since last contact
        $days = $buyer->last_contact_at?->diffInDays(now()) ?? 100;
        if ($days < 7) return self::WEIGHT_ACTIVITY;
        if ($days < 30) return self::WEIGHT_ACTIVITY * 0.5;
        return 0;
    }

    private function scoreAction(Kisi $buyer): float
    {
        $scoreResult = $this->actionScoreService->calculateScore($buyer);
        $score = $scoreResult['score'] ?? 0; // Assuming 0-100 scale
        return ($score / 100) * self::WEIGHT_ACTION;
    }

    private function scoreChurn(Kisi $buyer): float
    {
        $churnResult = $this->churnService->calculateChurnRisk($buyer);
        $riskScore = $churnResult['score'] ?? 100; // Higher risk = Lower match score contribution
        $contribution = 100 - $riskScore;
        return ($contribution / 100) * self::WEIGHT_CHURN;
    }
}
