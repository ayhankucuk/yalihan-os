<?php

namespace Database\Seeders;

use App\Models\Kisi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DanismanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates deterministic consultant personas for workflow validation:
     * - Atılay (Luxury specialist)
     * - Sedat (Commercial specialist)
     * - Yunus (Residential specialist)
     *
     * Fixes applied (2026-08-24):
     * - users.tenant_id set to canonical tenant (required by StoreIlanRequest tenant scope)
     * - users.aktiflik_durumu set to 1 (required by UserController::search filter)
     * - users.role_id set to danisman role_id (internal FK)
     * - kisiler.tenant_id set (NOT NULL constraint since migration 2026_07_18_011152)
     * - kisiler.danisman_id linked back to the created user
     * - Idempotent backfill for pre-existing records
     */
    public function run(): void
    {
        // Environment guard - only run in local/dev/test
        if (app()->environment('production', 'staging')) {
            $this->command->warn('Skipping DanismanSeeder in production/staging environment');
            return;
        }

        // Resolve canonical tenant (id=1 from TenantBaselineSeeder)
        $tenantId = DB::table('tenants')->where('id', 1)->value('id') ?? 1;

        // Ensure danisman Spatie role exists
        $spatieRole = Role::firstOrCreate(
            ['name' => 'danisman', 'guard_name' => 'web']
        );

        // Resolve internal role_id from roles table (same row)
        $danismanRoleId = DB::table('roles')
            ->where('name', 'danisman')
            ->where('guard_name', 'web')
            ->value('id');

        $danismanlar = [
            [
                'name'           => 'Atılay',
                'email'          => 'atilay@yalihan.test',
                'telefon'        => '+90 532 111 1111',
                'specialization' => 'Luxury Properties',
            ],
            [
                'name'           => 'Sedat',
                'email'          => 'sedat@yalihan.test',
                'telefon'        => '+90 532 222 2222',
                'specialization' => 'Commercial Properties',
            ],
            [
                'name'           => 'Yunus',
                'email'          => 'yunus@yalihan.test',
                'telefon'        => '+90 532 333 3333',
                'specialization' => 'Residential Properties',
            ],
        ];

        foreach ($danismanlar as $data) {
            // ── 1. User ───────────────────────────────────────────────────────
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('test123'),
                    'email_verified_at' => now(),
                    'ulke_id'           => 1,
                    'tenant_id'         => $tenantId,
                    'role_id'           => $danismanRoleId,
                    'aktiflik_durumu'   => 1,
                ]
            );

            // Idempotent backfill for pre-existing records missing these fields
            $dirty = [];
            if (empty($user->tenant_id))       $dirty['tenant_id']       = $tenantId;
            if (empty($user->role_id))         $dirty['role_id']         = $danismanRoleId;
            if (empty($user->aktiflik_durumu)) $dirty['aktiflik_durumu'] = 1;

            if (!empty($dirty)) {
                $user->forceFill($dirty)->saveQuietly();
            }

            // Ensure Spatie danisman role is assigned (idempotent sync)
            $user->syncRoles([$spatieRole]);

            // ── 2. Kisi ───────────────────────────────────────────────────────
            $kisi = Kisi::firstOrCreate(
                ['eposta' => $data['email']],
                [
                    'ad'               => $data['name'],
                    'soyad'            => 'Danışman',
                    'telefon'          => $data['telefon'],
                    'kisi_tipi'        => \App\Enums\KisiTipi::DANISMAN,
                    'aktiflik_durumu'  => 1,
                    'notlar'           => "Test danışman - {$data['specialization']}",
                    'ulke_id'          => 1,
                    'tenant_id'        => $tenantId,  // NOT NULL — required
                    'danisman_id'      => $user->id,  // Link kisi → user
                ]
            );

            // Idempotent backfill for pre-existing kisi records
            $kisiDirty = [];
            if (empty($kisi->tenant_id))   $kisiDirty['tenant_id']   = $tenantId;
            if (empty($kisi->danisman_id)) $kisiDirty['danisman_id'] = $user->id;

            if (!empty($kisiDirty)) {
                $kisi->forceFill($kisiDirty)->saveQuietly();
            }

            $this->command->info("✅ Danışman created/verified: {$data['name']} ({$data['email']}) tenant_id={$tenantId}");
        }

        $this->command->info('✅ DanismanSeeder completed successfully');
        $this->command->info('📧 Test credentials: atilay/sedat/yunus@yalihan.test / password: test123');
    }
}
