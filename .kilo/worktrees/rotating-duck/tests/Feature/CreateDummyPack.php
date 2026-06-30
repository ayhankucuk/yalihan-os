<?php
use App\Models\FeaturePack;
use App\Models\Feature;
use App\Models\FeaturePackItem;

// 1. Create Pack
$pack = FeaturePack::firstOrCreate(
    ['name' => 'Standart Konut Paketi'],
    [
        'description' => 'Temel konut özellikleri (Oda, Salon, Isıtma vb.)',
        'aktiflik_durumu' => true,
        'display_order' => 1
    ]
);

echo "Pack: " . $pack->name . " (ID: " . $pack->id . ")" . PHP_EOL;

// 2. Add Features to Pack
$features = Feature::where('aktiflik_durumu', true)->limit(5)->get();

foreach($features as $index => $feature) {
    // Check if exists
    $exists = FeaturePackItem::where('feature_pack_id', $pack->id)
        ->where('feature_id', $feature->id)
        ->exists();

    if (!$exists) {
        FeaturePackItem::create([
            'feature_pack_id' => $pack->id,
            'feature_id' => $feature->id,
            'display_order' => $index
        ]);
        echo "Added feature: " . $feature->name . PHP_EOL;
    } else {
        echo "Feature already in pack: " . $feature->name . PHP_EOL;
    }
}

echo "Done. Pack feature count: " . $pack->features()->count() . PHP_EOL;
