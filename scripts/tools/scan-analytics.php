<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IlanKategori;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Models\TemplateChangeLog;
use Illuminate\Support\Facades\File;

echo "Scanning Property Hub Analytics...\n";

// Replicating logic from PropertyHubController::analytics

$categories = IlanKategori::where('seviye', 0)
    ->where('aktiflik_durumu', true)
    ->take(10)
    ->get();

$topFeatures = Feature::where('aktiflik_durumu', true)
    ->withCount('assignments')
    ->orderByDesc('assignments_count')
    ->take(15)
    ->get();

// Build heatmap data
$heatmapData = [];
foreach ($topFeatures as $feature) {
    $heatmapData[$feature->id] = [];
    foreach ($categories as $category) {
        $count = FeatureAssignment::where('feature_id', $feature->id)
            ->whereHasMorph('assignable', [YayinTipiSablonu::class], function ($q) use ($category) {
                 $q->whereHas('altKategoriler', function ($subQ) use ($category) {
                     $subQ->where('ilan_kategorileri.parent_id', $category->id);
                 });
            })
            ->count();
        $heatmapData[$feature->id][$category->id] = $count;
    }
}

// Coverage stats
$totalYayinTipleri = YayinTipiSablonu::count();
$withAssignments = YayinTipiSablonu::has('featureAssignments')->count();

$coverageStats = [
    'template_coverage' => $totalYayinTipleri > 0 ? round(($withAssignments / $totalYayinTipleri) * 100) : 0,
    'feature_utilization' => Feature::count() > 0 ? round((Feature::has('assignments')->count() / Feature::count()) * 100) : 0,
];

// Orphaned features
$orphanedFeaturesCount = Feature::where('aktiflik_durumu', true)
    ->whereDoesntHave('assignments')
    ->count();

// Metrics
$metrics = [
    'templates_created_today' => TemplateChangeLog::whereDate('created_at', today())->count(),
    'orphaned_features_count' => $orphanedFeaturesCount,
    'total_yayin_tipleri' => $totalYayinTipleri,
    'yayin_tipleri_with_assignments' => $withAssignments,
];

$report = [
    'metrics' => $metrics,
    'coverage' => $coverageStats,
    'top_features' => $topFeatures->map(fn($f) => ['name' => $f->name, 'count' => $f->assignments_count]),
    'heatmap_sample' => $heatmapData
];

$outputPath = __DIR__ . '/../../storage/app/analytics_scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Analytics scan complete.\n";
echo "Report saved to: " . $outputPath . "\n";
