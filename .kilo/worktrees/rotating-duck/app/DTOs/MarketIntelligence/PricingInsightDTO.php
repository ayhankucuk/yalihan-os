<?php

namespace App\DTOs\MarketIntelligence;

use App\Enums\MarketIntelligence\PricingPosition;

/**
 * Pricing Insight DTO
 *
 * İlan detay sayfasında gösterilen fiyat pozisyon kartının veri taşıyıcısı.
 * Tamamen deterministik — AI veya rand() bağımlılığı sıfır.
 */
final class PricingInsightDTO
{
    public function __construct(
        public readonly int $ilan_id,
        public readonly float $current_price,
        public readonly ?float $benchmark_price,
        public readonly ?float $benchmark_min,
        public readonly ?float $benchmark_max,
        public readonly int $sample_size,
        public readonly ?float $price_delta_percent,
        public readonly PricingPosition $pricing_position,
        public readonly int $pricing_score,
        public readonly string $confidence,
        public readonly bool $insufficient_data,
        public readonly string $reason,
        public readonly int $confidence_score = 0,
        public readonly string $confidence_label = 'VERY_LOW',
        public readonly string $confidence_reason = '',
        public readonly int $demand_score = 0,
        public readonly string $demand_label = 'WEAK',
        public readonly string $demand_reason = '',
        public readonly int $opportunity_score = 0,
        public readonly string $opportunity_action = 'INSUFFICIENT_DATA',
        public readonly string $opportunity_reason = '',
    ) {}

    public function toArray(): array
    {
        return [
            'ilan_id' => $this->ilan_id,
            'current_price' => $this->current_price,
            'benchmark_price' => $this->benchmark_price,
            'benchmark_min' => $this->benchmark_min,
            'benchmark_max' => $this->benchmark_max,
            'sample_size' => $this->sample_size,
            'price_delta_percent' => $this->price_delta_percent,
            'pricing_position' => $this->pricing_position->value,
            'pricing_position_label' => $this->pricing_position->label(),
            'pricing_score' => $this->pricing_score,
            'confidence' => $this->confidence,
            'insufficient_data' => $this->insufficient_data,
            'reason' => $this->reason,
            'confidence_score' => $this->confidence_score,
            'confidence_label' => $this->confidence_label,
            'confidence_reason' => $this->confidence_reason,
            'demand_score' => $this->demand_score,
            'demand_label' => $this->demand_label,
            'demand_reason' => $this->demand_reason,
            'opportunity_score' => $this->opportunity_score,
            'opportunity_action' => $this->opportunity_action,
            'opportunity_reason' => $this->opportunity_reason,
        ];
    }
}
