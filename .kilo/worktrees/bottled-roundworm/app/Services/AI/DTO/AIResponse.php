<?php

namespace App\Services\AI\DTO;

/**
 * 🛡️ SAB SEALED
 * AI Response DTO (Contract Layer).
 */
final readonly class AIResponse
{
    public function __construct(
        public string $provider,
        public string $model,
        public string $content,
        public int $inputTokens,
        public int $outputTokens,
        public float $costUsd,
        public int $latencyMs,
        public array $raw = []
    ) {}
}
