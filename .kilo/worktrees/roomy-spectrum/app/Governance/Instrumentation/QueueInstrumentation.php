<?php

namespace App\Governance\Instrumentation;

use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Queue Instrumentation Trait
 *
 * En kritik telemetri alanı — en yüksek değer, en yüksek risk (GUARDRAILS #9).
 *
 * Dispatch → Execution → Retry → Failed zincirini izler.
 * Tenant context'in kuyruk boyunca korunduğunu doğrular.
 *
 * Safety Guardrail #9: Queue jobs'larına doğrudan eklemek yerine
 * trait olarak sunulur — mevcut job mantığı bozulmaz.
 *
 * KULLANIM:
 *   class IlanIndexJob implements ShouldQueue
 *   {
 *       use QueueInstrumentation;
 *
 *       public function handle(): void
 *       {
 *           $this->recordQueueStart();
 *           $this->restoreTenantContext();
 *           // ... iş mantığı
 *           $this->recordQueueSuccess();
 *       }
 *
 *       public function failed(\Throwable $e): void
 *       {
 *           $this->recordQueueFailure($e);
 *       }
 *   }
 */
trait QueueInstrumentation
{
    /**
     * Job dispatch edildiğinde çağrılır (dispatcher tarafında).
     */
    public function recordQueueDispatch(?int $tenantId = null): void
    {
        try {
            $hasTenantContext = $tenantId !== null;

            GovernanceMetrics::increment('queue.dispatch', [
                'job'              => static::class,
                'tenant_id'        => $tenantId,
                'has_tenant_context' => $hasTenantContext,
            ]);

            if (! $hasTenantContext) {
                GovernanceMetrics::violation(
                    violationType: 'queue_without_tenant',
                    severity: 'critical',
                    tags: [
                        'job' => static::class,
                    ]
                );

                Log::error('[GovernanceTelemetry] Queue job tenant context olmadan dispatch edildi', [
                    'job' => static::class,
                ]);
            }

        } catch (\Throwable $e) {
            // Fail-open — dispatch kesinlikle durmasın
            Log::error('[GovernanceTelemetry] QueueInstrumentation dispatch hatası', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Job execution başında çağrılır — tenant restore kontrolü.
     */
    protected function recordQueueStart(): void
    {
        try {
            $tenantId        = $this->getTenantIdFromJob();
            $tenantRestored  = $this->isTenantRestored();

            GovernanceMetrics::increment('queue.execution', [
                'job'             => static::class,
                'tenant_id'       => $tenantId,
                'tenant_restored' => $tenantRestored,
            ]);

            if (! $tenantRestored) {
                GovernanceMetrics::violation(
                    violationType: 'tenant_not_restored',
                    severity: 'critical',
                    tags: [
                        'job'       => static::class,
                        'tenant_id' => $tenantId,
                    ]
                );
            }

        } catch (\Throwable $e) {
            // Fail-open
        }
    }

    /**
     * Job başarıyla tamamlandığında çağrılır.
     */
    protected function recordQueueSuccess(): void
    {
        try {
            GovernanceMetrics::increment('queue.success', [
                'job'       => static::class,
                'tenant_id' => $this->getTenantIdFromJob(),
            ]);
        } catch (\Throwable $e) {
            // Fail-open
        }
    }

    /**
     * Job başarısız olduğunda çağrılır.
     */
    protected function recordQueueFailure(\Throwable $exception): void
    {
        try {
            GovernanceMetrics::increment('queue.failure', [
                'job'        => static::class,
                'tenant_id'  => $this->getTenantIdFromJob(),
                'error_type' => get_class($exception),
            ]);
        } catch (\Throwable $e) {
            // Fail-open
        }
    }

    /**
     * Job'dan tenant ID'yi güvenle alır.
     * Job sınıfında $tenantId property'si varsa kullanır.
     */
    private function getTenantIdFromJob(): ?int
    {
        return $this->tenantId ?? null;
    }

    /**
     * Tenant context'in restore edilip edilmediğini kontrol eder.
     * Job sınıfında $tenantRestored flag'i varsa kullanır.
     */
    private function isTenantRestored(): bool
    {
        return $this->tenantRestored ?? false;
    }
}
