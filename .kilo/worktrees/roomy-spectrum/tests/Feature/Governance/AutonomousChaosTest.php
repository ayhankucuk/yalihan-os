<?php

namespace Tests\Feature\Governance;

use App\Modules\GovernanceCore\Services\DriftDetectionService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Domain\PropertyHub\Resiliency\HealthAutoRecoveryService;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: ConfigSnapshotService + AutonomousDriftResponder dep missing.
 * Autonomous Governance OS Chaos & Verification Suite
 */
class AutonomousChaosTest extends TestCase
{

    /** @test */
    public function it_creates_auto_correction_draft_for_low_risk_drift()
    {
        $tenantId = 'AUTON_TENANT';
        $snapshot = [
            'rules' => [],
            'templates' => [
                ['id' => 1, 'ad' => 'Golden', 'aciklama' => 'Safe', 'aktiflik_durumu' => 1, 'display_order' => 1]
            ]
        ];

        $slug = 'auton-fix-' . bin2hex(random_bytes(8));

        // 1. Manually seed DB with slight drift (ad changed)
        $id = DB::table('yayin_tipi_sablonlari')->insertGetId([
            'tenant_id' => $tenantId,
            'ad' => 'Drifted_Name',
            'slug' => $slug,
            'aciklama' => 'Safe',
            'aktiflik_durumu' => 1,
            'display_order' => 1,
            'created_at' => now(),
        ]);

        // Fix snapshot to match the ID we just got
        $snapshot['templates'][0]['id'] = $id;

        $version = PropertyConfigVersion::create([
            'tenant_id' => $tenantId,
            'version_hash' => 'SAFE_BASE_' . uniqid(),
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => $snapshot,
            'signature' => ConfigSnapshotService::computeSignature($snapshot),
            'applied_at' => now(),
        ]);

        // 2. Trigger Detection
        $detector = resolve(DriftDetectionService::class);
        $results = $detector->detect($version);

        $this->assertNotEmpty($results['drifts']);

        // 3. Verify Autonomous Responder created a DRAFT correction
        $durumKolonu = $this->yonetimDurumuKolonu();
        $autoCorrection = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where($durumKolonu, VersionStateMachine::DURUM_TASLAK)
            ->where('description', 'like', '%AUTONOMOUS FIX%')
            ->first();

        $this->assertNotNull($autoCorrection);
        $this->assertEquals($snapshot, $autoCorrection->snapshot_json);
    }

    /** @test */
    public function it_triggers_hard_lock_for_ungoverned_mutation()
    {
        $tenantId = 'LOCK_TENANT';
        \Illuminate\Support\Facades\Cache::forget("governance.compromised.{$tenantId}");

        $version = PropertyConfigVersion::create([
            'tenant_id' => $tenantId,
            'version_hash' => 'BASE',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'snapshot_json' => ['templates' => []],
            'signature' => ConfigSnapshotService::computeSignature(['templates' => []]),
            'applied_at' => now(),
        ]);

        // 1. Insert ungoverned record (None in snapshot)
        DB::table('yayin_tipi_sablonlari')->insert([
            'id' => 999,
            'tenant_id' => $tenantId,
            'ad' => 'Invader',
            'slug' => 'hack-slug-2',
            'aciklama' => 'Hack',
            'aktiflik_durumu' => 1,
            'display_order' => 9,
            'created_at' => now(),
        ]);

        // 2. Trigger Detection
        $detector = resolve(DriftDetectionService::class);
        $detector->detect($version);

        // 3. Verify System Lock (Hard Lock)
        $isLocked = \Illuminate\Support\Facades\Cache::get("governance.compromised.{$tenantId}");
        $this->assertTrue($isLocked, "System should be locked for tenant [{$tenantId}] due to ungoverned mutation.");
    }

    /** @test */
    public function it_proposes_recovery_on_health_degradation_trend()
    {
        $tenantId = 'RECOVERY_TENANT';

        // 1. Create a safe baseline (Oldest)
        $v0 = new PropertyConfigVersion([
            'tenant_id' => $tenantId,
            'version_hash' => 'SAFE_V0',
            'yonetim_durumu' => VersionStateMachine::DURUM_ARSIVLENDI,
            'risk_score' => 10,
            'snapshot_json' => ['safe' => true],
            'description' => 'Safe Baseline',
            'applied_at' => now()->subDays(10),
        ]);
        $v0->created_at = now()->subDays(10);
        $v0->save();

        // 2. Create history with increasing risk (Worsening trend)
        // Order in nodes: [Latest, Previous, Oldest]
        // Scores: [90, 60, 40] -> Degrading.

        $v3 = new PropertyConfigVersion([
            'tenant_id' => $tenantId,
            'version_hash' => 'BAD_V3_OLD',
            'yonetim_durumu' => VersionStateMachine::DURUM_ARSIVLENDI,
            'risk_score' => 40,
            'applied_at' => now()->subDays(3),
        ]);
        $v3->created_at = now()->subDays(3);
        $v3->save();

        $v2 = new PropertyConfigVersion([
            'tenant_id' => $tenantId,
            'version_hash' => 'BAD_V2_MED',
            'yonetim_durumu' => VersionStateMachine::DURUM_ARSIVLENDI,
            'risk_score' => 60,
            'applied_at' => now()->subDays(2),
        ]);
        $v2->created_at = now()->subDays(2);
        $v2->save();

        $v1 = new PropertyConfigVersion([
            'tenant_id' => $tenantId,
            'version_hash' => 'BAD_V1_LATEST',
            'yonetim_durumu' => VersionStateMachine::DURUM_AKTIF,
            'risk_score' => 90,
            'applied_at' => now()->subDays(1),
        ]);
        $v1->created_at = now()->subDays(1);
        $v1->save();

        $recoveryService = resolve(HealthAutoRecoveryService::class);
        $proposal = $recoveryService->monitorAndProposeRecovery($tenantId);

        $this->assertNotNull($proposal, "Health monitor should propose a recovery draft.");
        $this->assertStringContainsString('RECOVERY_PROPOSAL', $proposal->version_hash);
    }

    private function yonetimDurumuKolonu(): string
    {
        return Schema::hasColumn('property_config_versions', 'yonetim_durumu')
            ? 'yonetim_durumu'
            : 'governance_state';
    }
}
