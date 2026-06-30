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
        // Spatie & Legacy accepted roles
        $accepted = [
            'admin', 'superadmin', 'editor',
            'Admin', 'Süper Admin', 'Editör',
            'süper admin', 'süperadmin',
            UserRole::SUPERADMIN->value,
            UserRole::EDITOR->value,
        ];

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($accepted)) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($accepted as $roleName) {
                if ($user->hasRole($roleName)) {
                    return true;
                }
            }
        }

        // Fallback to legacy role relationship check
        if ($user->role) {
            $currentRoleName = strtolower(trim($user->role->name));
            $adminRoles = ['admin', 'superadmin', 'super admin', 'süper admin', 'süperadmin', 'editor', 'editör'];
            return in_array($currentRoleName, $adminRoles);
        }

        return false;
    }
}
