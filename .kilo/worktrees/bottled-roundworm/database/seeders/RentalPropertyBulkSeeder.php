<?php

namespace Database\Seeders;

use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentalPropertyBulkSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve required FK values from database
        $yayinTipiId = DB::table('yayin_tipleri')->value('id') ?? 1;
        $anaKategoriId = DB::table('ilan_kategorileri')->whereNull('parent_id')->value('id') ?? 1;
        $userId = DB::table('users')->value('id');

        if (!$userId) {
            $this->command->error('No user found in database. Please seed users first.');
            return;
        }

        $this->command->info("Seeding 100 rental properties...");
        $bar = $this->command->getOutput()->createProgressBar(100);
        $bar->start();

        $propertyIds = [];
        $now = now();

        for ($i = 1; $i <= 100; $i++) {
            $baslik = "Enterprise Test Property {$i}";
            $slug = 'enterprise-test-prop-' . $i . '-' . Str::random(6);

            $propertyId = DB::table('ilanlar')->insertGetId([
                'baslik'         => $baslik,
                'slug'           => $slug,
                'fiyat'          => rand(500, 5000),
                'para_birimi'    => 'TRY',
                'yayin_durumu'   => 'Aktif',
                'danisman_id'    => $userId,
                'ana_kategori_id'=> $anaKategoriId,
                'il_id'          => 1,
                'ilce_id'        => 1,
                'mahalle_id'     => 1,
                'yayin_tipi_id'  => $yayinTipiId,
                'brut_m2'        => rand(60, 300),
                'net_m2'         => rand(50, 250),
                'user_id'        => $userId,
                'rental_enabled' => true,
                'min_stay_nights'=> rand(1, 3),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $propertyIds[] = $propertyId;

            // Seed 60 days of availability for each property
            $startDate = Carbon::today();
            $availabilityData = [];
            for ($day = 0; $day < 60; $day++) {
                $availabilityData[] = [
                    'property_id'   => $propertyId,
                    'date'          => $startDate->copy()->addDays($day)->format('Y-m-d'),
                    'is_available'  => true,
                    'source_system' => 'internal',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            PropertyAvailability::insertOrIgnore($availabilityData);

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ 100 properties seeded with 60-day availability windows.");
        $this->command->info("Property IDs: " . $propertyIds[0] . " → " . end($propertyIds));
    }
}
