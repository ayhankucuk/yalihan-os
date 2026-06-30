<?php

namespace App\Services\AI;

/**
 * Phase K: AI Feature Flags Helper
 *
 * Centralized flag checking to avoid code duplication
 * Context7: Single source of truth for feature toggles
 * ✅ Uses aktiflik_durumu (NOT status)
 */
class AiFeatureFlags
{
    /**
     * Check if specific AI feature is enabled
     *
     * @param string $feature assist|quality_check|publish_gate
     * @return bool
     */
    public static function isEnabled(string $feature): bool
    {
        // Master switch check first (✅ SAB: aktiflik_durumu)
        if (!config('ups_ai.aktiflik_durumu', true)) {
            return false;
        }

        return match ($feature) {
            'assist' => config('ups_ai.assist_aktiflik_durumu', true),
            'quality_check' => config('ups_ai.quality_check_aktiflik_durumu', true),
            'publish_gate' => config('ups_ai.publish_gate_aktiflik_durumu', true),
            default => false,
        };
    }

    /**
     * Get publish gate mode
     *
     * @return string soft|hard
     */
    public static function getPublishGateMode(): string
    {
        return config('ups_ai.publish_gate_mode', 'soft');
    }

    /**
     * Get quality minimum score
     */
    public static function getQualityMinScore(): int
    {
        return config('ups_ai.quality_min_score', 60);
    }

    /**
     * Check if any AI feature is disabled (safe mode)
     *
     * ✅ SAB: aktiflik_durumu
     */
    public static function isInSafeMode(): bool
    {
        return !config('ups_ai.aktiflik_durumu', true);
    }
}
