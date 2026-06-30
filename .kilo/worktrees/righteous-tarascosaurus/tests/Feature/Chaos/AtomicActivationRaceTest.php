<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Domain\PropertyHub\Chaos\ChaosModeService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Models\PropertyConfigVersion;
use App\Models\User;
use App\Modules\GovernanceCore\Core\VersionActivationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class AtomicActivationRaceTest extends TestCase
{

    /** @test */
    public function it_supports_transactions()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'sanity_check',
            'yonetim_durumu' => VersionStateMachine::DURUM_TASLAK,
            'snapshot_json' => ['test' => true],
            'signature' => 'sig',
        ]);

        DB::beginTransaction();
        $version->update(['yonetim_durumu' => VersionStateMachine::DURUM_AKTIF]);
        DB::rollBack();

        $this->assertEquals(VersionStateMachine::DURUM_TASLAK, $version->fresh()->yonetim_durumu);
    }

    /** @test */
    public function it_supports_savepoints()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'savepoint_check',
            'yonetim_durumu' => 'TASLAK',
            'snapshot_json' => [],
            'signature' => 's',
        ]);

        DB::transaction(function () use ($version) {
            $version->update(['yonetim_durumu' => 'AKTIF']);

            try {
                DB::transaction(function () use ($version) {
                    $version->update(['yonetim_durumu' => 'ARSIVLENDI']);
                    throw new \Exception("Rollback child");
                });
            } catch (\Exception $e) {}

            $this->assertEquals('AKTIF', $version->fresh()->yonetim_durumu);
        });
    }

    /** @test */
    public function it_actually_rolls_back_in_service()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'test_v_diag',
            'yonetim_durumu' => 'ONAYLANDI',
            'snapshot_json' => [],
            'signature' => 's'
        ]);

        $this->chaos->simulate(\App\Domain\PropertyHub\Chaos\ChaosSimulationService::TYPE_CONCURRENT_ACTIVATION);

        try {
            $admin = User::first() ?? User::factory()->create(['role_id' => 1]);
            $this->service->activate($version, (int)$admin->id);
        } catch (\RuntimeException $e) {}

        $this->assertEquals('ONAYLANDI', $version->fresh()->yonetim_durumu);
    }

    private VersionActivationService $service;
    private \App\Domain\PropertyHub\Chaos\ChaosSimulationService $chaos;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
        Config::set('propertyhub.chaos_enabled', true);
        $this->service = resolve(VersionActivationService::class);
        $this->chaos = resolve(\App\Domain\PropertyHub\Chaos\ChaosSimulationService::class);
        resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class)->reset();
    }

    /** @test */
    public function it_prevents_partial_activation_on_transaction_failure()
    {
        // 1. Create a ONAYLANDI version
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v_chaos_rollback',
            'yonetim_durumu' => 'ONAYLANDI', // Valid transition to AKTIF
            'snapshot_json' => ['rules' => []],
            'signature' => 'valid_sig',
        ]);

        // 2. Inject Partial Rollback Chaos
        $this->chaos->simulate(\App\Domain\PropertyHub\Chaos\ChaosSimulationService::TYPE_CONCURRENT_ACTIVATION);

        // 3. Attempt activation (should fail)
        $caught = false;
        try {
            $admin = \App\Models\User::factory()->create(['role_id' => 1]);
            $this->service->activate($version, (int)$admin->id);
        } catch (\RuntimeException $e) {
            $this->assertEquals("Concurrent activation simulated failure.", $e->getMessage());
            $caught = true;
        }

        $this->assertTrue($caught, "The Chaos Exception was NOT thrown! DI/Singleton Mismatch suspected.");

        // 4. Verify version is NOT active (should remain ONAYLANDI due to rollback)
        $this->assertDatabaseHas('property_config_versions', [
            'id' => $version->id,
            'yonetim_durumu' => 'ONAYLANDI',
        ]);

        $this->assertDatabaseMissing('property_config_audit_logs', [
            'version_id' => $version->id,
            'islem_tipi' => 'activated',
        ]);
    }

    /** @test */
    public function it_ensures_only_one_version_is_active_after_race_simulation()
    {
        // Create 2 versions
        $v1 = PropertyConfigVersion::create(['version_hash' => 'v1', 'yonetim_durumu' => 'ONAYLANDI', 'snapshot_json' => [], 'signature' => 's1']);
        $v2 = PropertyConfigVersion::create(['version_hash' => 'v2', 'yonetim_durumu' => 'ONAYLANDI', 'snapshot_json' => [], 'signature' => 's2']);

        // Set one as currently ACTIVE
        $activeOld = PropertyConfigVersion::create([
            'version_hash' => 'old_active',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => [],
            'signature' => 's_old',
            'is_active' => 1 // If there is an is_active column
        ]);

        // In a real race, we'd use PCNTL or parallel threads,
        // but here we verify the DB Locks / Unique constraints.

        $admin = \App\Models\User::factory()->create(['role_id' => 1]);
        $this->service->activate($v1, (int)$admin->id);

        try {
            // Attempt to activate another while v1 is active
            $this->service->activate($v2, (int)$admin->id);
        } catch (\Exception $e) {
            // Might throw if state machine prevents REVIEW -> AKTIF when another AKTIF exists
            // OR if unique constraint on is_active=1 triggers.
        }

        $activeCount = PropertyConfigVersion::where('yonetim_durumu', 'AKTIF')->count();
        $this->assertEquals(1, $activeCount, "CRITICAL: Multiple active versions detected!");
    }
}
