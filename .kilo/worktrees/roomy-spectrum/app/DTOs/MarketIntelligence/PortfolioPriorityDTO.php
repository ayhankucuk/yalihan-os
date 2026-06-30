<?php

namespace App\DTOs\MarketIntelligence;

/**
 * Portfolio Priority DTO — MIE v1.4
 *
 * Portföy önceliklendirme katmanının veri taşıyıcısı.
 * Her ilan için priority score, label, reason ve intelligence sinyallerini taşır.
 *
 * Tamamen deterministik — AI veya rand() bağımlılığı sıfır.
 */
final class PortfolioPriorityDTO
{
    public function __construct(
        public readonly int $listing_id,
        public readonly int $priority_score,
        public readonly string $priority_label,
        public readonly string $priority_reason,
        public readonly string $opportunity_action,
        public readonly int $opportunity_score,
        public readonly int $confidence_score,
        public readonly int $demand_score,
        public readonly string $pricing_position,
        public readonly ?float $days_on_market,
    ) {}

    public function toArray(): array
    {
        return [
            'listing_id' => $this->listing_id,
            'priority_score' => $this->priority_score,
            'priority_label' => $this->priority_label,
            'priority_reason' => $this->priority_reason,
            'opportunity_action' => $this->opportunity_action,
            'opportunity_score' => $this->opportunity_score,
            'confidence_score' => $this->confidence_score,
            'demand_score' => $this->demand_score,
            'pricing_position' => $this->pricing_position,
            'days_on_market' => $this->days_on_market,
        ];
    }
}
