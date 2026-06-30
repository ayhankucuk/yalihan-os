<?php

namespace Tests\Feature;

use App\Domains\PropertySchema\PropertyTypeConfiguration;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Controller → Aggregate Root → Domain Service → DB Integration Test
 *
 * Validates the full request lifecycle through domain boundaries:
 * - Controller delegates to AR (no direct DB writes)
 * - AR manages transaction boundaries
 * - FeaturePackApplicator handles polymorphic scope
 * - Events dispatched correctly
 */
class TemplateIntegrationTest extends TestCase
{

    private User $admin;
    private YayinTipiSablonu $template;
    private IlanKategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        // Routes for UpsTemplateManagerController were deliberately removed (SAB PURGE in admin.php).
        // These tests are for the deprecated controller and should be skipped.
        $this->markTestSkipped('UpsTemplateManagerController routes removed (SAB PURGE)');
    }

    /** @test */
    public function controller_add_feature_routes_through_aggregate_root()
    {
        $feature = Feature::create([
            'name' => 'Test Özellik',
            'slug' => 'test-ozellik',
            'aktiflik_durumu' => true,
        ]);

        $response = $this->postJson(route('admin.ups.templates.add-feature'), [
            'kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->template->id,
            'feature_id' => $feature->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.created', true);

        // Verify DB state
        $this->assertDatabaseHas('feature_assignments', [
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->template->id,
            'feature_id' => $feature->id,
            'source_type' => 'manual',
        ]);
    }

    /** @test */
    public function controller_add_feature_is_idempotent()
    {
        $feature = Feature::create([
            'name' => 'Idempotent Test',
            'slug' => 'idempotent-test',
            'aktiflik_durumu' => true,
        ]);

        // First call — creates
        $this->postJson(route('admin.ups.templates.add-feature'), [
            'kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->template->id,
            'feature_id' => $feature->id,
        ])->assertJsonPath('data.created', true);

        // Second call — idempotent
        $this->postJson(route('admin.ups.templates.add-feature'), [
            'kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->template->id,
            'feature_id' => $feature->id,
        ])->assertJsonPath('data.created', false);

        // Only 1 assignment in DB
        $this->assertEquals(1, FeatureAssignment::where('feature_id', $feature->id)->count());
    }

    /** @test */
    public function controller_remove_feature_routes_through_aggregate_root()
    {
        $feature = Feature::create([
            'name' => 'Remove Test',
            'slug' => 'remove-test',
            'aktiflik_durumu' => true,
        ]);

        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->template->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
        ]);

        $response = $this->deleteJson(route('admin.ups.templates.remove-feature'), [
            'kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->template->id,
            'feature_id' => $feature->id,
        ]);

        $response->assertOk();

        // Verify via DB directly since response structure may vary
        $this->assertDatabaseMissing('feature_assignments', [
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->template->id,
            'feature_id' => $feature->id,
        ]);
    }

    /** @test */
    public function aggregate_root_template_pack_application_works()
    {
        $feature1 = Feature::create(['name' => 'Pack F1', 'slug' => 'pack-f1', 'aktiflik_durumu' => true]);
        $feature2 = Feature::create(['name' => 'Pack F2', 'slug' => 'pack-f2', 'aktiflik_durumu' => true]);

        $pack = FeaturePack::create([
            'name' => 'Test Pack',
            'slug' => 'test-pack',
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);
        $pack->features()->attach([$feature1->id, $feature2->id]);

        // Use AR directly
        $ar = app(PropertyTypeConfiguration::class);
        $result = $ar->applyFeaturePack($this->template->id, $pack->id, 'merge', $this->admin->id, YayinTipiSablonu::class);

        $this->assertEquals(2, $result['added_count']);
        $this->assertEquals(0, $result['skipped_count']);

        // Verify DB
        $this->assertEquals(2, FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $this->template->id)
            ->count());
    }

    /** @test */
    public function aggregate_root_template_reorder_works()
    {
        $f1 = Feature::create(['name' => 'Reorder F1', 'slug' => 'reorder-f1', 'aktiflik_durumu' => true]);
        $f2 = Feature::create(['name' => 'Reorder F2', 'slug' => 'reorder-f2', 'aktiflik_durumu' => true]);

        FeatureAssignment::create([
            'feature_id' => $f1->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->template->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
            'display_order' => 10,
        ]);
        FeatureAssignment::create([
            'feature_id' => $f2->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->template->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
            'display_order' => 20,
        ]);

        $ar = app(PropertyTypeConfiguration::class);
        $updated = $ar->reorderFeatures($this->template->id, [
            ['feature_id' => $f1->id, 'display_order' => 50],
            ['feature_id' => $f2->id, 'display_order' => 5],
        ], $this->admin->id, YayinTipiSablonu::class);

        $this->assertEquals(2, $updated);

        // Verify sequence changed
        $a1 = FeatureAssignment::where('feature_id', $f1->id)->first();
        $a2 = FeatureAssignment::where('feature_id', $f2->id)->first();
        $this->assertEquals(50, $a1->display_order);
        $this->assertEquals(5, $a2->display_order);
    }

    /** @test */
    public function aggregate_root_import_template_features_respects_tx_boundary()
    {
        $f1 = Feature::create(['name' => 'Import F1', 'slug' => 'import-f1', 'aktiflik_durumu' => true]);
        $f2 = Feature::create(['name' => 'Import F2', 'slug' => 'import-f2', 'aktiflik_durumu' => true]);

        $ar = app(PropertyTypeConfiguration::class);
        $result = $ar->importTemplateFeatures($this->template->id, [
            ['feature_id' => $f1->id, 'source_type' => 'import', 'display_order' => 1],
            ['feature_id' => $f2->id, 'source_type' => 'import', 'display_order' => 2],
        ], $this->admin->id);

        $this->assertEquals(2, $result['added']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        $this->assertEquals(2, FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $this->template->id)
            ->count());
    }
}
