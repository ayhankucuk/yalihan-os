<?php

namespace App\Services\MarketIntelligence;

/**
 * Workflow Decision Service — MIE v1.5
 *
 * Tek ilan intelligence payload'ından queue kararı verir.
 * Karar kuralları tamamen deterministik — AI sıfır, rand() sıfır.
 *
 * Queue Types:
 *   PRICE_REVIEW         → fiyat revizyonu düşünülmeli
 *   OPPORTUNITY_FOLLOWUP → fırsat, yakın takip
 *   MANUAL_REVIEW        → veri belirsiz / edge case
 *   WATCHLIST             → izlenmeli, hemen aksiyon yok
 *   NO_ACTION             → müdahale gerekmiyor
 *
 * Workflow States:
 *   NEW          → yeni oluşan öneri
 *   IN_REVIEW    → inceleniyor
 *   ACTION_TAKEN → aksiyon alınmış
 *   WAITING      → beklemeye alındı
 *   CLOSED       → kapanmış
 */
class WorkflowDecisionService
{
    /** Queue type importance sırası (küçük = daha önemli). */
    public const QUEUE_IMPORTANCE = [
        'PRICE_REVIEW' => 1,
        'OPPORTUNITY_FOLLOWUP' => 2,
        'MANUAL_REVIEW' => 3,
        'WATCHLIST' => 4,
        'QUALITY_REVIEW' => 5,
        'NO_ACTION' => 6,
    ];

    /**
     * Tek ilan için queue kararı ver.
     *
     * @param array{
     *   opportunity_action?: string,
     *   confidence_label?: string,
     *   priority_label?: string,
     *   priority_score?: int,
     *   pricing_position?: string,
     *   demand_label?: string,
     *   days_on_market?: float|null,
     * } $intelligencePayload
     *
     * @return array{queue_type: string, workflow_state: string, reason: string}
     */
    public function decide(array $intelligencePayload): array
    {
        $action = $intelligencePayload['opportunity_action'] ?? 'INSUFFICIENT_DATA';
        $confidence = $intelligencePayload['confidence_label'] ?? 'VERY_LOW';
        $priority = $intelligencePayload['priority_label'] ?? 'LOW';
        $position = $intelligencePayload['pricing_position'] ?? 'insufficient_data';
        $demand = $intelligencePayload['demand_label'] ?? 'WEAK';

        // Rule 1: MANUAL_REVIEW — veri yetersiz
        if ($confidence === 'VERY_LOW' || $action === 'INSUFFICIENT_DATA') {
            return [
                'queue_type' => 'MANUAL_REVIEW',
                'workflow_state' => 'NEW',
                'reason' => $this->buildReason('MANUAL_REVIEW', $position, $confidence, $demand, $action),
            ];
        }

        // Rule 2: PRICE_REVIEW — SELL + medium/high confidence
        if ($action === 'SELL' && in_array($confidence, ['HIGH', 'MEDIUM'], true)) {
            return [
                'queue_type' => 'PRICE_REVIEW',
                'workflow_state' => 'NEW',
                'reason' => $this->buildReason('PRICE_REVIEW', $position, $confidence, $demand, $action),
            ];
        }

        // Rule 3: OPPORTUNITY_FOLLOWUP — BUY + medium/high confidence
        if ($action === 'BUY' && in_array($confidence, ['HIGH', 'MEDIUM'], true)) {
            return [
                'queue_type' => 'OPPORTUNITY_FOLLOWUP',
                'workflow_state' => 'NEW',
                'reason' => $this->buildReason('OPPORTUNITY_FOLLOWUP', $position, $confidence, $demand, $action),
            ];
        }

        // Rule 4: WATCHLIST — WAIT + medium/high priority
        if ($action === 'WAIT' && in_array($priority, ['MEDIUM', 'HIGH', 'CRITICAL'], true)) {
            return [
                'queue_type' => 'WATCHLIST',
                'workflow_state' => 'NEW',
                'reason' => $this->buildReason('WATCHLIST', $position, $confidence, $demand, $action),
            ];
        }

        // Rule 5: NO_ACTION — fair + medium/high confidence + low priority
        return [
            'queue_type' => 'NO_ACTION',
            'workflow_state' => 'NEW',
            'reason' => $this->buildReason('NO_ACTION', $position, $confidence, $demand, $action),
        ];
    }

    /**
     * Queue importance sıralaması (küçük = daha önemli).
     */
    public function getQueueImportance(string $queueType): int
    {
        return self::QUEUE_IMPORTANCE[$queueType] ?? 99;
    }

    /**
     * Deterministic Turkish reason builder.
     */
    private function buildReason(string $queueType, string $position, string $confidence, string $demand, string $action): string
    {
        $positionLabel = match ($position) {
            'underpriced' => 'benchmark altı fiyat',
            'fair' => 'piyasa uyumlu fiyat',
            'overpriced' => 'benchmark üstü fiyat',
            'aggressively_overpriced' => 'belirgin yüksek fiyat',
            default => 'belirsiz fiyat pozisyonu',
        };

        $confidenceLabel = match ($confidence) {
            'HIGH' => 'yüksek güven',
            'MEDIUM' => 'orta güven',
            'LOW' => 'düşük güven',
            default => 'çok düşük güven',
        };

        $demandLabel = match ($demand) {
            'HOT' => 'yüksek talep',
            'ACTIVE' => 'aktif talep',
            'SLOW' => 'yavaş talep',
            default => 'zayıf talep',
        };

        return match ($queueType) {
            'PRICE_REVIEW' => "{$positionLabel}, {$confidenceLabel} ile fiyat revizyonu önerilir.",
            'OPPORTUNITY_FOLLOWUP' => "{$positionLabel}, {$demandLabel}, fırsat takibi önerilir.",
            'MANUAL_REVIEW' => "{$confidenceLabel}, veri yetersiz — manuel inceleme gerekli.",
            'WATCHLIST' => "{$positionLabel}, {$demandLabel} — izleme listesine alındı.",
            'NO_ACTION' => "{$positionLabel}, {$confidenceLabel}, {$demandLabel} — aksiyon gerekmiyor.",
            default => 'Belirsiz durum.',
        };
    }
}
