<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Services\Execution\ExecutionMetricsService;
use App\Services\Execution\ExecutionRuntimeService;
use App\Services\Execution\RecoveryEngineService;
use App\Services\Listing\YalihanLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * OperationsConsoleController — Sprint 15 Runtime Operations Console
 *
 * Multi-tenant execution overview for operators.
 *
 * Operatör şu soruları cevaplayabilmeli:
 *   - Hangi capability başarısız?
 *   - Hangi execution replay edildi?
 *   - Hangi tenant etkileniyor?
 *   - Hangi işlem retry bekliyor?
 *   - Başarı ve replay oranları nedir?
 *   - Nerede insan müdahalesi gerekiyor?
 *
 * @sab-ignore-thin
 * @sab-ignore status: HTTP query parameter, not a domain DB field
 * @sab-ignore aktiflik_durumu: not applicable to query params and API response keys
 * @sab-ignore active: HTTP query parameter, not a domain DB field
 */
class OperationsConsoleController extends Controller
{
    public function __construct(
        private readonly ExecutionRuntimeRepositoryInterface $repository,
        private readonly ExecutionRuntimeService $runtimeService,
        private readonly ExecutionMetricsService $metricsService,
        private readonly RecoveryEngineService $recoveryService,
    ) {}

    /**
     * GET /admin/operations
     * Ana konsol sayfası — tüm widget'ları içeren dashboard.
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $tenantId = $this->resolveTenantId($request);

        return view('admin.operations.console', [
            'tenantId' => $tenantId,
        ]);
    }

    /**
     * GET /admin/operations/api/overview
     * Tüm metrikleri tek payload'da döner.
     */
    public function overview(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $report = $this->metricsService->generateReport($tenantId);
        $active = $this->repository->getActiveExecutions($tenantId);
        $failed = $this->repository->getFailedByTenant($tenantId);
        $recoveryQueue = $this->recoveryService->getReadyForRetry($tenantId);

        return response()->json([
            'tenant_id'      => $tenantId,
            'timestamp'      => now()->toIso8601String(),
            'metrics'        => $report,
            'active_executions' => $this->formatMany($active),
            'failed_executions' => $this->formatMany($failed),
            'recovery_queue' => $this->formatMany($recoveryQueue),
            'summary'        => $this->buildSummary($report, $failed, $recoveryQueue),
        ]);
    }

    /**
     * GET /admin/operations/api/executions
     * Filtrelenebilir execution listesi.
     */
    public function executions(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $execStatus = $request->query('execution_status');
        $capKey = $request->query('capability');
        $limit = min((int) $request->query('limit', 50), 200);

        $query = WorkforceExecution::query()
            ->byTenant($tenantId)
            ->orderBy('created_at', 'desc');

        if ($execStatus && in_array($execStatus, WorkforceExecution::VALID_STATUSES, true)) {
            $query->where('execution_status', $execStatus);
        }

        if ($capKey) {
            $query->where('capability', $capKey);
        }

        $executions = $query->limit($limit)->get();

        return response()->json([
            'executions' => $this->formatMany($executions),
            'count'      => $executions->count(),
            'filters'    => [
                'execution_status' => $execStatus,
                'capability'      => $capKey,
                'limit'           => $limit,
            ],
        ]);
    }

    /**
     * GET /admin/operations/api/executions/{uuid}
     * Tek execution detayı + replay zinciri.
     */
    public function show(string $uuid): JsonResponse
    {
        $exec = $this->repository->findByUuid($uuid);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        // Replay zinciri
        $chain = $this->getReplayChain($exec);

        // Recovery plan
        $recoveryPlan = $exec->execution_status === WorkforceExecution::STATUS_FAILED
            ? $this->recoveryService->planRecovery($exec)
            : null;

        return response()->json([
            'execution'     => $this->formatOne($exec),
            'replay_chain' => $this->formatMany($chain),
            'recovery_plan' => $recoveryPlan,
        ]);
    }

    /**
     * POST /admin/operations/api/executions/{uuid}/recover
     * Manuel recovery tetikle.
     */
    public function recover(Request $request, string $uuid): JsonResponse
    {
        $exec = $this->repository->findByUuid($uuid);

        if (!$exec) {
            return response()->json(['error' => 'Execution bulunamadı'], 404);
        }

        if ($exec->execution_status !== WorkforceExecution::STATUS_FAILED) {
            return response()->json([
                'error' => 'Sadece FAILED durumundaki execution\'lar kurtarılabilir.'
            ], 422);
        }

        try {
            $recovery = $this->recoveryService->recover(
                failedExecutionUuid: $uuid,
                actorId: $request->user()?->id,
                actorType: $request->user() ? WorkforceExecution::ACTOR_USER : WorkforceExecution::ACTOR_SYSTEM,
                recoveryReason: $request->string('reason', 'Manuel operatör müdahalesi')->toString(),
            );

            return response()->json([
                'message'   => 'Recovery başarıyla başlatıldı.',
                'recovery'  => $this->formatOne($recovery),
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[OperationsConsole] recover failed', [
                'uuid'  => $uuid,
                'error' => $e->getMessage(),
            ]);
            /** @sab-ignore-catch */
            return response()->json(['error' => 'Recovery başlatılamadı.'], 500);
        }
    }

    /**
     * GET /admin/operations/api/metrics/capability
     * Capability bazında performans metrikleri.
     */
    public function capabilityMetrics(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $capability = $request->query('capability');

        $metrics = $this->metricsService->calculateCapabilityMetrics($tenantId, $capability ?: null);

        return response()->json([
            'tenant_id'  => $tenantId,
            'capabilities' => $metrics,
        ]);
    }

    /**
     * GET /admin/operations/api/recovery-queue
     * Retry kuyruğu.
     */
    public function recoveryQueue(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $limit = min((int) $request->query('limit', 50), 200);

        $queue = $this->recoveryService->getReadyForRetry($tenantId, $limit);

        return response()->json([
            'queue'  => $this->formatMany($queue),
            'count'  => $queue->count(),
        ]);
    }

    // ─── Private ─────────────────────────────────────────────────────────

    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->query('tenant_id') ?? $request->user()?->tenant_id ?? 1);
    }

    private function formatOne(WorkforceExecution $exec): array
    {
        return [
            'uuid'                   => $exec->uuid,
            'aggregate_type'         => $exec->aggregate_type,
            'aggregate_id'           => $exec->aggregate_id,
            'capability'             => $exec->capability,
            'trigger_type'          => $exec->trigger_type,
            'actor_type'            => $exec->actor_type,
            'execution_status'       => $exec->execution_status,
            'status_label'          => $this->statusLabel($exec->execution_status),
            'status_color'          => $this->statusColor($exec->execution_status),
            'error_code'            => $exec->error_code,
            'error_message'         => $exec->error_message,
            'failure_classification'=> $exec->failure_classification,
            'retry_count'           => $exec->retry_count ?? 0,
            'max_retries'           => $exec->max_retries ?? 3,
            'next_retry_at'         => $exec->next_retry_at?->toIso8601String(),
            'duration_ms'           => $exec->duration_ms,
            'started_at'            => $exec->started_at?->toIso8601String(),
            'finished_at'           => $exec->finished_at?->toIso8601String(),
            'created_at'            => $exec->created_at?->toIso8601String(),
            'replay_of_uuid'        => $exec->replay_of_uuid,
            'recovery_of_uuid'       => $exec->recovery_of_uuid,
            'is_replay'             => $exec->isReplay(),
            'is_failed'             => $exec->isFailed(),
        ];
    }

    /** @param \Illuminate\Database\Eloquent\Collection<int, WorkforceExecution> $execs */
    private function formatMany($execs): array
    {
        return $execs->map(fn (WorkforceExecution $e) => $this->formatOne($e))->all();
    }

    private function getReplayChain(WorkforceExecution $exec): array
    {
        $chain = [$exec];

        // transitive closure — follow replay_of_uuid root
        $current = $exec;
        while ($current->replay_of_uuid !== null) {
            $parent = $this->repository->findByUuid($current->replay_of_uuid);
            if (!$parent) {
                break;
            }
            $chain[] = $parent;
            $current = $parent;
        }

        // parent chain
        $current = $exec;
        while ($current->parent_uuid !== null) {
            $child = $this->repository->findByUuid($current->parent_uuid);
            if (!$child) {
                break;
            }
            $chain[] = $child;
            $current = $child;
        }

        // Sort by created_at
        usort($chain, fn (WorkforceExecution $a, WorkforceExecution $b) =>
            $a->created_at->timestamp <=> $b->created_at->timestamp
        );

        return $chain;
    }

    private function buildSummary(array $report, $failed, $recoveryQueue): array
    {
        return [
            'total_executions'  => $report['total_executions'],
            'success_rate'      => $report['success_rate'],
            'failure_rate'      => $report['failure_rate'],
            'replay_rate'       => $report['replay_rate'],
            'avg_retry_count'   => $report['avg_retry_count'],
            'failed_count'      => $failed->count(),
            'recovery_queue'    => $recoveryQueue->count(),
            'needs_attention'   => $failed->count() + $recoveryQueue->count(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            WorkforceExecution::STATUS_REQUESTED  => 'Bekliyor',
            WorkforceExecution::STATUS_RUNNING     => 'Çalışıyor',
            WorkforceExecution::STATUS_COMPLETED  => 'Tamamlandı',
            WorkforceExecution::STATUS_FAILED      => 'Başarısız',
            WorkforceExecution::STATUS_CANCELLED  => 'İptal Edildi',
            default => $status,
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            WorkforceExecution::STATUS_REQUESTED  => 'gray',
            WorkforceExecution::STATUS_RUNNING     => 'blue',
            WorkforceExecution::STATUS_COMPLETED  => 'green',
            WorkforceExecution::STATUS_FAILED      => 'red',
            WorkforceExecution::STATUS_CANCELLED  => 'amber',
            default => 'gray',
        };
    }
}
