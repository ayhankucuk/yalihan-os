<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Domain\PropertyHub\Chaos\ChaosModeService;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class RegistryTamperChaosTest extends TestCase
{

    private ActiveConfigRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('propertyhub.chaos_enabled', true);
        $this->registry = resolve(ActiveConfigRegistry::class);
    }

    /** @test */
    public function it_rejects_version_with_mismatched_signature()
    {
        $snapshot = ['rules' => ['test']];
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v_tamper',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshot,
            'signature' => 'invalid_signature_manually_set',
        ]);

        $this->expectException(\App\Exceptions\CriticalGovernanceException::class);
        $this->expectExceptionMessageMatches('/Possible data tampering/i');

        $this->registry->getActiveVersion();
    }

    /** @test */
    public function it_triggers_lockdown_and_records_incident_on_tamper()
    {
        PropertyConfigVersion::create([
            'version_hash' => 'v_tamper_incident',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['malicious' => true],
            'signature' => 'fake_signature',
        ]);

        try {
            $this->registry->getActiveVersion();
        } catch (\App\Exceptions\CriticalGovernanceException $e) {
            // Expected
        }

        // Verify D3: Incident recorded
        $this->assertDatabaseHas('governance_incidents', [
            'olay_tipi' => 'signature_mismatch', // This is what recordTamper uses
            'risk_seviyesi' => 'CRITICAL',
        ]);

        // Verify Lockdown: Redis key 'governance.system_compromised' should be set
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('governance.system_compromised'));
    }
}
