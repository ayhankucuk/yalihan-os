<?php

namespace App\Services\Governance\Telemetry;

use App\Contracts\Governance\TelemetryPublisherInterface;
use App\Enums\Governance\GovernanceTelemetryEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * RedisTelemetryPublisher — Phase 4C: Governance Telemetry
 *
 * Yönetişim olaylarını Redis'e düşük gecikmeyle (<10ms) yazar.
 * GovernanceObservabilityService bu sayaçları okuyarak gerçek zamanlı
 * dashboard ve uyarı sistemi sağlar.
 *
 * SAB:
 *   - Okuma: ConfigurationRegistryInterface üzerinden değil, doğrudan Cache (telemetry izleme özel durumu)
 *   - Yazma: Bu servis sadece metrik sayaçları yazar, domain verisi yazmaz
 *   - Tenant: Her kiracı için ayrı namespace (tenant.{id}.governance.*)
 */
final class RedisTelemetryPublisher implements TelemetryPublisherInterface
{
    private const TTL_HOURLY  = 7200;   // 2 saat
    private const TTL_DAILY   = 86400;  // 1 gün
    private const TTL_WEEKLY  = 604800; // 7 gün

    public function publish(
        GovernanceTelemetryEvent $event,
        string $correlationId,
        string $entityType,
        int|string $entityId,
        float $durationMs,
        array $metadata = []
    ): void {
        try {
            $tenantId = $metadata['tenant_id'] ?? 0;
            $hour     = now()->format('Y-m-d:H');
            $day      = now()->format('Y-m-d');

            // ── 1. Saatlik olay sayacı ──────────────────────────────
            $hourlyKey = "governance.metrics.{$tenantId}.{$event->value}.hourly.{$hour}";
            Cache::increment($hourlyKey);
            Cache::put($hourlyKey . '.ttl', now()->timestamp, self::TTL_HOURLY);

            // ── 2. Günlük olay sayacı ───────────────────────────────
            $dailyKey = "governance.metrics.{$tenantId}.{$event->value}.daily.{$day}";
            Cache::increment($dailyKey);

            // ── 3. Gecikme dağılımı (son 100 ölçüm) ─────────────────
            $latencyKey = "governance.latency.{$tenantId}.{$event->value}";
            $latencies  = Cache::get($latencyKey, []);
            $latencies[] = round($durationMs, 2);
            if (count($latencies) > 100) {
                array_shift($latencies); // En eskiyi at — sliding window
            }
            Cache::put($latencyKey, $latencies, self::TTL_DAILY);

            // ── 4. Varlık bazlı son aktivite ────────────────────────
            $entityKey = "governance.entity.{$tenantId}.{$entityType}.{$entityId}.last_event";
            Cache::put($entityKey, [
                'event'          => $event->value,
                'correlation_id' => $correlationId,
                'duration_ms'    => $durationMs,
                'at'             => now()->toIso8601String(),
                'metadata'       => $metadata,
            ], self::TTL_WEEKLY);

            // ── 5. İhlal tespiti: gecikme eşiği aşıldı mı? ─────────
            if ($durationMs > 10.0) {
                $violationKey = "governance.violations.{$tenantId}.latency.{$day}";
                Cache::increment($violationKey);
                Log::channel('daily')->warning('GovernanceTelemetry: latency threshold exceeded', [
                    'event'          => $event->value,
                    'duration_ms'    => $durationMs,
                    'correlation_id' => $correlationId,
                    'tenant_id'      => $tenantId,
                ]);
            }

        } catch (\Throwable $e) {
            // SAB: Telemetri hatası asla iş akışını durdurmamalı — @sab-ignore-catch
            Log::channel('daily')->error('RedisTelemetryPublisher failed', [
                'error' => $e->getMessage(),
                'event' => $event->value,
            ]);
        }
    }
}
