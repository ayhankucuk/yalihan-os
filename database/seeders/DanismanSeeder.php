<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kisi;
use Illuminate\Database\Seeder;
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
     */
    public function run(): void
    {
        // Environment guard - only run in local/dev/test
        if (app()->environment('production', 'staging')) {
            $this->command->warn('Skipping DanismanSeeder in production/staging environment');
            return;
        }

        // Ensure danisman role exists
        $danismanRole = Role::firstOrCreate(
            ['name' => 'danisman', 'guard_name' => 'web']
        );

        $danismanlar = [
            [
                'name' => 'Atılay',
                'email' => 'atilay@yalihan.test',
                'telefon' => '+90 532 111 1111',
                'specialization' => 'Luxury Properties',
            ],
            [
                'name' => 'Sedat',
                'email' => 'sedat@yalihan.test',
                'telefon' => '+90 532 222 2222',
                'specialization' => 'Commercial Properties',
            ],
            [
                'name' => 'Yunus',
                'email' => 'yunus@yalihan.test',
                'telefon' => '+90 532 333 3333',
                'specialization' => 'Residential Properties',
            ],
        ];

        foreach ($danismanlar as $danismanData) {
            // Create or update User
            $user = User::firstOrCreate(
                ['email' => $danismanData['email']],
                [
                    'name' => $danismanData['name'],
                    'password' => Hash::make('test123'),
                    'email_verified_at' => now(),
                    'ulke_id' => 1, // TR
                ]
            );

            // Assign danisman role if not already assigned
            if (!$user->hasRole('danisman')) {
                $user->assignRole($danismanRole);
            }

            // Create or update Kisi record
            $kisi = Kisi::firstOrCreate(
                ['eposta' => $danismanData['email']],
                [
                    'ad' => $danismanData['name'],
                    'soyad' => 'Danışman', // Generic surname for test
                    'telefon' => $danismanData['telefon'],
                    'kisi_tipi' => \App\Enums\KisiTipi::DANISMAN,
                    'aktiflik_durumu' => 1,
                    'notlar' => "Test danışman - {$danismanData['specialization']}",
                    'ulke_id' => 1, // TR
                ]
            );

            $this->command->info("✅ Danışman created/verified: {$danismanData['name']} ({$danismanData['email']})");
        }

        $this->command->info('✅ DanismanSeeder completed successfully');
        $this->command->info('📧 Test credentials: email@yalihan.test / password: test123');
    }
}
