<?php
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\IlanKategoriYayinTipi;

// 1. ORPHANED FEATURES ANALYSIS
$total_features = Feature::count();
$assigned_features = FeatureAssignment::select('feature_id')->distinct()->count();
$orphaned_features = $total_features - $assigned_features;

// 2. ASSIGNMENT DETAILS BY CATEGORY
$assignment_by_category = IlanKategori::withCount('yayinTipleri')
    ->with(['yayinTipleri' => function ($q) {
        $q->withCount('featureAssignments');
    }])
    ->get()
    ->map(function ($kat) {
        $total_types = $kat->yayin_tipleri_count ?? 0;
        $total_assignments = $kat->yayinTipleri->sum('feature_assignments_count') ?? 0;
        return [
            'name' => $kat->name,
            'id' => $kat->id,
            'yayin_tipi_count' => $total_types,
            'total_assignments' => $total_assignments,
            'avg_per_type' => $total_types > 0 ? round($total_assignments / $total_types, 2) : 0,
        ];
    });

// 3. GET ORPHANED FEATURES LIST
$orphaned_list = Feature::whereNotIn(
    'id',
    FeatureAssignment::select('feature_id')->distinct()
)->with('category')->get()->map(function ($f) {
    return [
        'id' => $f->id,
        'name' => $f->name,
        'slug' => $f->slug,
        'category' => $f->category?->name ?? 'Uncategorized',
        'type' => $f->field_type ?? 'unknown',
    ];
})->sortBy('category');

$result = [
    'summary' => [
        'total_features' => $total_features,
        'assigned_features' => $assigned_features,
        'orphaned_count' => $orphaned_features,
        'orphaned_percentage' => round(($orphaned_features / $total_features) * 100, 2),
        'coverage' => round(($assigned_features / $total_features) * 100, 2),
    ],
    'by_category' => $assignment_by_category->values()->toArray(),
    'orphaned_features' => $orphaned_list->values()->toArray(),
];

file_put_contents(
    storage_path('analysis_orphaned_features.json'),
    json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
