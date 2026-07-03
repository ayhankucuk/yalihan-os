<?php

namespace App\Services\Hermes;

use App\Domain\Workspace\Enums\WorkspaceState;
use App\Models\Hermes\HermesAnalytics;
use App\Models\Hermes\HermesEventLog;
use App\Models\Hermes\WorkforceExecutionLog;
use App\Models\PortfolioDriveWorkspace;
use Illuminate\Support\Facades\DB;

/**
 * WorkforceService
 *
 * Sprint 4.3 — AI Workforce Vertical Slice
 * Sprint 4.5 — Digital Property Intelligence Platform
 *
 * Provides dashboard metrics by aggregating HermesEventLog, WorkforceExecutionLog,
 * and PortfolioDriveWorkspace data.
 * Read-only: no external API calls, no financial mutations.
 */
class WorkforceService
{
    /**
     * Get complete workforce dashboard metrics
     */
    public function getDashboardMetrics(?int $tenantId = null): array
    {
        $eventMetrics = $this->getEventMetrics($tenantId);
        $workforceMetrics = $this->getWorkforceMetrics($tenantId);
        $agentMetrics = $this->getAgentMetrics($tenantId);
        $chainMetrics = $this->getChainMetrics($tenantId);
        $driveMetrics = $this->getDriveWorkspaceMetrics($tenantId);
        $lifecycleMetrics = $this->getLifecycleMetrics($tenantId);
        $aiCompletionMetrics = $this->getAiCompletionMetrics($tenantId);

        return [
            'generated_at' => now()->toIso8601String(),
            'tenant_id' => $tenantId,

            // Hermes event bus overview
            'events' => [
                'received' => $eventMetrics['total_received'],
                'processed' => $eventMetrics['total_processed'],
                'failed' => $eventMetrics['total_failed'],
                'success_rate' => $eventMetrics['success_rate'],
            ],

            // Workforce chain metrics
            'workforce' => [
                'total_executions' => $workforceMetrics['total_executions'],
                'completed' => $workforceMetrics['completed'],
                'failed' => $workforceMetrics['failed'],
                'pending' => $workforceMetrics['pending'],
                'success_rate' => $workforceMetrics['success_rate'],
                'avg_duration_ms' => $workforceMetrics['avg_duration_ms'],
            ],

            // Drive workspace metrics — Sprint 4.4
            'drive_workspace' => [
                'total_workspaces' => $driveMetrics['total'],
                'ready' => $driveMetrics['ready'],
                'creating' => $driveMetrics['creating'],
                'error' => $driveMetrics['error'],
                'success_rate' => $driveMetrics['success_rate'],
            ],

            // Sprint 4.5: Property Digital Twin lifecycle
            'property_lifecycle' => [
                'total' => $lifecycleMetrics['total'],
                'by_state' => $lifecycleMetrics['by_state'],
                'completion_percent_avg' => $lifecycleMetrics['completion_percent_avg'],
                'pre_publishing' => $lifecycleMetrics['pre_publishing'],
                'live' => $lifecycleMetrics['live'],
                'archived' => $lifecycleMetrics['archived'],
            ],

            // Sprint 4.5: AI completion metrics
            'ai_workforce' => [
                'avg_completion_percent' => $aiCompletionMetrics['avg_completion_percent'],
                'fully_complete' => $aiCompletionMetrics['fully_complete'],
                'partially_complete' => $aiCompletionMetrics['partially_complete'],
                'not_started' => $aiCompletionMetrics['not_started'],
                'by_agent' => $aiCompletionMetrics['by_agent'],
            ],

            // Per-agent breakdown
            'agents' => $agentMetrics,

            // Chain-level view
            'chains' => [
                'total_chains' => $chainMetrics['total_chains'],
                'complete_chains' => $chainMetrics['complete_chains'],
                'incomplete_chains' => $chainMetrics['incomplete_chains'],
                'completion_rate' => $chainMetrics['completion_rate'],
                'avg_chain_duration_ms' => $chainMetrics['avg_chain_duration_ms'],
            ],

            // Queue size (events received but not yet processed)
            'queue' => [
                'size' => $eventMetrics['pending'],
                'oldest_pending_at' => $eventMetrics['oldest_pending_at'],
            ],
        ];
    }

    /**
     * Get Hermes event-level metrics
     */
    private function getEventMetrics(?int $tenantId): array
    {
        $query = HermesEventLog::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $totalReceived = (clone $query)->count();
        $totalProcessed = (clone $query)->where('status', HermesEventLog::STATUS_PROCESSED)->count();
        $totalFailed = (clone $query)->where('status', HermesEventLog::STATUS_FAILED)->count();
        $pending = (clone $query)->whereIn('status', [
            HermesEventLog::STATUS_RECEIVED,
            HermesEventLog::STATUS_PROCESSING,
        ])->count();

        $oldestPending = (clone $query)
            ->whereIn('status', [HermesEventLog::STATUS_RECEIVED, HermesEventLog::STATUS_PROCESSING])
            ->orderBy('occurred_at')
            ->value('occurred_at');

        return [
            'total_received' => $totalReceived,
            'total_processed' => $totalProcessed,
            'total_failed' => $totalFailed,
            'pending' => $pending,
            'success_rate' => $totalReceived > 0
                ? round(($totalProcessed / $totalReceived) * 100, 1)
                : 100.0,
            'oldest_pending_at' => $oldestPending?->toIso8601String(),
        ];
    }

    /**
     * Get workforce execution-level metrics
     */
    private function getWorkforceMetrics(?int $tenantId): array
    {
        $query = WorkforceExecutionLog::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $total = (clone $query)->count();
        $completed = (clone $query)->where('status', WorkforceExecutionLog::STATUS_COMPLETED)->count();
        $failed = (clone $query)->where('status', WorkforceExecutionLog::STATUS_FAILED)->count();
        $pending = (clone $query)->whereIn('status', [
            WorkforceExecutionLog::STATUS_PENDING,
            WorkforceExecutionLog::STATUS_RUNNING,
        ])->count();
        $avgDuration = (clone $query)->whereNotNull('duration_ms')->avg('duration_ms');

        return [
            'total_executions' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0
                ? round(($completed / $total) * 100, 1)
                : 100.0,
            'avg_duration_ms' => $avgDuration ? round($avgDuration, 2) : 0.0,
        ];
    }

    /**
     * Get per-agent breakdown
     */
    private function getAgentMetrics(?int $tenantId): array
    {
        $query = WorkforceExecutionLog::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $agents = (clone $query)
            ->select('agent_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [WorkforceExecutionLog::STATUS_COMPLETED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [WorkforceExecutionLog::STATUS_FAILED])
            ->selectRaw('AVG(duration_ms) as avg_duration_ms')
            ->groupBy('agent_name')
            ->get();

        $result = [];
        foreach ($agents as $row) {
            $total = (int) $row->total;
            $comp = (int) $row->completed;
            $result[] = [
                'agent' => $row->agent_name,
                'total_executions' => $total,
                'completed' => $comp,
                'failed' => (int) $row->failed,
                'success_rate' => $total > 0 ? round(($comp / $total) * 100, 1) : 100.0,
                'avg_duration_ms' => $row->avg_duration_ms ? round((float) $row->avg_duration_ms, 2) : 0.0,
            ];
        }

        return $result;
    }

    /**
     * Get chain-level metrics
     */
    private function getChainMetrics(?int $tenantId): array
    {
        $query = WorkforceExecutionLog::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        // Get all unique chain IDs
        $chainIds = (clone $query)->distinct()->pluck('chain_id');

        $totalChains = $chainIds->count();
        $completeChains = 0;
        $totalDuration = 0.0;
        $durationCount = 0;

        foreach ($chainIds as $chainId) {
            $isComplete = WorkforceExecutionLog::isChainComplete($chainId);
            if ($isComplete) {
                $completeChains++;
            }

            // Sum duration for this chain (max of all steps)
            $chainDuration = WorkforceExecutionLog::where('chain_id', $chainId)
                ->max('duration_ms');
            if ($chainDuration !== null) {
                $totalDuration += $chainDuration;
                $durationCount++;
            }
        }

        return [
            'total_chains' => $totalChains,
            'complete_chains' => $completeChains,
            'incomplete_chains' => $totalChains - $completeChains,
            'completion_rate' => $totalChains > 0
                ? round(($completeChains / $totalChains) * 100, 1)
                : 100.0,
            'avg_chain_duration_ms' => $durationCount > 0
                ? round($totalDuration / $durationCount, 2)
                : 0.0,
        ];
    }

    /**
     * Get recent chain summaries
     */
    public function getRecentChains(int $limit = 10, ?int $tenantId = null): array
    {
        $query = WorkforceExecutionLog::query()
            ->select('chain_id')
            ->selectRaw('MIN(created_at) as started_at')
            ->selectRaw('MAX(completed_at) as finished_at')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [WorkforceExecutionLog::STATUS_COMPLETED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [WorkforceExecutionLog::STATUS_FAILED])
            ->selectRaw('SUM(duration_ms) as total_duration_ms')
            ->groupBy('chain_id');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $chains = $query->orderByDesc('started_at')
            ->limit($limit)
            ->get();

        return $chains->map(fn ($row) => [
            'chain_id' => $row->chain_id,
            'started_at' => $row->started_at?->toIso8601String(),
            'finished_at' => $row->finished_at?->toIso8601String(),
            'completed_steps' => (int) $row->completed,
            'failed_steps' => (int) $row->failed,
            'total_duration_ms' => $row->total_duration_ms ? round((float) $row->total_duration_ms, 2) : null,
        ])->toArray();
    }

    /**
     * Get Property Digital Twin lifecycle metrics — Sprint 4.5
     */
    private function getLifecycleMetrics(?int $tenantId): array
    {
        $query = PortfolioDriveWorkspace::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $total = (clone $query)->count();

        $byState = [];
        $prePublishing = 0;
        $live = 0;
        $archived = 0;

        $states = (clone $query)
            ->selectRaw('lifecycle_state, COUNT(*) as count')
            ->groupBy('lifecycle_state')
            ->pluck('count', 'lifecycle_state');

        foreach ($states as $state => $count) {
            $byState[$state] = (int) $count;
        }

        $prePublishing = (clone $query)->whereIn('lifecycle_state', [
            WorkspaceState::DRAFT->value,
            WorkspaceState::WORKSPACE_CREATED->value,
            WorkspaceState::MEDIA_READY->value,
            WorkspaceState::DESCRIPTION_READY->value,
            WorkspaceState::QUALITY_CHECKED->value,
            WorkspaceState::READY_FOR_PUBLISH->value,
        ])->count();

        $live = (clone $query)->whereIn('lifecycle_state', [
            WorkspaceState::PUBLISHED->value,
            WorkspaceState::ACTIVE->value,
        ])->count();

        $archived = (clone $query)->where('lifecycle_state', WorkspaceState::ARCHIVED->value)->count();

        $avgPercent = (clone $query)->avg('ai_completion_percent') ?? 0;

        return [
            'total' => $total,
            'by_state' => $byState,
            'completion_percent_avg' => round((float) $avgPercent, 1),
            'pre_publishing' => $prePublishing,
            'live' => $live,
            'archived' => $archived,
        ];
    }

    /**
     * Get AI Workforce completion metrics — Sprint 4.5
     */
    private function getAiCompletionMetrics(?int $tenantId): array
    {
        $query = PortfolioDriveWorkspace::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $total = (clone $query)->count();
        $fullyComplete = (clone $query)->where('ai_completion_percent', 100)->count();
        $partiallyComplete = (clone $query)
            ->where('ai_completion_percent', '>', 0)
            ->where('ai_completion_percent', '<', 100)->count();
        $notStarted = (clone $query)->where('ai_completion_percent', 0)->count();

        // Per-agent completion
        $allFlags = (clone $query)->whereNotNull('ai_completion_flags')->pluck('ai_completion_flags');
        $agents = ['photo_agent', 'description_agent', 'property_score_agent', 'publish_decision_agent'];
        $byAgent = [];

        foreach ($agents as $agent) {
            $complete = $allFlags->filter(fn ($flags) => ($flags[$agent]['complete'] ?? false) === true)->count();
            $byAgent[$agent] = [
                'complete' => $complete,
                'pending' => max(0, $total - $complete),
            ];
        }

        return [
            'avg_completion_percent' => $total > 0 ? round(($fullyComplete * 100 + $partiallyComplete * 50) / $total, 1) : 0.0,
            'fully_complete' => $fullyComplete,
            'partially_complete' => $partiallyComplete,
            'not_started' => $notStarted,
            'by_agent' => $byAgent,
        ];
    }

    /**
     * Get workforce agent registry status
     */
    public function getAgentRegistryStatus(): array
    {
        $registry = app(AgentRegistry::class);
        $stats = $registry->stats();

        return [
            'total_agents' => $stats['total_agents'],
            'workforce_agents' => array_values(array_filter(
                $stats['agents'],
                fn ($agent) => ($agent['layer'] ?? '') === 'workforce'
            )),
            'registered_events' => $registry->getRegisteredEvents(),
        ];
    }

    /**
     * Get Drive workspace metrics — Sprint 4.4
     */
    private function getDriveWorkspaceMetrics(?int $tenantId): array
    {
        $query = PortfolioDriveWorkspace::query();
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $total = (clone $query)->count();
        $ready = (clone $query)->where('workspace_status', PortfolioDriveWorkspace::STATUS_READY)->count();
        $creating = (clone $query)->where('workspace_status', PortfolioDriveWorkspace::STATUS_CREATING)->count();
        $error = (clone $query)->where('workspace_status', PortfolioDriveWorkspace::STATUS_ERROR)->count();

        return [
            'total' => $total,
            'ready' => $ready,
            'creating' => $creating,
            'error' => $error,
            'success_rate' => $total > 0
                ? round(($ready / $total) * 100, 1)
                : 100.0,
        ];
    }
}
