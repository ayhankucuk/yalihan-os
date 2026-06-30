<?php

namespace Tests\Feature\Admin\PropertyHub;

use App\Models\Feature;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Models\AltKategoriYayinTipi;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class CategorySpecificFeaturesTest extends TestCase
{

    protected $admin;
    protected $yayinTipi;
    protected $mainCategory;
    protected $subCategory;
    protected $feature;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Admin
        $this->admin = User::factory()->create(['role_id' => 1]);

        // Setup Template
        $this->yayinTipi = YayinTipiSablonu::factory()->create([
            'ad' => 'Satılık Konut',
            'slug' => 'satilik-konut',
            'aktiflik_durumu' => true
        ]);

        // Setup Categories
        $this->mainCategory = IlanKategori::factory()->create([
            'name' => 'Konut',
            'seviye' => 0,
            'aktiflik_durumu' => true
        ]);

        $this->subCategory = IlanKategori::factory()->create([
            'parent_id' => $this->mainCategory->id,
            'name' => 'Daire',
            'seviye' => 1,
            'aktiflik_durumu' => true
        ]);

        // Setup Feature
        $this->feature = Feature::factory()->create([
            'name' => 'Balkon',
            'type' => 'boolean',
            'aktiflik_durumu' => true
        ]);
    }

    /** @test */
    public function it_can_save_category_specific_assignments()
    {
        // First, ensure the pivot record doesn't exist
        $this->assertDatabaseMissing('alt_kategori_yayin_tipi', [
            'yayin_tipi_id' => $this->yayinTipi->id,
            'alt_kategori_id' => $this->subCategory->id
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.property-hub.templates.save-pivot-assignments'), [
                'yayin_tipi_id' => $this->yayinTipi->id,
                'alt_kategori_id' => $this->subCategory->id,
                'feature_ids' => [$this->feature->id]
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify Pivot Record Created
        $pivot = AltKategoriYayinTipi::where('yayin_tipi_id', $this->yayinTipi->id)
            ->where('alt_kategori_id', $this->subCategory->id)
            ->first();

        $this->assertNotNull($pivot);

        // Verify Feature Assignment
        $this->assertDatabaseHas('feature_assignments', [
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $pivot->id,
            'feature_id' => $this->feature->id
        ]);
    }

    /** @test */
    public function it_can_retrieve_category_specific_assignments()
    {
        // Create pivot manually first
        $pivot = AltKategoriYayinTipi::create([
            'yayin_tipi_id' => $this->yayinTipi->id,
            'alt_kategori_id' => $this->subCategory->id,
            'aktiflik_durumu' => true
        ]);

        // Assign feature
        $pivot->featureAssignments()->create([
            'feature_id' => $this->feature->id
        ]);

        // Then retrieve
        $url = route('admin.property-hub.templates.pivot-assignments') . '?yayin_tipi_id=' . $this->yayinTipi->id . '&alt_kategori_id=' . $this->subCategory->id;

        $response = $this->actingAs($this->admin)
            ->getJson($url);

        $response->assertStatus(200)
            ->assertJson(['pivot_exists' => true]);

        $data = $response->json('assignments');
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($this->feature->id, $data[0]['feature_id']);
    }

    /** @test */
    public function it_can_remove_features_via_sync()
    {
        // Setup initial assignment
        $this->actingAs($this->admin)
            ->postJson(route('admin.property-hub.templates.save-pivot-assignments'), [
                'yayin_tipi_id' => $this->yayinTipi->id,
                'alt_kategori_id' => $this->subCategory->id,
                'feature_ids' => [$this->feature->id]
            ]);

        $pivot = AltKategoriYayinTipi::where('yayin_tipi_id', $this->yayinTipi->id)
            ->where('alt_kategori_id', $this->subCategory->id)
            ->first();

        $this->assertDatabaseHas('feature_assignments', [
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $pivot->id,
            'feature_id' => $this->feature->id
        ]);

        // Sync with empty array (remove)
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.property-hub.templates.save-pivot-assignments'), [
                'yayin_tipi_id' => $this->yayinTipi->id,
                'alt_kategori_id' => $this->subCategory->id,
                'feature_ids' => []
            ]);

        $response->assertStatus(200);

        // Feature Assignment should be gone
        $this->assertDatabaseMissing('feature_assignments', [
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $pivot->id,
            'feature_id' => $this->feature->id
        ]);
    }
}
