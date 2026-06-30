<?php

namespace Database\Seeders;

use App\Models\Kisi;
use App\Models\User;
use Illuminate\Database\Seeder;

class MusteriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates deterministic customer personas for workflow validation.
     * Note: Customers don't have User accounts, only Kisi records.
     */
    public function run(): void
    {
        // Environment guard - only run in local/dev/test
        if (app()->environment('production', 'staging')) {
            $this->command->warn('Skipping MusteriSeeder in production/staging environment');
            return;
        }

        // Get danışman users for assignment
        $atilay = User::where('email', 'atilay@yalihan.test')->first();
        $sedat = User::where('email', 'sedat@yalihan.test')->first();
        $yunus = User::where('email', 'yunus@yalihan.test')->first();

        if (!$atilay || !$sedat || !$yunus) {
            $this->command->warn('⚠️  Danışman users not found. Run DanismanSeeder first.');
            return;
        }

        $musteriler = [
            [
                'ad' => 'Ahmet',
                'soyad' => 'Yılmaz',
                'email' => 'ahmet.yilmaz@test.com',
                'telefon' => '+90 532 444 1111',
                'danisman_id' => $atilay->id,
                'notlar' => 'Test müşteri - High budget buyer (Luxury segment)',
                'kisi_tipi' => \App\Enums\KisiTipi::ALICI,
                'type' => 'Buyer',
            ],
            [
                'ad' => 'Ayşe',
                'soyad' => 'Demir',
                'email' => 'ayse.demir@test.com',
                'telefon' => '+90 532 444 2222',
                'danisman_id' => $sedat->id,
                'notlar' => 'Test müşteri - Luxury property seller',
                'kisi_tipi' => \App\Enums\KisiTipi::SATICI,
                'type' => 'Seller',
            ],
            [
                'ad' => 'Mehmet',
                'soyad' => 'Kaya',
                'email' => 'mehmet.kaya@test.com',
                'telefon' => '+90 532 444 3333',
                'danisman_id' => $yunus->id,
                'notlar' => 'Test müşteri - Commercial rental seeker',
                'kisi_tipi' => \App\Enums\KisiTipi::KIRACI,
                'type' => 'Renter',
            ],
            [
                'ad' => 'Fatma',
                'soyad' => 'Şahin',
                'email' => 'fatma.sahin@test.com',
                'telefon' => '+90 532 444 4444',
                'danisman_id' => $atilay->id,
                'notlar' => 'Test müşteri - Portfolio investor',
                'kisi_tipi' => \App\Enums\KisiTipi::YATIRIMCI,
                'type' => 'Investor',
            ],
            [
                'ad' => 'Ali',
                'soyad' => 'Çelik',
                'email' => 'ali.celik@test.com',
                'telefon' => '+90 532 444 5555',
                'danisman_id' => $sedat->id,
                'notlar' => 'Test müşteri - First-time home buyer',
                'kisi_tipi' => \App\Enums\KisiTipi::ALICI,
                'type' => 'First-time Buyer',
            ],
        ];

        foreach ($musteriler as $musteriData) {
            $kisi = Kisi::firstOrCreate(
                ['eposta' => $musteriData['email']],
                [
                    'ad' => $musteriData['ad'],
                    'soyad' => $musteriData['soyad'],
                    'telefon' => $musteriData['telefon'],
                    'kisi_tipi' => $musteriData['kisi_tipi'],
                    'aktiflik_durumu' => 1,
                    'danisman_id' => $musteriData['danisman_id'],
                    'notlar' => $musteriData['notlar'],
                    'ulke_id' => 1, // TR
                ]
            );

            $danismanName = User::find($musteriData['danisman_id'])->name ?? 'Unknown';
            $this->command->info("✅ Müşteri created/verified: {$musteriData['ad']} {$musteriData['soyad']} (Assigned to: {$danismanName})");
        }

        $this->command->info('✅ MusteriSeeder completed successfully');
        $this->command->info('📝 Note: Customers have no login accounts, only CRM records');
    }
}
