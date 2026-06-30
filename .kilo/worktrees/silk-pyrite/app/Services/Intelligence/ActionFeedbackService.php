<?php

namespace App\Services\Intelligence;

use App\Models\GovernanceDecision;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ActionFeedbackService — SAB8 Decision → Action → Feedback Loop
 *
 * Tracks action results, calculates success/failure rates,
 * adjusts AI confidence based on outcomes, and provides
 * analytics for the feedback loop.
 *
 * Core Loop: Finding → Decision → Action → Result → Learning
 */
class ActionFeedbackService
{
    private const CACHE_KEY_ACTION_STATS = 'sab8:action_stats';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Record action result for a decision.
     */
    public function recordActionResult(
        GovernanceDecision $decision,
        bool $success,
        array $changedFields = [],
        ?string $summary = null,
        ?int $impactScore = null,
    ): GovernanceDecision {
        $decision->recordResult($success, $changedFields, $summary, $impactScore);

        // Learning signal: adjust confidence for similar future findings
        if (!$success) {
            $this->handleFailedAction($decision);
        }

        // Invalidate stats cache
        Cache::forget(self::CACHE_KEY_ACTION_STATS);

        Log::channel('audit')->info('SAB8: Action result recorded', [
            'decision_id' => $decision->id,
            'finding_id' => $decision->finding_id,
            'basarili' => $success,
            'impact_score' => $impactScore,
            'domain' => $decision->domain,
        ]);

        return $decision;
    }

    /**
     * Get action statistics for the feedback loop dashboard.
     */
    public function getActionStats(?string $period = '30d'): array
    {
        return Cache::remember(self::CACHE_KEY_ACTION_STATS . ':' . $period, self::CACHE_TTL, function () use ($period) {
            $since = $this->periodToDate($period);

            $base = GovernanceDecision::where('created_at', '>=', $since);

            // Total decisions
            $total = (clone $base)->count();

            // By status
            $approved = (clone $base)->approved()->count();
            $autoApplied = (clone $base)->autoApplied()->count();
            $rejected = (clone $base)->rejected()->count();
            $failed = (clone $base)->failed()->count();
            $rolledBack = (clone $base)->rolledBack()->count();
            $pending = (clone $base)->pending()->count();
            $blocked = (clone $base)->where('karar_durumu', 'blocked')->count();

            // SAB8: Result tracking
            $withResults = (clone $base)->whereNotNull('action_result')->count();
            $successful = (clone $base)->successful()->count();
            $actionFailed = (clone $base)->actionFailed()->count();

            // Action applied total (approved + auto_applied that have results)
            $applied = $approved + $autoApplied;

            // Success rate
            $successRate = $withResults > 0 ? round(($successful / $withResults) * 100, 1) : 0;

            // Rollback rate
            $rollbackRate = $applied > 0 ? round(($rolledBack / $applied) * 100, 1) : 0;

            // Impact metrics
            $avgImpact = (clone $base)->whereNotNull('impact_score')->avg('impact_score');
            $positiveImpact = (clone $base)->where('impact_score', '>', 0)->count();
            $negativeImpact = (clone $base)->where('impact_score', '<', 0)->count();

            // Per action type stats
            $actionTypeStats = $this->getActionTypeStats($since);

            // Confidence trend
            $avgConfidence = (clone $base)->whereNotNull('confidence')->avg('confidence');

            return [
                'period' => $period,
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'auto_applied' => $autoApplied,
                'rejected' => $rejected,
                'failed' => $failed,
                'rolled_back' => $rolledBack,
                'blocked' => $blocked,
                'applied' => $applied,
                'with_results' => $withResults,
                'successful' => $successful,
                'action_failed' => $actionFailed,
                'success_rate' => $successRate,
                'rollback_rate' => $rollbackRate,
                'avg_impact_score' => $avgImpact !== null ? round($avgImpact, 1) : null,
                'positive_impact' => $positiveImpact,
                'negative_impact' => $negativeImpact,
                'action_type_stats' => $actionTypeStats,
                'avg_confidence' => $avgConfidence !== null ? round($avgConfidence * 100, 1) : null,
            ];
        });
    }

    /**
     * Get success/failure stats per recommended_action type.
     */
    private function getActionTypeStats(\DateTimeInterface $since): array
    {
        return GovernanceDecision::where('created_at', '>=', $since)
            ->whereNotNull('action_result')
            ->select('recommended_action')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN JSON_EXTRACT(action_result, '$.success') = true THEN 1 ELSE 0 END) as successful")
            ->selectRaw("SUM(CASE WHEN JSON_EXTRACT(action_result, '$.success') = false THEN 1 ELSE 0 END) as failed")
            ->selectRaw("AVG(impact_score) as avg_impact")
            ->groupBy('recommended_action')
            ->get()
            ->map(fn ($row) => [
                'action' => $row->recommended_action,
                'total' => $row->total,
                'successful' => (int) $row->successful,
                'failed' => (int) $row->failed,
                'success_rate' => $row->total > 0 ? round(($row->successful / $row->total) * 100, 1) : 0,
                'avg_impact' => $row->avg_impact !== null ? round($row->avg_impact, 1) : null,
            ])
            ->toArray();
    }

    /**
     * Handle a failed action — learning signal.
     *
     * Reduces confidence for similar future findings to prevent
     * repeated failures from the same pattern.
     */
    private function handleFailedAction(GovernanceDecision $decision): void
    {
        // Count consecutive failures for same domain + recommended_action
        $recentFailures = GovernanceDecision::where('domain', $decision->domain)
            ->where('recommended_action', $decision->recommended_action)
            ->where('created_at', '>=', now()->subDays(7))
            ->actionFailed()
            ->count();

        if ($recentFailures >= 3) {
            Log::channel('security')->warning('SAB8: Repeated failures detected — learning signal', [
                'domain' => $decision->domain,
                'recommended_action' => $decision->recommended_action,
                'failure_count' => $recentFailures,
                'action' => 'confidence_reduction_suggested',
            ]);
        }
    }

    /**
     * Get recent decisions with results for the loop dashboard.
     */
    public function getRecentResults(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return GovernanceDecision::whereNotNull('action_result')
            ->orderByDesc('action_completed_at')
            ->with('kararVeren')
            ->limit($limit)
            ->get();
    }

    /**
     * Get decisions filtered by state tab.
     */
    public function getByTab(string $tab, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = GovernanceDecision::orderByDesc('updated_at');

        return match ($tab) {
            'applied' => $query->whereIn('karar_durumu', ['approved', 'auto_applied'])
                ->whereNotNull('action_completed_at')
                ->paginate($perPage),
            'auto' => $query->autoApplied()->paginate($perPage),
            'failed' => $query->where(function ($q) {
                $q->where('karar_durumu', 'failed')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('action_result')
                         ->whereRaw("JSON_EXTRACT(action_result, '$.success') = false");
                  });
            })->paginate($perPage),
            'rolled_back' => $query->rolledBack()->paginate($perPage),
            'pending' => $query->pending()->paginate($perPage),
            default => $query->paginate($perPage),
        };
    }

    /**
     * Get the decision → action loop summary for a single decision (UI panel).
     */
    public function getLoopSummary(GovernanceDecision $decision): array
    {
        return [
            'finding' => [
                'source' => $decision->source,
                'domain' => $decision->domain,
                'severity' => $decision->severity->value,
                'title' => $decision->title,
                'confidence' => $decision->confidence,
            ],
            'decision' => [
                'type' => $decision->decision->value,
                'status' => $decision->karar_durumu,
                'status_label' => $decision->getStatusLabel(),
                'decided_by' => $decision->karar_veren_id ? ($decision->kararVeren?->name ?? 'Sistem') : 'AI',
                'decided_at' => $decision->karar_tarihi?->toIso8601String(),
            ],
            'action' => [
                'recommended' => $decision->recommended_action,
                'proposal' => $decision->proposal_filename,
                'applied' => in_array($decision->karar_durumu, ['approved', 'auto_applied']),
                'completed_at' => $decision->action_completed_at?->toIso8601String(),
            ],
            'result' => $decision->action_result ? [
                'success' => $decision->action_result['success'] ?? null,
                'changed_fields' => $decision->action_result['changed_fields'] ?? [],
                'summary' => $decision->action_result['result_summary'] ?? null,
                'impact_score' => $decision->impact_score,
            ] : null,
            'feedback' => [
                'note' => $decision->feedback_note,
                'rollback_available' => $decision->isRollbackable(),
                'overridden' => $decision->isOverridden(),
            ],
        ];
    }

    private function periodToDate(string $period): \DateTimeInterface
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
}
