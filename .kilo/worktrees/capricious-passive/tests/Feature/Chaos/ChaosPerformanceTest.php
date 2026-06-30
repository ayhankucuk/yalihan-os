<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry;
use App\Models\PropertyConfigVersion;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChaosPerformanceTest extends TestCase
{

    private ActiveConfigRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = resolve(ActiveConfigRegistry::class);
        $this->registry->reset();
    }

    /** @test */
    public function it_resolves_active_version_within_latency_budget()
    {
        // 1. Setup Active Version
        PropertyConfigVersion::create([
            'version_hash' => 'v_perf_budget',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['rules' => [], 'templates' => []],
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature(['rules' => [], 'templates' => []]),
        ]);

        // 2. Warm up (ignore first hit if caching is involved)
        $this->registry->getActiveVersion();
        $this->registry->reset();

        // 3. Measure
        $startTime = microtime(true);
        $this->registry->getActiveVersion();
        $endTime = microtime(true);

        $durationMs = ($endTime - $startTime) * 1000;

        // BUDGET: < 200ms
        $this->assertLessThan(200, $durationMs, "Resolution latency is too high: {$durationMs}ms");
    }

    /** @test */
    public function it_resolves_active_version_within_query_budget()
    {
        // 1. Setup Active Version
        PropertyConfigVersion::create([
            'version_hash' => 'v_query_budget',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['rules' => [], 'templates' => []],
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature(['rules' => [], 'templates' => []]),
        ]);

        $this->registry->reset();

        // 2. Monitor Queries
        DB::enableQueryLog();

        $this->registry->getActiveVersion();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // BUDGET: < 15 Queries
        $this->assertLessThan(15, $queryCount, "Too many queries during resolution: {$queryCount}");
    }
}
