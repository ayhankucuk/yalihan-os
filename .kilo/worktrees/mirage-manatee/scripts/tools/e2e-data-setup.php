<?php

use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Ozellik;
use App\Models\UpsTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * E2E Data Setup Script
 *
 * Synchronizes 'ozellikler' (Land features) to 'features' table
 * and creates assignments for Arsa & Arazi (ID: 3) + Satılık (Junction ID: 60)
 */

echo "🚀 Starting E2E Data Setup...\n";

// 🛡️ Guard: Production Safety
if (app()->environment('production')) {
    echo "⚠️  Production environment detected! Aborting E2E data setup.\n";
    exit(1);
}

// 1. Find Arsa Features
$ozellikler = Ozellik::where('slug', 'like', 'arsa_%')
    ->orWhere('slug', 'imar_durumu')
    ->orWhere('slug', 'tapu_durumu')
    ->get();

if ($ozellikler->isEmpty()) {
    echo "❌ No features found in 'ozellikler' table. Run ArsaFeaturesSeeder first.\n";
    exit(1);
}

// 2. Ensure "Arsa Özellikleri" category exists in FeatureCategory
$featureCategory = FeatureCategory::firstOrCreate(
    ['slug' => 'arsa-ozellikleri'],
    [
        'name' => 'Arsa Özellikleri',
        'aktiflik_durumu' => true
    ]
);

echo "📦 Feature Category: {$featureCategory->name}\n";

// 3. Sync to features table
$syncedCount = 0;
foreach ($ozellikler as $oz) {
    Feature::updateOrCreate(
        ['slug' => $oz->slug],
        [
            'name' => $oz->name,
            'description' => $oz->aciklama,
            'type' => $oz->veri_tipi === 'number' ? 'number' : ($oz->veri_tipi === 'boolean' ? 'boolean' : 'select'),
            'options' => $oz->veri_secenekleri,
            'unit' => $oz->birim,
            'feature_category_id' => $featureCategory->id,
            'is_required' => $oz->zorunlu,
            'is_filterable' => $oz->arama_filtresi,
            'is_searchable' => $oz->arama_filtresi,
            'aktiflik_durumu' => true,
            'display_order' => 0
        ]
    );
    $syncedCount++;
}

echo "✅ Synced {$syncedCount} features to 'features' table.\n";

// 4. Create Assignments for Junction ID 60 (Arsa-Satılık)
$pivot = YayinTipiSablonu::find(60);

if (!$pivot) {
    echo "❌ Junction ID 60 not found. Checking for Arsa (3) + Satılık match...\n";
    $pivot = YayinTipiSablonu::where('kategori_id', 3)
        ->where('yayin_tipi', 'Satılık')
        ->first();
}

if ($pivot) {
    echo "🔗 Found Junction: {$pivot->id} ({$pivot->yayin_tipi} in Kategori {$pivot->kategori_id})\n";

    // Ensure UpsTemplate exists for this pivot
    $upsTemplate = UpsTemplate::where('yayin_tipi_sablonu_id', $pivot->id)->first();

    if (!$upsTemplate) {
        echo "🎨 Creating new UpsTemplate for this junction...\n";
        $templateJson = [
            'zorunlu_alanlar' => ['baslik', 'fiyat', 'arsa_tipi', 'ada_no', 'parsel_no'],
            'opsiyonel_alanlar' => ['imar_durumu', 'tapu_durumu', 'kaks', 'taks', 'gabari'],
            'gizli_alanlar' => [],
            'validasyon_kurallari' => [
                'arsa_tipi' => 'required',
                'ada_no' => 'required',
                'parsel_no' => 'required'
            ],
            'ui_ipuclari' => [
                ['slug' => 'arsa_tipi', 'label' => 'Arsa Tipi', 'hint' => 'Arsanızın tipini seçin'],
                ['slug' => 'ada_no', 'label' => 'Ada No', 'hint' => 'Tapu üzerindeki Ada No'],
                ['slug' => 'parsel_no', 'label' => 'Parsel No', 'hint' => 'Tapu üzerindeki Parsel No']
            ]
        ];

        $upsTemplate = UpsTemplate::create([
            'yayin_tipi_sablonu_id' => $pivot->id,
            'kategori_id' => $pivot->kategori_id,
            'yayin_tipi_id' => $pivot->yayin_tipi_id, // Ensure this exists on pivot
            'template_json' => $templateJson,
            'template_version' => 1,
            'template_hash' => md5(json_encode($templateJson)),
            'aktiflik_durumu' => 1
        ]);
        $pivot->update(['ups_template_id' => $upsTemplate->id]);
    }

    $features = Feature::whereIn('slug', $ozellikler->pluck('slug'))->get();
    $assignedCount = 0;

    foreach ($features as $index => $f) {
        // Assign to pivot for FeatureTemplateResolver
        FeatureAssignment::updateOrCreate(
            [
                'feature_id' => $f->id,
                'assignable_type' => 'App\Models\YayinTipiSablonu',
                'assignable_id' => $pivot->id,
            ],
            [
                'is_required' => $f->is_required,
                'is_visible' => true,
                'display_order' => $index,
                'group_name' => 'Arsa Özellikleri',
                'aktiflik_durumu' => true
            ]
        );
        $assignedCount++;
    }
    echo "✅ Assigned {$assignedCount} features to Junction {$pivot->id} and UpsTemplate {$upsTemplate->id}.\n";
} else {
    echo "❌ Could not find valid Junction for Arsa Satılık.\n";
}

// 5. Create Assignments for Sub-Category 15 (Arsa Konut/Villa) + Satılık
// This is required because the wizard context switches to sub-category config if selected.
echo "\n🔄 Checking Sub-Category 15 (Arsa Konut/Villa)...\n";
$subPivot = YayinTipiSablonu::where('kategori_id', 15) // Arsa (Konut/Villa)
    ->where('yayin_tipi', 'Satılık')
    ->first();

if ($subPivot) {
    echo "🔗 Found Sub-Junction: {$subPivot->id} ({$subPivot->yayin_tipi} in Kategori {$subPivot->kategori_id})\n";

    // Ensure UpsTemplate exists for this sub-pivot
    $subUpsTemplate = UpsTemplate::where('yayin_tipi_sablonu_id', $subPivot->id)->first();
    if (!$subUpsTemplate) {
        echo "🎨 Creating new UpsTemplate for Sub-Junction...\n";
        // Re-use same template json for simplicity
        $templateJson = [
             'zorunlu_alanlar' => ['baslik', 'fiyat', 'arsa_tipi', 'ada_no', 'parsel_no'],
             'validasyon_kurallari' => ['arsa_tipi' => 'required', 'ada_no' => 'required', 'parsel_no' => 'required'],
             'ui_ipuclari' => []
        ];

        $subUpsTemplate = UpsTemplate::create([
            'yayin_tipi_sablonu_id' => $subPivot->id,
            'kategori_id' => $subPivot->kategori_id,
            'yayin_tipi_id' => $subPivot->yayin_tipi_id,
            'template_json' => $templateJson,
            'template_version' => 1,
            'template_hash' => md5(json_encode($templateJson)),
            'aktiflik_durumu' => 1
        ]);
        $subPivot->update(['ups_template_id' => $subUpsTemplate->id]);
    }

    $features = Feature::whereIn('slug', $ozellikler->pluck('slug'))->get();
    $subAssignedCount = 0;

    foreach ($features as $index => $f) {
        FeatureAssignment::updateOrCreate(
            [
                'feature_id' => $f->id,
                'assignable_type' => 'App\Models\YayinTipiSablonu',
                'assignable_id' => $subPivot->id,
            ],
            [
                'is_required' => $f->is_required,
                'is_visible' => true,
                'display_order' => $index,
                'group_name' => 'Arsa Özellikleri',
                'aktiflik_durumu' => true
            ]
        );
        $subAssignedCount++;
    }
    echo "✅ Assigned {$subAssignedCount} features to Sub-Junction {$subPivot->id}.\n";
} else {
    echo "⚠️ Sub-Category 15 Junction not found. Skipping.\n";
}

echo "🎉 E2E Data Setup Complete!\n";
