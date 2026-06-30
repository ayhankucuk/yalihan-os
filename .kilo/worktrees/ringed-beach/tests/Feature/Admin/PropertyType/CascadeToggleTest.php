<?php

namespace Tests\Feature\Admin\PropertyType;

use App\Models\AltKategoriYayinTipi;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class CascadeToggleTest extends TestCase
{

    protected $admin;
    protected $parentCategory;
    protected $childCategory1;
    protected $childCategory2;
    protected $yayinTipi;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Admin
        $this->admin = User::factory()->create(['role_id' => 1]);

        // Setup Parent Category
        $this->parentCategory = IlanKategori::factory()->create([
            'seviye' => 0,
            'name' => 'Parent Category',
            'aktiflik_durumu' => true
        ]);

        // Setup Child Categories
        $this->childCategory1 = IlanKategori::factory()->create([
            'seviye' => 1,
            'parent_id' => $this->parentCategory->id,
            'name' => 'Child 1',
            'aktiflik_durumu' => true
        ]);

        $this->childCategory2 = IlanKategori::factory()->create([
            'seviye' => 1,
            'parent_id' => $this->parentCategory->id,
            'name' => 'Child 2',
            'aktiflik_durumu' => true
        ]);

        // Setup Yayin Tipi (Global)
        $this->yayinTipi = YayinTipiSablonu::factory()->create([
            'ad' => 'Test Yayin Tipi',
            'aktiflik_durumu' => true
        ]);
    }

    /** @test */
    public function it_can_cascade_toggle_to_children_when_cascade_is_true()
    {
        // Assert initial state: No pivot records
        $this->assertDatabaseMissing('alt_kategori_yayin_tipi', ['yayin_tipi_id' => $this->yayinTipi->id]);

        // Act: Apply to Parent with cascade=true
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.property_types.toggle_yayin_tipi', $this->parentCategory->id), [
                'alt_kategori_id' => $this->parentCategory->id, // Targeting parent
                'yayin_tipi_id' => $this->yayinTipi->id,
                'aktiflik_durumu' => true,
                'cascade' => true
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Check that pivots created for BOTH children
        $this->assertDatabaseHas('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory1->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => true
        ]);

        $this->assertDatabaseHas('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory2->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => true
        ]);
    }

    /** @test */
    public function it_toggles_single_subcategory_when_cascade_is_false()
    {
        // Act: Apply to Child 1 ONLY
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.property_types.toggle_yayin_tipi', $this->parentCategory->id), [
                'alt_kategori_id' => $this->childCategory1->id,
                'yayin_tipi_id' => $this->yayinTipi->id,
                'aktiflik_durumu' => true,
                'cascade' => false
            ]);

        $response->assertStatus(200);

        // Assert Child 1 has pivot
        $this->assertDatabaseHas('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory1->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => true
        ]);

        // Assert Child 2 DOES NOT have pivot
        $this->assertDatabaseMissing('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory2->id,
            'yayin_tipi_id' => $this->yayinTipi->id
        ]);
    }

    /** @test */
    public function it_can_cascade_deactivate()
    {
        // Setup: Create active pivots first
        AltKategoriYayinTipi::create(['alt_kategori_id' => $this->childCategory1->id, 'yayin_tipi_id' => $this->yayinTipi->id, 'aktiflik_durumu' => true]);
        AltKategoriYayinTipi::create(['alt_kategori_id' => $this->childCategory2->id, 'yayin_tipi_id' => $this->yayinTipi->id, 'aktiflik_durumu' => true]);

        // Act: Apply to Parent with cascade=true and aktiflik_durumu=false
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.property_types.toggle_yayin_tipi', $this->parentCategory->id), [
                'alt_kategori_id' => $this->parentCategory->id,
                'yayin_tipi_id' => $this->yayinTipi->id,
                'aktiflik_durumu' => false,
                'cascade' => true
            ]);

        $response->assertStatus(200);

        // Check that pivots updated to false
        $this->assertDatabaseHas('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory1->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => false
        ]);

        $this->assertDatabaseHas('alt_kategori_yayin_tipi', [
            'alt_kategori_id' => $this->childCategory2->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => false
        ]);
    }
}
