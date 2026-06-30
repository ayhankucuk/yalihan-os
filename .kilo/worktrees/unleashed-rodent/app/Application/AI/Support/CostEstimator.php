<?php

namespace App\Application\AI\Support;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\Enums\AIProvider;

/**
 * 🛡️ CostEstimator
 * Scores providers based on their cost profile.
 * High score = Low cost (good for budget).
 */
final class CostEstimator
{
    public function score(AIProvider $provider, CortexRequestData $request): float
    {
        return match ($provider) {
            AIProvider::DEEPSEEK => 95, // Extremely cheap
            AIProvider::OLLAMA => 85,   // Local, but energy/infrastructure cost exists
            AIProvider::GEMINI => 60,   // Middle ground
            AIProvider::OPENAI => 55,   // Often most expensive at high tiers
            AIProvider::CLAUDE => 50,
        };
    }
}
