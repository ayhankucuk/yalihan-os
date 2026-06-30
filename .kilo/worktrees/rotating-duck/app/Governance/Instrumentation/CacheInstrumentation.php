<?php

namespace App\Governance\Instrumentation;

use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Cache Instrumentation Trait
 *
 * Cache operasyonlarını izler. Global cache (tenant prefix'siz) operasyonları
 * otomatik olarak ihlal sayar.
 *
 * Safety Guardrail #8: Cache invalidation path'ini bloklamaz.
 * Cache operasyonu önce yapılır, telemetri afterResponse() ile kayıt tutulur.
 *
 * KULLANIM:
 *   class TenantCacheService
 *   {
 *       use CacheInstrumentation;
 *
 *       public function forget(string $key, int $tenantId): void
 *       {
 *           Cache::forget("tenant:{$tenantId}:{$key}");
 *           $this->recordCacheForget($key, $tenantId);
 *       }
 *   }
 */
trait CacheInstrumentation
{
    protected function recordCacheGet(string $key, ?int $tenantId = null): void
    {
        $this->trackCacheOperation('get', $key, $tenantId);
    }

    protected function recordCachePut(string $key, ?int $tenantId = null): void
    {
        $this->trackCacheOperation('put', $key, $tenantId);
    }

    protected function recordCacheForget(string $key, ?int $tenantId = null): void
    {
        $this->trackCacheOperation('forget', $key, $tenantId);
    }

    protected function recordCacheFlush(?int $tenantId = null): void
    {
        $this->trackCacheOperation('flush', '*', $tenantId);
    }

    private function trackCacheOperation(
        string $operation,
        string $key,
        ?int $tenantId
    ): void {
        try {
            $hasTenantScope = $tenantId !== null;

            GovernanceMetrics::increment('cache.operation', [
                'operation'       => $operation,
                'tenant_id'       => $tenantId,
                'has_tenant_scope' => $hasTenantScope,
            ]);

            // Global cache operasyonu tespiti
            if (! $hasTenantScope && $this->isGlobalCacheKey($key)) {
                GovernanceMetrics::violation(
                    violationType: 'global_cache',
                    severity: 'warning',
                    tags: [
                        'operation' => $operation,
                        'key'       => $key,
                        'class'     => static::class,
                    ]
                );

                Log::warning('[GovernanceTelemetry] Global cache operasyonu tespit edildi', [
                    'operation' => $operation,
                    'key'       => $key,
                ]);
            }

        } catch (\Throwable $e) {
            // Fail-open
            Log::error('[GovernanceTelemetry] CacheInstrumentation hatası', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tenant prefix'i olmayan key global kabul edilir.
     * Doğru format: "tenant:{tenantId}:{key}"
     */
    private function isGlobalCacheKey(string $key): bool
    {
        return ! preg_match('/^tenant:\d+:/', $key);
    }
}
