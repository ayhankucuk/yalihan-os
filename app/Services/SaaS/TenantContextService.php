<?php

namespace App\Services\SaaS;

use App\Models\SaaS\Tenant;
use App\Models\PropertyWorkspace;
use RuntimeException;

/**
 * TenantContextService
 *
 * Purpose: Manages the current tenant and workspace context globally for the application lifecycle.
 * This is the primary boundary guard for Multi-tenant isolation and Workspace isolation.
 *
 * Sprint 12B: Workspace Tenant Isolation
 * - Added workspace context management
 * - Workspace ownership validation
 */
class TenantContextService
{
    protected ?Tenant $currentTenant = null;

    /**
     * @var PropertyWorkspace|null Current workspace context for workspace-scoped operations
     */
    protected ?PropertyWorkspace $currentWorkspace = null;

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

    // ─────────────────────────────────────────────────────────────────
    // Workspace Context (Sprint 12B)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Set the current workspace context.
     *
     * Used for workspace-scoped operations like publish/unpublish/submit.
     */
    public function setWorkspace(PropertyWorkspace $workspace): void
    {
        $this->currentWorkspace = $workspace;
    }

    /**
     * Get the current workspace context.
     *
     * @return PropertyWorkspace|null Returns null if workspace context is not set
     */
    public function getWorkspace(): ?PropertyWorkspace
    {
        return $this->currentWorkspace;
    }

    /**
     * Get the current workspace. Throws exception if not set.
     *
     * @throws RuntimeException If workspace context is not established
     */
    public function requireWorkspace(): PropertyWorkspace
    {
        if (!$this->currentWorkspace) {
            throw new RuntimeException(
                'Workspace context not established. Workspace-scoped operation requires active workspace.'
            );
        }

        return $this->currentWorkspace;
    }

    /**
     * Check if a workspace context exists.
     */
    public function hasWorkspace(): bool
    {
        return !is_null($this->currentWorkspace);
    }

    /**
     * Clear the workspace context.
     */
    public function clearWorkspace(): void
    {
        $this->currentWorkspace = null;
    }

    /**
     * Clear both tenant and workspace context.
     */
    public function clear(): void
    {
        $this->currentTenant = null;
        $this->currentWorkspace = null;
    }
}
