<?php

namespace App\Domain\AI\ValueObjects;

use App\Domain\AI\Enums\AIProvider;

/**
 * 🛡️ ProviderScore Value Object
 * Stores the 100-point breakdown for a single provider based on various metrics.
 */
final class ProviderScore
{
    public function __construct(
        public readonly AIProvider $provider,
        public readonly float $totalScore,
        public readonly float $taskFit,
        public readonly float $costScore,
        public readonly float $latencyScore,
        public readonly float $reliabilityScore,
        public readonly float $qualityScore,
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'total_score' => $this->totalScore,
            'breakdown' => [
                'task_fit' => $this->taskFit,
                'cost' => $this->costScore,
                'latency' => $this->latencyScore,
                'reliability' => $this->reliabilityScore,
                'quality' => $this->qualityScore,
            ]
        ];
    }
}
