<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PropertyConfigVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class Sprint12ObservabilityTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Create user with direct role_id to bypass Spatie guard issues in tests
        $this->actingAs(\App\Models\User::factory()->create(['role_id' => 1]));
    }

    /** @test */
    public function it_records_tamper_incidents_in_database()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v_test_incident',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['rules' => []],
            'signature' => 'invalid_sig',
        ]);

        $service = resolve(\App\Domain\PropertyHub\Observability\GovernanceIncidentService::class);
        $service->recordTamper($version, "Verification Failed");

        $this->assertDatabaseHas('governance_tamper_incidents', [
            'version_hash' => 'v_test_incident',
            'severity' => 'CRITICAL',
        ]);
    }

    /** @test */
    public function it_records_drift_telemetry_idempotently()
    {
        $service = resolve(\App\Domain\PropertyHub\Observability\DriftTelemetryService::class);

        $metrics = ['drift' => 5, 'compromised' => 1];
        $service->record($metrics);

        // Second call within same hour should be ignored
        $service->record(['drift' => 99]);

        $this->assertDatabaseCount('governance_drift_telemetry', 1);
        $this->assertDatabaseHas('governance_drift_telemetry', ['drift_count' => 5]);
    }

    /** @test */
    public function it_provides_human_readable_explanations()
    {
        $service = resolve(\App\Domain\PropertyHub\Observability\HealthExplainService::class);

        $driftExplanation = $service->explain('drift', ['reason' => 'content_mismatch']);
        $this->assertStringContainsString('İÇERİK SAPMASI', $driftExplanation);

        $healthyExplanation = $service->explain('healthy');
        $this->assertStringContainsString('uyumludur', $healthyExplanation);
    }

    /** @test */
    public function it_exhibits_performance_budget_compliance()
    {
        // Timeline endpoint should return within budget and use cache
        $startTime = microtime(true);
        $this->get(route('admin.property-hub.versions.observability.timeline'))->assertStatus(200);
        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(200, $duration, "Timeline API execution exceeded 200ms budget.");
    }

    /** @test */
    public function it_verifies_d1_audit_timeline_integrity()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v_test_audit',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['test' => true],
            'signature' => 'sig_audit',
        ]);

        DB::table('property_config_audit_logs')->insert([
            'version_id' => $version->id,
            'islem_yapan_id' => auth()->id(),
            'islem_tipi' => 'ACTIVATION',
            'ek_bilgiler' => json_encode(['description' => 'Test Log']),
            'olusturma_tarihi' => now(),
        ]);

        $response = $this->get(route('admin.property-hub.versions.observability.timeline'));
        $response->assertJsonFragment(['islem_tipi' => 'ACTIVATION']);
        $response->assertJsonFragment(['kullanici_adi' => auth()->user()->name]);
    }
}
