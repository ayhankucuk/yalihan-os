<?php

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Enums\UpsFeatureLifecycle;
use Illuminate\Support\Facades\File;

echo "Scanning UPS Governance Data...\n";

// Replicating UpsFeatureGovernanceService logic

// 1. Feature Usage Stats
$features = Feature::withCount('assignments')
    ->with('category')
    ->orderBy('slug')
    ->get()
    ->map(function ($feature) {
        $templatesCount = FeatureAssignment::where('feature_id', $feature->id)
            ->select('assignable_type', 'assignable_id')
            ->distinct()
            ->count();

        return [
            'id' => $feature->id,
            'name' => $feature->name,
            'slug' => $feature->slug,
            'category' => $feature->category?->name,
            'durum_etiketi' => $feature->aktiflik_durumu ? 'Active' : 'Passive',
            'lifecycle' => $feature->lifecycle?->value ?? 'active',
            'assignments_count' => $feature->assignments_count,
            'templates_count' => $templatesCount,
            'is_orphaned' => $feature->assignments_count === 0,
        ];
    });

// 2. Summary Report
$summary = [
    'archived_but_assigned' => Feature::where('lifecycle', 'archived')->has('assignments')->count(),
    'inactive_but_assigned' => Feature::where('aktiflik_durumu', false)->has('assignments')->count(),
    'deprecated_assigned' => Feature::where('lifecycle', 'deprecated')->has('assignments')->count(),
    'orphaned_count' => Feature::doesntHave('assignments')->count(),
    'total_by_lifecycle' => [
        'draft' => Feature::where('lifecycle', 'draft')->count(),
        'active' => Feature::where('lifecycle', 'active')->orWhereNull('lifecycle')->count(),
        'deprecated' => Feature::where('lifecycle', 'deprecated')->count(),
        'archived' => Feature::where('lifecycle', 'archived')->count(),
    ]
];

$report = [
    'summary' => $summary,
    'features' => $features
];

$outputPath = __DIR__ . '/../../storage/app/ups_governance_scan_results.json';
File::put($outputPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Governance scan complete.\n";
echo "Report saved to: " . $outputPath . "\n";
