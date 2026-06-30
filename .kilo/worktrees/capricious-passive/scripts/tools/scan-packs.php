<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FeaturePack;
use Illuminate\Support\Facades\File;

echo "Scanning Feature Packs...\n";

$packs = FeaturePack::with(['features'])
    ->withCount('features')
    ->orderBy('display_order')
    ->get();

$report = $packs->map(function($p) {
    return [
        'id' => $p->id,
        'name' => $p->name,
        'slug' => $p->slug,
        'durum_etiketi' => $p->aktiflik_durumu ? 'Active' : 'Passive',
        'price' => $p->price,
        'feature_count' => $p->features_count,
        'features' => $p->features->map(fn($f) => $f->name)->toArray()
    ];
});

$outputPath = __DIR__ . '/../../storage/app/pack_scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Scanned " . $packs->count() . " packs.\n";
echo "Report saved to: " . $outputPath . "\n";
