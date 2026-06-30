<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\IlanKategori;
use App\Models\Kisi;
use App\Models\OzellikKategori;
use App\Models\User;
use App\Models\V2\Ilan;
use App\Models\V2\AiIlanTaslagi;
use App\Models\Ilan as MainIlan;
use App\Policies\FeaturePolicy;
use App\Policies\IlanKategoriPolicy;
use App\Policies\KisiPolicy;
use App\Policies\OzellikKategoriPolicy;
use App\Policies\Api\V2\IlanPolicy;
use App\Policies\Api\V2\DraftPolicy;
use App\Policies\IlanPolicy as MainIlanPolicy;
use App\Models\Talep;
use App\Models\Lead;
use App\Policies\TalepPolicy;
use App\Policies\LeadPolicy;
use App\Policies\DanismanPolicy;
use App\Models\OwnerReportRow;
use App\Models\OwnerReportExport;
use App\Policies\OwnerReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Feature::class => FeaturePolicy::class,
        OzellikKategori::class => OzellikKategoriPolicy::class,
        IlanKategori::class => IlanKategoriPolicy::class,
        // 🏠 Main Ilan Policy (Admin CRUD)
        MainIlan::class => MainIlanPolicy::class,
        // 🚀 V2 API Policies
        Ilan::class => IlanPolicy::class,
        AiIlanTaslagi::class => DraftPolicy::class,
        // 📊 Owner Reporting Policies
        OwnerReportRow::class => OwnerReportPolicy::class,
        OwnerReportExport::class => OwnerReportPolicy::class,
        // 👤 CRM Policies (Phase 2: Authorization Normalization)
        Kisi::class => KisiPolicy::class,
        Talep::class => TalepPolicy::class,
        Lead::class => LeadPolicy::class,
        User::class => DanismanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerBladeDirectives();

        // Superadmin otomatik olarak tüm izinlere sahiptir
        Gate::before(function (User $user) {
            // Eager load role relationship if not already loaded
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }

            if (!$user->role) {
                return null; // Let other gates handle it
            }

            // Normalize role name (handle case sensitivity and spaces)
            $roleName = strtolower(trim($user->role->name));

            // Check if user is superadmin (with variations)
            $superadminVariations = ['superadmin', 'super-admin', 'süper admin', 'süperadmin', 'admin'];
            if (in_array($roleName, $superadminVariations)) {
                return true;
            }

            return null; // Let other gates handle it
        });

        // Rol bazlı Gate tanımları
        Gate::define('view-admin-panel', function (User $user) {
            $allowed = [
                'Admin', 'Süper Admin', 'Danışman',
                'admin', 'süper admin', 'danışman', 'editor', 'editör',
            ];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        Gate::define('manage-users', function (User $user) {
            $allowed = ['Süper Admin', 'superadmin', 'süper admin', 'admin'];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        Gate::define('manage-settings', function (User $user) {
            $allowed = ['Süper Admin', 'superadmin', 'süper admin', 'admin'];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        Gate::define('manage-ilanlar', function (User $user) {
            $allowed = ['Süper Admin', 'superadmin', 'admin', 'Danışman', 'danışman', 'danisman'];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        Gate::define('edit-ilanlar', function (User $user) {
            $allowed = [
                'Süper Admin', 'superadmin', 'admin',
                'Danışman', 'danışman', 'danisman', 'Editör', 'editor', 'editör',
            ];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        Gate::define('manage-notifications', function (User $user) {
            $allowed = ['Süper Admin', 'superadmin', 'super-admin', 'süper admin', 'admin'];
            return $user->hasAnyRole($allowed) ||
                   ($user->role && in_array(strtolower(trim($user->role->name)), array_map('strtolower', $allowed)));
        });

        // ✅ SECURITY FIX (2026-01-17): Resource-aware gate with ownership control
        Gate::define('edit-ilan', function (User $user, \App\Models\Ilan $ilan) {
            // Superadmin bypass
            $superadminRoles = ['superadmin', 'süper admin', 'süperadmin', 'admin'];
            if ($user->role && in_array(strtolower(trim($user->role->name)), $superadminRoles)) {
                return true;
            }

            // Danışman/Editör sadece kendi ilanlarını düzenleyebilir
            $allowedRoles = ['danışman', 'danisman', 'editör', 'editor'];
            if ($user->role && in_array(strtolower(trim($user->role->name)), $allowedRoles)) {
                return $user->id === $ilan->danisman_id;
            }

            return false;
        });
    }

    /**
     * Özel Blade direktiflerini kaydet
     */
    private function registerBladeDirectives(): void
    {
        // @role('roleName') or @role('role1', 'role2')
        Blade::directive('role', function ($roles) {
            return "<?php if(Auth::check() && \App\Providers\AuthServiceProvider::hasRole({$roles})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // @admin
        Blade::if('admin', function () {
            return Auth::check() && Auth::user()->role && Auth::user()->role->name === UserRole::SUPERADMIN->value;
        });

        // @danisman
        Blade::if('danisman', function () {
            return Auth::check() && Auth::user()->role && Auth::user()->role->name === UserRole::DANISMAN->value;
        });

        // @editor
        Blade::if('editor', function () {
            return Auth::check() && Auth::user()->role && Auth::user()->role->name === UserRole::EDITOR->value;
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

        // Role ilişkisini kontrol et
        if ($user->role && isset($user->role->name)) {
            $userRole = $user->role->name;
        }
        // Doğrudan role_id özelliğini kontrol et
        elseif (isset($user->role_id)) {
            $userRole = $user->role_id;
        } else {
            return false;
        }

        foreach ($roles as $role) {
            if ($userRole === $role) {
                return true;
            }
        }

        return false;
    }
}
