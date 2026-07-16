<?php

namespace App\Repositories;

use App\Models\WorkforceExecution;

/**
 * ExecutionRuntimeRepository — Sprint 13 Replay & Recovery
 *
 * Canonical repository for workforce execution records.
 * All state transitions go through this interface.
 */
interface ExecutionRuntimeRepositoryInterface
{
    /**
     * Yeni REQUESTED execution oluştur.
     */
    public function createRequested(array $data): WorkforceExecution;

    /**
     * Execution'ı RUNNING olarak işaretle.
     */
    public function markRunning(string $uuid): WorkforceExecution;

    /**
     * Execution'ı COMPLETED olarak işaretle.
     */
    public function markCompleted(string $uuid, array $resultSnapshot = []): WorkforceExecution;

    /**
     * Execution'ı FAILED olarak işaretle.
     */
    public function markFailed(string $uuid, string $errorCode, string $errorMessage, array $context = []): WorkforceExecution;

    /**
     * Idempotency key ile mevcut execution'ı bul (yoksa null).
     */
    public function findByIdempotencyKey(string $key): ?WorkforceExecution;

    /**
     * UUID ile execution bul.
     */
    public function findByUuid(string $uuid): ?WorkforceExecution;

    /**
     * Replay execution oluştur (yeni UUID, replay_of_uuid set, orijinal değiştirilmez).
     */
    public function createReplay(
        string $originalUuid,
        array $overrideData = [],
        ?string $replayReason = null
    ): WorkforceExecution;

    /**
     * Aggregate (Ilan/Property) bazında execution geçmişini getir.
     */
    public function getExecutionHistory(string $aggregateType, int $aggregateId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Tenant bazında başarısız execution'ları getir.
     */
    public function getFailedByTenant(int $tenantId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Recovery execution başlatıldığında recovery metadata set et.
     * @param array{recovery_of_uuid?: string, failure_classification?: string,
     *                retry_policy?: string, retry_count?: int,
     *                max_retries?: int, next_retry_at?: \Carbon\Carbon|null,
     *                recovered_at?: \Carbon\Carbon} $fields
     */
    public function markRecoveryStarted(string $uuid, array $fields): WorkforceExecution;

    /**
     * Recovery fields güncelle (retry_count, max_retries, next_retry_at, classification).
     */
    public function updateRecoveryFields(string $uuid, array $fields): WorkforceExecution;
}
