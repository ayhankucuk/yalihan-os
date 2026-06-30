<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\OzellikKategori;
use App\Models\User;

class OzellikKategoriPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, OzellikKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, OzellikKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, OzellikKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, OzellikKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, OzellikKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        // Spatie roles check
        $roleNames = [
            'admin', 'superadmin', 'editor',
            'Admin', 'Süper Admin', 'Editör',
            'süper admin', 'süperadmin',
            UserRole::SUPERADMIN->value,
            UserRole::EDITOR->value,
        ];

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($roleNames)) {
                return true;
            }
        }

        // Fallback to legacy role check
        if ($user->role) {
            $currentRoleName = strtolower(trim($user->role->name));
            $adminRoles = ['admin', 'superadmin', 'super admin', 'süper admin', 'süperadmin', 'editor', 'editör'];
            return in_array($currentRoleName, $adminRoles);
        }

        return false;
    }
}
