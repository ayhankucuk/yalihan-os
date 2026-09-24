<?php

namespace App\Policies;

use App\Models\PortfolioDriveWorkspace;
use App\Models\User;

/**
 * PortfolioDriveWorkspacePolicy
 *
 * Sprint 4.6: Property Digital Twin Cockpit
 *
 * Tenant isolation policy — SPRINT AUTHORITY RULE 1.
 * Cross-tenant access to workspaces is strictly forbidden.
 */
class PortfolioDriveWorkspacePolicy
{
    /**
     * Admin-only access for cockpit views.
     *
     * Rule 1: super-admin preserves global cross-tenant platform authority.
     * Rule 2: tenant admin is restricted strictly to their own tenant workspace.
     * Rule 3: cross-tenant access is strictly forbidden (SAB Rule 1).
     * Rule 4: null-tenant workspace behavior is preserved.
     */
    public function view(User $user, PortfolioDriveWorkspace $workspace): bool
    {
        // 1. Super-admin preserves global cross-tenant platform authority
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Tenant isolation — SAB Rule 1: Cross-tenant access is strictly forbidden
        if ($workspace->tenant_id !== null) {
            if ($user->tenant_id !== $workspace->tenant_id) {
                return false;
            }
        }

        // 3. Within own tenant (or null-tenant workspace): must be admin
        if ($user->hasRole('admin') || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $workspace->tenant_id === null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function update(User $user, PortfolioDriveWorkspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function delete(User $user, PortfolioDriveWorkspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }
}
