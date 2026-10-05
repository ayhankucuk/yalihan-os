<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\User;

class FeaturePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Feature $feature): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Feature $feature): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Feature $feature): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Feature $feature): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Feature $feature): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        // SECURITY FIX: Canonical super-admin via Spatie
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admin and other elevated roles via Spatie
        if ($user->hasAnyRole(['admin', 'editor'])) {
            return true;
        }

        return false;
    }
}
