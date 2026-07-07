<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "Inspecting Feature Assignments...\n";

// 1. Assignable Type Distribution
$distribution = DB::table('feature_assignments')
    ->select('assignable_type', DB::raw('count(*) as c'))
    ->groupBy('assignable_type')
    ->orderByDesc('c')
    ->get();

echo "\n--- Distribution ---\n";
foreach ($distribution as $d) {
    echo "{$d->assignable_type}: {$d->c}\n";
}

// 2. First 20 records
$samples = DB::table('feature_assignments')
    ->select('assignable_type', 'assignable_id', 'feature_id')
    ->take(20)
    ->get();

echo "\n--- Samples (First 20) ---\n";
foreach ($samples as $s) {
    echo "Type: {$s->assignable_type} | ID: {$s->assignable_id} | Feature: {$s->feature_id}\n";
}

$report = [
    'distribution' => $distribution,
    'samples' => $samples
];

$outputPath = __DIR__ . '/../../storage/app/feature_assignments_inspection.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\nInspection complete. Report saved to: " . $outputPath . "\n";
