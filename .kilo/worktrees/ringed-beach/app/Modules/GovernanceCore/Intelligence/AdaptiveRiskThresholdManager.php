<?php

declare(strict_types=1);

namespace App\Modules\GovernanceCore\Intelligence;

use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Cache;

/**
 * Class AdaptiveRiskThresholdManager
 *
 * Manages tenant-specific risk thresholds dynamically based on history.
 * ✅ SAB: Deterministic, versioned threshold sets.
 */
class AdaptiveRiskThresholdManager
{
    private const DEFAULT_HIGH_RISK_THRESHOLD = 80;
    private const BASE_CACHE_KEY = 'gov_v2:thresholds:';

    /**
     * Get the adaptive threshold for a specific tenant.
     */
    public function getThreshold(string $tenantId, string $type = 'HIGH_RISK'): int
    {
        $cached = Cache::get(self::BASE_CACHE_KEY . "{$tenantId}:{$type}");

        if ($cached !== null) {
            return (int) $cached;
        }

        // Fallback to default or calculate if history exists
        return $this->calculateAdaptiveThreshold($tenantId, $type);
    }

    /**
     * Dynamically calculate thresholds based on past X versions.
     * Deterministic based on DB state.
     */
    private function calculateAdaptiveThreshold(string $tenantId, string $type): int
    {
        $recentVersions = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->whereNotNull('risk_score')
            ->latest()
            ->limit(10)
            ->get();

        if ($recentVersions->isEmpty()) {
            return self::DEFAULT_HIGH_RISK_THRESHOLD;
        }

        $avgRisk = $recentVersions->avg('risk_score');

        // Adaptive Logic: If average risk is low, we tighten the threshold.
        // If average risk is high (stable high change environment), we might loosen it slightly.
        // Base: 80. If avg risk is 20, threshold becomes 70 (stricter).
        $adaptiveThreshold = self::DEFAULT_HIGH_RISK_THRESHOLD;

        if ($avgRisk < 30) {
            $adaptiveThreshold = 70; // Stricter for quiet tenants
        } elseif ($avgRisk > 60) {
            $adaptiveThreshold = 85; // Slightly loose for high-change tenants
        }

        // Cache for 1 hour to maintain performance
        Cache::put(self::BASE_CACHE_KEY . "{$tenantId}:{$type}", $adaptiveThreshold, now()->addHour());

        return $adaptiveThreshold;
    }

    /**
     * Explicitly update the threshold (Versioned trigger).
     */
    public function refreshThresholds(string $tenantId): void
    {
        Cache::forget(self::BASE_CACHE_KEY . "{$tenantId}:HIGH_RISK");
        $this->getThreshold($tenantId);
    }
}
