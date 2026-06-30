<?php

namespace App\Policies\Api\V2;

use App\Models\V2\User;
use App\Models\V2\Ilan;
use Illuminate\Auth\Access\Response;

/**
 * Ilan Policy - Authorization rules for Listing operations
 * 
 * Context7: 100% Compliant
 */
class IlanPolicy
{
    /**
     * Determine if the user can view any listings
     */
    public function viewAny(User $user): bool
    {
        return true; // Public endpoint
    }

    /**
     * Determine if the user can view the listing
     */
    public function view(User $user, Ilan $ilan): bool
    {
        return true; // Public endpoint
    }

    /**
     * Determine if the user can create listings
     */
    public function create(User $user): bool
    {
        return $user->aktiflik_durumu && $user->rol !== 'musteri';
    }

    /**
     * Determine if the user can update the listing
     */
    public function update(User $user, Ilan $ilan): Response
    {
        if ($user->id !== $ilan->danisman_id) {
            return Response::deny('Bu ilana erişim izniniz yok');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can delete the listing
     */
    public function delete(User $user, Ilan $ilan): Response
    {
        if ($user->id !== $ilan->danisman_id) {
            return Response::deny('Bu ilana erişim izniniz yok');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can restore the listing
     */
    public function restore(User $user, Ilan $ilan): bool
    {
        return $user->id === $ilan->danisman_id;
    }

    /**
     * Determine if the user can permanently delete the listing
     */
    public function forceDelete(User $user, Ilan $ilan): bool
    {
        return $user->rol === 'admin';
    }
}
