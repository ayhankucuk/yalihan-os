<?php

namespace Tests\Unit\Governance\Telemetry;

use App\Governance\Analytics\GovernanceAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GovernanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private GovernanceAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analytics = new GovernanceAnalytics();
    }

    /** @test */
    public function drift_yok_iken_has_drift_false_doner(): void
    {
        // Baseline: %10 ihlal (Son 7 gün, ama 24 saat dışı)
        $this->seedEvents(now()->subDays(2), 100, 10);

        // Current: %10 ihlal (Son 24 saat)
        $this->seedEvents(now()->subHours(12), 100, 10);

        $result = $this->analytics->detectDrift();

        $this->assertFalse($result['has_drift']);
        $this->assertEquals(0, $result['drift_percentage']);
    }

    /** @test */
    public function drift_esigi_asilinca_has_drift_true_doner(): void
    {
        // Threshold %5 olsun (artış miktarı üzerinden)
        config(['governance.telemetry.drift_detection.threshold_percentage' => 5]);

        // Baseline: %5 ihlal (24 saat öncesi)
        $this->seedEvents(now()->subDays(2), 100, 5);

        // Current: %20 ihlal (Son 24 saat)
        // Toplam 7 günlük: 200 event, 25 ihlal (%12.5 baseline)
        // Son 24 saat: 100 event, 20 ihlal (%20 current)
        // Drift: 20 - 12.5 = 7.5 (> 5 threshold)
        $this->seedEvents(now()->subHours(12), 100, 20);

        $result = $this->analytics->detectDrift();

        $this->assertTrue($result['has_drift']);
        $this->assertEquals(7.5, $result['drift_percentage']);
    }

    /** @test */
    public function anomali_spike_tespit_edilir(): void
    {
        // 24 saatlik ortalama: Saatte 1 ihlal (23 saat boyunca)
        for ($i = 1; $i <= 23; $i++) {
            $this->seedEvents(now()->subHours($i), 1, 1);
        }

        // Son 1 saat: 10 ihlal (3x eşiğini geçer)
        $this->seedEvents(now()->subMinutes(30), 10, 10);

        $anomalies = $this->analytics->detectAnomalies();

        // Hem 'violation_spike' hem 'critical_violation' (missing_tenant) döner
        $this->assertGreaterThanOrEqual(1, count($anomalies));
        // Servis Context7 kanonik adını kullanır: 'type' → 'tip'
        $types = array_column($anomalies, 'tip');
        $this->assertContains('violation_spike', $types);
    }

    /** @test */
    public function saglik_raporu_array_doner_asla_null_degil(): void
    {
        $report = $this->analytics->generateHealthReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('health_score', $report);
        $this->assertArrayHasKey('drift', $report);
        $this->assertArrayHasKey('anomalies', $report);
    }

    /** @test */
    public function redis_veya_db_hatasi_durumunda_fail_open(): void
    {
        // Tabloyu siliyoruz ki DB hatası oluşsun
        DB::statement('DROP TABLE governance_events');

        $result = $this->analytics->detectDrift();

        $this->assertFalse($result['has_drift']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function drift_esigi_config_kontrollu_sabit_deger_kullanir(): void
    {
        config(['governance.telemetry.drift_detection.threshold_percentage' => 50]);

        // Baseline: %0
        $this->seedEvents(now()->subDays(2), 100, 0);

        // Current: %30
        // Total 7d: 200 events, 30 violations (15% baseline)
        // Son 24h: 100 event, 30 violations (30% current)
        // Drift: 30 - 15 = 15 (< 50 threshold)
        $this->seedEvents(now()->subHours(12), 100, 30);

        $result = $this->analytics->detectDrift();

        $this->assertFalse($result['has_drift']);
        $this->assertEquals(15, $result['drift_percentage']);
    }

    // ---------------------------------------------------------------
    // HELPER
    // ---------------------------------------------------------------

    private function seedEvents(\DateTimeInterface $occurredAt, int $total, int $violations): void
    {
        for ($i = 0; $i < $total; $i++) {
            $isViolation = $i < $violations;
            DB::table('governance_events')->insert([
                'metric'       => $isViolation ? 'governance.violation.test' : 'test.event',
                'is_violation' => $isViolation,
                'tags'         => json_encode($isViolation ? ['violation_type' => 'missing_tenant'] : []),
                'occurred_at'  => $occurredAt,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
