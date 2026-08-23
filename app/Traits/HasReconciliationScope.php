<?php

namespace App\Traits;

use App\Models\Settlement\ReconciliationExecution;

/**
 * C5.1: Reconciliation Scope Trait
 *
 * Attaches to models that participate in C5 reconciliation.
 * Ensures tenant isolation and replay-safety invariants.
 */
trait HasReconciliationScope
{
    /**
     * C5.1: Reconciliation must always be tenant-scoped.
     * Cross-tenant reconciliation is strictly forbidden.
     */
    public function scopeReconciliationScoped($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
