<?php

namespace App\Services\Intelligence;

use App\Models\AgentMemory;
use App\Models\AgentRun;
use App\Models\GovernanceDecision;
use App\Models\GovernanceSuppression;
use App\Models\OptimizerSuggestion;
use Illuminate\Support\Facades\Cache;

/**
 * OperatorIntelligenceService — SAB5 Operator Control Layer
 *
 * Aggregates system-wide AI metrics for the operator status bar
 * and intelligence center. Provides the single source of truth
 * for system state, risk assessment, and control actions.
 */
class OperatorIntelligenceService
{
    /**
     * Get global AI system status for the top bar.
     * Cached for 30 seconds to avoid per-request DB hits.
     */
    public function getSystemStatus(): array
    {
        return Cache::remember('sab5:system_status', 30, function () {
            return $this->computeSystemStatus();
        });
    }

    /**
     * Compute fresh system status (uncached).
     */
    private function computeSystemStatus(): array
    {
        $safeMode = config('governance.safe_mode', false);
        $pendingCount = GovernanceDecision::pending()->count();
        $blockedCount = GovernanceDecision::where('karar_durumu', 'blocked')->count();

        $today = now()->startOfDay();
        $autoRunToday = GovernanceDecision::where('karar_durumu', 'auto_applied')
            ->where('karar_tarihi', '>=', $today)
            ->count();
        $rollbacksToday = GovernanceDecision::where('karar_durumu', 'rolled_back')
            ->where('updated_at', '>=', $today)
            ->count();

        // Get latest watcher run for overall agent status
        $latestWatcher = AgentRun::forAgent('watcher')->latest('started_at')->first();
        $watcherStatus = $latestWatcher
            ? ($latestWatcher->agent_durumu === 'completed' ? 'running' : $latestWatcher->agent_durumu)
            : 'idle';

        // Count active agents (ran in last hour)
        $activeAgents = AgentRun::where('started_at', '>=', now()->subHour())
            ->where('agent_durumu', 'completed')
            ->distinct('agent_name')
            ->count('agent_name');

        // Pending optimizer suggestions
        $pendingSuggestions = OptimizerSuggestion::pending()->count();

        // Risk level assessment
        $riskLevel = $this->assessRiskLevel($pendingCount, $blockedCount, $rollbacksToday, $safeMode);

        return [
            'watcher_status' => $watcherStatus,
            'active_agents' => $activeAgents,
            'pending_decisions' => $pendingCount,
            'blocked_count' => $blockedCount,
            'auto_run_today' => $autoRunToday,
            'rollbacks_today' => $rollbacksToday,
            'pending_suggestions' => $pendingSuggestions,
            'risk_level' => $riskLevel,
            'safe_mode' => $safeMode,
            'last_scan' => $latestWatcher?->started_at?->diffForHumans(),
        ];
    }

    /**
     * Assess overall system risk level.
     */
    private function assessRiskLevel(int $pending, int $blocked, int $rollbacks, bool $safeMode): string
    {
        if ($safeMode) {
            return 'safe_mode';
        }

        if ($blocked >= 3 || $rollbacks >= 3) {
            return 'high';
        }

        if ($blocked >= 1 || $rollbacks >= 1 || $pending >= 10) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Full system overview for Intelligence Center.
     */
    public function getSystemOverview(): array
    {
        $totals = [
            'total_findings' => GovernanceDecision::count(),
            'total_decisions' => GovernanceDecision::whereNotNull('karar_tarihi')->count(),
            'auto_applied' => GovernanceDecision::autoApplied()->count(),
            'pending' => GovernanceDecision::pending()->count(),
            'approved' => GovernanceDecision::approved()->count(),
            'rejected' => GovernanceDecision::rejected()->count(),
            'blocked' => GovernanceDecision::where('karar_durumu', 'blocked')->count(),
            'failed' => GovernanceDecision::failed()->count(),
            'rolled_back' => GovernanceDecision::rolledBack()->count(),
            'overridden' => GovernanceDecision::overridden()->count(),
        ];

        $totalResolved = $totals['auto_applied'] + $totals['approved'] + $totals['rejected'];
        $totals['auto_run_rate'] = $totalResolved > 0
            ? round(($totals['auto_applied'] / $totalResolved) * 100, 1)
            : 0;

        $totals['suppression_count'] = GovernanceSuppression::where('aktiflik_durumu', true)->count();

        return $totals;
    }

    /**
     * Get live decision feed (recent decisions with context).
     */
    public function getLiveDecisionFeed(int $limit = 30): array
    {
        return GovernanceDecision::with('kararVeren')
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'time' => $d->updated_at?->format('H:i'),
                    'title' => $d->title,
                    'karar_durumu' => $d->karar_durumu,
                    'severity' => $d->severity?->value ?? 'unknown',
                    'source' => $d->source,
                    'domain' => $d->domain,
                    'confidence' => $d->confidence,
                    'karar_veren' => $d->kararVeren?->name,
                    'explanation_summary' => $d->explanation['summary'] ?? null,
                    'is_overridden' => $d->isOverridden(),
                ];
            })
            ->toArray();
    }

    /**
     * Get risk panel data — high-risk decisions, frequent rollbacks, unstable rules.
     */
    public function getRiskPanel(): array
    {
        $highRiskDecisions = GovernanceDecision::where(function ($q) {
                $q->where('karar_durumu', 'blocked')
                  ->orWhere('karar_durumu', 'failed')
                  ->orWhere('confidence', '<', 0.5);
            })
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'karar_durumu' => $d->karar_durumu,
                'severity' => $d->severity?->value,
                'confidence' => $d->confidence,
                'source' => $d->source,
                'reason' => $d->reason,
            ])
            ->toArray();

        // Frequent rollback sources (last 30 days)
        $rollbackSources = GovernanceDecision::rolledBack()
            ->where('updated_at', '>=', now()->subDays(30))
            ->selectRaw('source, domain, COUNT(*) as rollback_count')
            ->groupBy('source', 'domain')
            ->orderByDesc('rollback_count')
            ->limit(5)
            ->get()
            ->toArray();

        // Unstable rules — sources with both auto_applied AND rolled_back
        $unstableRules = GovernanceDecision::whereIn('karar_durumu', ['auto_applied', 'rolled_back', 'failed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->selectRaw('source, domain, karar_durumu, COUNT(*) as cnt')
            ->groupBy('source', 'domain', 'karar_durumu')
            ->get()
            ->groupBy(fn ($r) => $r->source . '.' . $r->domain)
            ->filter(fn ($group) => $group->pluck('karar_durumu')->unique()->count() > 1)
            ->map(fn ($group) => [
                'rule' => $group->first()->source . '.' . $group->first()->domain,
                'breakdown' => $group->pluck('cnt', 'karar_durumu')->toArray(),
            ])
            ->values()
            ->take(5)
            ->toArray();

        return [
            'high_risk_decisions' => $highRiskDecisions,
            'rollback_sources' => $rollbackSources,
            'unstable_rules' => $unstableRules,
        ];
    }

    /**
     * Get system memory visualization data.
     */
    public function getSystemMemory(): array
    {
        $memories = AgentMemory::active()
            ->orderBy('agent_name')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('agent_name')
            ->map(fn ($group) => $group->map(fn ($m) => [
                'key' => $m->memory_key,
                'type' => $m->memory_type,
                'value' => $m->memory_value,
                'updated' => $m->updated_at?->diffForHumans(),
                'expires' => $m->expires_at?->diffForHumans(),
            ])->toArray())
            ->toArray();

        $optimizerHistory = OptimizerSuggestion::whereIn('oneri_durumu', ['applied', 'approved', 'rejected'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'type' => $s->suggestion_type,
                'target' => $s->target_rule,
                'oneri_durumu' => $s->oneri_durumu,
                'confidence' => $s->confidence,
                'reason' => $s->reason,
                'date' => $s->updated_at?->format('d.m.Y H:i'),
            ])
            ->toArray();

        return [
            'agent_memories' => $memories,
            'optimizer_history' => $optimizerHistory,
        ];
    }

    /**
     * Get current AI behavior settings.
     */
    public function getBehaviorSettings(): array
    {
        return [
            'safe_mode' => config('governance.safe_mode', false),
            'auto_run_threshold' => config('governance.auto_run_threshold', 'low'),
            'risk_tolerance' => config('governance.risk_tolerance', 'medium'),
            'confidence_minimum' => config('governance.confidence_minimum', 0.5),
            'max_daily_actions' => config('governance.max_daily_actions', 50),
        ];
    }

    /**
     * Invalidate cached system status.
     */
    public function invalidateCache(): void
    {
        Cache::forget('sab5:system_status');
    }
}
