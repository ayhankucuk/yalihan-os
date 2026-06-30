<?php

namespace App\Modules\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('crm_tags')->insert([
            ['name' => 'VIP', 'color' => '#f59e0b', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Takip', 'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
