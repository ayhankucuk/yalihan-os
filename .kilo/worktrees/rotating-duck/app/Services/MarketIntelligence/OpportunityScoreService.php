<?php

namespace App\Services\MarketIntelligence;

use App\Enums\MarketIntelligence\PricingPosition;

/**
 * Opportunity Score Service — MIE v1.3
 *
 * Fiyat pozisyonu + güven + talep sinyallerini birleştirerek
 * ilan için aksiyon önerisi üretir: BUY / WAIT / SELL / INSUFFICIENT_DATA.
 *
 * Tamamen deterministik — rand() sıfır, AI sıfır.
 *
 * BUY = fırsat, agresif aksiyon alınabilir
 * WAIT = bekle, veri izlenmeli
 * SELL = bu formda tutma, fiyat/pozisyon revizyonu gerek (yatırım tavsiyesi DEĞİL)
 */
class OpportunityScoreService
{
    /**
     * Opportunity değerlendirmesi yap.
     *
     * @param string $pricingPosition PricingPosition enum value
     * @param int    $pricingScore    0–100 pricing score
     * @param int    $demandScore     0–100 demand score
     * @param int    $confidenceScore 0–100 confidence score
     *
     * @return array{opportunity_score: int, opportunity_action: string, opportunity_reason: string}
     */
    public function evaluate(
        string $pricingPosition,
        int $pricingScore,
        int $demandScore,
        int $confidenceScore,
    ): array {
        $pricingEdge = $this->pricingEdgeScore($pricingPosition, $pricingScore);
        $demandSupport = $this->demandSupportScore($demandScore);
        $confidenceWeight = $this->confidenceWeightScore($confidenceScore);

        $opportunityScore = (int) min(100, max(0, $pricingEdge + $demandSupport + $confidenceWeight));

        $demandLabel = $this->demandLabel($demandScore);
        $confidenceLabel = $this->confidenceLabel($confidenceScore);

        $action = $this->determineAction($pricingPosition, $demandLabel, $confidenceLabel);
        $reason = $this->buildReason($pricingPosition, $demandLabel, $confidenceLabel, $action);

        return [
            'opportunity_score' => $opportunityScore,
            'opportunity_action' => $action,
            'opportunity_reason' => $reason,
        ];
    }

    /**
     * Pricing Edge Score (0–40).
     *
     * UNDERPRICED → 35–40 (scaled by pricing_score)
     * FAIR → 18–28
     * OVERPRICED → 5–15
     * AGGRESSIVELY_OVERPRICED → 0–5
     * INSUFFICIENT_DATA → 0
     */
    private function pricingEdgeScore(string $pricingPosition, int $pricingScore): int
    {
        $ratio = min(1.0, max(0.0, $pricingScore / 100));

        return match ($pricingPosition) {
            PricingPosition::UNDERPRICED->value => 35 + (int) round($ratio * 5),
            PricingPosition::FAIR->value => 18 + (int) round($ratio * 10),
            PricingPosition::OVERPRICED->value => 5 + (int) round($ratio * 10),
            PricingPosition::AGGRESSIVELY_OVERPRICED->value => (int) round($ratio * 5),
            default => 0, // INSUFFICIENT_DATA
        };
    }

    /**
     * Demand Support Score (0–30).
     *
     * HOT → 30, ACTIVE → 20, SLOW → 10, WEAK → 0
     */
    private function demandSupportScore(int $demandScore): int
    {
        return match (true) {
            $demandScore >= 75 => 30,
            $demandScore >= 50 => 20,
            $demandScore >= 25 => 10,
            default => 0,
        };
    }

    /**
     * Confidence Weight Score (0–30).
     *
     * HIGH → 30, MEDIUM → 20, LOW → 10, VERY_LOW → 0
     */
    private function confidenceWeightScore(int $confidenceScore): int
    {
        return match (true) {
            $confidenceScore >= 80 => 30,
            $confidenceScore >= 50 => 20,
            $confidenceScore >= 20 => 10,
            default => 0,
        };
    }

    /**
     * Demand label from score (mirrors DemandScoreService).
     */
    private function demandLabel(int $demandScore): string
    {
        return match (true) {
            $demandScore >= 75 => 'HOT',
            $demandScore >= 50 => 'ACTIVE',
            $demandScore >= 25 => 'SLOW',
            default => 'WEAK',
        };
    }

    /**
     * Confidence label from score (mirrors ConfidenceCalculator).
     */
    private function confidenceLabel(int $confidenceScore): string
    {
        return match (true) {
            $confidenceScore >= 80 => 'HIGH',
            $confidenceScore >= 50 => 'MEDIUM',
            $confidenceScore >= 20 => 'LOW',
            default => 'VERY_LOW',
        };
    }

    /**
     * Deterministic action decision.
     *
     * IF confidence = VERY_LOW → INSUFFICIENT_DATA
     * ELSE IF UNDERPRICED + demand IN (HOT, ACTIVE) + confidence IN (HIGH, MEDIUM) → BUY
     * ELSE IF FAIR + demand IN (ACTIVE, HOT) → WAIT
     * ELSE IF OVERPRICED or AGGRESSIVELY_OVERPRICED → SELL
     * ELSE → WAIT
     */
    private function determineAction(string $pricingPosition, string $demandLabel, string $confidenceLabel): string
    {
        // Gate: insufficient confidence
        if ($confidenceLabel === 'VERY_LOW') {
            return 'INSUFFICIENT_DATA';
        }

        // Gate: insufficient pricing data
        if ($pricingPosition === PricingPosition::INSUFFICIENT_DATA->value) {
            return 'INSUFFICIENT_DATA';
        }

        // BUY: underpriced + strong demand + reliable confidence
        if (
            $pricingPosition === PricingPosition::UNDERPRICED->value
            && in_array($demandLabel, ['HOT', 'ACTIVE'], true)
            && in_array($confidenceLabel, ['HIGH', 'MEDIUM'], true)
        ) {
            return 'BUY';
        }

        // SELL: overpriced or aggressively overpriced
        if (in_array($pricingPosition, [
            PricingPosition::OVERPRICED->value,
            PricingPosition::AGGRESSIVELY_OVERPRICED->value,
        ], true)) {
            return 'SELL';
        }

        // Default: WAIT
        return 'WAIT';
    }

    /**
     * Deterministic reason string.
     * No AI, no speculative language.
     */
    private function buildReason(string $pricingPosition, string $demandLabel, string $confidenceLabel, string $action): string
    {
        if ($action === 'INSUFFICIENT_DATA') {
            if ($confidenceLabel === 'VERY_LOW') {
                return 'Aksiyon önerisi için güven seviyesi yetersiz.';
            }

            return 'Fiyat pozisyonu belirlenemedi, aksiyon önerisi oluşturulamıyor.';
        }

        $positionLabel = match ($pricingPosition) {
            PricingPosition::UNDERPRICED->value => 'benchmark altı fiyat',
            PricingPosition::FAIR->value => 'piyasa uyumlu fiyat',
            PricingPosition::OVERPRICED->value => 'benchmark üstü fiyat',
            PricingPosition::AGGRESSIVELY_OVERPRICED->value => 'belirgin yüksek fiyat',
            default => 'belirsiz fiyat pozisyonu',
        };

        $demandTr = match ($demandLabel) {
            'HOT' => 'yüksek talep',
            'ACTIVE' => 'aktif talep',
            'SLOW' => 'yavaş talep',
            default => 'zayıf talep',
        };

        $confidenceTr = match ($confidenceLabel) {
            'HIGH' => 'yüksek güven',
            'MEDIUM' => 'orta güven',
            'LOW' => 'düşük güven',
            default => 'çok düşük güven',
        };

        $actionAdvice = match ($action) {
            'BUY' => 'Fırsat — agresif aksiyon alınabilir.',
            'SELL' => 'Fiyat/pozisyon revizyonu önerilir.',
            'WAIT' => 'Bekle — veri izlenmeye devam edilmeli.',
            default => '',
        };

        return "{$positionLabel}, {$demandTr}, {$confidenceTr}. {$actionAdvice}";
    }
}
