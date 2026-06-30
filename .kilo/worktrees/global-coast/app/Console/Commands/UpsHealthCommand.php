<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\FeatureAssignment;
use App\Models\Feature;


class UpsHealthCommand extends Command
{
    protected $signature = 'ups:health
                            {--show-detail : Show detailed information}
                            {--performance : Run performance benchmarks}
                            {--json : Output as JSON}';
    protected $description = 'UPS Phase 1: Monitor schema health and legacy runtime usage';

    private array $report = [];
    private bool $hasCriticalIssues = false;

    public function handle()
    {
        $this->info('🏥 Yalıhan UPS Health Check');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $this->checkOrphanFeatureAssignments();
        $this->checkOrphanFeatureAssignmentsSummary();
        $this->checkUnassignedFeatures();
        $this->checkUnassignedFeatureSummary();
        $this->checkLegacyRuntimeUsage();

        if ($this->option('performance')) {
            $this->checkPerformanceMetrics();
        }

        $this->newLine();
        $this->displaySummary();

        if ($this->option('json')) {
            $this->line(json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $this->hasCriticalIssues ? 1 : 0;
    }

    private function checkOrphanFeatureAssignments(): void
    {
        $this->info('📊 [1/5] Checking orphan feature_assignments...');

        // V2: Check YayinTipiSablonu assignments
        $orphans = FeatureAssignment::where('assignable_type', 'App\\Models\\YayinTipiSablonu')
            ->whereNotIn('assignable_id', function ($query) {
                $query->select('id')->from('yayin_tipi_sablonlari')->where('aktiflik_durumu', true);
            })
            ->with('feature')
            ->get();

        $this->report['orphan_assignments'] = ['count' => $orphans->count()];

        if ($orphans->count() > 0) {
            $this->warn("   ⚠️  Found {$orphans->count()} orphan feature_assignments (YayinTipiSablonu)");
        } else {
            $this->info('   ✅ No orphan feature_assignments');
        }
    }

    private function checkOrphanFeatureAssignmentsSummary(): void
    {
        $this->info('📊 [2/5] Orphan feature_assignments summary...');

        $topN = 20;

        // A) Missing feature: feature_assignments.feature_id has no matching features.id
        $missingFeatureQuery = FeatureAssignment::query()
            ->leftJoin('features', 'feature_assignments.feature_id', '=', 'features.id')
            ->whereNull('features.id');

        $missingFeatureCount = $missingFeatureQuery->count();

        // B) Missing assignable (YayinTipiSablonu): assignable_id has no matching yayin_tipi_sablonlari.id
        $missingAssignableQuery = FeatureAssignment::query()
            ->where('assignable_type', 'App\\Models\\YayinTipiSablonu')
            ->leftJoin(
                'yayin_tipi_sablonlari',
                'feature_assignments.assignable_id',
                '=',
                'yayin_tipi_sablonlari.id'
            )
            ->whereNull('yayin_tipi_sablonlari.id');

        $missingAssignableCount = $missingAssignableQuery->count();

        $orphanTotal = $missingFeatureCount + $missingAssignableCount;

        $this->report['orphan_feature_assignments_summary'] = [
            'orphan_total' => $orphanTotal,
            'orphan_by_missing_feature' => $missingFeatureCount,
            'orphan_by_missing_assignable_template' => $missingAssignableCount,
        ];

        if ($orphanTotal > 0) {
            $this->warn("   ⚠️ orphan_total: {$orphanTotal}");
        } else {
            $this->info('   ✅ No orphan feature_assignments detected in summary');
        }
    }

    private function checkUnassignedFeatures(): void
    {
        $this->info('📊 [3/5] Checking unassigned features...');

        $unassigned = Feature::where('aktiflik_durumu', true)->whereDoesntHave('assignments')->get();
        $this->report['unassigned_features'] = [
            'count' => $unassigned->count(),
            'sample_slugs' => $unassigned->take(20)->pluck('slug')->toArray(),
        ];

        if ($unassigned->count() > 0) {
            $this->warn("   ⚠️  Found {$unassigned->count()} active but unassigned features");
            if ($this->option('show-detail')) {
                foreach ($unassigned->take(20) as $f) {
                    $this->line("      - {$f->slug}");
                }
            }
        } else {
            $this->info('   ✅ All active features are assigned');
        }
    }

    private function checkUnassignedFeatureSummary(): void
    {
        $this->info('📊 [4/5] Unassigned features summary...');

        $totalFeatures = Feature::count();
        $activeFeatures = Feature::where('aktiflik_durumu', true)->count();

        // Assigned features (distinct feature_id)
        $assignedFeatures = FeatureAssignment::selectRaw('COUNT(DISTINCT feature_id) as cnt', [])->value('cnt') ?? 0;

        // Unassigned active features: active features with no assignments
        $unassignedQuery = Feature::leftJoin('feature_assignments', 'features.id', '=', 'feature_assignments.feature_id')
            ->where('features.aktiflik_durumu', true)
            ->whereNull('feature_assignments.id');

        $unassignedFeatures = $unassignedQuery->count();

        $percentUnassigned = $activeFeatures > 0
            ? round(($unassignedFeatures / $activeFeatures) * 100, 1)
            : 0.0;

        $topN = 20;

        $topUnassigned = $unassignedQuery
            ->orderBy('features.id')
            ->limit($topN + 1)
            ->get(['features.id', 'features.slug']);

        $topList = $topUnassigned->take($topN);
        $extraCount = max(0, $topUnassigned->count() - $topList->count());

        $this->report['unassigned_features_summary'] = [
            'total_features' => $totalFeatures,
            'active_features' => $activeFeatures,
            'assigned_features' => $assignedFeatures,
            'unassigned_features' => $unassignedFeatures,
            'percent_unassigned' => $percentUnassigned,
            'top_unassigned' => $topList->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'slug' => $feature->slug,
                ];
            })->toArray(),
            'extra_unassigned_count' => $extraCount,
        ];

        $this->line("   • total_features: {$totalFeatures}");
        $this->line("   • active_features: {$activeFeatures}");
        $this->line("   • assigned_features: {$assignedFeatures}");
        $this->line("   • unassigned_features: {$unassignedFeatures} ({$percentUnassigned}%)");

        $this->line("   • top_unassigned_slugs (first {$topN}):");
        foreach ($topList as $feature) {
            $this->line("     - {$feature->id}:{$feature->slug}");
        }
        if ($extraCount > 0) {
            $this->line("     (+{$extraCount} more)");
        }

        // Verbose breakdown (use --show-detail as verbose)
        if ($this->option('show-detail')) {
            // Unassigned by feature category
            $this->newLine();
            $this->info('   • unassigned_by_feature_category:');

            $unassignedByCategory = Feature::leftJoin('feature_assignments', 'features.id', '=', 'feature_assignments.feature_id')
                ->leftJoin('feature_categories', 'features.feature_category_id', '=', 'feature_categories.id')
                ->where('features.aktiflik_durumu', true)
                ->whereNull('feature_assignments.id')
                ->groupBy('feature_categories.id', 'feature_categories.name')
                ->selectRaw('COALESCE(feature_categories.name, "Uncategorized") as category, COUNT(features.id) as count', [])
                ->orderByRaw('COUNT(features.id) desc', [])
                ->get();

            foreach ($unassignedByCategory as $row) {
                $this->line("     - {$row->category}: {$row->count}");
            }

            // Assigned by assignable_type
            $this->newLine();
            $this->info('   • assigned_by_assignable_type:');

            $assignedByAssignableType = FeatureAssignment::selectRaw('assignable_type, COUNT(DISTINCT feature_id) as count', [])
                ->groupBy('assignable_type')
                ->orderBy('count', 'desc')
                ->get();

            foreach ($assignedByAssignableType as $row) {
                $type = $row->assignable_type ?? 'null';
                $this->line("     - {$type}: {$row->count}");
            }
        }
    }

    private function checkLegacyRuntimeUsage(): void
    {
        $this->info('📊 [5/5] Checking legacy runtime usage...');

        $legacyHits = [];

        // Check alt_kategori_yayin_tipi usage
        $controller = app_path('Http/Controllers/Api/CategoriesController.php');
        if (file_exists($controller)) {
            $content = file_get_contents($controller);
            // Remove comments before checking
            $contentWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $content);
            $contentWithoutComments = preg_replace('/\/\/.*$/m', '', $contentWithoutComments);
            if (str_contains($contentWithoutComments, 'alt_kategori_yayin_tipi')) {
                $legacyHits[] = 'CategoriesController: alt_kategori_yayin_tipi pivot query';
            }
        }

        // Check /admin/ozellikler routes (exclude simple redirects and valid context paths)
        $routes = base_path('routes/admin.php');
        if (file_exists($routes)) {
            $content = file_get_contents($routes);
            // Remove comments
            $contentWithoutComments = preg_replace('/\/\*.*?\*\//s', '', $content);
            $contentWithoutComments = preg_replace('/\/\/.*$/m', '', $contentWithoutComments);
            // Look for controller calls in /ozellikler prefix group (not /{id}/ozellikler or admin/ozellikler context7)
            if (preg_match('/Route::prefix\(\'\/ ozellikler\'\)->.*?Route::(get|post|put|delete)\([^)]*,\s*\[.*?Controller/', $contentWithoutComments)) {
                $legacyHits[] = 'admin.php: /admin/ozellikler routes with controller calls (inside prefix group)';
            }
        }

        $this->report['legacy_runtime_usage'] = $legacyHits;

        if (!empty($legacyHits)) {
            $this->error('   ❌ LEGACY RUNTIME USAGE DETECTED:');
            foreach ($legacyHits as $hit) {
                $this->warn("      - {$hit}");
            }
            $this->hasCriticalIssues = true;
        } else {
            $this->info('   ✅ No legacy runtime usage detected');
        }
    }

    private function displaySummary(): void
    {
        $this->info('═══════════════════════════════════════════════');

        if ($this->hasCriticalIssues) {
            $this->error('❌ UPS HEALTH CHECK: FAIL');
        } else {
            $this->info('✅ UPS HEALTH CHECK: PASS');
        }

        $this->info('═══════════════════════════════════════════════');
    }

    private function checkPerformanceMetrics(): void
    {
        $this->info('📊 [6/6] Performance Metrics...');
        $this->newLine();

        // Check indexes
        $this->info('   🔍 Database Indexes:');
        $indexes = $this->checkFeatureAssignmentIndexes();
        $this->report['performance']['indexes'] = $indexes;

        foreach ($indexes as $index => $exists) {
            $icon = $exists ? '✅' : '❌';
            $this->line("      {$icon} {$index}");
        }

        $this->newLine();

        // Benchmark feature loading
        $this->info('   ⏱️  Feature Loading Benchmarks:');
        $benchmarks = $this->benchmarkFeatureLoading();
        $this->report['performance']['benchmarks'] = $benchmarks;

        $this->line("      • Load 100 assignments (no eager): {$benchmarks['without_eager_ms']}ms");
        $this->line("      • Load 100 assignments (with eager): {$benchmarks['with_eager_ms']}ms");
        $this->line("      • Performance gain: {$benchmarks['improvement_percent']}%");

        if ($benchmarks['with_eager_ms'] > 100) {
            $this->warn("      ⚠️  Slow query detected (>{$benchmarks['with_eager_ms']}ms)");
        } else {
            $this->info("      ✅ Query performance acceptable");
        }

        $this->newLine();

        // Check N+1 potential
        $this->info('   🔎 N+1 Query Analysis:');
        $n1Analysis = $this->analyzeN1Queries();
        $this->report['performance']['n1_analysis'] = $n1Analysis;

        $this->line("      • Total query count (no eager): {$n1Analysis['queries_without_eager']}");
        $this->line("      • Total query count (with eager): {$n1Analysis['queries_with_eager']}");
        $this->line("      • Queries saved: {$n1Analysis['queries_saved']}");

        if ($n1Analysis['queries_saved'] > 50) {
            $this->info("      ✅ Significant N+1 prevention ({$n1Analysis['queries_saved']} queries)");
        }
    }

    private function checkFeatureAssignmentIndexes(): array
    {
        $indexes = [];

        // Get all indexes on feature_assignments table
        $tableIndexes = DB::select(
            "SHOW INDEX FROM feature_assignments"
        );

        $indexNames = collect($tableIndexes)->pluck('Key_name')->unique()->toArray();

        // Check critical indexes
        $indexes['PRIMARY'] = in_array('PRIMARY', $indexNames);
        $indexes['feature_id_index'] = in_array('feature_assignments_feature_id_foreign', $indexNames) ||
            in_array('feature_id', $indexNames);
        $indexes['assignable_composite'] = in_array('feature_assignments_assignable_type_assignable_id_index', $indexNames) ||
            in_array('assignable_type_assignable_id', $indexNames);
        $indexes['unique_constraint'] = in_array('feature_assignments_feature_id_assignable_type_assignable_id_unique', $indexNames) ||
            in_array('unique_assignment', $indexNames);

        return $indexes;
    }

    private function benchmarkFeatureLoading(): array
    {
        DB::enableQueryLog();

        // Without eager loading
        $start = microtime(true);
        $assignments = FeatureAssignment::limit(100)->get();
        foreach ($assignments as $assignment) {
            $feature = $assignment->feature;
        }
        $withoutEagerMs = round((microtime(true) - $start) * 1000, 2);
        $queriesWithout = count(DB::getQueryLog());
        DB::flushQueryLog();

        // With eager loading
        $start = microtime(true);
        $assignments = FeatureAssignment::with('feature')->limit(100)->get();
        foreach ($assignments as $assignment) {
            $feature = $assignment->feature;
        }
        $withEagerMs = round((microtime(true) - $start) * 1000, 2);
        $queriesWith = count(DB::getQueryLog());
        DB::flushQueryLog();

        DB::disableQueryLog();

        $improvement = $withoutEagerMs > 0
            ? round((($withoutEagerMs - $withEagerMs) / $withoutEagerMs) * 100, 1)
            : 0;

        return [
            'without_eager_ms' => $withoutEagerMs,
            'with_eager_ms' => $withEagerMs,
            'improvement_percent' => $improvement,
            'queries_without' => $queriesWithout,
            'queries_with' => $queriesWith,
        ];
    }

    private function analyzeN1Queries(): array
    {
        DB::enableQueryLog();

        // Simulate N+1 scenario
        $assignments = FeatureAssignment::limit(50)->get();
        foreach ($assignments as $assignment) {
            $_ = $assignment->feature;
        }
        $queriesWithoutEager = count(DB::getQueryLog());
        DB::flushQueryLog();

        // With eager loading
        $assignments = FeatureAssignment::with('feature')->limit(50)->get();
        foreach ($assignments as $assignment) {
            $_ = $assignment->feature;
        }
        $queriesWithEager = count(DB::getQueryLog());
        DB::flushQueryLog();

        DB::disableQueryLog();

        return [
            'queries_without_eager' => $queriesWithoutEager,
            'queries_with_eager' => $queriesWithEager,
            'queries_saved' => $queriesWithoutEager - $queriesWithEager,
        ];
    }
}
