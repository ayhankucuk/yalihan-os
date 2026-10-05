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
