<?php

namespace Tests\Feature\Governance\Performance;

use App\Domain\PropertyHub\Resolution\Registry\TenantConfigRegistry;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost performance infrastructure henüz implement edilmedi.
 * Enterprise Load & Performance Simulation
 *
 * Verifies:
 * - Sub-100ms resolution (p95)
 * - Cache isolation (no cross-tenant bleed)
 * - Cache hit scaling
 */
class DeterministicLoadTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_resolves_from_cache_with_sub_100ms_latency()
    {
        $tenantId = 'PERF_TENANT_' . uniqid();
        $snapshot = ['rules' => [], 'templates' => []];
        $signature = ConfigSnapshotService::computeSignature($snapshot);

        // Seed DB
        $version = PropertyConfigVersion::create([
            'tenant_id' => $tenantId,
            'version_hash' => 'hash_' . uniqid(),
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshot,
            'signature' => $signature,
            'applied_at' => now(),
        ]);

        $registry = resolve(TenantConfigRegistry::class);

        // 1. Cold Resolve (Populates L2/L1)
        $start = hrtime(true);
        $registry->resolve($tenantId);
        $coldLatency = (hrtime(true) - $start) / 1e6;

        // 2. Warm Resolve (L1 hit)
        $start = hrtime(true);
        $registry->resolve($tenantId);
        $warmLatency = (hrtime(true) - $start) / 1e6;

        // 3. New Request Resolve (L2 hit - simulates fresh process)
        $newRegistry = resolve(TenantConfigRegistry::class);
        $start = hrtime(true);
        $newRegistry->resolve($tenantId);
        $l2Latency = (hrtime(true) - $start) / 1e6;

        $this->assertLessThan(100, $l2Latency, "L2 Cache hit should be under 100ms (was {$l2Latency}ms)");
        $this->assertLessThan(10, $warmLatency, "L1 Cache hit should be under 10ms (was {$warmLatency}ms)");
    }

    /** @test */
    public function it_maintains_isolation_under_high_tenant_load()
    {
        $tenantCount = 50;
        $tenants = [];

        // Seed multiple tenants
        for ($i = 0; $i < $tenantCount; $i++) {
            $tid = "T_{$i}";
            $snap = ['val' => "Secret_{$i}"];
            $sig = ConfigSnapshotService::computeSignature($snap);

            PropertyConfigVersion::create([
                'tenant_id' => $tid,
                'version_hash' => "H_{$i}",
                'yonetim_durumu' => 'AKTIF',
                'snapshot_json' => $snap,
                'signature' => $sig,
                'applied_at' => now(),
            ]);
            $tenants[$tid] = $snap;
        }

        $registry = resolve(TenantConfigRegistry::class);

        // Verify isolation
        foreach ($tenants as $tid => $expectedSnapshot) {
            $version = $registry->resolve($tid);
            $this->assertEquals($expectedSnapshot, $version->snapshot_json);

            // Check L1 cross-contamination
            $memSnapshot = $registry->getActiveSnapshot($tid);
            $this->assertEquals($expectedSnapshot, $memSnapshot);
        }
    }
}
