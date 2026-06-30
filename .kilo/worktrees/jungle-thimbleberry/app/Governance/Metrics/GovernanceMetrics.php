<?php

namespace App\Governance\Metrics;

use App\Governance\Jobs\FlushGovernanceEventsJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Phase 4C — Core Governance Metrics Collector
 *
 * =====================================================================
 * TEMEL PRENSİPLER (Safety Guardrails)
 * =====================================================================
 *
 * #1  Passive Observer  : Sadece gözlemler, asla zorlamaz
 * #4  Async-First       : afterResponse() ile flush, critical path bloklanmaz
 * #5  Fail-Open         : Her hata try/catch ile yutuluр, exception yok
 * #6  Performance       : Redis INCR < 1ms, buffer < 2ms, toplam < 10ms
 * #12 Composite Score   : getHealthScore() tek int ASLA; breakdown array ZORUNLU
 * #15 Self-Monitoring   : Kendi gecikmesini ve hatalarını ölçer
 *
 * =====================================================================
 * KULLANIM
 * =====================================================================
 *
 *   // Normal operasyon kaydı
 *   GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);
 *
 *   // İhlal kaydı
 *   GovernanceMetrics::violation('missing_tenant', 'critical', ['repo' => 'IlanRepository']);
 *
 *   // Sağlık skoru (composite — int değil array)
 *   $score = GovernanceMetrics::getHealthScore();
 *   // ['overall' => 97, 'breakdown' => [...], 'trend' => 'stable']
 */
class GovernanceMetrics
{
    // In-memory event buffer — afterResponse() ile flush edilir
    private static array $buffer = [];

    // Self-monitoring: telemetri kendi gecikmesini ölçer
    private static array $telemetryStats = [
        'increments'  => 0,
        'failures'    => 0,
        'buffer_size' => 0,
    ];

    // ---------------------------------------------------------------
    // PUBLIC API
    // ---------------------------------------------------------------

    /**
     * Normal bir governance operasyonunu kaydet.
     *
     * @param string $metric  Örn: "repository.write", "cache.operation", "queue.dispatch"
     * @param array  $tags    Örn: ['tenant_id' => 1, 'operation' => 'update']
     */
    public static function increment(string $metric, array $tags = []): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $start = microtime(true);

        try {
            // 1. Redis atomik sayaç — < 1ms
            self::redisIncrement($metric, $tags);

            // 2. In-memory buffer — < 0.1ms
            self::bufferEvent($metric, $tags, false);

            self::$telemetryStats['increments']++;

        } catch (\Throwable $e) {
            // Fail-open: asla business akışını kesme
            self::recordSelfFailure($e, $metric);
        }

        // Self-monitoring: performans bütçesi kontrolü
        self::checkPerformanceBudget($start, $metric);
    }

    /**
     * Governance ihlalini kaydet.
     *
     * @param string $violationType  Örn: "missing_tenant", "global_cache", "queue_without_tenant"
     * @param string $severity       "info" | "warning" | "critical"
     * @param array  $tags
     */
    public static function violation(
        string $violationType,
        string $severity = 'warning',
        array $tags = []
    ): void {
        if (! self::isEnabled()) {
            return;
        }

        $metric = "governance.violation.{$violationType}";

        try {
            // İhlal sayacı Redis'te ayrı izlenir
            self::redisIncrement($metric, $tags);

            // Violation event'i buffer'a ekle
            self::bufferEvent($metric, $tags, true, $violationType, $severity);

        } catch (\Throwable $e) {
            self::recordSelfFailure($e, $metric);
        }
    }

    /**
     * Composite sağlık skoru döner.
     *
     * Safety Guardrail #12: Tek int YASAK — breakdown array ZORUNLU.
     * Tek sayı kritik sorunları gizleyebilir (genel 92 ama tenant isolation 0 olabilir).
     *
     * @return array{overall: int, breakdown: array, trend: string, timestamp: string}
     */
    public static function getHealthScore(): array
    {
        try {
            $thresholds = config('governance.telemetry.health_thresholds', []);

            $scores = [
                'repository_integrity' => self::calculateRepositoryScore(),
                'tenant_isolation'     => self::calculateTenantIsolationScore(),
                'queue_safety'         => self::calculateQueueSafetyScore(),
                'cache_governance'     => self::calculateCacheScore(),
                'ci_compliance'        => 100, // CI gate — static, Phase 4C'de izlenecek
                'drift_stability'      => self::calculateDriftScore(),
            ];

            $overall = (int) round(array_sum($scores) / count($scores));

            return [
                'overall'    => $overall,
                'breakdown'  => $scores,
                'thresholds' => $thresholds,
                'trend'      => self::calculateTrend($overall),
                'timestamp'  => now()->toIso8601String(),
            ];

        } catch (\Throwable $e) {
            // Fail-open: telemetri hatası sağlık skoru hesaplamayı kesmez
            Log::error('[GovernanceTelemetry] Health score hesaplanamadı', [
                'error' => $e->getMessage(),
            ]);

            return [
                'overall'    => -1, // -1 = telemetri hatası (0'dan farklı)
                'breakdown'  => [],
                'thresholds' => [],
                'trend'      => 'unknown',
                'timestamp'  => now()->toIso8601String(),
                'error'      => 'Telemetry unavailable',
            ];
        }
    }

    /**
     * Belirli bir metriğin Redis sayacını okur.
     */
    public static function get(string $metric, array $tags = []): int
    {
        $key = self::buildKey($metric, $tags);
        return (int) Redis::get($key);
    }

    /**
     * Buffer'ı manuel flush et (test veya shutdown için).
     */
    public static function flush(): void
    {
        self::dispatchBuffer();
    }

    /**
     * Self-monitoring istatistikleri döner.
     */
    public static function getSelfStats(): array
    {
        return array_merge(self::$telemetryStats, [
            'buffer_size' => count(self::$buffer),
        ]);
    }

    // ---------------------------------------------------------------
    // PRIVATE: Redis
    // ---------------------------------------------------------------

    private static function redisIncrement(string $metric, array $tags): void
    {
        $prefix = config('governance.telemetry.redis_prefix', 'governance:metrics:');
        $ttl    = config('governance.telemetry.redis_ttl', 604800);

        $key = $prefix . self::buildKey($metric, $tags);

        Redis::incr($key);
        Redis::expire($key, $ttl);
    }

    private static function buildKey(string $metric, array $tags): string
    {
        if (empty($tags)) {
            return $metric;
        }

        ksort($tags); // Tutarlı sıralama
        $tagStr = http_build_query($tags, '', ':');
        return "{$metric}:{$tagStr}";
    }

    // ---------------------------------------------------------------
    // PRIVATE: In-Memory Buffer & Async Flush
    // ---------------------------------------------------------------

    private static function bufferEvent(
        string $metric,
        array $tags,
        bool $isViolation,
        ?string $violationType = null,
        string $severity = 'info'
    ): void {
        self::$buffer[] = [
            'metric'         => $metric,
            'tags'           => $tags,
            'is_violation'   => $isViolation,
            'violation_type' => $violationType,
            'severity'       => $severity,
            'trace_id'       => app()->bound('trace_id') ? app('trace_id') : null,
            'request_id'     => app()->bound('request_id') ? app('request_id') : null,
            'tenant_id'      => $tags['tenant_id'] ?? null,
            'source_class'   => $tags['source_class'] ?? null,
            'occurred_at'    => now(),
        ];

        $threshold = config('governance.telemetry.buffer_flush_threshold', 100);

        if (count(self::$buffer) >= $threshold) {
            self::dispatchBuffer();
        }
    }

    /**
     * Buffer'ı afterResponse() ile asenkron flush et.
     * Bu sayede HTTP yanıtı kullanıcıya gider, SONRA DB yazılır.
     */
    private static function dispatchBuffer(): void
    {
        if (empty(self::$buffer)) {
            return;
        }

        $events       = self::$buffer;
        self::$buffer = [];

        try {
            // afterResponse: yanıt kullanıcıya gittikten sonra çalışır
            dispatch(new FlushGovernanceEventsJob($events))->afterResponse();
        } catch (\Throwable $e) {
            // Fail-open: dispatch başarısız olsa da iş akışı etkilenmez
            self::recordSelfFailure($e, 'flush');
        }
    }

    // ---------------------------------------------------------------
    // PRIVATE: Health Score Calculations
    // ---------------------------------------------------------------

    private static function calculateRepositoryScore(): int
    {
        $writes     = self::get('repository.write');
        $violations = self::get('governance.violation.missing_tenant') +
                      self::get('governance.violation.repository_bypass');

        return self::violationRateToScore($violations, $writes);
    }

    private static function calculateTenantIsolationScore(): int
    {
        $total      = self::get('repository.write') + self::get('repository.read');
        $violations = self::get('governance.violation.missing_tenant') +
                      self::get('governance.violation.cross_tenant');

        return self::violationRateToScore($violations, $total);
    }

    private static function calculateQueueSafetyScore(): int
    {
        $dispatches = self::get('queue.dispatch');
        $violations = self::get('governance.violation.queue_without_tenant') +
                      self::get('governance.violation.tenant_not_restored');

        return self::violationRateToScore($violations, $dispatches);
    }

    private static function calculateCacheScore(): int
    {
        $operations = self::get('cache.operation');
        $violations = self::get('governance.violation.global_cache');

        return self::violationRateToScore($violations, $operations);
    }

    private static function calculateDriftScore(): int
    {
        // Basit drift: son 1 saatteki ihlal oranı vs 7 günlük ortalama
        // Phase 4C analytics engine daha sofistike hesaplar
        return 100; // Başlangıçta tam puan, analytics engine devreye girince güncellenir
    }

    /**
     * İhlal oranından 0-100 arası skor hesaplar.
     */
    private static function violationRateToScore(int $violations, int $total): int
    {
        if ($total === 0) {
            return 100;
        }

        $violationRate = $violations / $total;
        return max(0, min(100, (int) round((1 - $violationRate) * 100)));
    }

    private static function calculateTrend(int $currentScore): string
    {
        return 'stable';
    }

    // ---------------------------------------------------------------
    // PRIVATE: Self-Monitoring & Performance
    // ---------------------------------------------------------------

    private static function checkPerformanceBudget(float $start, string $metric): void
    {
        $elapsed = (microtime(true) - $start) * 1000; // ms

        $budget = config('governance.telemetry.performance_budget_ms', 10);

        if ($elapsed > $budget) {
            // Bütçe aşıldı — log et ama kesme (fail-open prensibi)
            Log::warning('[GovernanceTelemetry] Performance bütçesi aşıldı', [
                'metric'     => $metric,
                'elapsed_ms' => round($elapsed, 2),
                'budget_ms'  => $budget,
            ]);

            // BUG FIX (2026-05-13):
            // Orijinal kod: ($self::$telemetryStats[...] ?? 0) + 1
            // PHP'de static metodlarda $self:: geçersiz — sadece self:: kullanılır.
            // $self:: yazımı "Undefined variable $self" hatası fırlatıyordu.
            // Bu hata try/catch dışında olduğu için fail-open mekanizması devreye
            // giremiyordu; budget aşımı sayacı hiç çalışmıyordu.
            // Düzeltme: self:: ile doğrudan static property erişimi.
            self::$telemetryStats['budget_exceeded'] = (self::$telemetryStats['budget_exceeded'] ?? 0) + 1;
        }
    }

    private static function recordSelfFailure(\Throwable $e, string $context): void
    {
        self::$telemetryStats['failures']++;

        Log::error('[GovernanceTelemetry] Telemetri hatası — fail-open devrede', [
            'context' => $context,
            'error'   => $e->getMessage(),
        ]);
    }

    private static function isEnabled(): bool
    {
        return (bool) config('governance.telemetry.enabled', true);
    }
}
