<?php

namespace App\Application\AI\Support;

use App\Domain\AI\Enums\AIProvider;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ ProviderReliabilityRepository
 * Interfaces with telemetry cache to provide real-time reliability scores.
 * High score = High reliability (low error rate).
 */
final class ProviderReliabilityRepository
{
    /**
     * Get reliability score (1-100) for a provider.
     */
    public function score(AIProvider $provider): float
    {
        $statsKey = "ai_stats:{$provider->value}:success_rate";
        $successRate = Cache::get($statsKey); // 0.0 to 1.0

        if ($successRate === null) {
            return $this->getDefaultBaseline($provider);
        }

        return $successRate * 100;
    }

    private function getDefaultBaseline(AIProvider $provider): float
    {
        return match ($provider) {
            AIProvider::OPENAI => 98,   // Very reliable
            AIProvider::GEMINI => 95,   // Very reliable
            AIProvider::OLLAMA => 90,   // Depends on local host status
            AIProvider::DEEPSEEK => 80, // Known for capacity/overloaded issues
            default => 85,
        };
    }
}
