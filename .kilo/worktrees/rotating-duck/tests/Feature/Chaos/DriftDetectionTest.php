<?php

declare(strict_types=1);

namespace Tests\Feature\Chaos;

use App\Modules\GovernanceCore\Services\DriftDetectionService;
use App\Models\PropertyConfigVersion;
use App\Models\YayinTipiSablonu;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Services\PropertyHub\ConfigSnapshotService henüz implement edilmedi.
 */
class DriftDetectionTest extends TestCase
{

    private DriftDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(DriftDetectionService::class);
    }

    /** @test */
    public function it_detects_value_drift_scenario_a()
    {
        $tenantId = 'TEST_TENANT_' . rand(1000, 9999);

        // 1. Setup a master template
        $master = YayinTipiSablonu::create([
            'ad' => 'Original Name',
            'slug' => 'original-name',
            'aktiflik_durumu' => 1,
            'display_order' => 1,
            'tenant_id' => $tenantId
        ]);

        // 2. Create version with this template
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'tenant_id' => $tenantId,
            'snapshot_json' => [
                'templates' => [
                    [
                        'id' => $master->id,
                        'ad' => 'Original Name',
                        'aciklama' => null,
                        'aktiflik_durumu' => 1,
                        'display_order' => 1
                    ]
                ]
            ],
            'signature' => 'sig',
            'yonetim_durumu' => 'TASLAK'
        ]);

        // 3. TAMPER: Change DB directly
        $master->update(['ad' => 'Tampered Name']);

        // 4. Detect
        $results = $this->service->detect($version);

        $this->assertCount(1, $results['drifts']);
        $this->assertEquals($master->id, $results['drifts'][0]['id']);
    }

    /** @test */
    public function it_detects_shadow_missing_scenario_b()
    {
        $tenantId = 'TEST_TENANT_' . rand(1000, 9999);

        // 1. Create version for non-existent DB record
        $version = PropertyConfigVersion::create([
            'version_hash' => 'v1',
            'tenant_id' => $tenantId,
            'snapshot_json' => [
                'templates' => [
                    [
                        'id' => 999, // Missing in DB
                        'ad' => 'Ghost',
                        'aktiflik_durumu' => 1,
                        'display_order' => 1
                    ]
                ]
            ],
            'signature' => 'sig'
        ]);

        $results = $this->service->detect($version);

        $this->assertCount(1, $results['shadow_missing']);
        $this->assertEquals(999, $results['shadow_missing'][0]);
    }

    /** @test */
    public function it_detects_ungoverned_records_scenario_c()
    {
        $tenantId = 'TEST_TENANT_' . rand(1000, 9999);

        // 1. Create DB record
        $wild = YayinTipiSablonu::create([
            'ad' => 'Wild Record',
            'slug' => 'wild-record',
            'aktiflik_durumu' => 1,
            'display_order' => 1,
            'tenant_id' => $tenantId
        ]);

        // 2. Empty snapshot (represented by version with no templates)
        $version = PropertyConfigVersion::create([
            'tenant_id' => $tenantId,
            'version_hash' => 'v_empty',
            'snapshot_json' => ['templates' => []],
            'signature' => 'sig'
        ]);

        $results = $this->service->detect($version);

        $this->assertContains($wild->id, $results['ungoverned']);
    }

    /** @test */
    public function it_does_not_produce_false_positive_for_identical_data(): void
    {
        $tenantId = 'TEST_TENANT_FP_' . rand(1000, 9999);

        // 1. DB'de null aciklama ile template oluştur
        $master = YayinTipiSablonu::create([
            'ad'              => 'Satilik Daire',
            'slug'            => 'satilik-daire-fp',
            'aciklama'        => null,
            'aktiflik_durumu' => 1,
            'display_order'   => 1,
            'tenant_id'       => $tenantId,
        ]);

        // 2. Snapshot'ta aynı değerler
        $version = PropertyConfigVersion::create([
            'version_hash'   => 'v_fp_guard',
            'tenant_id'      => $tenantId,
            'snapshot_json'  => [
                'templates' => [[
                    'id'              => $master->id,
                    'ad'              => 'Satilik Daire',
                    'aciklama'        => null,   // DB ile ayni: null
                    'aktiflik_durumu' => 1,       // DB'den "1" gelse de canonical: 1
                    'display_order'   => 1,
                ]],
            ],
            'signature'      => 'sig_fp_guard',
            'yonetim_durumu' => 'TASLAK',
        ]);

        // 3. Pasif detect — AutonomousDriftResponder tetiklenmesin
        $results = $this->service->detectPassive($version);

        $this->assertCount(0, $results['drifts'],
            'Ayni veri icin value drift uretilmemeli (false-positive guard).');
        $this->assertCount(0, $results['shadow_missing'],
            'Shadow missing olmamali.');
    }
}
