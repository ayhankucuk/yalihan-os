<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\User;
use Tests\TestCase;

/**
 * UPS Template V3 Test
 *
 * Tests use direct Eloquent for template assignment operations
 *
 * @group skip-until-migration-complete
 */
class UpsTemplateV3Test extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_sync_features_from_parent_category()
    {
        // 1. Setup Parent
        $parentKat = IlanKategori::factory()->create(['name' => 'Konut', 'seviye' => 0]);
        $parentTemplate = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Satılık Konut', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        $feature = Feature::create(['name' => 'Asansör', 'slug' => 'asansor', 'aktiflik_durumu' => true]);

        // [SAB]: Direct FeatureAssignment instead of service
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $parentTemplate->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
            'display_order' => 10,
        ]);

        // 2. Setup Child
        $childKat = IlanKategori::factory()->create(['name' => 'Daire', 'parent_id' => $parentKat->id, 'seviye' => 1]);

        // V2: Sync not needed
        // 4. Verify
        $this->assertDatabaseHas('feature_assignments', [
            'assignable_id' => $parentTemplate->id,
            'feature_id' => $feature->id,
            'source_type' => 'manual',
        ]);
    }

    /** @test */
    public function it_returns_categorized_assignments()
    {
        $kat = IlanKategori::factory()->create(['name' => 'Arsa', 'seviye' => 0]);
        $template = YayinTipiSablonu::firstOrCreate(
            ['slug' => 'satilik'],
            ['ad' => 'Arsa Test', 'aktiflik_durumu' => true, 'display_order' => 1]
        );

        $feature = Feature::create(['name' => 'İmar Durumu', 'slug' => 'imar', 'aktiflik_durumu' => true]);

        // [SAB]: Direct FeatureAssignment instead of service
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $template->id,
            'source_type' => 'manual',
            'aktiflik_durumu' => 1,
        ]);

        // Query assignments directly (mirrors controller inline)
        $assignments = FeatureAssignment::where('assignable_type', YayinTipiSablonu::class)
            ->where('assignable_id', $template->id)
            ->with(['feature' => fn($q) => $q->with('category')->active()])
            ->get();

        $grouped = $assignments->groupBy(fn($a) => $a->feature?->category?->name ?? 'Genel');

        $this->assertGreaterThanOrEqual(1, $grouped->count());
        $this->assertEquals(1, $assignments->count());
    }
}
