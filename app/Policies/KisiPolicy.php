<?php

namespace App\Policies;

use App\Models\Kisi;
use App\Models\User;
use App\Services\SaaS\TenantContextService;

/**
 * Kisi Authorization Policy
 *
 * Phase 2: Authorization Normalization
 * Centralizes all Kisi authorization logic.
 * Sprint 12D: Full tenant isolation enforcement.
 *
 * @see docs/governance/PHASE_1_RUNTIME_CONTAINMENT_PLAN.md
 */
class KisiPolicy
{
    private TenantContextService $tenantContext;

    public function __construct()
    {
        $this->tenantContext = app(TenantContextService::class);
    }

    private function enforceTenantIsolation(Kisi $kisi): bool
    {
        if (!$this->tenantContext->hasTenant()) {
            return false;
        }
        return $kisi->tenant_id === $this->tenantContext->getTenant()->id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        return $user->id === $kisi->danisman_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        return $user->id === $kisi->danisman_id;
    }

    public function delete(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        return $user->id === $kisi->danisman_id;
    }

    public function restore(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function viewPrivateData(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        return $user->id === $kisi->danisman_id;
    }

    public function assignDanisman(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function viewActivityLog(User $user, Kisi $kisi): bool
    {
        if ($this->tenantContext->hasTenant() && !$this->enforceTenantIsolation($kisi)) {
            return false;
        }
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        return $user->id === $kisi->danisman_id;
    }

    public function export(User $user): bool
    {
        return true;
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function viewStatistics(User $user): bool
    {
        return true;
    }

    public function mergeDuplicates(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    // ─── Sprint 12D: Tenant Isolation Helpers ─────────────────────────────

    /**
     * Sprint 12D: Verify Kisi belongs to the current tenant.
     *
     * @throws \RuntimeException if tenant context is not established
     */
    public function belongsToCurrentTenant(Kisi $kisi): bool
    {
        if (!$this->tenantContext->hasTenant()) {
            throw new \RuntimeException(
                'Tenant context not established. Cross-tenant access prevented.'
            );
        }
        return $kisi->tenant_id === $this->tenantContext->getTenant()->id;
    }

    /**
     * Sprint 12D: Cross-tenant prevention gate for PropertyOwnership associations.
     *
     * Kisi and Property must belong to the SAME tenant.
     */
    public function canBeAssociatedWithProperty(Kisi $kisi, int $propertyTenantId): bool
    {
        if (!$this->tenantContext->hasTenant()) {
            return false;
        }
        return $kisi->tenant_id === $propertyTenantId
            && $propertyTenantId === $this->tenantContext->getTenant()->id;
    }
}
