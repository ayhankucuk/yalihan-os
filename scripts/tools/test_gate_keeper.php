<?php

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\IlanKategoriYayinTipi;
use App\Services\AI\YalihanCortex;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Mock Authentication
$user = User::find(85);
if (!$user) {
    echo "User 85 not found!\n";
    exit(1);
}
Auth::login($user);

// 1.1 Disable External Services for Test
config(['services.n8n.new_ilan_webhook_url' => null]);

echo "--- Geri Dönüş: Final Guard Test Başlatılıyor ---\n";
echo "Aktif Kullanıcı: " . $user->name . " (" . $user->id . ")\n";

// 2. Create Dummy Data (if needed)
$mughla = \App\Models\Il::firstOrCreate(['il_adi' => 'Muğla'], ['plaka_kodu' => '48']);
$bodrum = \App\Models\Ilce::firstOrCreate(['ilce_adi' => 'Bodrum', 'il_id' => $mughla->id]);
$yalikavak = \App\Models\Mahalle::firstOrCreate(['mahalle_adi' => 'Yalıkavak', 'ilce_id' => $bodrum->id], ['mahalle_id' => 1]);
$sahibi = \App\Models\Kisi::firstOrCreate(['ad' => 'Test', 'soyad' => 'Sahibi'], ['telefon' => '5551234567']);

// 2. Create Listing Data
$bungalovId = 78;
$dailyRentalCatId = 38; // Günlük Kiralama category (ilan_kategorileri)
$uniqueId = uniqid();

$data = [
    'baslik' => 'Bodrum Yalıkavak\'ta Havuzlu Lüks Bungalov ' . $uniqueId,
    'aciklama' => 'Eşsiz doğa manzaralı, geniş bahçeli ve özel havuzlu rüya gibi bir bungalov. Modern tasarımı ile fark yaratıyor.',
    'ana_kategori_id' => 23, // Yazlık
    'alt_kategori_id' => $bungalovId,
    'yayin_tipi_id' => $dailyRentalCatId,
    'fiyat' => 5000,
    'gunluk_fiyat' => 5000,
    'para_birimi' => 'TRY',
    'il_id' => $mughla->id,
    'ilce_id' => $bodrum->id,
    'mahalle_id' => $yalikavak->id,
    'oda_sayisi' => 2,
    'banyo_sayisi' => 1,
    'net_m2' => 80,
    'max_misafir' => 4,
    'max_guests' => 4,
    'minimum_stay' => 1,
    'check_in_time' => '14:00',
    'check_out_time' => '11:00',
    'cleaning_fee' => 500,
    'havuz' => true,
    'havuz_var' => true,
    'sezon_baslangic' => '2024-05-01',
    'sezon_bitis' => '2024-10-31',
    'lat' => 37.034,
    'lng' => 27.245,
    'adres' => 'Yalıkavak Mahallesi, No: 123, Bodrum, Muğla',
    'ilan_sahibi_id' => $sahibi->id,
    'yayin_statusu' => 'Taslak',
];

// 3. Store Listing using Service
$service = app(\App\Services\Ilan\IlanCrudService::class);
try {
    echo "İlan kaydediliyor...\n";
    $ilan = $service->store($data);
    echo "İlan Oluşturuldu! ID: " . $ilan->id . " Ref: " . $ilan->referans_no . "\n";

    // 4. Quality Check (Gate Keeper)
    echo "Kalite Kontrolü (Gate Keeper) çalıştırılıyor...\n";
    $cortex = app(YalihanCortex::class);
    $qualityResult = $cortex->checkIlanQuality($ilan);

    echo "Sonuç: " . ($qualityResult['passed'] ? "BAŞARILI ✅" : "BAŞARISIZ ❌") . "\n";
    echo "Tamamlanma Oranı: %" . $qualityResult['completion_percentage'] . "\n";
    echo "Mesaj: " . $qualityResult['message'] . "\n";

    if (!$qualityResult['passed']) {
        echo "Eksik Alanlar:\n";
        foreach ($qualityResult['missing_fields'] as $field) {
            echo "- " . $field['label'] . " (" . $field['field'] . ")\n";
        }
    }

    // 5. Cleanup (Optional, but good for testing)
    // $ilan->delete();
    // echo "Test ilanı silindi.\n";

} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "--- Test Tamamlandı ---\n";
