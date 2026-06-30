<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Domain\PropertyHub\Chaos\ChaosSimulationService;
use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChaosRedisFailureTest extends TestCase
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
    public function it_falls_back_to_safe_lock_on_redis_failure_chaos()
    {
        // 1. Setup Active version
        $snapshot = ['templates' => []];
        $sig = \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($snapshot);

        $version = PropertyConfigVersion::factory()->create([
            'yonetim_durumu' => 'AKTIF',
            'signature' => $sig,
            'snapshot_json' => $snapshot
        ]);

        // 2. Inject Redis Failure
        $this->chaos->inject(ChaosSimulationService::TYPE_REDIS_FAILURE);

        // 3. Resolve (should trigger SAFE_LOCK fallback)
        $resolved = $this->registry->getActiveVersion();

        $this->assertEquals($version->id, $resolved->id);

        // Use JSON contains check
        $this->assertDatabaseHas('governance_incidents', [
            'olay_tipi' => 'chaos_injection',
        ]);

        $incident = \App\Models\GovernanceIncident::where('olay_tipi', 'chaos_injection')->latest()->first();
        $this->assertEquals('redis_failure', $incident->details['scenario']);
    }
}
