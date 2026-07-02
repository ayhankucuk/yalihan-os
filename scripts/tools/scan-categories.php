<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IlanKategori;
use Illuminate\Support\Facades\File;

echo "Scanning Private Listings Categories...\n";

// Fetch all categories
$categories = IlanKategori::orderBy('seviye')->orderBy('display_order')->get();

$report = $categories->map(function($c) {
    return [
        'id' => $c->id,
        'name' => $c->name,
        'slug' => $c->slug,
        'level' => $c->seviye, // 0: Main, 1: Sub, 2: Type
        'level_name' => $c->seviye_aciklamasi,
        'parent' => $c->parent ? $c->parent->name : '-',
        'durum_etiketi' => $c->aktiflik_durumu ? 'Active' : 'Passive',
        'display_order' => $c->display_order,
        'children_count' => $c->children()->count(),
    ];
});

$outputPath = __DIR__ . '/../../storage/app/category_scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Scanned " . $categories->count() . " categories.\n";
echo "Report saved to: " . $outputPath . "\n";

// Summary by Level
$byLevel = $categories->groupBy('level_name');
echo "\nSummary by Level:\n";
foreach($byLevel as $level => $items) {
    echo "- $level: " . $items->count() . " items\n";
}
