<?php

namespace App\Policies;

use App\Models\User;

class DanismanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only admin can view the entire danışman list.
        return $user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Admin can view anyone.
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        // Danışman can ONLY view their own profile.
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admin can create new danışman.
        return $user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin can update anyone.
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        // Danışman can update their OWN profile, BUT role mutation must be prevented in the controller/form request.
        // This policy allows profile update, but role governance must be enforced elsewhere (or using a specific manageRoles method).
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admin can delete danışman.
        return $user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }
    
    /**
     * Distinct authority: Only admin can manage roles/permissions.
     */
    public function manageRoles(User $user, User $model): bool
    {
        return $user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }
}
