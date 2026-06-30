<?php

use App\Models\Ilan;
use App\Models\Lead;
use App\Services\Cortex\MatchingEngine;
use Illuminate\Support\Facades\DB;

echo "🚀 Cortex Matching Engine Testi Başlatılıyor...\n";

DB::beginTransaction();

try {
    // 1. Test İlanları Oluştur
    $ilan1 = Ilan::create([
        'baslik' => 'Bodrum Villa - Test 1',
        'fiyat' => 15000000,
        'para_birimi' => 'TL',
        'yayin_statusu' => 'Aktif',
        'lat' => 37.0344,
        'lng' => 27.4305,
        'oda_sayisi' => '4+1'
    ]);

    $ilan2 = Ilan::create([
        'baslik' => 'Yalıkavak Daire - Test 2',
        'fiyat' => 8000000,
        'para_birimi' => 'TL',
        'yayin_statusu' => 'Aktif',
        'lat' => 37.1034,
        'lng' => 27.2911,
        'oda_sayisi' => '2+1'
    ]);

    echo "✅ Test ilanları oluşturuldu.\n";

    // 2. Test Lead Oluştur
    $lead = Lead::create([
        'ad' => 'Cortex',
        'soyad' => 'Test',
        'lead_durumu' => 'yeni',
        'lat' => 37.0360, // İlan 1'e çok yakın (~200m)
        'lng' => 27.4320,
        'butce_min' => 5000000,
        'butce_max' => 20000000,
        'ilgi_alanlari' => ['oda_sayisi' => '4+1']
    ]);

    echo "✅ Test lead oluşturuldu.\n";

    // 3. Matching Engine Çalıştır
    $engine = new MatchingEngine();
    $matches = $engine->findMatchesForLead($lead);

    echo "\n📊 EŞLEŞME SONUÇLARI (Cortex Akıllı Eşleşme Başarıyla Ateşlendi):\n";
    echo str_repeat("-", 60) . "\n";

    foreach ($matches as $match) {
        $ilan = $match['ilan'];
        echo "📍 İlan: {$ilan->baslik}\n";
        echo "   💰 Fiyat: " . number_format($ilan->fiyat) . " TL\n";
        echo "   🎯 Toplam Skor: %{$match['total_score']}\n";
        echo "   🔍 Detay: Mesafe: {$match['breakdown']['distance_km']}km, Lokasyon: {$match['breakdown']['location']}, Bütçe: {$match['breakdown']['budget']}, Özellik: {$match['breakdown']['features']}\n";
        echo str_repeat("-", 60) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "\n🧹 Test verileri temizlendi (Rollback).\n";
}
