<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Count features for Satılık and Kiralık
$results = \Illuminate\Support\Facades\DB::table('feature_assignments')
    ->where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->whereIn('assignable_id', [1, 2])
    ->selectRaw('assignable_id, COUNT(*) as feature_count')
    ->groupBy('assignable_id')
    ->get();

echo "Feature Counts:\n";
foreach ($results as $row) {
    echo "  Template ID {$row->assignable_id}: {$row->feature_count} features\n";
}

// Show details
echo "\nDetail for Satılık (ID:1):\n";
$satilik = \Illuminate\Support\Facades\DB::table('feature_assignments as fa')
    ->join('features as f', 'fa.feature_id', '=', 'f.id')
    ->where('fa.assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('fa.assignable_id', 1)
    ->select('f.id', 'f.slug', 'fa.is_required')
    ->get();
foreach ($satilik as $feature) {
    $req = $feature->is_required ? 'REQUIRED' : 'optional';
    echo "  [{$feature->id}] {$feature->slug} ({$req})\n";
}

echo "\nDetail for Kiralık (ID:2):\n";
$kiralik = \Illuminate\Support\Facades\DB::table('feature_assignments as fa')
    ->join('features as f', 'fa.feature_id', '=', 'f.id')
    ->where('fa.assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('fa.assignable_id', 2)
    ->select('f.id', 'f.slug', 'fa.is_required')
    ->get();
foreach ($kiralik as $feature) {
    $req = $feature->is_required ? 'REQUIRED' : 'optional';
    echo "  [{$feature->id}] {$feature->slug} ({$req})\n";
}
