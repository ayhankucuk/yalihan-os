<?php

namespace App\Governance\Instrumentation;

use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Repository Instrumentation Trait
 *
 * Repository sınıflarına use edilir. Yazma/okuma operasyonlarını
 * GovernanceMetrics'e iletir. Mevcut iş mantığını DEĞİŞTİRMEZ.
 *
 * Safety Guardrail #7: Side-effect free — business data'ya dokunmaz,
 * transaction boundary değiştirmez, validasyon eklemez.
 *
 * KULLANIM:
 *   class IlanRepository
 *   {
 *       use RepositoryInstrumentation;
 *
 *       public function update(Ilan $ilan, array $data): Ilan
 *       {
 *           $this->recordWrite('update', ['tenant_id' => $ilan->tenant_id]);
 *           return ...;
 *       }
 *   }
 */
trait RepositoryInstrumentation
{
    /**
     * Bir yazma operasyonunu kaydet.
     *
     * @param string $operation  Örn: "create", "update", "delete", "bulk_update"
     * @param array  $context    Örn: ['tenant_id' => 1, 'model_id' => 42]
     */
    protected function recordWrite(string $operation, array $context = []): void
    {
        try {
            $tags = array_merge($context, [
                'operation'    => $operation,
                'repository'   => static::class,
                'source_class' => static::class,
            ]);

            GovernanceMetrics::increment('repository.write', $tags);

            // Tenant context kontrolü — yoksa ihlal kaydet
            if (empty($context['tenant_id'])) {
                GovernanceMetrics::violation(
                    violationType: 'missing_tenant',
                    severity: 'warning',
                    tags: array_merge($tags, [
                        'backtrace' => $this->safeBacktrace(),
                    ])
                );

                Log::warning('[GovernanceTelemetry] Repository write without tenant context', [
                    'operation'  => $operation,
                    'repository' => static::class,
                ]);
            }

        } catch (\Throwable $e) {
            // Fail-open: telemetri hatası repository'yi durdurmasın
            Log::error('[GovernanceTelemetry] RepositoryInstrumentation hatası', [
                'operation' => $operation,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Bir okuma operasyonunu kaydet.
     */
    protected function recordRead(string $operation, array $context = []): void
    {
        try {
            GovernanceMetrics::increment('repository.read', array_merge($context, [
                'operation'  => $operation,
                'repository' => static::class,
            ]));
        } catch (\Throwable $e) {
            // Fail-open
        }
    }

    /**
     * Intentional bypass — dokümante edilmiş, beklenen cross-tenant erişim.
     *
     * IDE tarafından "Expected Bypass" olarak etiketlendi (S8 kararı).
     * MatchingEngine gibi yerler bunu kullanır.
     *
     * @param string $reason   PHASE4_SEMANTIC_CLASSIFICATION.md referansı gibi açıklama
     * @param array  $context
     */
    protected function recordExpectedBypass(string $reason, array $context = []): void
    {
        try {
            GovernanceMetrics::increment('repository.expected_bypass', array_merge($context, [
                'reason'     => $reason,
                'repository' => static::class,
            ]));
        } catch (\Throwable $e) {
            // Fail-open
        }
    }

    /**
     * Performans açısından güvenli backtrace — sadece 5 frame, arg yok.
     */
    private function safeBacktrace(): string
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        return collect($frames)
            ->map(fn($f) => ($f['class'] ?? '') . '::' . ($f['function'] ?? ''))
            ->implode(' → ');
    }
}
