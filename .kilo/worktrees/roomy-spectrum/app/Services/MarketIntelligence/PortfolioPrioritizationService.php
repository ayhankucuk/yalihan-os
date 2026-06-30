<?php

namespace App\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\PortfolioPriorityDTO;

/**
 * Portfolio Prioritization Service — MIE v1.4
 *
 * Tekil ilan intelligence payload'ından portföy önceliklendirme skoru üretir.
 * Tüm portföyü sıralayarak "bugün hangi ilanlara önce bakmalıyım?" sorusunu cevaplar.
 *
 * Tamamen deterministik — rand() sıfır, AI sıfır, tahmin sıfır.
 *
 * Formula:
 *   priority = action_urgency(0-35) + opportunity(0-25) + demand_pressure(0-15)
 *              + confidence_weight(0-15) + age_pressure(0-10)
 *   clamp(0, 100)
 */
class PortfolioPrioritizationService
{
    /**
     * Tek ilan için priority hesapla.
     *
     * @param array{
     *   listing_id: int,
     *   opportunity_action: string,
     *   opportunity_score: int,
     *   demand_score: int,
     *   confidence_score: int,
     *   pricing_position: string,
     *   days_on_market: float|null,
     * } $payload Intelligence payload from PricingInsightDTO
     */
    public function evaluateListing(array $payload): PortfolioPriorityDTO
    {
        $actionUrgency = $this->actionUrgencyScore($payload['opportunity_action'] ?? 'INSUFFICIENT_DATA');
        $opportunityComponent = $this->opportunityComponent($payload['opportunity_score'] ?? 0);
        $demandPressure = $this->demandPressureScore($payload['demand_score'] ?? 0);
        $confidenceWeight = $this->confidenceWeightScore($payload['confidence_score'] ?? 0);
        $agePressure = $this->agePressureScore($payload['days_on_market'] ?? null);

        $priorityScore = (int) min(100, max(0,
            $actionUrgency + $opportunityComponent + $demandPressure + $confidenceWeight + $agePressure
        ));

        $label = $this->label($priorityScore);
        $reason = $this->buildReason($payload, $priorityScore);

        return new PortfolioPriorityDTO(
            listing_id: $payload['listing_id'],
            priority_score: $priorityScore,
            priority_label: $label,
            priority_reason: $reason,
            opportunity_action: $payload['opportunity_action'] ?? 'INSUFFICIENT_DATA',
            opportunity_score: $payload['opportunity_score'] ?? 0,
            confidence_score: $payload['confidence_score'] ?? 0,
            demand_score: $payload['demand_score'] ?? 0,
            pricing_position: $payload['pricing_position'] ?? 'insufficient_data',
            days_on_market: $payload['days_on_market'] ?? null,
        );
    }

    /**
     * Portföy listesini priority_score desc sırala.
     *
     * @param iterable<array> $payloads Intelligence payloads
     * @return PortfolioPriorityDTO[]
     */
    public function prioritize(iterable $payloads): array
    {
        $results = [];

        foreach ($payloads as $payload) {
            $results[] = $this->evaluateListing($payload);
        }

        usort($results, fn (PortfolioPriorityDTO $a, PortfolioPriorityDTO $b) =>
            $b->priority_score <=> $a->priority_score
        );

        return $results;
    }

    /**
     * Priority label.
     */
    public function label(int $score): string
    {
        return match (true) {
            $score >= 75 => 'CRITICAL',
            $score >= 55 => 'HIGH',
            $score >= 35 => 'MEDIUM',
            default      => 'LOW',
        };
    }

    /**
     * Action Urgency Score (0–35).
     *
     * SELL → 35 (zayıf fiyat pozisyonu, müdahale gerekli)
     * BUY → 30 (fırsat, hızlı hareket)
     * INSUFFICIENT_DATA → 20 (belirsizlik riski)
     * WAIT → 15 (düşük öncelik)
     */
    private function actionUrgencyScore(string $action): int
    {
        return match ($action) {
            'SELL' => 35,
            'BUY' => 30,
            'INSUFFICIENT_DATA' => 20,
            'WAIT' => 15,
            default => 15,
        };
    }

    /**
     * Opportunity Score Contribution (0–25).
     * Linear normalization: opportunity_score / 100 * 25
     */
    private function opportunityComponent(int $opportunityScore): int
    {
        return (int) round(min(100, max(0, $opportunityScore)) / 100 * 25);
    }

    /**
     * Demand Pressure Score (0–15).
     *
     * HOT(≥75) → 15, ACTIVE(≥50) → 10, SLOW(≥25) → 7, WEAK(<25) → 3
     */
    private function demandPressureScore(int $demandScore): int
    {
        return match (true) {
            $demandScore >= 75 => 15,
            $demandScore >= 50 => 10,
            $demandScore >= 25 => 7,
            default            => 3,
        };
    }

    /**
     * Confidence Weight (0–15).
     *
     * HIGH(≥80) → 15, MEDIUM(≥50) → 10, LOW(≥20) → 5, VERY_LOW → 0
     */
    private function confidenceWeightScore(int $confidenceScore): int
    {
        return match (true) {
            $confidenceScore >= 80 => 15,
            $confidenceScore >= 50 => 10,
            $confidenceScore >= 20 => 5,
            default                => 0,
        };
    }

    /**
     * Staleness / Age Pressure (0–10).
     *
     * >90 gün → 10, 60-90 → 7, 30-60 → 4, <30 → 1, null → 0
     */
    private function agePressureScore(?float $daysOnMarket): int
    {
        if ($daysOnMarket === null) {
            return 0;
        }

        return match (true) {
            $daysOnMarket > 90  => 10,
            $daysOnMarket >= 60 => 7,
            $daysOnMarket >= 30 => 4,
            default             => 1,
        };
    }

    /**
     * Deterministic priority reason.
     * No AI, no speculative language.
     */
    private function buildReason(array $payload, int $priorityScore): string
    {
        $parts = [];

        // Pricing position
        $positionLabel = match ($payload['pricing_position'] ?? 'insufficient_data') {
            'underpriced' => 'benchmark altı fiyat',
            'fair' => 'piyasa uyumlu fiyat',
            'overpriced' => 'benchmark üstü fiyat',
            'aggressively_overpriced' => 'belirgin yüksek fiyat',
            default => 'belirsiz fiyat pozisyonu',
        };
        $parts[] = $positionLabel;

        // Demand label
        $demandScore = $payload['demand_score'] ?? 0;
        $parts[] = match (true) {
            $demandScore >= 75 => 'yüksek talep',
            $demandScore >= 50 => 'aktif talep',
            $demandScore >= 25 => 'yavaş talep',
            default            => 'zayıf talep',
        };

        // Confidence label
        $confidenceScore = $payload['confidence_score'] ?? 0;
        $parts[] = match (true) {
            $confidenceScore >= 80 => 'yüksek güven',
            $confidenceScore >= 50 => 'orta güven',
            $confidenceScore >= 20 => 'düşük güven',
            default                => 'çok düşük güven',
        };

        // Age
        $days = $payload['days_on_market'] ?? null;
        if ($days !== null) {
            $parts[] = (int) round($days) . ' gün piyasada';
        }

        return implode(', ', $parts) . '.';
    }
}
