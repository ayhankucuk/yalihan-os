<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lead $lead): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === $lead->assigned_agent_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lead $lead): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === $lead->assigned_agent_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lead $lead): bool
    {
        if ($user->hasRole(['admin', 'super-admin']) || (method_exists($user, 'isAdmin') && $user->isAdmin())) {
            return true;
        }

        return $user->id === $lead->assigned_agent_id;
    }
}
