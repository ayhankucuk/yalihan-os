<?php

namespace App\Application\AI\Support;

use App\Domain\AI\Enums\AIProvider;
use Illuminate\Support\Facades\Cache;

/**
 * 🛡️ ProviderLatencyRepository
 * Interfaces with telemetry cache to provide real-time latency scores.
 * High score = Low latency (fast response).
 */
final class ProviderLatencyRepository
{
    /**
     * Get latency score (1-100) for a provider.
     */
    public function score(AIProvider $provider): float
    {
        $statsKey = "ai_stats:{$provider->value}:avg_latency";
        $latencyMs = Cache::get($statsKey);

        if (!$latencyMs) {
            return $this->getDefaultBaseline($provider);
        }

        // Score decreases as latency increases (relative to 5s cap)
        // 5000ms = 0 score, 500ms = 100 score (approx)
        $score = 100 - ($latencyMs / 50); 
        
        return max(10, min(100, $score));
    }

    private function getDefaultBaseline(AIProvider $provider): float
    {
        return match ($provider) {
            AIProvider::OLLAMA => 85,   // Very fast locally
            AIProvider::GEMINI => 75,   // Fast inference
            AIProvider::OPENAI => 70,   // Stable but variable
            AIProvider::DEEPSEEK => 65, // Often high latency/queuing
            default => 60,
        };
    }
}
