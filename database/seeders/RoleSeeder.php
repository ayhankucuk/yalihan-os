<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates core roles for the system:
     * - super-admin: Full system access
     * - admin: Administrative access
     * - danisman: Consultant access
     * - musteri: Customer access (optional)
     * - owner: Mülk sahibi portalı erişimi
     */
    public function run(): void
    {
        // Environment guard - only run in local/dev/test
        if (app()->environment('production', 'staging')) {
            $this->command->warn('Skipping RoleSeeder in production/staging environment');
            return;
        }

        $roles = [
            [
                'name' => 'super-admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'admin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'danisman',
                'guard_name' => 'web',
            ],
            [
                'name' => 'musteri',
                'guard_name' => 'web',
            ],
            [
                'name' => 'owner',
                'guard_name' => 'web',
                // Mülk sahibi: /owner portalına erişim
                // Kisi modeline bağlı kullanıcılar bu role atanır
            ],
        ];

        foreach ($roles as $roleData) {
            // Find existing role by name+guard_name using raw query to bypass all Eloquent
            // global scopes (CountryScope). CountryScope adds WHERE ulke_id=... when
            // roles table has ulke_id AND user is authenticated. Using raw query ensures
            // we always find historical roles regardless of ulke_id mismatches.
            $existing = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', $roleData['name'])
                ->where('guard_name', $roleData['guard_name'])
                ->first();

            if ($existing) {
                // Update existing row to canonical values (name convergence)
                \Illuminate\Support\Facades\DB::table('roles')
                    ->where('id', $existing->id)
                    ->update(['name' => $roleData['name']]);
            } else {
                // Insert new canonical role
                \Illuminate\Support\Facades\DB::table('roles')
                    ->insert([
                        'name' => $roleData['name'],
                        'guard_name' => $roleData['guard_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $this->command?->info("Role created/verified: {$roleData['name']}");
        }

        $this->command?->info('✅ RoleSeeder completed successfully');
    }
}
