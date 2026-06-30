<?php

namespace App\Console\Commands\Ups;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\Logging\LogService;
use App\Services\Ups\UpsFeatureGovernanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * UPS AI Feature Mapping Command
 *
 * Context7: AI-powered feature-to-template assignment with safety-first approach
 * - Snapshot before apply
 * - Duplicate-safe insert (unique constraint check)
 * - Confidence gate (min 0.70)
 * - Manual review queue for low-confidence
 * - Single source of truth for assignable_type (YayinTipiSablonu)
 */
class AiFeatureMapCommand extends Command
{
    protected $signature = 'ups:ai-feature-map
                            {--apply : Apply changes (default: dry-run)}
                            {--limit= : Limit number of features to process}
                            {--min-confidence=0.70 : Minimum confidence threshold}';

    protected $description = 'AI-powered feature-to-template mapping (unassigned features -> feature_assignments)';

    private const ASSIGNABLE_TYPE = 'App\\Models\\YayinTipiSablonu';

    // Category slug -> kategori_id mapping (sabit config)
    private const CATEGORY_MAP = [
        'konut' => 5,
        'arsa' => 2,
        'isyeri' => 9,
        'devremulk' => 56,
        'yazlik' => 7,
    ];

    private UpsFeatureGovernanceService $governance;
    private SmartFieldGenerationService $aiService;
    private array $stats = [
        'total' => 0,
        'will_assign' => 0,
        'manual_review' => 0,
        'duplicate_skip' => 0,
        'missing_pivot' => 0,
        'low_confidence' => 0,
    ];

    public function __construct(
        UpsFeatureGovernanceService $governance,
        SmartFieldGenerationService $aiService
    ) {
        parent::__construct();
        $this->governance = $governance;
        $this->aiService = $aiService;
    }

    public function handle(): int
    {
        $t0 = LogService::startTimer('ups_ai_feature_map');
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $minConfidence = (float) $this->option('min-confidence');

        // Validate assignable_type exists
        if (!class_exists(self::ASSIGNABLE_TYPE)) {
            $this->error('❌ CRITICAL: Assignable class ' . self::ASSIGNABLE_TYPE . ' does not exist');
            return Command::FAILURE;
        }

        $this->info('🔍 Fetching unassigned features from UPS...');

        // Get unassigned features
        $usage = $this->governance->getUsageStats(['orphaned' => true]);
        $unassigned = collect($usage)->filter(fn($f) => $f['is_orphaned'])->values();

        if ($unassigned->isEmpty()) {
            $this->info('✅ No unassigned features found. Nothing to do.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d unassigned features', $unassigned->count()));

        // Apply limit
        if ($limit) {
            $unassigned = $unassigned->take($limit);
            $this->warn(sprintf('⚠️  Limited to %d features', $limit));
        }

        $slugs = $unassigned->pluck('slug')->toArray();
        $this->stats['total'] = count($slugs);

        $this->info('🤖 Generating AI recommendations...');
        $recommendations = $this->aiService->generateSmartRecommendations($slugs);

        // Process recommendations
        $this->info('📋 Processing recommendations...');
        $assignmentPlan = [];
        $manualReview = [];

        foreach ($recommendations as $rec) {
            $featureSlug = $rec['slug'];
            $feature = Feature::where('slug', $featureSlug)->first();

            if (!$feature) {
                $this->warn("⚠️  Feature not found: {$featureSlug}");
                continue;
            }

            // Confidence gate
            if ($rec['confidence'] < $minConfidence) {
                $this->stats['low_confidence']++;
                $manualReview[] = [
                    'feature_id' => $feature->id,
                    'slug' => $featureSlug,
                    'confidence' => $rec['confidence'],
                    'reason' => 'low_confidence',
                    'suggested_category' => $rec['category_slug'],
                    'suggested_yayin_tipi' => $rec['yayin_tipi_slug'],
                ];
                continue;
            }

            // Resolve pivot ID
            $pivotId = $this->resolvePivotId(
                $rec['category_slug'],
                $rec['yayin_tipi_slug']
            );

            if (!$pivotId) {
                $this->stats['missing_pivot']++;
                $manualReview[] = [
                    'feature_id' => $feature->id,
                    'slug' => $featureSlug,
                    'confidence' => $rec['confidence'],
                    'reason' => 'missing_pivot',
                    'suggested_category' => $rec['category_slug'],
                    'suggested_yayin_tipi' => $rec['yayin_tipi_slug'],
                ];
                continue;
            }

            // Check duplicate
            $exists = FeatureAssignment::where('feature_id', $feature->id)
                ->where('assignable_type', self::ASSIGNABLE_TYPE)
                ->where('assignable_id', $pivotId)
                ->exists();

            if ($exists) {
                $this->stats['duplicate_skip']++;
                continue;
            }

            // Add to assignment plan
            $this->stats['will_assign']++;
            $assignmentPlan[] = [
                'feature_id' => $feature->id,
                'slug' => $featureSlug,
                'assignable_id' => $pivotId,
                'confidence' => $rec['confidence'],
                'category_slug' => $rec['category_slug'],
                'yayin_tipi_slug' => $rec['yayin_tipi_slug'],
            ];
        }

        // Print summary
        $this->printSummary($assignmentPlan, $manualReview, $apply);

        if (!$apply) {
            $this->info("\n✅ DRY-RUN complete. Use --apply to execute.");
            LogService::info('ups_ai_feature_map_dry_run', [
                'stats' => $this->stats,
                'duration_ms' => (int) LogService::stopTimer($t0),
            ]);
            return Command::SUCCESS;
        }

        // APPLY: Snapshot + Transaction + Insert
        $this->info("\n🔒 Creating snapshot...");
        $snapshotPath = $this->createSnapshot($assignmentPlan);
        $this->info("📸 Snapshot: {$snapshotPath}");

        $this->info("\n💾 Applying assignments in transaction...");
        $inserted = 0;

        try {
            DB::transaction(function () use ($assignmentPlan, &$inserted) {
                foreach ($assignmentPlan as $plan) {
                    FeatureAssignment::create([
                        'feature_id' => $plan['feature_id'],
                        'assignable_type' => self::ASSIGNABLE_TYPE,
                        'assignable_id' => $plan['assignable_id'],
                        'is_required' => false,
                        'is_visible' => true,
                        'display_order' => 999,
                    ]);
                    $inserted++;
                }
            });

            $this->info(sprintf("\n✅ SUCCESS: %d assignments created", $inserted));
        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: Transaction failed - {$e->getMessage()}");
            $this->warn("⚠️  Rollback executed. No changes applied.");

            LogService::error('ups_ai_feature_map_failed', [
                'error' => $e->getMessage(),
                'stats' => $this->stats,
            ], $e);

            return Command::FAILURE;
        }

        // Write manual review to file
        if (!empty($manualReview)) {
            $reviewPath = $this->writeManualReview($manualReview);
            $this->warn("\n⚠️  Manual review required: {$reviewPath}");
        }

        // Re-check unassigned count
        $this->info("\n🔍 Verifying results...");
        $afterUsage = $this->governance->getUsageStats(['orphaned' => true]);
        $afterUnassigned = collect($afterUsage)->filter(fn($f) => $f['is_orphaned'])->count();

        $this->info(sprintf(
            "BEFORE: %d unassigned | AFTER: %d unassigned",
            $unassigned->count(),
            $afterUnassigned
        ));

        LogService::info('ups_ai_feature_map_success', [
            'stats' => $this->stats,
            'inserted' => $inserted,
            'before_unassigned' => $unassigned->count(),
            'after_unassigned' => $afterUnassigned,
            'snapshot' => $snapshotPath,
            'duration_ms' => (int) LogService::stopTimer($t0),
        ]);

        return Command::SUCCESS;
    }

    private function resolvePivotId(?string $categorySlug, ?string $yayinTipiSlug): ?int
    {
        if (!$categorySlug) {
            return null;
        }

        $kategoriId = self::CATEGORY_MAP[$categorySlug] ?? null;

        if (!$kategoriId) {
            return null;
        }

        // If yayin_tipi_slug provided, find exact match
        if ($yayinTipiSlug) {
            $pivot = YayinTipiSablonu::where('kategori_id', $kategoriId)
                ->active()
                ->whereRaw('LOWER(yayin_tipi) = ?', [strtolower($yayinTipiSlug)])
                ->first();

            if ($pivot) {
                return $pivot->id;
            }
        }

        // FALLBACK 1: Most common pivot for kategori_id (by ilan count)
        $pivot = YayinTipiSablonu::where('kategori_id', $kategoriId)
            ->active()
            ->withCount('ilanlar')
            ->orderByDesc('ilanlar_count')
            ->first();

        if ($pivot) {
            return $pivot->id;
        }

        // FALLBACK 2 (DEADLOCK BREAKER): First active pivot for kategori_id
        $pivot = YayinTipiSablonu::where('kategori_id', $kategoriId)
            ->active()
            ->orderBy('id', 'asc')
            ->first();

        return $pivot?->id;
    }

    private function createSnapshot(array $assignmentPlan): string
    {
        $timestamp = now()->format('Ymd_His');
        $dir = storage_path('logs/ups_ai_map');

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = "feature_assignments_snapshot_{$timestamp}.json";
        $path = "{$dir}/{$filename}";

        $snapshot = [
            'timestamp' => now()->toISOString(),
            'command' => 'ups:ai-feature-map --apply',
            'assignable_type' => self::ASSIGNABLE_TYPE,
            'stats' => $this->stats,
            'plan' => $assignmentPlan,
        ];

        file_put_contents(
            $path,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $path;
    }

    private function writeManualReview(array $manualReview): string
    {
        $timestamp = now()->format('Ymd_His');
        $dir = storage_path('logs/ups_ai_map');
        $filename = "manual_review_{$timestamp}.json";
        $path = "{$dir}/{$filename}";

        $data = [
            'timestamp' => now()->toISOString(),
            'count' => count($manualReview),
            'items' => $manualReview,
        ];

        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $path;
    }

    private function printSummary(array $assignmentPlan, array $manualReview, bool $apply): void
    {
        $this->newLine();
        $this->info('📊 SUMMARY');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total features', $this->stats['total']],
                ['Will assign', $this->stats['will_assign']],
                ['Duplicate (skip)', $this->stats['duplicate_skip']],
                ['Low confidence', $this->stats['low_confidence']],
                ['Missing pivot', $this->stats['missing_pivot']],
                ['Manual review', count($manualReview)],
            ]
        );

        if (!empty($assignmentPlan) && count($assignmentPlan) <= 10) {
            $this->newLine();
            $this->info('🎯 Assignment Plan (top 10):');
            $this->table(
                ['Feature Slug', 'Category', 'Yayin Tipi', 'Confidence'],
                collect($assignmentPlan)->take(10)->map(fn($p) => [
                    $p['slug'],
                    $p['category_slug'],
                    $p['yayin_tipi_slug'] ?? 'auto',
                    number_format($p['confidence'], 2),
                ])->toArray()
            );
        }
    }
}
