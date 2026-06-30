<?php

namespace App\Policies;

use App\Models\Ilan;
use App\Models\User;

class IlanPolicy
{
    /**
     * Determine whether the user can view any ilanlar.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the ilan.
     */
    public function view(User $user, Ilan $ilan): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === ($ilan->danisman_id ?? 0);
    }

    /**
     * Determine whether the user can create ilanlar.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the ilan.
     */
    public function update(User $user, Ilan $ilan): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === ($ilan->danisman_id ?? 0);
    }

    /**
     * Determine whether the user can delete the ilan.
     */
    public function delete(User $user, Ilan $ilan): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === ($ilan->danisman_id ?? 0);
    }

    public function viewPrivateListingData(User $user, Ilan $ilan): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        return $user->id === ($ilan->danisman_id ?? 0);
    }

    /**
     * [YALIHAN_REPORTING_0206] Rapor görüntüleme yetkisi
     */
    public function viewIlanRaporu(User $user, Ilan $ilan): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->id === ($ilan->danisman_id ?? 0)) {
            return true;
        }

        return false;
    }
}
