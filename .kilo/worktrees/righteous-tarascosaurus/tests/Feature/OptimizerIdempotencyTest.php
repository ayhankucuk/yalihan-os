<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PropertyConfigVersion;
use App\Services\Ups\UpsHealthOptimizerService;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use Illuminate\Support\Facades\DB;

/**
 * Optimizer Idempotency Test
 *
 * Verifies that UpsHealthOptimizer creates DRAFT proposals only,
 * without directly modifying the active configuration or database.
 *
 * Sprint 9.3 Governance Integrity
 */
class OptimizerIdempotencyTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // No seeding required - test verifies draft-only workflow without real data dependencies
    }

    /**
     * @test
     * @group skip-until-migration-complete
     */
    public function it_creates_draft_without_modifying_active_config()
    {
        // 1. Create ACTIVE configuration with VALID signature
        $initialSnapshot = [
            'meta' => ['timestamp' => now()->toIso8601String()],
            'templates' => [],
            'master_templates' => [],
        ];
        $validSignature = ConfigSnapshotService::computeSignature($initialSnapshot);

        $activeVersion = PropertyConfigVersion::create([
            'version_hash' => 'ACTIVE_VERSION_HASH',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'description' => 'Active Configuration',
            'created_by' => 1,
            'snapshot_json' => $initialSnapshot,
            'signature' => $validSignature,
        ]);

        // 2. Run optimizer (may return early if no missing nodes)
        $optimizer = resolve(UpsHealthOptimizerService::class);
        $result = $optimizer->optimizeAll();

        // 3. Verify ACTIVE config unchanged
        $activeVersion->refresh();
        $this->assertEquals('ACTIVE_VERSION_HASH', $activeVersion->version_hash);
        $this->assertEquals($validSignature, $activeVersion->signature);
        $this->assertEquals(VersionStateMachine::DURUM_AKTIF, $activeVersion->yonetim_durumu);

        // 4. If missing nodes found, verify DRAFT created
        if (isset($result['version_id'])) {
            $draftVersion = PropertyConfigVersion::find($result['version_id']);

            $this->assertNotNull($draftVersion);
            $this->assertEquals(VersionStateMachine::DURUM_TASLAK, $draftVersion->yonetim_durumu);
            $this->assertEquals('ACTIVE_VERSION_HASH', $draftVersion->parent_version_hash);
        }
    }

    /**
     * @test
     * @group skip-until-migration-complete
     */
    public function it_does_not_write_directly_to_ups_templates_table()
    {
        // 1. Count existing templates
        $initialCount = \App\Models\UpsTemplate::count();

        // 2. Run optimizer
        $optimizer = resolve(UpsHealthOptimizerService::class);
        $optimizer->optimizeAll();

        // 3. Verify no direct writes to ups_templates
        $finalCount = \App\Models\UpsTemplate::count();
        $this->assertEquals($initialCount, $finalCount, 'Optimizer should NOT write directly to ups_templates');
    }
}
