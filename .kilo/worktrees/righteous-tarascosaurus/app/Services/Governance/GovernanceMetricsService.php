<?php

namespace App\Services\Governance;

use App\Enums\Governance\GovernanceTelemetryEvent;
use Illuminate\Support\Facades\Cache;

/**
 * GovernanceMetricsService — Phase 4C: Gerçek Zamanlı Metrik Okuyucu
 *
 * RedisTelemetryPublisher tarafından yazılan sayaçları okur ve
 * admin dashboard için normalize edilmiş veri üretir.
 *
 * Tasarım ilkeleri:
 * - Tüm operasyonlar read-only: Cache::get(), Cache::tags() sadece
 * - <2ms hedef gecikme (Redis in-memory okuma)
 * - Tenant izolasyonu: her çağrı tenant_id alır
 */
final class GovernanceMetricsService
{
    /**
     * Belirli bir kiracı için bugünkü yönetişim özetini döner.
     *
     * @return array{
     *     publish_attempts: int,
     *     publish_succeeded: int,
     *     publish_rejected: int,
     *     avg_latency_ms: float,
     *     p95_latency_ms: float,
     *     latency_violations: int,
     *     success_rate: float,
     * }
     */
    public function getDailySummary(int $tenantId = 0): array
    {
        $day = now()->format('Y-m-d');

        $attempted  = (int) Cache::get("governance.metrics.{$tenantId}." . GovernanceTelemetryEvent::PUBLISH_ATTEMPTED->value . ".daily.{$day}", 0);
        $succeeded  = (int) Cache::get("governance.metrics.{$tenantId}." . GovernanceTelemetryEvent::PUBLISH_SUCCEEDED->value . ".daily.{$day}", 0);
        $rejected   = (int) Cache::get("governance.metrics.{$tenantId}." . GovernanceTelemetryEvent::PUBLISH_REJECTED->value . ".daily.{$day}", 0);
        $violations = (int) Cache::get("governance.violations.{$tenantId}.latency.{$day}", 0);

        [$avgLatency, $p95Latency] = $this->computeLatencyStats($tenantId);

        return [
            'publish_attempts'  => $attempted,
            'publish_succeeded' => $succeeded,
            'publish_rejected'  => $rejected,
            'avg_latency_ms'    => $avgLatency,
            'p95_latency_ms'    => $p95Latency,
            'latency_violations' => $violations,
            'success_rate'      => $attempted > 0
                ? round(($succeeded / $attempted) * 100, 1)
                : 100.0,
        ];
    }

    /**
     * Son N saatin saatlik trend verisini döner.
     * Dashboard grafikleri için kullanılır.
     *
     * @return array<int, array{hour: string, count: int}>
     */
    public function getHourlyTrend(
        int $tenantId = 0,
        GovernanceTelemetryEvent $event = GovernanceTelemetryEvent::PUBLISH_ATTEMPTED,
        int $hours = 24
    ): array {
        $result = [];
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour     = now()->subHours($i)->format('Y-m-d:H');
            $hourLabel = now()->subHours($i)->format('H:00');
            $key      = "governance.metrics.{$tenantId}.{$event->value}.hourly.{$hour}";
            $result[] = [
                'hour'  => $hourLabel,
                'count' => (int) Cache::get($key, 0),
            ];
        }
        return $result;
    }

    /**
     * Belirli bir varlığın son yönetişim olayını döner.
     */
    public function getEntityLastEvent(int $tenantId, string $entityType, int|string $entityId): ?array
    {
        return Cache::get("governance.entity.{$tenantId}.{$entityType}.{$entityId}.last_event");
    }

    /**
     * Sistem geneli yönetişim skoru (0-100).
     * Başarı oranı + gecikme + ihlal sayısına göre hesaplanır.
     */
    public function computeHealthScore(int $tenantId = 0): int
    {
        $summary = $this->getDailySummary($tenantId);

        $successScore    = $summary['success_rate'];                           // 0-100
        $latencyPenalty  = min(20, $summary['latency_violations'] * 2);        // max -20
        $rejectionPenalty = min(10, $summary['publish_rejected'] * 1);         // max -10

        return max(0, (int) round($successScore - $latencyPenalty - $rejectionPenalty));
    }

    /**
     * Ortalama ve P95 gecikme hesaplar (Redis latency window'dan).
     *
     * @return array{float, float}
     */
    private function computeLatencyStats(int $tenantId): array
    {
        $latencies = [];
        foreach (GovernanceTelemetryEvent::cases() as $event) {
            $key  = "governance.latency.{$tenantId}.{$event->value}";
            $data = Cache::get($key, []);
            array_push($latencies, ...$data);
        }

        if (empty($latencies)) {
            return [0.0, 0.0];
        }

        sort($latencies);
        $avg = round(array_sum($latencies) / count($latencies), 2);
        $p95 = round($latencies[(int) ceil(count($latencies) * 0.95) - 1] ?? 0, 2);

        return [$avg, $p95];
    }
}
