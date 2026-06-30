<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧠 FEATURE DEPENDENCIES KURULUMU\n\n";

// Check if table exists
try {
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable('feature_dependencies');
    echo "feature_dependencies tablosu: " . ($tableExists ? "✅ VAR" : "❌ YOK") . "\n\n";
} catch (\Exception $e) {
    echo "⚠️ Tablo kontrolü yapılamadı, devam ediliyor...\n\n";
    $tableExists = false;
}

// Define dependencies
// Note: This table uses feature_id references, not string names
// Logic: Find feature IDs and category IDs to insert

$categoryName = 'Malikane'; // Example
$malikane = \App\Models\IlanKategori::where('name', $categoryName)->first();

if (!$malikane) {
    echo "❌ Malikane kategorisi bulunamadı!\n";
} else {
    echo "✅ Malikane kategorisi bulundu (ID: {$malikane->id})\n";
    
    // Example feature dependencies mapping
    $featureMappings = [
        'mustemilat' => 'zorunlu',
        'sarap_mahzeni' => 'zorunlu',
        'sinema_salonu' => 'zorunlu',
    ];

    foreach ($featureMappings as $fSlug => $durum) {
        $feature = \App\Models\Feature::where('slug', $fSlug)->first();
        if ($feature) {
            \Illuminate\Support\Facades\DB::table('feature_dependencies')->updateOrInsert(
                [
                    'category_id' => $malikane->id,
                    'feature_id' => $feature->id,
                ],
                [
                    'dependency_type' => $durum,
                    'aktiflik_durumu' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            echo "  ✅ Mühürlendi: {$fSlug} -> {$durum}\n";
        } else {
            echo "  ⚠️ Feature bulunamadı: {$fSlug}\n";
        }
    }
}

echo "\n📊 Mevcut bağımlılık sayısı: " . \Illuminate\Support\Facades\DB::table('feature_dependencies')->count() . " adet\n";
