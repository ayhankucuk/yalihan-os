<?php

namespace App\DTOs\MarketIntelligence;

/**
 * Action Queue Item DTO — MIE v1.5
 *
 * Workflow board'da gösterilecek aksiyon kuyruğu öğesi.
 * Her ilan için queue type, workflow state ve intelligence sinyallerini taşır.
 *
 * Tamamen deterministik — AI veya rand() bağımlılığı sıfır.
 */
final class ActionQueueItemDTO
{
    public function __construct(
        public readonly int $listing_id,
        public readonly ?string $title,
        public readonly string $queue_type,
        public readonly string $workflow_state,
        public readonly int $priority_score,
        public readonly string $priority_label,
        public readonly string $opportunity_action,
        public readonly string $confidence_label,
        public readonly string $reason,
        public readonly ?float $days_on_market,
        public readonly ?float $current_price,
        public readonly ?float $benchmark_price,
    ) {}

    public function toArray(): array
    {
        return [
            'listing_id' => $this->listing_id,
            'title' => $this->title,
            'queue_type' => $this->queue_type,
            'workflow_state' => $this->workflow_state,
            'priority_score' => $this->priority_score,
            'priority_label' => $this->priority_label,
            'opportunity_action' => $this->opportunity_action,
            'confidence_label' => $this->confidence_label,
            'reason' => $this->reason,
            'days_on_market' => $this->days_on_market,
            'current_price' => $this->current_price,
            'benchmark_price' => $this->benchmark_price,
        ];
    }
}
