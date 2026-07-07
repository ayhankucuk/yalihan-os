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
     */
    public function view(User $user, PortfolioDriveWorkspace $workspace): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        // Tenant isolation — SAB Rule 1
        if ($workspace->tenant_id !== null) {
            return $user->tenant_id === $workspace->tenant_id;
        }

        return true;
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
        return $user->hasRole(['admin', 'super-admin']);
    }
}
