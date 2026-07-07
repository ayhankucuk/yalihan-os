<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Feature;
use Illuminate\Support\Facades\File;

echo "Scanning Property Hub Features...\n";

$features = Feature::with('category')->orderBy('feature_category_id')->orderBy('display_order')->get();

$report = $features->map(function($f) {
    return [
        'id' => $f->id,
        'name' => $f->name,
        'category' => $f->category?->name ?? 'Uncategorized',
        'type' => $f->type,
        'durum_etiketi' => $f->aktiflik_durumu ? 'Active' : 'Passive',
        'required' => $f->is_required,
        'slug' => $f->slug,
        'display_order' => $f->display_order
    ];
});

$outputPath = __DIR__ . '/../../storage/app/scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Scanned " . $features->count() . " features.\n";
echo "Report saved to: " . $outputPath . "\n";

// Print summary by category
$byCategory = $features->groupBy(fn($f) => $f->category?->name ?? 'Uncategorized');

echo "\nSummary by Category:\n";
foreach($byCategory as $cat => $items) {
    echo "- $cat: " . $items->count() . " features\n";
}
