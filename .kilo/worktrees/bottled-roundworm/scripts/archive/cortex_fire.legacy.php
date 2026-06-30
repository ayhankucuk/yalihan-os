<?php

use App\Models\Lead;
use App\Models\Ilan;
use App\Services\Cortex\MatchingEngine;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 CORTEX MATCHING ENGINE ATEŞLENİYOR...\n";
echo "------------------------------------------\n";

// 1. Test için bir Lead bul veya oluştur
$lead = Lead::first();

if (!$lead) {
    echo "⚠️ Sistemde Lead bulunamadı, örnek bir tane oluşturuluyor...\n";
    $lead = Lead::create([
        'ad' => 'Test',
        'soyad' => 'Kullanıcısı',
        'email' => 'test@cortex.ai',
        'telefon' => '05000000000',
        'lead_durumu' => 'yeni',
        'butce_min' => 5000000,
        'butce_max' => 15000000,
        'lat' => 37.0344, // Bodrum civarı
        'lng' => 27.4305,
        'ilgi_alanlari' => ['oda_sayisi' => 3]
    ]);
}

echo "👤 LEAD: {$lead->ad} {$lead->soyad}\n";
echo "💰 Bütçe: " . number_format($lead->butce_min) . " - " . number_format($lead->butce_max) . " TL\n";
echo "📍 Konum: {$lead->lat}, {$lead->lng}\n";
echo "🏠 Tercih: {$lead->ilgi_alanlari['oda_sayisi']} Oda\n";
echo "------------------------------------------\n";

// 2. Matching Engine'i çalıştır
$engine = new MatchingEngine();
$matches = $engine->findMatchesForLead($lead, 5);

if ($matches->isEmpty()) {
    echo "❌ Hiçbir eşleşme bulunamadı! (Aktif ilan veya koordinat eksik olabilir)\n";
} else {
    echo "🎯 EN UYGUN 5 İLAN:\n\n";
    
    foreach ($matches as $index => $match) {
        $ilan = $match['ilan'];
        $score = $match['total_score'];
        $b = $match['breakdown'];
        
        echo ($index + 1) . ". [" . str_pad($score, 5) . " Puan] {$ilan->baslik}\n";
        echo "   💰 Fiyat: " . number_format($ilan->fiyat) . " TL\n";
        echo "   📍 Mesafe: {$b['distance_km']} km\n";
        echo "   🏠 Oda: {$ilan->oda_sayisi}\n";
        echo "   📊 Detay: Lokasyon: {$b['location']}, Bütçe: {$b['budget']}, Özellik: {$b['features']}\n";
        echo "   --------------------------------------\n";
    }
}

echo "\n✅ Cortex Akıllı Eşleşme Başarıyla Ateşlendi!\n";
