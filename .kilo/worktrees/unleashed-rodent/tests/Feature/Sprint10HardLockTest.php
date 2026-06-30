<?php

namespace Tests\Feature;

use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Exceptions\CriticalGovernanceException;
use App\Models\PropertyConfigVersion;
use DomainException;
use Tests\TestCase;

use Tests\Support\ResetsGovernanceState;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class Sprint10HardLockTest extends TestCase
{
    use ResetsGovernanceState;

    /** @test */
    public function it_blocks_illegal_state_transitions()
    {
        $vsm = new VersionStateMachine();

        // Valid: TASLAK -> INCELEME
        $this->assertTrue($vsm->canTransition(VersionStateMachine::DURUM_TASLAK, VersionStateMachine::DURUM_INCELEME));

        // Invalid: INCELEME -> TASLAK (Hardened)
        $this->assertFalse($vsm->canTransition(VersionStateMachine::DURUM_INCELEME, VersionStateMachine::DURUM_TASLAK));

        // Invalid: AKTIF -> TASLAK
        $this->assertFalse($vsm->canTransition(VersionStateMachine::DURUM_AKTIF, VersionStateMachine::DURUM_TASLAK));
    }

    /** @test */
    public function it_enforces_singleton_active_version_at_db_level()
    {
        PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['test' => 1],
            'signature' => 'sig1', // Dummy sig
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PropertyConfigVersion::create([
            'version_hash' => 'v2',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['test' => 2],
            'signature' => 'sig2',
        ]);
    }

    /** @test */
    public function it_detects_tampering_and_throws_critical_exception()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => 'TASLAK',
            'snapshot_json' => ['rules' => []],
            'signature' => 'dummy',
        ]);

        // Fix the signature and finalize
        $validSig = \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature(['rules' => []]);
        $version->update([
            'signature' => $validSig,
            'yonetim_durumu' => 'AKTIF'
        ]);

        // Now manually tamper at DB level (bypassing model guards)
        \Illuminate\Support\Facades\DB::table('property_config_versions')
            ->where('id', $version->id)
            ->update(['snapshot_json' => json_encode(['rules' => ['tampered' => true]])]);

        $registry = resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class);

        $this->expectException(CriticalGovernanceException::class);
        $registry->getActiveVersion();
    }

    /** @test */
    public function it_blocks_updates_to_finalized_versions()
    {
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'yonetim_durumu' => 'INCELEME',
            'snapshot_json' => ['test' => 1],
            'signature' => 'sig1',
        ]);

        $this->expectException(\App\Exceptions\PropertyHub\ActiveMutationViolationException::class);
        $this->expectExceptionMessage("ZERO-TRUST ERROR: Unauthorized save() call on ACTIVE configuration");

        // Change state to fixed/immutable state first if needed or just use active
        $version->update(['yonetim_durumu' => 'AKTIF']);

        $version->update(['version_hash' => 'malicious_change']);
    }

    /** @test */
    public function it_locks_down_system_on_compromise()
    {
        $registry = resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class);
        $registry->reset();
        \Illuminate\Support\Facades\Cache::forget('governance.system_compromised');

        // Initial state
        $this->assertFalse($registry->isSystemCompromised());

        // Use the ALREADY CREATED active version from DB if it exists, or create one exclusively
        $version = PropertyConfigVersion::where('yonetim_durumu', 'AKTIF')->first();
        if (!$version) {
            $version = PropertyConfigVersion::create([
                'version_hash' => 'v_compromised_base',
                'yonetim_durumu' => 'AKTIF',
                'snapshot_json' => ['rules' => []],
                'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature(['rules' => []]),
            ]);
        }

        // Simulate compromise detection by tampering DB directly
        \Illuminate\Support\Facades\DB::table('property_config_versions')
            ->where('id', $version->id)
            ->update(['signature' => 'invalid_signature']);

        $registry->reset(); // Force reload

        try {
            $registry->getActiveVersion();
        } catch (CriticalGovernanceException $e) {
            // Expected
        }

        $this->assertTrue($registry->isSystemCompromised());

        // Subsequent calls should fail immediately even with valid data
        $this->expectException(CriticalGovernanceException::class);
        $this->expectExceptionMessage("CONTEXT7 HARD LOCK: System is compromised");

        $registry->verifyIntegrity($version);
    }

    /** @test */
    public function it_blocks_optimizer_if_compromised()
    {
        \Illuminate\Support\Facades\Cache::forever('governance.system_compromised', true);
        resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class)->tripLockdown();

        $optimizer = new \App\Services\Ups\UpsHealthOptimizerService();

        $this->expectException(CriticalGovernanceException::class);
        $this->expectExceptionMessage("CONTEXT7 HARD LOCK: System is compromised");

        $optimizer->optimizeAll();
    }

    /** @test */
    public function it_guarantees_performance_on_health_matrix()
    {
        // Trait handles reset
        $registry = resolve(\App\Domain\PropertyHub\Resolution\Registry\ActiveConfigRegistry::class);
        $registry->reset();
        $registry->clear();

        // Setup some data
        \App\Models\IlanKategori::factory()->count(2)->create()->each(function($c) {
            $c->yayinTipleri()->create([
                'ad' => 'Test',
                'yayin_tipi_id' => 1,
                'slug' => 'test-' . uniqid()
            ]);
        });

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Seed active config
        PropertyConfigVersion::create([
            'version_hash' => 'v_perf',
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => ['templates' => [], 'rules' => []],
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature(['templates' => [], 'rules' => []]),
        ]);

        $controller = new \App\Http\Controllers\Admin\UpsHealthController();
        $controller->index();

        $queryCount = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // @rules 9: No N+1 queries, count < 15
        $this->assertLessThan(15, $queryCount, "Performance Violation: Health Matrix query count must be < 15. Actual: {$queryCount}");
    }
}
