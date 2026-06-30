<?php

namespace App\DTOs\MarketIntelligence;

/**
 * Advisor Insight DTO — MIE v3
 *
 * AI tabanlı danışman yorumu. V1/V2 deterministik sinyallerini
 * insana anlatır, hiçbir skoru override etmez.
 *
 * AI karar vermez — sadece yorumlar ve açıklar.
 */
final class AdvisorInsightDTO
{
    public function __construct(
        public readonly string $summary,
        public readonly string $reasoning,
        public readonly string $recommended_action,
        public readonly string $urgency, // LOW | MEDIUM | HIGH
        public readonly string $risk_note,
    ) {}

    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'reasoning' => $this->reasoning,
            'recommended_action' => $this->recommended_action,
            'urgency' => $this->urgency,
            'risk_note' => $this->risk_note,
        ];
    }
}
