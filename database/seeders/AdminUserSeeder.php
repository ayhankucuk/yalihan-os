<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Canonical super-admin bootstrap seeder.
     *
     * Fixes: tenant_id=NULL and role_id=NULL which blocked post-login
     * redirect to admin.dashboard.index.
     *
     * Requires: TenantBaselineSeeder and RoleSeeder run first.
     */
    public function run(): void
    {
        // Resolve canonical super-admin tenant (id=1 from TenantBaselineSeeder)
        $tenantId = DB::table('tenants')->where('id', 1)->value('id') ?? 1;

        // Resolve super-admin role_id from roles table
        $superAdminRoleId = DB::table('roles')
            ->where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->value('id');

        $admin = User::firstOrCreate(
            ['email' => 'ayhankucuk@gmail.com'],
            [
                'name'               => 'Ayhan Küçük',
                'password'           => Hash::make('admin123'),
                'email_verified_at'  => now(),
                'ulke_id'            => 1,
                'tenant_id'          => $tenantId,
                'role_id'            => $superAdminRoleId,
                'aktiflik_durumu'    => 1,
            ]
        );

        // Yalıhan Emlak super-admin
        $yalihan = User::firstOrCreate(
            ['email' => 'yalihanemlak@gmail.com'],
            [
                'name'               => 'Yalıhan Emlak',
                'password'           => Hash::make('admin123'),
                'email_verified_at'  => now(),
                'ulke_id'            => 1,
                'tenant_id'          => $tenantId,
                'role_id'            => $superAdminRoleId,
                'aktiflik_durumu'    => 1,
            ]
        );

        // Backfill tenant_id / role_id for already-existing records
        foreach ([$admin, $yalihan] as $user) {
            $dirty = [];

            if (empty($user->tenant_id)) {
                $dirty['tenant_id'] = $tenantId;
            }

            if (empty($user->role_id) && $superAdminRoleId) {
                $dirty['role_id'] = $superAdminRoleId;
            }

            if (!empty($dirty)) {
                $user->forceFill($dirty)->saveQuietly();
            }
        }

        // Ensure Spatie super-admin role is assigned to both users
        if (class_exists(Role::class)) {
            $spatieRole = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            foreach ([$admin, $yalihan] as $user) {
                // getRoleNames() uses Spatie model_has_roles pivot
                if (!$user->hasRole('super-admin')) {
                    $user->assignRole($spatieRole);
                }
            }
        }

        $this->command->info('✅ AdminUserSeeder: tenant_id=' . $tenantId . ', role_id=' . ($superAdminRoleId ?? 'NULL'));
    }
}
