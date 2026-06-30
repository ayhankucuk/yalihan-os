<?php

namespace App\Services\AI\Monetization;

use App\Models\AI\AiCreditBalance;
use App\Models\AI\AiFeaturePrice;
use App\Models\SaaS\Tenant;
use App\Exceptions\AI\InsufficientCreditsException;
use Illuminate\Support\Facades\Log;

/**
 * AiBudgetGuard Service
 * 🛡️ SAB §12.2: Credit-Based AI Guard (Circuit Breaker)
 */
class AiBudgetGuard
{
    /**
     * Check if the tenant has enough credits to execute a feature.
     * 
     * @throws InsufficientCreditsException
     */
    public function canExecute(Tenant $tenant, string $featureSlug): bool
    {
        $balance = AiCreditBalance::where('tenant_id', $tenant->id)->first();
        
        if (!$balance) {
            Log::warning("Tenant [{$tenant->id}] has no AI credit balance record. Blocking execution.");
            throw new InsufficientCreditsException("AI kredi bakiyesi bulunamadı.");
        }

        $price = AiFeaturePrice::where('feature_slug', $featureSlug)
            ->where('plan_id', $tenant->subscription?->plan_id)
            ->first();

        $cost = $price ? $price->base_cost_credits : 1; // Default 1 credit if not priced

        if (!$balance->hasCredits($cost)) {
            Log::info("Tenant [{$tenant->id}] blocked by AiBudgetGuard (Insufficient Credits for [{$featureSlug}]).");
            throw new InsufficientCreditsException("Yetersiz AI kredisi. Gerekli: {$cost}, Mevcut: {$balance->available_credits}");
        }

        return true;
    }

    /**
     * Deduct credits after successful AI execution.
     */
    public function deductCredits(Tenant $tenant, string $featureSlug): void
    {
        $balance = AiCreditBalance::where('tenant_id', $tenant->id)->first();
        
        if (!$balance) return;

        $price = AiFeaturePrice::where('feature_slug', $featureSlug)
            ->where('plan_id', $tenant->subscription?->plan_id)
            ->first();

        $cost = $price ? $price->base_cost_credits : 1;

        $balance->decrement('available_credits', $cost);
        $balance->increment('used_credits', $cost);

        Log::info("Deducted {$cost} credits from Tenant [{$tenant->id}] for feature [{$featureSlug}].");
    }
}
