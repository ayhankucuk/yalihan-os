<?php

namespace App\Policies;

use App\Models\Kisi;
use App\Models\User;

/**
 * Kisi Authorization Policy
 *
 * Phase 2: Authorization Normalization
 * Centralizes all Kisi authorization logic following Laravel best practices.
 *
 * @see docs/governance/PHASE_1_RUNTIME_CONTAINMENT_PLAN.md
 */
class KisiPolicy
{
    /**
     * Determine whether the user can view any kisiler.
     *
     * Used for: Index pages, list operations
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view kisiler list
        // (Repository layer enforces ownership filtering)
        return true;
    }

    /**
     * Determine whether the user can view the kisi.
     *
     * Used for: Show pages, detail views
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function view(User $user, Kisi $kisi): bool
    {
        // Admin: can view all
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Danışman: can view only their kisiler
        return $user->id === $kisi->danisman_id;
    }

    /**
     * Determine whether the user can create kisiler.
     *
     * Used for: Create forms, store operations
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // All authenticated users can create kisiler
        return true;
    }

    /**
     * Determine whether the user can update the kisi.
     *
     * Used for: Edit forms, update operations
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function update(User $user, Kisi $kisi): bool
    {
        // Admin: can update all
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Danışman: can update only their kisiler
        return $user->id === $kisi->danisman_id;
    }

    /**
     * Determine whether the user can delete the kisi.
     *
     * Used for: Delete operations (soft delete)
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function delete(User $user, Kisi $kisi): bool
    {
        // Admin: can delete all
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Danışman: can delete only their kisiler
        return $user->id === $kisi->danisman_id;
    }

    /**
     * Determine whether the user can restore the kisi.
     *
     * Used for: Restore operations (undelete)
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function restore(User $user, Kisi $kisi): bool
    {
        // Only admins can restore
        return $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can permanently delete the kisi.
     *
     * Used for: Force delete operations (permanent)
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function forceDelete(User $user, Kisi $kisi): bool
    {
        // Only admins can force delete
        return $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can view private/sensitive kisi data.
     *
     * Used for: TC Kimlik, financial data, sensitive notes
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function viewPrivateData(User $user, Kisi $kisi): bool
    {
        // Admin: can view all private data
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Danışman: can view private data of their kisiler
        return $user->id === $kisi->danisman_id;
    }

    /**
     * Determine whether the user can assign/reassign kisi to another danisman.
     *
     * Used for: Danisman assignment operations
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function assignDanisman(User $user, Kisi $kisi): bool
    {
        // Only admins can reassign kisiler
        return $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can view kisi activity log.
     *
     * Used for: Activity log, audit trail
     *
     * @param User $user
     * @param Kisi $kisi
     * @return bool
     */
    public function viewActivityLog(User $user, Kisi $kisi): bool
    {
        // Admin: can view all activity logs
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Danışman: can view activity log of their kisiler
        return $user->id === $kisi->danisman_id;
    }

    /**
     * Determine whether the user can export kisi data.
     *
     * Used for: Export operations, reports
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        // All authenticated users can export
        // (Repository layer enforces ownership filtering)
        return true;
    }

    /**
     * Determine whether the user can bulk update kisiler.
     *
     * Used for: Bulk operations
     *
     * @param User $user
     * @return bool
     */
    public function bulkUpdate(User $user): bool
    {
        // Only admins can perform bulk updates
        return $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can view kisi statistics.
     *
     * Used for: Dashboard, analytics
     *
     * @param User $user
     * @return bool
     */
    public function viewStatistics(User $user): bool
    {
        // All authenticated users can view statistics
        // (Repository layer enforces ownership filtering)
        return true;
    }

    /**
     * Determine whether the user can merge duplicate kisiler.
     *
     * Used for: Duplicate resolution
     *
     * @param User $user
     * @return bool
     */
    public function mergeDuplicates(User $user): bool
    {
        // Only admins can merge duplicates
        return $user->hasRole(['admin', 'super-admin']);
    }
}
