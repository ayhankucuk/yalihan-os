<?php

namespace Tests\Feature\Admin\PropertyHub;

use App\Models\AltKategoriYayinTipi;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Models\User;
use App\Models\YayinTipiSablonu;
use App\Services\Ilan\IlanFeatureService;
use Tests\TestCase;

class TemplateResolutionTest extends TestCase
{

    protected $service;
    protected $category;
    protected $yayinTipi;
    protected $globalFeature;
    protected $pivotFeature;
    protected $pivot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IlanFeatureService::class);

        // 1. Setup Feature Category
        $featCat = FeatureCategory::factory()->create(['name' => 'General']);

        // 2. Setup Features
        $this->globalFeature = Feature::factory()->create(['name' => 'Global Feature', 'feature_category_id' => $featCat->id]);
        $this->pivotFeature = Feature::factory()->create(['name' => 'Pivot Feature', 'feature_category_id' => $featCat->id]);

        // 3. Setup Category & YayinTipi
        $this->category = IlanKategori::factory()->create(['name' => 'Test Category']);
        $this->yayinTipi = YayinTipiSablonu::factory()->create(['ad' => 'Test Type', 'slug' => 'test-type']);

        // 4. Assign Global Feature to YayinTipi
        FeatureAssignment::create([
            'feature_id' => $this->globalFeature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->yayinTipi->id,
            'is_visible' => true,
            'display_order' => 1
        ]);

        // 5. Create Pivot (Toggle)
        $this->pivot = AltKategoriYayinTipi::create([
            'alt_kategori_id' => $this->category->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'aktiflik_durumu' => true
        ]);

        // 6. Assign Pivot Feature to Pivot
        FeatureAssignment::create([
            'feature_id' => $this->pivotFeature->id,
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $this->pivot->id,
            'is_visible' => true,
            'display_order' => 2
        ]);
    }

    /**
     * @test
     * @group skip-until-migration-complete
     */
    public function it_resolves_global_and_pivot_features_together()
    {
        // Act
        $result = $this->service->getFeaturesByCategory($this->category->id, $this->yayinTipi->id);

        // Extract all feature IDs from result
        $resolvedFeatureIds = collect($result['feature_categories'])
            ->pluck('features')
            ->flatten(1)
            ->pluck('id')
            ->toArray();

        // Assert Global Feature is present
        $this->assertContains($this->globalFeature->id, $resolvedFeatureIds, 'Global feature should be present.');

        // Assert Pivot Feature is present (This is expected to FAIL currently)
        $this->assertContains($this->pivotFeature->id, $resolvedFeatureIds, 'Pivot feature should be present.');
    }

    /**
     * @test
     * @group skip-until-migration-complete
     */
    public function pivot_assignment_overrides_required_flag()
    {
        // Setup: Same feature assigned to both, but Pivot is REQUIRED
        $sharedFeature = Feature::factory()->create(['name' => 'Shared Feature']);

        // Global: Not Required
        FeatureAssignment::create([
            'feature_id' => $sharedFeature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $this->yayinTipi->id,
            'is_required' => false,
            'is_visible' => true
        ]);

        // Pivot: Required
        FeatureAssignment::create([
            'feature_id' => $sharedFeature->id,
            'assignable_type' => AltKategoriYayinTipi::class,
            'assignable_id' => $this->pivot->id,
            'is_required' => true,
            'is_visible' => true
        ]);

        // Act
        $result = $this->service->getFeaturesByCategory($this->category->id, $this->yayinTipi->id);

        $feature = collect($result['feature_categories'])
            ->pluck('features')
            ->flatten(1)
            ->firstWhere('id', $sharedFeature->id);

        $this->assertNotNull($feature);
        $this->assertTrue($feature['required'], 'Pivot required flag should override global.');
    }
}
