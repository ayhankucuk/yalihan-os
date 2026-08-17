<?php

namespace App\Services\Execution;

use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Traits\GuardsAgentWrites;

/**
 * ExecutionRuntimeService — Sprint 13/15 Replay & Recovery
 *
 * Canonical runtime execution lifecycle service.
 *
 * Mimari garantiler:
 *   1. replay() her zaman yeni UUID üretir — orijinal record değiştirilmez
 *   2. replay_of_uuid root execution'a pointer verir (transitive closure)
 *   3. State transitions (RUNNING/COMPLETED/FAILED) repository üzerinden yapılır
 *   4. Tenant isolation KURAL 1 ile zorunlu
 *
 * DI: ExecutionRuntimeRepositoryInterface üzerinden tüm veri erişimi
 * Contract: RecoveryEngineService ve OperationsConsoleController tarafından kullanılır
 *
 * @see RecoveryEngineService::recover()
 * @see OperationsConsoleController
 * @see ExecutionRuntimeRepositoryInterface
 */
class ExecutionRuntimeService
{
    use GuardsAgentWrites;

    public function __construct(
        protected ExecutionRuntimeRepositoryInterface $repository,
    ) {}

    /**
     * Create a REPLAY execution for an existing execution.
     *
     * Replay-safe: always creates a NEW record with a new UUID.
     * Never mutates the original execution.
     *
     * @param string $originalUuid     UUID of the execution to replay
     * @param int|null $actorId       User/system that triggered the replay
     * @param string|null $actorType   ACTOR_USER | ACTOR_HERMES | ACTOR_SYSTEM | ACTOR_AGENT
     * @param string|null $replayReason Human-readable reason for replay
     * @throws \DomainException Original execution not found
     */
    public function replay(
        string $originalUuid,
        ?int $actorId = null,
        ?string $actorType = null,
        ?string $replayReason = null,
    ): WorkforceExecution {
        $this->blockAgentWrite(__FUNCTION__);

        return $this->repository->createReplay(
            originalUuid: $originalUuid,
            overrideData: [
                'actor_id' => $actorId,
                'actor_type' => $actorType ?? WorkforceExecution::ACTOR_SYSTEM,
            ],
            replayReason: $replayReason,
        );
    }

    /**
     * Mark an execution as RUNNING.
     *
     * @throws \DomainException Execution not found
     */
    public function markRunning(string $uuid): WorkforceExecution
    {
        $this->blockAgentWrite(__FUNCTION__);

        return $this->repository->markRunning($uuid);
    }

    /**
     * Mark an execution as COMPLETED with optional result snapshot.
     *
     * @throws \DomainException Execution not found
     */
    public function markCompleted(string $uuid, array $resultSnapshot = []): WorkforceExecution
    {
        $this->blockAgentWrite(__FUNCTION__);

        return $this->repository->markCompleted($uuid, $resultSnapshot);
    }

    /**
     * Mark an execution as FAILED.
     *
     * @throws \DomainException Execution not found
     */
    public function markFailed(
        string $uuid,
        string $errorCode,
        string $errorMessage,
        array $context = [],
    ): WorkforceExecution {
        $this->blockAgentWrite(__FUNCTION__);

        return $this->repository->markFailed($uuid, $errorCode, $errorMessage, $context);
    }
}
