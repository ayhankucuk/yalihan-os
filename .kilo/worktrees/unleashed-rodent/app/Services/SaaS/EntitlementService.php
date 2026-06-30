<?php

namespace App\Services\SaaS;

use App\Models\SaaS\Tenant;
use RuntimeException;

/**
 * EntitlementService
 * 
 * Purpose: Enforces plan-based limitations and feature entitlements.
 * Adheres to canonical naming (durum).
 */
class EntitlementService
{
    public function __construct(
        protected TenantContextService $context
    ) {}

    /**
     * Assert that the tenant is allowed to perform a specific action.
     * Throws exception if denied.
     */
    public function assertAllowed(string $feature, int $requestedAmount = 1): void
    {
        $tenant = $this->context->getTenant();
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->isActive()) {
            throw new RuntimeException("Entitlement Denied: No active subscription for tenant [{$tenant->uuid}].");
        }

        $plan = $subscription->plan;
        $features = $plan->features ?? [];

        if (!isset($features[$feature])) {
            throw new RuntimeException("Entitlement Denied: Feature [{$feature}] not included in plan [{$plan->name}].");
        }

        if (is_numeric($features[$feature])) {
            $currentUsage = 0; // Usage aggregation logic
            if (($currentUsage + $requestedAmount) > $features[$feature]) {
                throw new RuntimeException("Entitlement Denied: Usage limit reached for [{$feature}].");
            }
        }
    }
}
