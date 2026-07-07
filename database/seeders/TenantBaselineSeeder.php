<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'id' => 1,
                'uuid' => (string) Str::uuid(),
                'name' => 'Primary Tenant',
                'domain' => 'primary.yalihan.test',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'uuid' => (string) Str::uuid(),
                'name' => 'Secondary Tenant',
                'domain' => 'secondary.yalihan.test',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 99,
                'uuid' => (string) Str::uuid(),
                'name' => 'Foreign Tenant',
                'domain' => 'foreign.yalihan.test',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tenants as $tenant) {
            DB::table('tenants')->updateOrInsert(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }
}
