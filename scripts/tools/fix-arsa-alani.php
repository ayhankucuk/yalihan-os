<?php

/**
 * Arsa Alanı Feature Oluşturma ve Atama Script
 *
 * @context7 SEALED - 3 Şubat 2026
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Feature;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;

echo "=== ARSA ALANI FEATURE RESTORATION ===\n\n";

// 1. arsa-alani feature var mı kontrol et
$arsaAlani = Feature::where('slug', 'arsa-alani')->first();

if ($arsaAlani) {
    echo "✅ arsa-alani zaten mevcut! ID: {$arsaAlani->id}\n";
} else {
    // Oluştur - Feature model 'name' column kullanıyor
    $arsaAlani = Feature::create([
        'slug' => 'arsa-alani',
        'name' => 'Arsa Alanı',
        'type' => 'number',
        'unit' => 'm²',
        'feature_category_id' => 1, // Temel Özellikler
        'aktiflik_durumu' => true,
        'display_order' => 10,
    ]);
    echo "✅ arsa-alani oluşturuldu! ID: {$arsaAlani->id}\n";
}

// 2. Arsa kategorilerine ata
$arsaIds = [3, 15, 16, 17, 18, 19, 20];
echo "\n--- IlanKategori Atamaları ---\n";

foreach ($arsaIds as $catId) {
    $kat = IlanKategori::find($catId);
    if ($kat) {
        $exists = $kat->featureAssignments()->where('feature_id', $arsaAlani->id)->exists();
        if (!$exists) {
            $kat->assignFeature($arsaAlani, ['is_required' => true, 'is_visible' => true]);
            echo "  ✅ Assigned to: {$kat->slug}\n";
        } else {
            echo "  ⏭️  Already assigned: {$kat->slug}\n";
        }
    }
}

// 3. YayinTipiSablonu'na ata (Satılık, Kat Karşılığı)
echo "\n--- YayinTipiSablonu Atamaları ---\n";
$sablonlar = YayinTipiSablonu::whereIn('slug', ['satilik', 'kat-karsiligi'])->get();

foreach ($sablonlar as $sablon) {
    $exists = $sablon->featureAssignments()->where('feature_id', $arsaAlani->id)->exists();
    if (!$exists) {
        // Manuel atama (HasFeatures trait yok)
        \App\Models\FeatureAssignment::create([
            'feature_id' => $arsaAlani->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $sablon->id,
            'is_required' => true,
            'is_visible' => true,
            'display_order' => 10,
        ]);
        echo "  ✅ Assigned to: {$sablon->ad} ({$sablon->slug})\n";
    } else {
        echo "  ⏭️  Already assigned: {$sablon->ad}\n";
    }
}

echo "\n=== ARSA ALANI SEALED ✅ ===\n";
