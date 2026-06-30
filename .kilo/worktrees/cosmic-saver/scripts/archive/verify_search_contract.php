<?php

use App\Models\User;
use App\Models\Ilan;
use App\Models\YazlikFiyatlandirma;
use App\Services\Admin\IlanSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    echo "Starting Verification...\n";
    $user = User::first() ?? User::factory()->create();

    // Create Test Listing without yayin_tipi to avoid legacy FK issues for this test
    $ilan = Ilan::factory()->create([
        'baslik' => 'Contract Test Unit',
        'yayin_durumu' => 'Aktif',
        'user_id' => $user->id,
        'fiyat' => 1000,
        'yayin_tipi_id' => null
    ]);

    // Add Pricing: July 1-31
    YazlikFiyatlandirma::create([
        'ilan_id' => $ilan->id,
        'baslangic_tarihi' => '2026-07-01',
        'bitis_tarihi' => '2026-07-31',
        'fiyat' => 5000,
        'aktiflik_durumu' => true,
    ]);

    $service = new IlanSearchService();

    // TEST 1: Search for Available Dates (July 5-10) -> Should Match
    $req1 = Request::create('/api/search', 'GET', [
        'check_in' => '2026-07-05',
        'check_out' => '2026-07-10',
        'q' => 'Contract Test' // Ensure we only get our test unit
    ]);

    $result1 = $service->search($req1)->get();
    echo "Test 1 (Available): Found " . $result1->count() . " (Expect 1)\n";

    // TEST 2: Search for Unavailable Dates (June 1-5) -> Should NOT Match
    $req2 = Request::create('/api/search', 'GET', [
        'check_in' => '2026-06-01',
        'check_out' => '2026-06-05',
        'q' => 'Contract Test'
    ]);

    $result2 = $service->search($req2)->get();
    echo "Test 2 (Unavailable): Found " . $result2->count() . " (Expect 0)\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} finally {
    DB::rollBack();
    echo "Transaction Rolled Back.\n";
}
