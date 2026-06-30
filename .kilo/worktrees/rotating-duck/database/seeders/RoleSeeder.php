<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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
            Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                $roleData
            );

            $this->command->info("Role created/verified: {$roleData['name']}");
        }

        $this->command->info('✅ RoleSeeder completed successfully');
    }
}
