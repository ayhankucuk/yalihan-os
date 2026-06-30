<?php

namespace App\Services\SaaS;

use App\Models\SaaS\Tenant;
use RuntimeException;

/**
 * TenantContextService
 * 
 * Purpose: Manages the current tenant context globally for the application lifecycle.
 * This is the primary boundary guard for Multi-tenant isolation.
 */
class TenantContextService
{
    protected ?Tenant $currentTenant = null;

    /**
     * Set the current tenant context.
     */
    public function setTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * Get the current tenant. Throws exception if not set to prevent data leaks.
     */
    public function getTenant(): Tenant
    {
        if (!$this->currentTenant) {
            // 🛡️ GOVERNANCE: Prevent unauthorized access to data without tenant context
            throw new RuntimeException('Tenant context not established. Multi-tenant boundary violation.');
        }

        return $this->currentTenant;
    }

    /**
     * Check if a tenant context exists.
     */
    public function hasTenant(): bool
    {
        return !is_null($this->currentTenant);
    }
}
