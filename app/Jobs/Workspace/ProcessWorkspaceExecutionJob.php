<?php

namespace App\Jobs\Workspace;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Models\WorkspaceExecution;
use App\Services\Hermes\HermesRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessWorkspaceExecutionJob
 *
 * Sprint 4.7: Workspace Execution Engine
 *
 * Executes a WorkspaceExecution by instantiating the registered handler and calling it.
 *
 * Architecture:
 *   WorkspaceExecutionService::dispatch() → creates record → queues this job
 *   Worker pops → execute() → markRunning → agent handle() → markSucceeded/Failed
 *   On failure → RetryService decides whether to retry or mark permanently failed
 *
 * The job is idempotent — if execution is already terminal, it returns immediately.
 */
class ProcessWorkspaceExecutionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public readonly WorkspaceExecution $execution,
    ) {
        $this->tries   = $execution->max_attempts;
        $this->timeout = $execution->timeout_seconds;
    }

    /**
     * Execute the job.
     */
    public function handle(HermesRegistry $registry): void
    {
        $exec = $this->execution->fresh();
        if (!$exec || $exec->isTerminal()) {
            Log::info('[ProcessWorkspaceExecutionJob] Skipping — execution already terminal', [
                'execution_id' => $this->execution->id,
                'state' => $exec?->state,
            ]);
            return;
        }

        $exec->markRunning();

        Log::info('[ProcessWorkspaceExecutionJob] Processing', [
            'execution_id' => $exec->id,
            'type'         => $exec->execution_type,
            'label'        => $exec->execution_label,
            'attempt'      => $exec->attempt_number,
            'workspace_id' => $exec->workspace_id,
        ]);

        try {
            $result = $this->executeHandler($exec, $registry);

            $exec->markSucceeded($result);

            Log::info('[ProcessWorkspaceExecutionJob] Succeeded', [
                'execution_id' => $exec->id,
                'duration_ms' => $exec->duration_ms,
            ]);
        } catch (\Throwable $e) {
            $this->handleFailure($exec, $e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $exec = $this->execution->fresh();
        if (!$exec || $exec->isTerminal()) {
            return;
        }

        Log::error('[ProcessWorkspaceExecutionJob] Permanently failed', [
            'execution_id' => $exec->id,
            'type'         => $exec->execution_type,
            'error'        => $exception?->getMessage(),
        ]);

        $exec->markFailed(
            $exception?->getMessage() ?? 'Unknown error',
            [
                'exception_class' => get_class($exception),
                'file'            => $exception?->getFile(),
                'line'            => $exception?->getLine(),
                'trace'           => $exception?->getTraceAsString(),
            ]
        );
    }

    /**
     * Prevent duplicate processing via unique job.
     */
    public function uniqueId(): string
    {
        return 'workspace-execution-' . $this->execution->id;
    }

    // ─── Private ───────────────────────────────────────────────────────────

    /**
     * Find the registered handler for this execution type and call it.
     */
    private function executeHandler(WorkspaceExecution $exec, HermesRegistry $registry): array
    {
        // Resolve handler by execution type
        $handler = $this->resolveHandler($exec->execution_type, $registry);

        if (!$handler) {
            throw new \RuntimeException("No handler found for execution type: {$exec->execution_type}");
        }

        // Build a HermesEventContract from the execution payload
        $event = $this->buildEvent($exec);

        // Call the handler
        $result = $handler->handle($event);

        return is_array($result) ? $result : ['result' => $result];
    }

    /**
     * Map execution_type to handler instance.
     */
    private function resolveHandler(string $type, HermesRegistry $registry): ?HermesHandlerContract
    {
        // Map execution_type → event name → handler
        // e.g. 'photo_agent' → 'workforce.workspace.created' → PhotoAgent
        $eventMap = [
            'photo_agent'          => 'workforce.workspace.created',
            'description_agent'   => 'workforce.photo_analysis.completed',
            'property_score_agent' => 'workforce.description.completed',
            'publish_decision_agent' => 'workforce.property_score.calculated',
            'drive_agent'         => 'workforce.workspace.requested',
            'notification_agent'  => 'workforce.notification.requested',
            'workspace_sync'     => 'workspace.sync.requested',
        ];

        $eventName = $eventMap[$type] ?? $type;

        $handlers = $registry->getHandlers($eventName);
        return $handlers[0] ?? null;
    }

    /**
     * Build a HermesEventContract from execution input_payload.
     */
    private function buildEvent(WorkspaceExecution $exec): HermesEventContract
    {
        // Use the existing Hermes event contract infrastructure
        // The event wraps the input_payload as a Hermes-compatible event
        return new class($exec) implements HermesEventContract {
            private WorkspaceExecution $exec;

            public function __construct(WorkspaceExecution $exec)
            {
                $this->exec = $exec;
            }

            public function eventName(): string
            {
                return $this->exec->execution_type;
            }

            public function tenantId(): ?int
            {
                return $this->exec->tenant_id;
            }

            public function toPayload(): array
            {
                $payload = $this->exec->input_payload ?? [];
                $payload['_workspace_id'] = $this->exec->workspace_id;
                $payload['_ilan_id']       = $this->exec->ilan_id;
                $payload['_execution_id'] = $this->exec->id;
                $payload['_chain_id']     = $this->exec->chain_id;
                return $payload;
            }
        };
    }

    /**
     * Handle failure with retry logic.
     */
    private function handleFailure(WorkspaceExecution $exec, \Throwable $e): void
    {
        $canRetry = $exec->canRetry();

        if ($canRetry) {
            $backoffSeconds = $exec->getBackoffSeconds();

            Log::warning('[ProcessWorkspaceExecutionJob] Will retry', [
                'execution_id'   => $exec->id,
                'retry_count'     => $exec->retry_count + 1,
                'backoff_seconds' => $backoffSeconds,
                'error'           => $e->getMessage(),
            ]);

            $exec->markRetrying();

            // Re-dispatch with delay
            self::dispatch($exec)
                ->delay(now()->addSeconds($backoffSeconds))
                ->onQueue($exec->queue_name);
        } else {
            Log::error('[ProcessWorkspaceExecutionJob] Max retries exceeded', [
                'execution_id' => $exec->id,
                'retry_count'  => $exec->retry_count,
                'error'        => $e->getMessage(),
            ]);

            $exec->markFailed($e->getMessage(), [
                'exception_class' => get_class($e),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
            ]);
        }
    }
}
