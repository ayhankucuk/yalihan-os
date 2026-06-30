<?php

namespace App\Services\SaaS;

use App\Models\SaaS\BillingLedgerEntry;
use App\Models\SaaS\Tenant;

/**
 * BillingLedgerService
 * 
 * Purpose: Manages the immutable financial ledger.
 * Only 'Append' operations are allowed.
 */
class BillingLedgerService
{
    /**
     * Record a financial transaction or usage cost.
     */
    public function record(Tenant $tenant, string $type, float $amount, string $referenceType = null, int $referenceId = null, array $metadata = []): BillingLedgerEntry
    {
        // 🛡️ GOVERNANCE: Immutable Ledger Entry
        return BillingLedgerEntry::create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'USD',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata
        ]);
    }

    /**
     * Append a charge based on a usage event.
     */
    public function appendUsageCharge(Tenant $tenant, array $usageEvent): void
    {
        $this->record(
            $tenant,
            'usage_fee',
            0.01, // Fixed cost per usage event for now
            'usage_event',
            null,
            $usageEvent
        );
    }
}
