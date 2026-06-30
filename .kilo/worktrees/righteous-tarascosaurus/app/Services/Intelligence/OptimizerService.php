<?php

namespace App\Services\Intelligence;

use App\Models\AgentMemory;
use App\Models\GovernanceDecision;
use App\Models\GovernanceRollback;
use App\Models\GovernanceSuppression;
use App\Models\OptimizerSuggestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OptimizerService — SAB4 Learning Engine
 *
 * Analyzes historical governance data to produce improvement suggestions.
 * The optimizer NEVER self-applies changes — all suggestions go through
 * the standard SAB approval pipeline.
 *
 * Learning Patterns:
 * 1. Repeated suppressions → rule too sensitive
 * 2. Frequent rollbacks → auto-run threshold too aggressive
 * 3. Always-approved findings → upgrade to auto-run
 * 4. Repeat failures → structural issue detection
 * 5. Override patterns → policy misalignment
 */
class OptimizerService
{
    private const SUPPRESSION_THRESHOLD = 5;
    private const ROLLBACK_THRESHOLD = 3;
    private const AUTO_APPROVE_THRESHOLD = 10;
    private const FAILURE_THRESHOLD = 3;
    private const OVERRIDE_THRESHOLD = 3;

    /**
     * Run full analysis and generate suggestions.
     *
     * @param int $lookbackDays Number of days to analyze
     * @return array Array of suggestion data (also persisted to DB)
     */
    public function analyze(int $lookbackDays = 30): array
    {
        $since = now()->subDays($lookbackDays);
        $suggestions = [];

        $suggestions = array_merge(
            $suggestions,
            $this->detectRepeatedSuppressions($since),
            $this->detectFrequentRollbacks($since),
            $this->detectAlwaysApproved($since),
            $this->detectRepeatFailures($since),
            $this->detectOverridePatterns($since),
            $this->detectAutonomyLevelSuggestion($since),
        );

        // Persist new suggestions (skip duplicates by target_rule + suggestion_type)
        foreach ($suggestions as $suggestion) {
            $existing = OptimizerSuggestion::where('target_rule', $suggestion['target_rule'])
                ->where('suggestion_type', $suggestion['type'])
                ->where('oneri_durumu', 'pending')
                ->exists();

            if (!$existing) {
                OptimizerSuggestion::create([
                    'suggestion_type' => $suggestion['type'],
                    'target_rule' => $suggestion['target_rule'],
                    'current_value' => $suggestion['current_value'] ?? null,
                    'suggested_value' => $suggestion['suggested_value'] ?? null,
                    'reason' => $suggestion['reason'],
                    'confidence' => $suggestion['confidence'],
                    'evidence' => $suggestion['evidence'],
                ]);
            }
        }

        // Update learning metrics in agent memory
        AgentMemory::remember('optimizer', 'analysis_stats', 'metric', [
            'last_run' => now()->toIso8601String(),
            'lookback_days' => $lookbackDays,
            'suggestions_generated' => count($suggestions),
            'pattern_types' => array_count_values(array_column($suggestions, 'type')),
        ]);

        Log::info('OptimizerService: analysis complete', [
            'suggestions' => count($suggestions),
            'lookback_days' => $lookbackDays,
        ]);

        return $suggestions;
    }

    /**
     * CASE 1: Same finding suppressed N+ times → rule too sensitive
     */
    private function detectRepeatedSuppressions(\DateTimeInterface $since): array
    {
        $suggestions = [];

        $repeated = GovernanceSuppression::select('rule_key', DB::raw('COUNT(*) as suppression_count'))
            ->where('created_at', '>=', $since)
            ->groupBy('rule_key')
            ->having('suppression_count', '>=', self::SUPPRESSION_THRESHOLD)
            ->get();

        foreach ($repeated as $row) {
            $confidence = min(0.95, 0.6 + ($row->suppression_count - self::SUPPRESSION_THRESHOLD) * 0.05);

            $suggestions[] = [
                'type' => 'rule_sensitivity',
                'target_rule' => $row->rule_key,
                'current_value' => 'active',
                'suggested_value' => 'increase_threshold',
                'reason' => "Kural '{$row->rule_key}' son dönemde {$row->suppression_count} kez bastırıldı. Kural hassasiyeti çok yüksek.",
                'confidence' => $confidence,
                'evidence' => [
                    'suppression_count' => $row->suppression_count,
                    'period_days' => now()->diffInDays($since),
                    'pattern' => 'repeated_suppression',
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * CASE 2: Frequent rollbacks on same source/domain → threshold too aggressive
     */
    private function detectFrequentRollbacks(\DateTimeInterface $since): array
    {
        $suggestions = [];

        $rollbacks = GovernanceDecision::where('karar_durumu', 'rolled_back')
            ->where('updated_at', '>=', $since)
            ->select('source', 'domain', DB::raw('COUNT(*) as rollback_count'))
            ->groupBy('source', 'domain')
            ->having('rollback_count', '>=', self::ROLLBACK_THRESHOLD)
            ->get();

        foreach ($rollbacks as $row) {
            $ruleKey = $row->source . '_' . $row->domain;
            $confidence = min(0.90, 0.65 + ($row->rollback_count - self::ROLLBACK_THRESHOLD) * 0.05);

            $suggestions[] = [
                'type' => 'threshold_adjustment',
                'target_rule' => $ruleKey,
                'current_value' => 'auto_run',
                'suggested_value' => 'needs_review',
                'reason' => "'{$ruleKey}' kuralında {$row->rollback_count} geri alma işlemi yapıldı. Otomatik uygulama eşiği çok agresif.",
                'confidence' => $confidence,
                'evidence' => [
                    'rollback_count' => $row->rollback_count,
                    'source' => $row->source,
                    'domain' => $row->domain,
                    'pattern' => 'frequent_rollback',
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * CASE 3: Findings that are always manually approved → can be auto-run
     */
    private function detectAlwaysApproved(\DateTimeInterface $since): array
    {
        $suggestions = [];

        // Find source/domain combos that are always approved with zero rejections
        $candidates = GovernanceDecision::where('created_at', '>=', $since)
            ->whereIn('karar_durumu', ['approved', 'rejected'])
            ->select(
                'source',
                'domain',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN karar_durumu = \'approved\' THEN 1 ELSE 0 END) as approved_count'),
                DB::raw('SUM(CASE WHEN karar_durumu = \'rejected\' THEN 1 ELSE 0 END) as rejected_count')
            )
            ->groupBy('source', 'domain')
            ->having('total', '>=', self::AUTO_APPROVE_THRESHOLD)
            ->having('rejected_count', '=', 0)
            ->get();

        foreach ($candidates as $row) {
            $ruleKey = $row->source . '_' . $row->domain;
            $approvalRate = $row->approved_count / $row->total;
            $confidence = min(0.95, 0.7 + ($row->total - self::AUTO_APPROVE_THRESHOLD) * 0.02);

            $suggestions[] = [
                'type' => 'automation_upgrade',
                'target_rule' => $ruleKey,
                'current_value' => 'needs_review',
                'suggested_value' => 'auto_run',
                'reason' => "'{$ruleKey}' kuralı {$row->total} kez incelendi ve %100 onaylandı. Otomatik uygulamaya yükseltilebilir.",
                'confidence' => $confidence,
                'evidence' => [
                    'total_decisions' => $row->total,
                    'approved' => $row->approved_count,
                    'rejected' => $row->rejected_count,
                    'approval_rate' => $approvalRate,
                    'pattern' => 'always_approved',
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * CASE 4: Repeated failures on same target → structural issue
     */
    private function detectRepeatFailures(\DateTimeInterface $since): array
    {
        $suggestions = [];

        $failures = GovernanceDecision::where('karar_durumu', 'failed')
            ->where('created_at', '>=', $since)
            ->select('source', 'domain', 'target', DB::raw('COUNT(*) as fail_count'))
            ->groupBy('source', 'domain', 'target')
            ->having('fail_count', '>=', self::FAILURE_THRESHOLD)
            ->get();

        foreach ($failures as $row) {
            $ruleKey = $row->source . '_' . $row->domain;

            $suggestions[] = [
                'type' => 'structural_issue',
                'target_rule' => $ruleKey,
                'current_value' => $row->target,
                'suggested_value' => 'investigate_or_suppress',
                'reason' => "'{$row->target}' hedefi {$row->fail_count} kez başarısız oldu. Yapısal sorun olabilir — incelenmeli veya bastırılmalı.",
                'confidence' => 0.85,
                'evidence' => [
                    'fail_count' => $row->fail_count,
                    'target' => $row->target,
                    'pattern' => 'repeat_failure',
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * CASE 5: Frequent overrides in same direction → policy misalignment
     */
    private function detectOverridePatterns(\DateTimeInterface $since): array
    {
        $suggestions = [];

        $overrides = GovernanceDecision::whereNotNull('override_decision')
            ->where('override_at', '>=', $since)
            ->select('source', 'domain', 'override_decision', DB::raw('COUNT(*) as override_count'))
            ->groupBy('source', 'domain', 'override_decision')
            ->having('override_count', '>=', self::OVERRIDE_THRESHOLD)
            ->get();

        foreach ($overrides as $row) {
            $ruleKey = $row->source . '_' . $row->domain;

            $suggestions[] = [
                'type' => 'policy_adjustment',
                'target_rule' => $ruleKey,
                'current_value' => 'current_classification',
                'suggested_value' => $row->override_decision,
                'reason' => "'{$ruleKey}' kuralı {$row->override_count} kez '{$row->override_decision}' olarak override edildi. Varsayılan politika güncellenmelidir.",
                'confidence' => min(0.90, 0.7 + ($row->override_count - self::OVERRIDE_THRESHOLD) * 0.05),
                'evidence' => [
                    'override_count' => $row->override_count,
                    'override_direction' => $row->override_decision,
                    'pattern' => 'frequent_override',
                ],
            ];
        }

        return $suggestions;
    }

    /**
     * Get learning metrics summary for the dashboard.
     */
    public function getLearningMetrics(): array
    {
        $pendingSuggestions = OptimizerSuggestion::pending()->count();
        $appliedSuggestions = OptimizerSuggestion::applied()->count();
        $rejectedSuggestions = OptimizerSuggestion::rejected()->count();
        $avgConfidence = OptimizerSuggestion::pending()->avg('confidence') ?? 0;

        $lastAnalysis = AgentMemory::recall('optimizer', 'analysis_stats');

        return [
            'pending_suggestions' => $pendingSuggestions,
            'applied_suggestions' => $appliedSuggestions,
            'rejected_suggestions' => $rejectedSuggestions,
            'total_suggestions' => $pendingSuggestions + $appliedSuggestions + $rejectedSuggestions,
            'avg_confidence' => round($avgConfidence, 2),
            'last_analysis' => $lastAnalysis,
            'decision_accuracy' => $this->calculateDecisionAccuracy(),
        ];
    }

    // ─── SAB6: Autonomy Level Suggestions ──────────────────────

    /**
     * CASE 6: Suggest autonomy level increase/decrease based on patterns.
     * Never applies directly — operator must approve.
     */
    private function detectAutonomyLevelSuggestion(\DateTimeInterface $since): array
    {
        $suggestions = [];
        $currentLevel = config('governance.autonomy_level', 2);

        // Check for level INCREASE: high success rate + low rollbacks
        $totalAutoRun = GovernanceDecision::where('karar_durumu', 'auto_applied')
            ->where('created_at', '>=', $since)
            ->count();
        $totalRollbacks = GovernanceDecision::where('karar_durumu', 'rolled_back')
            ->where('updated_at', '>=', $since)
            ->count();
        $totalFailed = GovernanceDecision::where('karar_durumu', 'failed')
            ->where('created_at', '>=', $since)
            ->count();

        $successRate = $totalAutoRun > 0
            ? ($totalAutoRun - $totalRollbacks - $totalFailed) / $totalAutoRun
            : 0;

        // Suggest INCREASE if success rate > 95% and at least 20 auto-runs, and level < 4
        if ($currentLevel < 4 && $totalAutoRun >= 20 && $successRate >= 0.95 && $totalRollbacks < 2) {
            $suggestions[] = [
                'type' => 'autonomy_level',
                'target_rule' => 'governance.autonomy_level',
                'current_value' => (string) $currentLevel,
                'suggested_value' => (string) ($currentLevel + 1),
                'reason' => sprintf(
                    'Son dönemde %d otonom aksiyon gerçekleşti, başarı oranı %%%.1f. Otonom seviye %d\'den %d\'e yükseltilebilir.',
                    $totalAutoRun,
                    $successRate * 100,
                    $currentLevel,
                    $currentLevel + 1
                ),
                'confidence' => min(0.85, 0.6 + $successRate * 0.2),
                'evidence' => [
                    'total_auto_run' => $totalAutoRun,
                    'total_rollbacks' => $totalRollbacks,
                    'total_failed' => $totalFailed,
                    'success_rate' => round($successRate, 4),
                    'current_level' => $currentLevel,
                    'pattern' => 'autonomy_upgrade',
                ],
            ];
        }

        // Suggest DECREASE if rollback rate > 10% or failure rate > 15%, and level > 0
        if ($currentLevel > 0 && $totalAutoRun >= 5) {
            $rollbackRate = $totalRollbacks / $totalAutoRun;
            $failureRate = $totalFailed / $totalAutoRun;

            if ($rollbackRate > 0.10 || $failureRate > 0.15) {
                $suggestions[] = [
                    'type' => 'autonomy_level',
                    'target_rule' => 'governance.autonomy_level',
                    'current_value' => (string) $currentLevel,
                    'suggested_value' => (string) max(0, $currentLevel - 1),
                    'reason' => sprintf(
                        'Geri alma oranı %%%.1f, başarısızlık oranı %%%.1f. Otonom seviye %d\'den %d\'e düşürülmelidir.',
                        $rollbackRate * 100,
                        $failureRate * 100,
                        $currentLevel,
                        max(0, $currentLevel - 1)
                    ),
                    'confidence' => min(0.90, 0.7 + max($rollbackRate, $failureRate)),
                    'evidence' => [
                        'total_auto_run' => $totalAutoRun,
                        'total_rollbacks' => $totalRollbacks,
                        'rollback_rate' => round($rollbackRate, 4),
                        'failure_rate' => round($failureRate, 4),
                        'current_level' => $currentLevel,
                        'pattern' => 'autonomy_downgrade',
                    ],
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Calculate decision accuracy: approved / (approved + rejected + rolled_back)
     */
    private function calculateDecisionAccuracy(): float
    {
        $approved = GovernanceDecision::approved()->count() + GovernanceDecision::autoApplied()->count();
        $negative = GovernanceDecision::whereIn('karar_durumu', ['rejected', 'rolled_back', 'failed'])->count();
        $total = $approved + $negative;

        return $total > 0 ? round($approved / $total, 4) : 1.0;
    }
}
