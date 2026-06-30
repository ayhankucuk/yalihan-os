<?php

namespace Tests\Feature\Governance;

use App\Domain\PropertyHub\Resolution\Registry\TenantConfigRegistry;
use App\Models\PropertyConfigVersion;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost dep: multi-tenant isolation layer henüz implement edilmedi.
 */
class MultiTenantIsolationTest extends TestCase
{

    /** @test */
    public function it_isolates_configuration_across_tenants()
    {
        $snapshotA = ['templates' => [['id' => 1, 'name' => 'Template A']]];
        $vA = PropertyConfigVersion::create([
            'tenant_id' => 'TENANT_A',
            'version_hash' => 'hash_iso_a_' . uniqid(),
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshotA,
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($snapshotA)
        ]);

        // Tenant B Active Configuration
        $snapshotB = ['templates' => [['id' => 2, 'name' => 'Template B']]];
        $vB = PropertyConfigVersion::create([
            'tenant_id' => 'TENANT_B',
            'version_hash' => 'hash_iso_b_' . uniqid(),
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshotB,
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($snapshotB)
        ]);

        $registry = resolve(TenantConfigRegistry::class);

        $resolvedA = $registry->resolve('TENANT_A');
        $resolvedB = $registry->resolve('TENANT_B');

        $this->assertEquals('TENANT_A', $resolvedA->tenant_id);
        $this->assertEquals($vA->version_hash, $resolvedA->version_hash);

        $this->assertEquals('TENANT_B', $resolvedB->tenant_id);
        $this->assertEquals($vB->version_hash, $resolvedB->version_hash);
    }

    /** @test */
    public function it_scopes_drift_detection_to_tenant()
    {
        $snapshot = ['templates' => [['id' => 1, 'ad' => 'Template A', 'aciklama' => '...', 'aktiflik_durumu' => 1, 'display_order' => 1]]];
        $vA = PropertyConfigVersion::create([
            'tenant_id' => 'TENANT_A_DRIFT',
            'version_hash' => 'hash_drift_a_' . uniqid(),
            'yonetim_durumu' => 'AKTIF',
            'snapshot_json' => $snapshot,
            'signature' => \App\Modules\GovernanceCore\Core\ConfigSnapshotService::computeSignature($snapshot)
        ]);

        // Create a template in DB for Tenant B that shouldn't affect Tenant A's drift
        \Illuminate\Support\Facades\DB::table('yayin_tipi_sablonlari')->insert([
            'tenant_id' => 'TENANT_B_DRIFT',
            'ad' => 'Ungoverned B',
            'slug' => 'ungoverned-b-' . uniqid(),
            'yayin_tipi_id' => 1,
            'aciklama' => '...',
            'aktiflik_durumu' => 1,
            'display_order' => 1
        ]);

        $driftService = resolve(\App\Modules\GovernanceCore\Services\DriftDetectionService::class);
        $results = $driftService->detect($vA);

        // Tenant A should see NO ungoverned records because we scoped the query to TENANT_A_DRIFT
        $this->assertCount(0, $results['ungoverned']);
    }
}
