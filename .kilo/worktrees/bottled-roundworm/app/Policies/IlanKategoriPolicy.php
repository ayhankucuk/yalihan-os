<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\IlanKategori;
use App\Models\User;

class IlanKategoriPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, IlanKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, IlanKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, IlanKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, IlanKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, IlanKategori $kategori): bool
    {
        return $this->canManage($user);
    }

    public function export(User $user): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        // 🚀 Context7: Superadmin check - Always allow
        if ($user->role) {
            $roleName = strtolower(trim($user->role->name));
            if (in_array($roleName, ['admin', 'superadmin', 'super admin', 'süper admin', 'süperadmin'])) {
                return true;
            }
        }

        // Öncelikle açıkça danışman (sadece danışman hakları) ise reddet
        if (method_exists($user, 'hasRole') && $user->hasRole('danisman')) {
            // Kullanıcı sadece danışman mı kontrolü: admin/editor rollerine sahip değilse direkt red
            $elevated = ['admin', 'editor', 'superadmin'];
            foreach ($elevated as $e) {
                if (method_exists($user, 'hasRole') && $user->hasRole($e)) {
                    return true;
                }
            }

            return false;
        }
        // Spatie roles varsa
        // Yönetim yetkisi olan roller
        $roleNames = [
            'admin',
            'superadmin',
            'editor',
            'Admin',
            'Süper Admin',
            'Editör',
            'süper admin',
            'süperadmin',
            UserRole::SUPERADMIN->value,
            UserRole::EDITOR->value,
        ];

        if (method_exists($user, 'hasAnyRole')) {
            // Case-insensitive check if possible, or just use the list
            if ($user->hasAnyRole($roleNames)) {
                return true;
            }
        } elseif (method_exists($user, 'hasRole')) {
            foreach ($roleNames as $r) {
                if ($user->hasRole($r)) {
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
