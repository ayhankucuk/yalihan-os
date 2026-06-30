<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class RoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // @role('roleName') or @role('role1', 'role2')
        Blade::directive('role', function ($roles) {
            return "<?php if(\Illuminate\Support\Facades\Auth::check() && \App\Providers\RoleServiceProvider::hasRole({$roles})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @admin
        Blade::if('admin', function () {
            if (!Auth::check()) return false;
            $user = Auth::user();
            $allowed = ['Süper Admin', 'superadmin', 'süper admin', 'admin'];
            return $user->hasAnyRole($allowed) || 
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        // @danisman
        Blade::if('danisman', function () {
            if (!Auth::check()) return false;
            $user = Auth::user();
            $allowed = ['Danışman', 'danışman', 'danisman'];
            return $user->hasAnyRole($allowed) || 
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        // @editor
        Blade::if('editor', function () {
            if (!Auth::check()) return false;
            $user = Auth::user();
            $allowed = ['Editör', 'editor', 'editör'];
            return $user->hasAnyRole($allowed) || 
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });
    }

    /**
     * Kullanıcının belirtilen rollerden birine sahip olup olmadığını kontrol eder.
     *
     * @param  string|array  ...$roles
     */
    public static function hasRole(...$roles): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        // Normalize requested roles
        $requestedRoles = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $requestedRoles = array_merge($requestedRoles, $role);
            } else {
                $requestedRoles[] = $role;
            }
        }
        $normalizedRequested = array_map('strtolower', array_map('trim', $requestedRoles));

        // Spatie check (case-insensitive intersect)
        $userSpatieRoles = $user->getRoleNames()->map(fn($r) => strtolower(trim($r)))->toArray();
        if (count(array_intersect($userSpatieRoles, $normalizedRequested)) > 0) {
            return true;
        }

        // Legacy check
        $userRoleName = $user->role->name ?? null;
        if ($userRoleName && in_array(strtolower(trim($userRoleName)), $normalizedRequested)) {
            return true;
        }

        return false;
    }
}
