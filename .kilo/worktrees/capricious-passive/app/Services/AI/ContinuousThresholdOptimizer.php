<?php

namespace App\Services\AI;

use App\Models\AiFeatureUsage;
use App\Models\AiOptimizationRun;
use App\Models\AiThresholdOverride;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContinuousThresholdOptimizer
{
    private const MIN_SAMPLE_SIZE = 50;
    private const WINDOW_DEFAULT = '7d';

    /**
     * Run the optimization process
     */
    public function optimize(string $window = self::WINDOW_DEFAULT, bool $dryRun = false, ?int $categoryId = null): array
    {
        $startedAt = now();
        $days = (int) filter_var($window, FILTER_SANITIZE_NUMBER_INT) ?: 7;

        $metrics = $this->calculateMetrics($days, $categoryId);
        $overrides = [];
        $diff = [];

        foreach ($metrics as $metric) {
            $currentThresholds = $this->getCurrentThresholds($metric->kategori_id, $metric->yayin_tipi_id);
            $newThresholds = $this->calculateNewThresholds($metric, $currentThresholds);

            if ($this->hasChanges($currentThresholds, $newThresholds)) {
                $overrides[] = [
                    'kategori_id' => $metric->kategori_id,
                    'yayin_tipi_id' => $metric->yayin_tipi_id,
                    'auto_apply_threshold' => $newThresholds['auto_apply'],
                    'suggest_threshold' => $newThresholds['suggest'],
                    'prev_auto' => $currentThresholds['auto_apply'],
                    'prev_suggest' => $currentThresholds['suggest'],
                    'metrics' => [
                        'accept_rate' => $metric->accept_rate,
                        'false_positive_rate' => $metric->false_positive_rate,
                        'sample_size' => $metric->total_count
                    ]
                ];

                $diff[] = [
                    'context' => "Cat:{$metric->kategori_id}|Pub:{$metric->yayin_tipi_id}",
                    'before' => $currentThresholds,
                    'after' => $newThresholds
                ];
            }
        }

        if (!$dryRun && count($overrides) > 0) {
            $run = AiOptimizationRun::create([
                'window' => $window,
                'changed_count' => count($overrides),
                'diff_json' => $diff,
                'started_at' => $startedAt,
                'ended_at' => now(),
                'executed_by' => 'system'
            ]);

            foreach ($overrides as $ov) {
                AiThresholdOverride::updateOrCreate(
                    [
                        'kategori_id' => $ov['kategori_id'],
                        'yayin_tipi_id' => $ov['yayin_tipi_id']
                    ],
                    [
                        'auto_apply_threshold' => $ov['auto_apply_threshold'],
                        'suggest_threshold' => $ov['suggest_threshold'],
                        'run_id' => $run->id,
                        'calculated_at' => now()
                    ]
                );
            }
        }

        return [
            'run_id' => $run->id ?? null,
            'changed_count' => count($overrides),
            'changes' => $overrides,
            'metrics_analyzed' => count($metrics)
        ];
    }

    /**
     * Calculate performance metrics per context
     */
    private function calculateMetrics(int $days, ?int $categoryId = null): \Illuminate\Support\Collection
    {
        $query = DB::table('ai_feature_usages')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                'kategori_id',
                'yayin_tipi_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN aksiyon = "user_applied" OR aksiyon = "auto_applied" THEN 1 ELSE 0 END) as accepted_count'),
                DB::raw('SUM(CASE WHEN aksiyon = "dismissed" THEN 1 ELSE 0 END) as dismissed_count'),
                DB::raw('SUM(CASE WHEN aksiyon = "auto_applied" THEN 1 ELSE 0 END) as auto_applied_count')
            )
            ->groupBy('kategori_id', 'yayin_tipi_id')
            ->having('total_count', '>=', self::MIN_SAMPLE_SIZE);

        if ($categoryId) {
            $query->where('kategori_id', $categoryId);
        }

        return $query->get()->map(function ($row) {
            $totalValid = $row->accepted_count + $row->dismissed_count;
            $row->accept_rate = $totalValid > 0 ? $row->accepted_count / $totalValid : 0;
            $row->false_positive_rate = $totalValid > 0 ? $row->dismissed_count / $totalValid : 0;
            return $row;
        });
    }

    /**
     * Get current thresholds (from DB overrides or config)
     */
    private function getCurrentThresholds(?int $catId, ?int $pubId): array
    {
        $engine = app(AdaptiveThresholdEngine::class);
        $current = $engine->getActiveThresholds($catId, $pubId);

        return [
            'auto_apply' => (float) $current['auto_apply'],
            'suggest' => (float) $current['suggest']
        ];
    }

    /**
     * Apply optimization logic
     */
    private function calculateNewThresholds($metric, array $current): array
    {
        $newAuto = $current['auto_apply'];
        $newSuggest = $current['suggest'];

        // Rule 1: High False Positive -> Slacken (Increase threshold)
        if ($metric->false_positive_rate > 0.30) {
            $newAuto += 0.03;
        }

        // Rule 2: Low Accept Rate -> Slacken Suggestion
        if ($metric->accept_rate < 0.45) {
            $newSuggest += 0.02;
        }

        // Rule 3: High Accept + Low FP -> Tighten (Decrease threshold)
        if ($metric->accept_rate > 0.75 && $metric->false_positive_rate < 0.10) {
            $newAuto -= 0.02;
        }

        // Enforce Bounds
        $newAuto = max(0.60, min(0.95, $newAuto));
        $newSuggest = max(0.30, min(0.85, $newSuggest));

        // Enforce Invariant: auto >= suggest + 0.20
        if ($newAuto < ($newSuggest + 0.20)) {
            $newAuto = $newSuggest + 0.20;
        }

        // Special Case: Yazlık (ID 5)
        if ($metric->kategori_id == 5) {
            $newAuto = max(0.90, $newAuto);
        }

        return [
            'auto_apply' => round($newAuto, 3),
            'suggest' => round($newSuggest, 3)
        ];
    }

    private function hasChanges(array $old, array $new): bool
    {
        return abs($old['auto_apply'] - $new['auto_apply']) > 0.001 ||
               abs($old['suggest'] - $new['suggest']) > 0.001;
    }

    /**
     * Rollback a specific run
     */
    public function rollback(int $runId): bool
    {
        $run = AiOptimizationRun::with('overrides')->find($runId);
        if (!$run) return false;

        // Batch pre-fetch: önceki override'ları tek sorguda al (N+1 önleme)
        $overrideKeys = $run->overrides->map(fn($o) => $o->kategori_id . '-' . $o->yayin_tipi_id)->unique()->toArray();
        $previousByKey = AiThresholdOverride::where('run_id', '<', $runId)
            ->whereIn(DB::raw("CONCAT(kategori_id, '-', yayin_tipi_id)"), $overrideKeys)
            ->orderBy('id', 'desc') // context7-ignore
            ->get()
            ->groupBy(fn($o) => $o->kategori_id . '-' . $o->yayin_tipi_id)
            ->map->first();

        foreach ($run->overrides as $override) {
            $lookupKey = $override->kategori_id . '-' . $override->yayin_tipi_id;
            $previous = $previousByKey[$lookupKey] ?? null;

            if ($previous) {
                // Restore previous
                $override->update([
                    'auto_apply_threshold' => $previous->auto_apply_threshold,
                    'suggest_threshold' => $previous->suggest_threshold,
                    'source' => 'rollback',
                    'run_id' => null
                ]);
            } else {
                // Delete if no previous, fallback to config
                $override->delete();
            }
        }

        return true;
    }
}
