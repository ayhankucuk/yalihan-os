<?php

namespace Tests\Unit\Governance\Telemetry;

use App\Governance\Metrics\GovernanceMetrics;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Phase 4C — GovernanceMetrics Unit Tests
 *
 * Safety Guardrails test coverage:
 *   #4  Async-First      : Telemetri critical path'i bloklamaz
 *   #5  Fail-Open        : Hata durumunda exception fırlatmaz
 *   #6  Performance      : < 10ms overhead
 *   #12 Composite Score  : Health score array döner, asla int
 *   #15 Self-Monitoring  : Kendi hata ve istatistiklerini tutar
 */
class GovernanceMetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test için telemetriyi aktif et
        config(['governance.telemetry.enabled' => true]);
    }

    // ---------------------------------------------------------------
    // #5 FAIL-OPEN: Telemetri hatası business'ı kesmez
    // ---------------------------------------------------------------

    /** @test */
    public function telemetri_redis_hatasi_exception_firlatmaz(): void
    {
        // Redis erişimi başarısız olsun
        Redis::shouldReceive('incr')->andThrow(new \Exception('Redis connection refused'));
        Redis::shouldReceive('expire')->andThrow(new \Exception('Redis connection refused'));

        // Business code çalışmaya devam etmeli — exception yok
        $this->expectNotToPerformAssertions();

        GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);

        // Buraya ulaşabiliyorsa fail-open çalışıyor demek
        $this->assertTrue(true);
    }

    /** @test */
    public function telemetri_devre_disi_birakilabilir_ve_hicbir_sey_bozmaz(): void
    {
        config(['governance.telemetry.enabled' => false]);

        Redis::shouldReceive('incr')->never();
        Redis::shouldReceive('expire')->never();

        GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);
        GovernanceMetrics::violation('missing_tenant', 'critical', []);

        // Hiçbir Redis çağrısı yapılmamalı ve exception olmamalı
        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------
    // #6 PERFORMANCE BUDGET: < 10ms
    // ---------------------------------------------------------------

    /** @test */
    public function increment_10ms_butcesini_asmaz(): void
    {
        Redis::shouldReceive('incr')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(true);

        $start = microtime(true);

        GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(
            10,
            $elapsed,
            "GovernanceMetrics::increment() {$elapsed}ms sürdü — bütçe: 10ms"
        );
    }

    /** @test */
    public function violation_kaydi_10ms_butcesini_asmaz(): void
    {
        Redis::shouldReceive('incr')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(true);

        $start = microtime(true);

        GovernanceMetrics::violation('missing_tenant', 'warning', ['tenant_id' => null]);

        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertLessThan(10, $elapsed,
            "GovernanceMetrics::violation() {$elapsed}ms sürdü — bütçe: 10ms"
        );
    }

    // ---------------------------------------------------------------
    // #12 COMPOSITE SCORE: Health score int değil array döner
    // ---------------------------------------------------------------

    /** @test */
    public function health_score_array_doner_asla_int_degil(): void
    {
        Redis::shouldReceive('get')->andReturn(0);

        $score = GovernanceMetrics::getHealthScore();

        $this->assertIsArray($score, 'getHealthScore() array döndürmelidir (int değil)');
    }

    /** @test */
    public function health_score_overall_anahtarini_icerir(): void
    {
        Redis::shouldReceive('get')->andReturn(0);

        $score = GovernanceMetrics::getHealthScore();

        $this->assertArrayHasKey('overall', $score);
        $this->assertArrayHasKey('breakdown', $score);
        $this->assertArrayHasKey('timestamp', $score);
    }

    /** @test */
    public function health_score_breakdown_tum_katmanlari_icerir(): void
    {
        Redis::shouldReceive('get')->andReturn(0);

        $score = GovernanceMetrics::getHealthScore();

        $breakdown = $score['breakdown'];

        $this->assertArrayHasKey('repository_integrity', $breakdown);
        $this->assertArrayHasKey('tenant_isolation', $breakdown);
        $this->assertArrayHasKey('queue_safety', $breakdown);
        $this->assertArrayHasKey('cache_governance', $breakdown);
        $this->assertArrayHasKey('ci_compliance', $breakdown);
        $this->assertArrayHasKey('drift_stability', $breakdown);
    }

    /** @test */
    public function health_score_redis_hatasinda_fail_open_doner(): void
    {
        Redis::shouldReceive('get')->andThrow(new \Exception('Redis down'));

        $score = GovernanceMetrics::getHealthScore();

        // Hata durumunda -1 döner (0'dan ayırt etmek için)
        $this->assertEquals(-1, $score['overall']);
        $this->assertArrayHasKey('error', $score);
    }

    // ---------------------------------------------------------------
    // #15 SELF-MONITORING: Kendi istatistiklerini tutar
    // ---------------------------------------------------------------

    /** @test */
    public function self_stats_hata_sayisini_tutar(): void
    {
        Redis::shouldReceive('incr')->andThrow(new \Exception('Redis error'));
        Redis::shouldReceive('expire')->andThrow(new \Exception('Redis error'));

        GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);

        $stats = GovernanceMetrics::getSelfStats();

        $this->assertArrayHasKey('failures', $stats);
        $this->assertGreaterThan(0, $stats['failures']);
    }

    // ---------------------------------------------------------------
    // REGRESSION: BUG FIX 2026-05-13
    // ---------------------------------------------------------------

    /**
     * @test
     *
     * BUG: checkPerformanceBudget() içinde $self::$telemetryStats kullanılmıştı.
     * PHP'de static metodlarda $self:: geçersizdir; self:: olmalıdır.
     * Hata try/catch dışında olduğu için fail-open devreye giremiyordu.
     * Budget aşımı sayacı hiç artmıyordu — silent failure.
     *
     * Bu test regression'ı önler: budget_exceeded sayacı çalışmalı.
     */
    public function performance_butcesi_asildiginda_sayac_artar(): void
    {
        Redis::shouldReceive('incr')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(true);

        // Bütçeyi 0ms yap — her çağrı bütçeyi aşacak
        config(['governance.telemetry.performance_budget_ms' => 0]);

        GovernanceMetrics::increment('repository.write', ['tenant_id' => 1]);

        $stats = GovernanceMetrics::getSelfStats();

        // Bug öncesi: bu assertion hiç çalışmıyordu ($self:: PHP fatal fırlatıyordu)
        // Bug sonrası: sayaç artmalı
        $this->assertArrayHasKey('budget_exceeded', $stats);
        $this->assertGreaterThan(0, $stats['budget_exceeded']);
    }

    // ---------------------------------------------------------------
    // İHLAL KAYDI
    // ---------------------------------------------------------------

    /** @test */
    public function violation_dogru_metric_ismiyle_kaydedilir(): void
    {
        $capturedKey = null;

        Redis::shouldReceive('incr')->once()->withArgs(function ($key) use (&$capturedKey) {
            $capturedKey = $key;
            return true;
        })->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(true);

        GovernanceMetrics::violation('missing_tenant', 'critical', []);

        $this->assertStringContainsString('governance.violation.missing_tenant', $capturedKey);
    }

    // ---------------------------------------------------------------
    // ENSTRÜMANTASYON SIDE-EFFECT YOK
    // ---------------------------------------------------------------

    /** @test */
    public function increment_business_veriyi_degistirmez(): void
    {
        Redis::shouldReceive('incr')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(true);

        $originalData = ['tenant_id' => 42, 'operation' => 'update'];
        $dataCopy     = $originalData;

        GovernanceMetrics::increment('repository.write', $dataCopy);

        // Tags array değişmemiş olmalı
        $this->assertEquals($originalData, $dataCopy);
    }
}
