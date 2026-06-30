<?php

namespace Tests\Feature;

use App\Models\IlanKategori;
use App\Models\PropertyConfigVersion;
use App\Models\UpsTemplate;
use App\Models\YayinTipiSablonu;
use App\Modules\GovernanceCore\Core\ConfigSnapshotService;
use App\Services\Ups\UpsHealthOptimizerService;
use Tests\TestCase;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class UpsGovernanceHardeningTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        // Setup initial categories and templates
        $cat = IlanKategori::create(['name' => 'Verif Cat', 'aktiflik_durumu' => 1]);
        $type = YayinTipiSablonu::create(['ad' => 'Verif Type', 'slug' => 'verif-type']);
        $cat->yayinTipleri()->attach($type->id);

        $template = UpsTemplate::create([
            'kategori_id' => $cat->id,
            'yayin_tipi_id' => $type->id,
            'yayin_tipi_sablonu_id' => $type->id,
            'template_json' => ['foo' => 'bar'],
            'template_hash' => \Illuminate\Support\Str::random(40),
            'aktiflik_durumu' => 1,
        ]);

        // Capture initial snapshot
        $this->artisan('bekci:governance:init');
    }

    /** @test */
    public function it_detects_drift_when_live_template_is_modified()
    {
        $activeVersion = PropertyConfigVersion::where('governance_state', 'ACTIVE')->first();
        $this->assertNotNull($activeVersion->snapshot_json);

        // Modify live template (BYPASS Governance)
        $template = UpsTemplate::first();
        $template->update(['template_json' => ['foo' => 'modified_drift']]);

        // Health check should detect drift
        $response = $this->actingAsAdmin()->get('/admin/ups/health');

        $response->assertStatus(200);
        $data = $response->viewData('stats');
        $this->assertEquals(1, $data['drifts'], 'Drift should be detected after manual DB update');
    }

    /** @test */
    public function optimizer_creates_draft_instead_of_direct_write()
    {
        // Add a new publication type without template
        $cat = IlanKategori::first();
        $newType = YayinTipiSablonu::create(['ad' => 'New Type', 'slug' => 'new-type']);
        $cat->yayinTipleri()->attach($newType->id);

        $optimizer = new UpsHealthOptimizerService();
        $result = $optimizer->optimizeAll();

        $this->assertEquals('DRAFT_PROMOTED', $result['durum']);
        $this->assertDatabaseHas('property_config_versions', [
            'governance_state' => 'DRAFT',
            'id' => $result['version_id'],
        ]);

        // Verify live UpsTemplate table STILL HAS NO TEMPLATE for the new type
        $this->assertDatabaseMissing('ups_templates', [
            'yayin_tipi_id' => $newType->id,
        ]);

        // But the snapshot in the DRAFT version has it
        $draft = PropertyConfigVersion::find($result['version_id']);
        $this->assertCount(2, $draft->snapshot_json['templates']);
    }

    private function actingAsAdmin()
    {
        $admin = \App\Models\User::factory()->create(['email' => 'admin@yalihan.com']);
        return $this->actingAs($admin);
    }
}
