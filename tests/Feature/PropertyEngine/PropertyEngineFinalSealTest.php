<?php

namespace Tests\Feature\PropertyEngine;

use App\Models\Feature;
use App\Models\YayinTipiSablonu;
use Tests\TestCase;

/**
 * FINAL SEAL VERIFICATION TEST (SAB v24.0)
 * 🛡️ Phase T4: Sealing Test Packs Standardization
 */
class PropertyEngineFinalSealTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 🛡️ Canonical Admin Fixture
        $this->actingAs($this->createAdminUser());
    }

    /**
     * PILLAR 2 & 3: Analytics & Template Index Smoke
     * @test
     */
    public function analytics_and_templates_indexes_are_reachable()
    {
        // Analytics
        $this->get(route('admin.property-hub.analytics.index'))
            ->assertStatus(200)
            ->assertViewIs('admin.property-hub.analytics.index');

        // Templates
        $this->get(route('admin.property-hub.templates.index'))
            ->assertStatus(200)
            ->assertViewIs('admin.property-hub.templates.index');
    }

    /**
     * PILLAR 4: Legacy Redirect Verification
     * @test
     */
    public function legacy_template_routes_redirect_to_unified_hub()
    {
        $this->get(route('admin.property-hub.yayin-tipi-sablonlari.index'))
            ->assertRedirect(route('admin.property-hub.templates.index'));
    }

    /**
     * PILLAR 5: Mutation Integrity (Assign / Remove / Sync)
     * @test
     */
    public function template_feature_mutation_flow_works_correctly()
    {
        $template = YayinTipiSablonu::factory()->create();
        $feature = Feature::factory()->create(['aktiflik_durumu' => true]);

        // 1. ASSIGN
        $this->postJson(route('admin.property-hub.templates.assign'), [
            'yayin_tipi_id' => $template->id,
            'feature_id' => $feature->id,
            'is_required' => true,
            'display_order' => 10
        ])->assertOk();

        $this->assertDatabaseHas('feature_assignments', [
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $template->id,
            'feature_id' => $feature->id,
            'is_required' => 1
        ]);

        // 2. SYNC
        $this->postJson(route('admin.property-hub.templates.sync', $template->id), [
            'features' => [
                [
                    'id' => $feature->id,
                    'is_required' => false,
                    'display_order' => 20
                ]
            ]
        ])->assertOk();

        $this->assertDatabaseHas('feature_assignments', [
            'assignable_id' => $template->id,
            'feature_id' => $feature->id,
            'is_required' => 0,
            'display_order' => 20
        ]);

        // 3. REMOVE
        $this->postJson(route('admin.property-hub.templates.unassign'), [
            'yayin_tipi_id' => $template->id,
            'feature_id' => $feature->id
        ])->assertOk();

        $this->assertDatabaseMissing('feature_assignments', [
            'assignable_id' => $template->id,
            'feature_id' => $feature->id
        ]);
    }

    /**
     * PILLAR 6: TKGM Surface Verification
     * @test
     */
    public function tkgm_bulk_endpoint_is_reachable()
    {
        $response = $this->get('/admin/tkgm-parsel');
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}
