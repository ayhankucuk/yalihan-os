<?php

use App\Models\Ilan;
use App\Models\User;
use App\Models\YazlikFiyatlandirma;
use App\Models\YazlikRezervasyon;
use App\Services\Admin\ReportingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Prevent verify script from polluting DB permanently
DB::beginTransaction();

try {
    $user = User::first() ?? User::factory()->create();

    // 0. Seed missing Publication Type if needed
    if (DB::table('eski_ilan_kategori_yayin_tipleri')->where('id', 3)->doesntExist()) {
         DB::table('eski_ilan_kategori_yayin_tipleri')->insert([
            'id' => 3,
            'yayin_tipi' => 'Günlük Kiralık',
            'aktiflik_durumu' => 1,
            'kategori_id' => 1
        ]);
    }

    // 1. Create Listings
    $listing = Ilan::factory()->create([
        'baslik' => 'Test Unit Metrics',
        'yayin_durumu' => 'Aktif',
        'ilan_sahibi_id' => $user->id,
        'fiyat' => 1000,
        'yayin_tipi_id' => 3
    ]);

    echo "Listing Created: ID {$listing->id}\n";

    // 2. Setup Scenario
    // Report Period: June 1 - June 30 (30 Days)
    $startDate = Carbon::create(2026, 6, 1, 0, 0, 0);
    $endDate = Carbon::create(2026, 6, 30, 23, 59, 59);

    // Blocked: June 5 - June 6 (2 Days)
    YazlikFiyatlandirma::create([
        'ilan_id' => $listing->id,
        'baslangic_tarihi' => '2026-06-05',
        'bitis_tarihi' => '2026-06-06',
        'fiyat' => 0,
        'aktiflik_durumu' => false, // Blocked
        'donem_adi' => 'kis' // Required ENUM
    ]);
    // Note: Total Days 30. Blocked 2. Available = 28.

    // Res 1: June 1 - June 3 (2 Nights). Price 2000.
    YazlikRezervasyon::create([
        'ilan_id' => $listing->id,
        'user_id' => $user->id,
        'musteri_adi' => $user->name ?? 'Test Client',
        'musteri_telefon' => '5551234567',
        'musteri_email' => 'test@example.com',
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-03',
        'toplam_fiyat' => 2000,
        'rezervasyon_durumu' => 'onaylandi',
        'yetiskin_sayisi' => 1, 'cocuk_sayisi' => 0 // Required
    ]);

    // Res 2: June 10 - June 11 (1 Night). Price 1500. Pending.
    YazlikRezervasyon::create([
        'ilan_id' => $listing->id,
        'user_id' => $user->id,
        'musteri_adi' => $user->name ?? 'Test Client',
        'musteri_telefon' => '5551234567',
        'musteri_email' => 'test@example.com',
        'check_in' => '2026-06-10',
        'check_out' => '2026-06-11',
        'toplam_fiyat' => 1500,
        'rezervasyon_durumu' => 'beklemede',
        'yetiskin_sayisi' => 1, 'cocuk_sayisi' => 0
    ]);

    // Res 3: June 29 - July 2 (3 Nights). Price 3000.
    // Overlap June: June 29, June 30 (2 Nights). Revenue 2000.
    YazlikRezervasyon::create([
        'ilan_id' => $listing->id,
        'user_id' => $user->id,
        'musteri_adi' => $user->name ?? 'Test Client',
        'musteri_telefon' => '5551234567',
        'musteri_email' => 'test@example.com',
        'check_in' => '2026-06-29',
        'check_out' => '2026-07-02',
        'toplam_fiyat' => 3000,
        'rezervasyon_durumu' => 'onaylandi',
        'yetiskin_sayisi' => 1, 'cocuk_sayisi' => 0
    ]);

    // 3. Expected Values
    // Booked Days: 2 (Res1) + 1 (Res2) + 2 (Res3) = 5
    // Available Days: 30 - 2 = 28
    // Rev: 2000 + 1500 + 2000 = 5500

    $expBooked = 5;
    $expAvail = 28;
    $expRev = 5500;

    $expOcc = round(($expBooked / $expAvail) * 100, 2); // 17.86
    $expADR = round($expRev / $expBooked, 2); // 1100.00
    $expRevPAR = round($expADR * ($expOcc / 100), 2); // 196.46

    echo "Expected Inventory: Available $expAvail, Booked $expBooked\n";
    echo "Expected Occupancy: $expOcc %\n";
    echo "Expected Revenue: $expRev\n";
    echo "Expected ADR: $expADR\n";
    echo "Expected RevPAR: $expRevPAR\n";

    // 4. Run Service
    $service = new ReportingService();
    $actOcc = $service->calculateOccupancy($listing->id, $startDate, $endDate);
    $actADR = $service->calculateADR($listing->id, $startDate, $endDate);
    $actRevPAR = $service->calculateRevPAR($listing->id, $startDate, $endDate);

    echo "\nACTUAL RESULTS:\n";
    echo "Occupancy: $actOcc %\n";
    echo "ADR: $actADR\n";
    echo "RevPAR: $actRevPAR\n";

    if (abs($actOcc - $expOcc) < 0.01 && abs($actADR - $expADR) < 0.01) {
        echo "\n✅ VERIFICATION PASSED\n";
    } else {
        echo "\n❌ VERIFICATION FAILED\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    DB::rollBack();
    echo "\nRolled back transaction.\n";
}
