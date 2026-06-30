<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Domain\PropertyHub\Chaos\ChaosSimulationService;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;
use App\Models\GovernanceIncident;
use Tests\TestCase;

class ChaosSignatureTamperTest extends TestCase
{

    private ChaosSimulationService $chaos;
    private ActiveConfigRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chaos = app(ChaosSimulationService::class);
        $this->registry = app(ActiveConfigRegistry::class);

        config(['propertyhub.strict_governance' => true]);
    }

    /** @test */
    public function it_triggers_hard_lockdown_on_signature_tamper_chaos()
    {
        // 1. Setup Active version
        $version = PropertyConfigVersion::factory()->create([
            'yonetim_durumu' => 'AKTIF',
            'signature' => 'original_sig',
            'snapshot_json' => ['templates' => []]
        ]);

        // 2. Inject Tamper Chaos
        $this->chaos->inject(ChaosSimulationService::TYPE_SIGNATURE_TAMPER);

        // 3. Attempt to resolve (should fail and lockdown)
        try {
            $this->registry->getActiveVersion();
            $this->fail("Should have thrown CriticalGovernanceException");
        } catch (\App\Exceptions\CriticalGovernanceException $e) {
            $this->assertStringContainsString("SECURITY ALERT", $e->getMessage());
        }

        // 4. Verify Lockdown
        $this->assertTrue($this->registry->isSystemCompromised());

        // 5. Verify Incident Recorded
        $this->assertDatabaseHas('governance_incidents', [
            'olay_tipi' => 'signature_mismatch',
            'risk_seviyesi' => 'CRITICAL'
        ]);
    }
}
