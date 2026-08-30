<?php

/**
 * Reset yalihanai_clone location tablolarını ORİJİNAL baseline durumuna döndürür.
 *
 * Orijinal baseline (audits/golden-thread-evidence/BLOKED-reason-report.md):
 *   iller:    3 kayıt — Muğla(id=1, plaka=48), İstanbul(id=2, plaka=34), Ankara(id=3, plaka=6)
 *   ilceler:  5 kayıt — Bodrum(1,il=48), Marmaris(2,il=48), Milas(3,il=48), Beşiktaş(4,il=34), Kadıköy(5,il=34)
 *   mahalleler: 0 kayıt
 *
 * Ayrıca migration kaydını ve log tablolarını temizler.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$db = config('database.connections.mysql.database', 'yalihanai_clone');
echo "Hedef DB: {$db}\n";

// 1. Migration kaydını sil
$deleted = DB::table('migrations')
    ->where('migration', 'like', '%reconcile_location_canonical_plaka_kodu%')
    ->delete();
echo "Migration kaydı silindi: {$deleted}\n";

// 2. Log tablolarını sil
Schema::dropIfExists('location_reconciliation_log');
Schema::dropIfExists('bodrum_fk_reconcile_log');
echo "Log tabloları silindi.\n";

// 3. mahalleler temizle
DB::table('mahalleler')->delete();
echo "mahalleler temizlendi.\n";

// 4. ilceler temizle
DB::table('ilceler')->delete();
echo "ilceler temizlendi.\n";

// 5. iller temizle
DB::table('iller')->delete();
echo "iller temizlendi.\n";

// 6. Orijinal iller ekle
$now = now();
$iller = [
    ['id' => 1,  'il_adi' => 'Muğla',    'plaka_kodu' => '48', 'lat' => 37.2153, 'lng' => 28.3636, 'aktiflik_durumu' => true, 'display_order' => 1],
    ['id' => 2,  'il_adi' => 'İstanbul', 'plaka_kodu' => '34', 'lat' => 41.0082, 'lng' => 28.9784, 'aktiflik_durumu' => true, 'display_order' => 2],
    ['id' => 3,  'il_adi' => 'Ankara',   'plaka_kodu' => '6',  'lat' => 39.9334, 'lng' => 32.8597, 'aktiflik_durumu' => true, 'display_order' => 3],
];
foreach ($iller as $il) {
    DB::table('iller')->insert(array_merge($il, ['created_at' => $now, 'updated_at' => $now]));
}
echo "iller eklendi: " . count($iller) . "\n";

// 7. Orijinal ilceler ekle
$ilceler = [
    ['id' => 1, 'il_id' => 48, 'ilce_adi' => 'Bodrum',   'lat' => null, 'lng' => null, 'aktiflik_durumu' => true, 'display_order' => 1],
    ['id' => 2, 'il_id' => 48, 'ilce_adi' => 'Marmaris', 'lat' => null, 'lng' => null, 'aktiflik_durumu' => true, 'display_order' => 2],
    ['id' => 3, 'il_id' => 48, 'ilce_adi' => 'Milas',    'lat' => null, 'lng' => null, 'aktiflik_durumu' => true, 'display_order' => 3],
    ['id' => 4, 'il_id' => 34, 'ilce_adi' => 'Beşiktaş', 'lat' => null, 'lng' => null, 'aktiflik_durumu' => true, 'display_order' => 4],
    ['id' => 5, 'il_id' => 34, 'ilce_adi' => 'Kadıköy',  'lat' => null, 'lng' => null, 'aktiflik_durumu' => true, 'display_order' => 5],
];
foreach ($ilceler as $ilce) {
    DB::table('ilceler')->insert(array_merge($ilce, ['created_at' => $now, 'updated_at' => $now]));
}
echo "ilceler eklendi: " . count($ilceler) . "\n";

// 8. Doğrulama
$illerCnt = DB::table('iller')->count();
$ilcelerCnt = DB::table('ilceler')->count();
$mahallelerCnt = DB::table('mahalleler')->count();
echo "\n=== RESET DOĞRULAMA ===\n";
echo "iller: {$illerCnt} (beklenen 3)\n";
echo "ilceler: {$ilcelerCnt} (beklenen 5)\n";
echo "mahalleler: {$mahallelerCnt} (beklenen 0)\n";

echo "\nReset tamamlandı.\n";