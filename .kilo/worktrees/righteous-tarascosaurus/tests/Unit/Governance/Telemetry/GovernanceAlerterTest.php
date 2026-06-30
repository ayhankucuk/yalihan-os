<?php

namespace Tests\Unit\Governance\Telemetry;

use App\Governance\Alerting\GovernanceAlerter;
use App\Governance\Analytics\GovernanceAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GovernanceAlerterTest extends TestCase
{
    use RefreshDatabase;

    private GovernanceAlerter $alerter;
    private $analyticsMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyticsMock = $this->createMock(GovernanceAnalytics::class);
        $this->alerter = new GovernanceAlerter($this->analyticsMock);

        // Config defaults
        config(['governance.telemetry.alerting.enabled' => true]);
        config(['governance.telemetry.alerting.dedup_window_minutes' => 60]);
        config(['governance.telemetry.alerting.rate_limit_per_hour' => 5]);
    }

    /** @test */
    public function drift_tespit_edildiginde_alert_olusturulur(): void
    {
        $this->analyticsMock->method('generateHealthReport')->willReturn([
            'drift' => [
                'has_drift' => true,
                'drift_percentage' => 15.5,
                'severity' => 'high'
            ],
            'anomalies' => []
        ]);

        $this->alerter->checkAndAlert();

        $this->assertDatabaseHas('governance_alerts', [
            'type' => 'governance_drift',
            'severity' => 'high'
        ]);
    }

    /** @test */
    public function dedup_penceresi_icerisinde_ayni_alert_tekrar_yazilmaz(): void
    {
        $this->analyticsMock->method('generateHealthReport')->willReturn([
            'drift' => ['has_drift' => true, 'drift_percentage' => 10],
            'anomalies' => []
        ]);

        // İlk alert
        $this->alerter->checkAndAlert();
        $this->assertEquals(1, DB::table('governance_alerts')->count());

        // İkinci deneme (aynı tip, 60 dk dolmadı)
        $this->alerter->checkAndAlert();
        $this->assertEquals(1, DB::table('governance_alerts')->count(), 'Duplicate alert should be ignored');
    }

    /** @test */
    public function rate_limit_asildiginda_alert_yazilmaz(): void
    {
        $limit = 5;
        config(['governance.telemetry.alerting.rate_limit_per_hour' => $limit]);

        // Limiti doldur (farklı tiplerle ki dedup'a takılmasın)
        for ($i = 0; $i < $limit; $i++) {
            DB::table('governance_alerts')->insert([
                'type' => "test_alert_{$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->analyticsMock->method('generateHealthReport')->willReturn([
            'drift' => ['has_drift' => true, 'drift_percentage' => 10],
            'anomalies' => []
        ]);

        $this->alerter->checkAndAlert();

        $this->assertEquals($limit, DB::table('governance_alerts')->count(), 'Rate limited alert should be ignored');
    }

    /** @test */
    public function db_hatasinda_exception_firlatmaz_fail_open(): void
    {
        // Tabloyu boz/sil
        DB::statement('DROP TABLE governance_alerts');

        $this->analyticsMock->method('generateHealthReport')->willReturn([
            'drift' => ['has_drift' => true],
            'anomalies' => []
        ]);

        // Exception fırlamamalı
        $this->alerter->checkAndAlert();
        
        $this->assertTrue(true);
    }

    /** @test */
    public function acknowledge_alerta_onay_verir(): void
    {
        $id = DB::table('governance_alerts')->insertGetId([
            'type' => 'test_alert',
            'acknowledged' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->alerter->acknowledge($id, 99);

        $this->assertTrue($result);
        $this->assertDatabaseHas('governance_alerts', [
            'id' => $id,
            'acknowledged' => true,
            'acknowledged_by' => 99
        ]);
    }
}
