<?php

namespace App\Services\AI;

class AiRolloutService
{
    /**
     * Check if AI feature is enabled for a specific user
     *
     * @param string $feature Feature name (vision|suggestion)
     * @param int|null $userId User ID (null = current user)
     * @return bool
     */
    public function isEnabledForUser(string $feature, ?int $userId = null): bool
    {
        // Global AI kill-switch
        if (!config('ai-runtime.ai_enabled')) {
            return false;
        }

        // Feature-specific kill-switch
        if (!config("ai-runtime.{$feature}_enabled")) {
            return false;
        }

        // Get rollout percentage
        $percentage = config("ai-runtime.rollout.{$feature}_percentage", 100);

        // 100% = everyone gets it
        if ($percentage >= 100) {
            return true;
        }

        // 0% = nobody gets it
        if ($percentage <= 0) {
            return false;
        }

        // Deterministic user-based rollout
        $userId = $userId ?? auth()->id() ?? 0;
        $hash = crc32($feature . ':' . $userId);

        return ($hash % 100) < $percentage;
    }

    /**
     * Get current runtime state
     */
    public function getRuntimeState(): array
    {
        return [
            'ai_enabled' => config('ai-runtime.ai_enabled'),
            'vision_enabled' => config('ai-runtime.vision_enabled'),
            'suggestion_enabled' => config('ai-runtime.suggestion_enabled'),
            'rollout' => config('ai-runtime.rollout'),
            'last_shutdown' => config('ai-runtime.last_shutdown'),
        ];
    }
}
