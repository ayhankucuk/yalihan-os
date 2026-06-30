<?php

namespace App\Services\AI;

use App\Models\AI\AiFeaturePrice;
use App\Models\AI\AiPricingPlan;
use Illuminate\Support\Facades\Cache;

class AiPricingService
{
    /**
     * SSOT: Pricing Engine (The Cash Register)
     * 
     * Responsibilities:
     * - Determine PRICE (Credits) for a given Feature and Plan
     * - Handle Dynamic Multipliers (e.g. larger docs = 2x price)
     */

    // Default price if no plan/config found (Fail-safe)
    private const DEFAULT_BASE_COST = 10;

    /**
     * Get the price in Credits for a feature usage
     */
    public function getPrice(string $featureSlug, ?int $planId = null, array $context = []): int
    {
        // 1. Resolve Plan ID
        $planId = $planId ?? config('ai.defaults.plan_id'); // If null, fallback to default plan
        
        // 2. Fetch Pricing Rule
        $priceRule = $this->getPricingRule($featureSlug, $planId);
        
        if (!$priceRule) {
            // Fallback for missing rules (development or misconfig)
             return self::DEFAULT_BASE_COST;
        }

        // 3. Calculate Cost
        $cost = $priceRule->base_cost_credits;

        // 4. Apply Multiplier
        if ($priceRule->is_dynamic) {
             $cost = (int) ($cost * $priceRule->multiplier);
        }

        // Future: Check context for extra multipliers (e.g. 'length' => 'long')

        return $cost;
    }

    private function getPricingRule(string $featureSlug, ?int $planId): ?AiFeaturePrice
    {
        if (!$planId) return null;

        $cacheKey = "ai_price_{$planId}_{$featureSlug}";

        return Cache::remember($cacheKey, 600, function () use ($featureSlug, $planId) {
            return AiFeaturePrice::where('plan_id', $planId)
                ->where('feature_slug', $featureSlug)
                ->first();
        });
    }
}
