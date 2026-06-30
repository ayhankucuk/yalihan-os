<?php

namespace App\Traits;

use App\Services\MenuService;
use Illuminate\Support\Facades\Auth;

/**
 * AdminMenu Trait
 * Context7: Admin menü yönetimi için trait
 *
 * Provides adminMenu() method for AdminController and AdminMenuServiceProvider
 */
trait AdminMenu
{
    /**
     * Get admin menu items
     * Context7: MenuService kullanarak menü öğelerini getir
     */
    public function adminMenu(): array
    {
        $menuService = app(MenuService::class);
        $user = Auth::user();

        // Kullanıcının rolünü belirle
        $role = 'user'; // Default

        if ($user) {
            if ($user->hasRole('superadmin')) {
                $role = 'superadmin';
            } elseif ($user->hasRole('admin')) {
                $role = 'admin';
            } elseif ($user->hasRole('danisman')) {
                $role = 'danisman';
            } elseif ($user->hasRole('editor')) {
                $role = 'danisman'; // Editor de danışman menüsünü görür
            }
        }

        return $menuService->getMenuForRole($role, $user?->id);
    }
}
