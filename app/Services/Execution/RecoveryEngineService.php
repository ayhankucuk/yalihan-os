<?php

namespace App\Services\Execution;

use App\Models\WorkforceExecution;
use App\Repositories\EloquentExecutionRuntimeRepository;
use App\Repositories\ExecutionRuntimeRepositoryInterface;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Collection;

/**
 * RecoveryEngineService — Sprint 13B Recovery Engine
 *
 * Canonical service for automatic recovery of failed executions.
 *
 * Mimari garantiler:
 *   1. Recovery yeni WorkforceExecution üretir — asla FAILED record'u değiştirmez
 *   2. failure_classification retry kararını belirler (PERMANENT = skip)
 *   3. Tenant/workspace isolation KURAL 1 ile zorunlu
 *   4. Retry policy exponential backoff: 10s → 1m → 5m → 15m → 1h
 *
 * @see ADR-042 + Sprint 13B Recovery Contract
 */
class RecoveryEngineService
{
    use GuardsAgentWrites;

    // ── Failure Classification ─────────────────────────────────────────────────

    public const CLASS_TRANSIENT  = 'TRANSIENT';  // Geçici hata — retry ile düzelebilir
    public const CLASS_PERMANENT  = 'PERMANENT';  // Kalıcı hata — retry faydasız
    public const CLASS_CONFIG     = 'CONFIG';     // Yapılandırma hatası — manuel müdahale gerekli
    public const CLASS_UNKNOWN    = 'UNKNOWN';     // Bilinmeyen — incelenmeli

    public const VALID_CLASSIFICATIONS = [
        self::CLASS_TRANSIENT,
        self::CLASS_PERMANENT,
        self::CLASS_CONFIG,
        self::CLASS_UNKNOWN,
    ];

    // ── Retry Policy — Exponential Backoff Delays (seconds) ────────────────────

    public const POLICY_EXPONENTIAL = 'EXPONENTIAL'; // 10s → 1m → 5m → 15m → 1h
    public const POLICY_LINEAR      = 'LINEAR';      // 30s → 1m → 2m → 5m
    public const POLICY_IMMEDIATE   = 'IMMEDIATE';   // Hemen (sadece TRANSIENT)

    public const VALID_POLICIES = [
        self::POLICY_EXPONENTIAL,
        self::POLICY_LINEAR,
        self::POLICY_IMMEDIATE,
    ];

    /** Default retry policy for each failure class */
    private const DEFAULT_POLICY_BY_CLASS = [
        self::CLASS_TRANSIENT => self::POLICY_EXPONENTIAL,
        self::CLASS_PERMANENT => self::POLICY_IMMEDIATE, // 0 retries
        self::CLASS_CONFIG    => self::POLICY_IMMEDIATE, // 0 retries
        self::CLASS_UNKNOWN   => self::POLICY_LINEAR,
    ];

    /** Max retries per policy */
    private const MAX_RETRIES_BY_POLICY = [
        self::POLICY_EXPONENTIAL => 5,
        self::POLICY_LINEAR      => 4,
        self::POLICY_IMMEDIATE   => 0,
    ];

    /** Exponential backoff delays in seconds */
    private const EXPONENTIAL_DELAYS = [10, 60, 300, 900, 3600]; // 10s, 1m, 5m, 15m, 1h

    /** Linear backoff delays in seconds */
    private const LINEAR_DELAYS = [30, 60, 120, 300]; // 30s, 1m, 2m, 5m

    // ── Error Code → Classification Map ──────────────────────────────────────

    /** Error code patterns that indicate PERMANENT failures */
    private const PERMANENT_PATTERNS = [
        'VALIDATION',
        'BUSINESS_RULE',
        'INVARIANT',
        'GUARD_FAILED',
        'POLICY_DENIED',
        'UNAUTHORIZED',
        'FORBIDDEN',
        'NOT_FOUND',
        'DUPLICATE',
        'CONFLICT',
    ];

    /** Error code patterns that indicate CONFIG failures */
    private const CONFIG_PATTERNS = [
        'API_KEY',
        'CREDENTIAL',
        'MISSING_CONFIG',
        'SERVICE_UNAVAILABLE',
        'RATE_LIMIT',
    ];

    public function __construct(
        protected ExecutionRuntimeRepositoryInterface $repository,
        protected ExecutionRuntimeService $runtimeService,
    ) {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Başarısız bir execution için recovery planı oluştur.
     * Yeni execution ÜRETMEZ — sadece planı döner.
     *
     * @return array{can_retry: bool, classification: string, policy: string,
     *                retry_count: int, max_retries: int, next_retry_at: \Carbon\Carbon|null,
     *                delay_seconds: int|null}
     */
    public function planRecovery(WorkforceExecution $execution): array
    {
        $this->blockAgentWrite(__FUNCTION__);

        $classification = $this->classifyFailure($execution);
        $policy = $this->resolvePolicy($execution, $classification);
        $retryCount = $execution->retry_count ?? 0;
        $maxRetries = $this->resolveMaxRetries($policy, $execution);

        if ($maxRetries === 0 || $retryCount >= $maxRetries) {
            return [
                'can_retry'     => false,
                'classification' => $classification,
                'policy'        => $policy,
                'retry_count'   => $retryCount,
                'max_retries'   => $maxRetries,
                'next_retry_at' => null,
                'delay_seconds' => null,
            ];
        }

        $delaySeconds = $this->resolveDelay($policy, $retryCount);
        $nextRetryAt = now()->addSeconds($delaySeconds);

        return [
            'can_retry'     => true,
            'classification' => $classification,
            'policy'        => $policy,
            'retry_count'   => $retryCount,
            'max_retries'   => $maxRetries,
            'next_retry_at' => $nextRetryAt,
            'delay_seconds' => $delaySeconds,
        ];
    }

    /**
     * Başarısız bir execution'ı otomatik olarak kurtar.
     * Yeni WorkforceExecution üretir — FAILED record değiştirilmez.
     *
     * @throws \DomainException Execution not failed veya retry exhausted
     */
    public function recover(
        string $failedExecutionUuid,
        ?int $actorId = null,
        ?string $actorType = null,
        ?string $recoveryReason = null,
    ): WorkforceExecution {
        $this->blockAgentWrite(__FUNCTION__);

        $failed = $this->repository->findByUuid($failedExecutionUuid);
        if (!$failed) {
            throw new \DomainException("Execution [{$failedExecutionUuid}] not found.");
        }

        if ($failed->execution_status !== WorkforceExecution::STATUS_FAILED) {
            throw new \DomainException(
                "Execution [{$failedExecutionUuid}] is not FAILED (current: {$failed->execution_status})."
            );
        }

        $plan = $this->planRecovery($failed);

        if (!$plan['can_retry']) {
            throw new \DomainException(
                "Execution [{$failedExecutionUuid}] cannot be retried. " .
                "Classification={$plan['classification']}, retries={$plan['retry_count']}/{$plan['max_retries']}."
            );
        }

        // ── Create recovery execution via runtime service ──────────────────────
        $recovery = $this->runtimeService->replay(
            originalUuid: $failedExecutionUuid,
            actorId: $actorId,
            actorType: $actorType ?? WorkforceExecution::ACTOR_SYSTEM,
            replayReason: $recoveryReason ?? "Auto-recovery: {$plan['classification']} failure, attempt #{$plan['retry_count']} + 1",
        );

        // Mark recovery execution with recovery metadata
        $recovery = $this->repository->markRecoveryStarted($recovery->uuid, [
            'recovery_of_uuid'       => $failedExecutionUuid,
            'failure_classification' => $plan['classification'],
            'retry_policy'          => $plan['policy'],
            'retry_count'           => ($failed->retry_count ?? 0) + 1,
            'max_retries'           => $plan['max_retries'],
            'next_retry_at'         => $plan['next_retry_at'],
            'recovered_at'          => now(),
        ]);

        return $recovery;
    }

    /**
     * Tüm tenant için retry'ye uygun execution'ları getir.
     * next_retry_at <= now() olan ve retry limiti dolmamış olanları döner.
     *
     * @return Collection<WorkforceExecution>
     */
    public function getReadyForRetry(?int $tenantId = null, int $limit = 50): Collection
    {
        $this->blockAgentWrite(__FUNCTION__);

        $query = WorkforceExecution::query()
            ->where('execution_status', WorkforceExecution::STATUS_FAILED)
            ->whereNotNull('failure_classification')
            ->where(function ($q) {
                // retry'ye uygun: classification TRANSIENT veya UNKNOWN
                $q->whereIn('failure_classification', [
                    self::CLASS_TRANSIENT,
                    self::CLASS_UNKNOWN,
                ]);
            })
            ->where(function ($q) {
                // Retry limiti dolmamış
                $q->whereRaw('retry_count < max_retries')
                  ->orWhereNull('max_retries');
            })
            ->where(function ($q) {
                // Zamanı gelmiş veya zamanı ayarlanmamış
                $q->where('next_retry_at', '<=', now())
                  ->orWhereNull('next_retry_at');
            })
            ->orderBy('next_retry_at', 'asc')
            ->limit($limit);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Execution'ın başarısızlık nedenini sınıflandır.
     * Classification sonucu workload execution'a KAYDEDİLMEZ — sadece plan() döner.
     */
    public function classifyFailure(WorkforceExecution $execution): string
    {
        $this->blockAgentWrite(__FUNCTION__);

        $errorCode = strtoupper($execution->error_code ?? '');

        // Permanent patterns
        foreach (self::PERMANENT_PATTERNS as $pattern) {
            if (str_contains($errorCode, $pattern)) {
                return self::CLASS_PERMANENT;
            }
        }

        // Config patterns
        foreach (self::CONFIG_PATTERNS as $pattern) {
            if (str_contains($errorCode, $pattern)) {
                return self::CLASS_CONFIG;
            }
        }

        // HTTP status-based inference
        if (str_contains($errorCode, '429')) {
            return self::CLASS_TRANSIENT; // Rate limit — geçici
        }
        if (str_contains($errorCode, '500') || str_contains($errorCode, '502') || str_contains($errorCode, '503')) {
            return self::CLASS_TRANSIENT; // Server error — geçici
        }
        if (str_contains($errorCode, 'TIMEOUT') || str_contains($errorCode, 'CONNECTION')) {
            return self::CLASS_TRANSIENT;
        }
        if (str_contains($errorCode, 'DISK') || str_contains($errorCode, 'MEMORY')) {
            return self::CLASS_TRANSIENT;
        }

        return self::CLASS_UNKNOWN;
    }

    /**
     * Tek bir FAILED execution'a classification ataması yap ve kaydet.
     */
    public function annotateClassification(string $uuid, string $classification): WorkforceExecution
    {
        $this->blockAgentWrite(__FUNCTION__);

        if (!in_array($classification, self::VALID_CLASSIFICATIONS, true)) {
            throw new \DomainException("Invalid classification: {$classification}.");
        }

        return $this->repository->updateRecoveryFields($uuid, [
            'failure_classification' => $classification,
            'retry_policy'          => $this->resolvePolicyForClassification($classification),
            'max_retries'          => self::MAX_RETRIES_BY_POLICY[$this->resolvePolicyForClassification($classification)],
        ]);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function resolvePolicy(WorkforceExecution $execution, string $classification): string
    {
        if ($execution->retry_policy) {
            return $execution->retry_policy;
        }
        return $this->resolvePolicyForClassification($classification);
    }

    private function resolvePolicyForClassification(string $classification): string
    {
        return self::DEFAULT_POLICY_BY_CLASS[$classification] ?? self::POLICY_EXPONENTIAL;
    }

    private function resolveMaxRetries(string $policy, WorkforceExecution $execution): int
    {
        // Policy-specific hard limit always wins (e.g. IMMEDIATE = 0 retries)
        $policyMax = self::MAX_RETRIES_BY_POLICY[$policy] ?? 3;

        if ($execution->max_retries !== null) {
            return min((int) $execution->max_retries, $policyMax);
        }

        return $policyMax;
    }

    private function resolveDelay(string $policy, int $retryCount): int
    {
        return match ($policy) {
            self::POLICY_EXPONENTIAL => self::EXPONENTIAL_DELAYS[$retryCount] ?? 3600,
            self::POLICY_LINEAR      => self::LINEAR_DELAYS[$retryCount] ?? 300,
            self::POLICY_IMMEDIATE   => 0,
            default                  => self::EXPONENTIAL_DELAYS[$retryCount] ?? 60,
        };
    }
}
