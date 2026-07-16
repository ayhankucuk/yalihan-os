<?php

namespace App\Repositories;

use App\Models\WorkforceExecution;
use App\Models\WorkforceExecution as WorkforceExecutionModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * EloquentExecutionRuntimeRepository — Sprint 13 Replay & Recovery
 *
 * Implements ExecutionRuntimeRepositoryInterface.
 * All mutations are replay-safe: replay creates NEW records, never mutates originals.
 */
class EloquentExecutionRuntimeRepository implements ExecutionRuntimeRepositoryInterface
{
    public function __construct(
        protected WorkforceExecutionModel $model
    ) {}

    public function createRequested(array $data): WorkforceExecution
    {
        $data['execution_status'] = WorkforceExecution::STATUS_REQUESTED;
        $data['uuid'] ??= (string) Str::uuid();

        return $this->model->forceCreate($data);
    }

    public function markRunning(string $uuid): WorkforceExecution
    {
        $exec = $this->findByUuidOrFail($uuid);
        $exec->execution_status = WorkforceExecution::STATUS_RUNNING; // context7-ignore
        $exec->started_at = now();
        $exec->save();
        return $exec->fresh();
    }

    public function markCompleted(string $uuid, array $resultSnapshot = []): WorkforceExecution
    {
        $exec = $this->findByUuidOrFail($uuid);
        $exec->execution_status = WorkforceExecution::STATUS_COMPLETED; // context7-ignore
        $exec->finished_at = now();
        $exec->duration_ms = $exec->started_at
            ? (int) $exec->started_at->diffInMilliseconds(now())
            : null;
        $exec->result_snapshot = $resultSnapshot;
        $exec->save();
        return $exec->fresh();
    }

    public function markFailed(string $uuid, string $errorCode, string $errorMessage, array $context = []): WorkforceExecution
    {
        $exec = $this->findByUuidOrFail($uuid);
        $exec->execution_status = WorkforceExecution::STATUS_FAILED; // context7-ignore
        $exec->finished_at = now();
        $exec->duration_ms = $exec->started_at
            ? (int) $exec->started_at->diffInMilliseconds(now())
            : null;
        $exec->error_code = $errorCode;
        $exec->error_message = $errorMessage;
        $exec->metadata = array_merge($exec->metadata ?? [], $context);
        $exec->save();
        return $exec->fresh();
    }

    public function findByIdempotencyKey(string $key): ?WorkforceExecution
    {
        return $this->model
            ->where('idempotency_key', $key)
            ->orderBy('id') // Deterministic
            ->first();
    }

    public function findByUuid(string $uuid): ?WorkforceExecution
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Replay-safe: creates a NEW execution record with a new UUID.
     * Never modifies the original execution.
     *
     * replay_of_uuid always points to the ROOT original execution.
     * If the original is itself a replay, the root is preserved (not the immediate parent).
     */
    public function createReplay(
        string $originalUuid,
        array $overrideData = [],
        ?string $replayReason = null
    ): WorkforceExecution {
        $original = $this->FindByUuid($originalUuid);

        if (!$original) {
            throw new \DomainException("Original execution [{$originalUuid}] not found.");
        }

        // replay_of_uuid always points to the root original — never the immediate parent.
        // If original is already a replay, preserve the existing root (transitive closure).
        $rootUuid = $original->replay_of_uuid ?? $originalUuid;

        $replayData = array_merge([
            'parent_uuid'       => $originalUuid,
            'replay_of_uuid'    => $rootUuid,
            'aggregate_type'    => $original->aggregate_type,
            'aggregate_id'      => $original->aggregate_id,
            'capability'        => $original->capability,
            'tenant_id'         => $original->tenant_id,
            'workspace_id'      => $original->workspace_id,
            'actor_type'        => $original->actor_type,
            'actor_id'          => $original->actor_id,
            'trigger_type'      => WorkforceExecution::TRIGGER_REPLAY,
            'replay_reason'     => $replayReason,
            'execution_status'  => WorkforceExecution::STATUS_REQUESTED,
            'input_snapshot'    => $original->input_snapshot,
        ], $overrideData);

        $replayData['uuid'] = (string) Str::uuid();

        return $this->model->forceCreate($replayData);
    }

    public function getExecutionHistory(string $aggregateType, int $aggregateId): Collection
    {
        return $this->model
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getFailedByTenant(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('execution_status', WorkforceExecution::STATUS_FAILED) // context7-ignore
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveExecutions(?int $tenantId = null): Collection
    {
        $query = $this->model
            ->whereIn('execution_status', [
                WorkforceExecution::STATUS_REQUESTED,
                WorkforceExecution::STATUS_RUNNING,
            ])
            ->orderBy('created_at', 'desc');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    private function findByUuidOrFail(string $uuid): WorkforceExecution
    {
        return $this->model->where('uuid', $uuid)->firstOrFail();
    }

    public function markRecoveryStarted(string $uuid, array $fields): WorkforceExecution
    {
        $exec = $this->findByUuidOrFail($uuid);

        $exec->recovery_of_uuid       = $fields['recovery_of_uuid']       ?? $exec->recovery_of_uuid;
        $exec->failure_classification = $fields['failure_classification'] ?? $exec->failure_classification;
        $exec->retry_policy           = $fields['retry_policy']           ?? $exec->retry_policy;
        $exec->retry_count            = $fields['retry_count']            ?? $exec->retry_count;
        $exec->max_retries            = $fields['max_retries']            ?? $exec->max_retries;
        $exec->next_retry_at          = $fields['next_retry_at']          ?? $exec->next_retry_at;
        $exec->recovered_at           = $fields['recovered_at']           ?? $exec->recovered_at;

        $exec->save();
        $exec->refresh();
        return $exec;
    }

    public function updateRecoveryFields(string $uuid, array $fields): WorkforceExecution
    {
        $exec = $this->findByUuidOrFail($uuid);

        $allowed = [
            'failure_classification',
            'retry_policy',
            'retry_count',
            'max_retries',
            'next_retry_at',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $fields)) {
                $exec->$field = $fields[$field];
            }
        }

        $exec->save();
        return $exec->fresh();
    }
}
