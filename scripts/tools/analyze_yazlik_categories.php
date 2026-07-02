<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "📊 YAZLIK KATEGORİSİ ANALİZİ\n";
echo "=============================\n\n";

try {
    $yazlik = \App\Models\IlanKategori::where('slug', 'yazlik')->first();
    
    if (!$yazlik) {
        echo "❌ Yazlık ana kategorisi bulunamadı!\n";
        exit(1);
    }
    
    echo "✅ Ana Kategori: {$yazlik->name} (ID: {$yazlik->id})\n\n";
    
    $altlar = \App\Models\IlanKategori::where('parent_id', $yazlik->id)->get(['id', 'name', 'slug']);
    
    echo "--- HEDEF KATEGORİLER ---\n";
    foreach ($altlar as $alt) {
        $featureCount = \App\Models\FeatureAssignment::where('assignable_id', $alt->id)
            ->where('assignable_type', \App\Models\IlanKategori::class)
            ->count();
        
        $icon = $featureCount >= 10 ? '✅' : ($featureCount > 0 ? '⚠️' : '❌');
        echo "{$icon} ID: {$alt->id} - {$alt->name} (Mevcut: {$featureCount} özellik)\n";
    }
    
    echo "\n--- KAYNAK ŞABLON ---\n";
    $villa = \App\Models\IlanKategori::find(75);
    if ($villa) {
        $villaFeatures = \App\Models\FeatureAssignment::where('assignable_id', 75)
            ->where('assignable_type', \App\Models\IlanKategori::class)
            ->count();
        echo "🏆 Villa (ID: 75) - {$villaFeatures} özellik (ŞABLON)\n";
    } else {
        echo "❌ Villa kategorisi bulunamadı!\n";
    }
    
    echo "\n✅ Analiz tamamlandı!\n";
    
} catch (\Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
    exit(1);
}
