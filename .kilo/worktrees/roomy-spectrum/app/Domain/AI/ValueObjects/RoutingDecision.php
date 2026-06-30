<?php

namespace App\Domain\AI\ValueObjects;

use App\Domain\AI\Enums\AIProvider;

/**
 * 🛡️ RoutingDecision Value Object
 * Captures the result of the AI Decision Engine's optimization process.
 */
final class RoutingDecision
{
    /**
     * @param AIProvider $selectedProvider
     * @param ProviderScore[] $rankedProviders
     * @param string $reason
     * @param bool $fallbackUsed
     */
    public function __construct(
        public readonly AIProvider $selectedProvider,
        public readonly array $rankedProviders,
        public readonly string $reason,
        public readonly bool $fallbackUsed = false,
    ) {}

    public function getTopScore(): ?ProviderScore
    {
        return $this->rankedProviders[0] ?? null;
    }

    public function toArray(): array
    {
        return [
            'selected_provider' => $this->selectedProvider->value,
            'reason' => $this->reason,
            'fallback_used' => $this->fallbackUsed,
            'rankings' => array_map(fn($score) => $score->toArray(), $this->rankedProviders),
        ];
    }
}
