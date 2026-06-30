<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\IlanKategori;
use App\Models\Feature;
use App\Models\YayinTipiSablonu;
use App\Models\FeatureAssignment;
use App\Modules\GovernanceCore\Core\UpsAnalyticsService;
use Illuminate\Support\Facades\DB;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Modules\GovernanceCore\Core\UpsAnalyticsService henüz implement edilmedi.
 */
class PropertyHubAnalyticsTest extends TestCase
{

    /**
     * Test analytics dashboard data structure and integrity.
     *
     * @return void
     */
    public function test_analytics_dashboard_data_structure_and_integrity()
    {
        // 1. Arrange: Seed Data

        // Create Main Category
        $mainCategory = IlanKategori::factory()->create([
            'name' => 'Main Category',
            'seviye' => 0,
            'parent_id' => null,
            'aktiflik_durumu' => true
        ]);

        // Create Sub Category linked to Main
        $subCategory = IlanKategori::factory()->create([
            'name' => 'Sub Category',
            'seviye' => 1,
            'parent_id' => $mainCategory->id,
            'aktiflik_durumu' => true
        ]);

        // Create Template
        $template = YayinTipiSablonu::factory()->create([
            'ad' => 'Test Template',
            'aktiflik_durumu' => true
        ]);

        // Link Template to Sub Category (Pivot)
        DB::table('alt_kategori_yayin_tipi')->insert([
            'alt_kategori_id' => $subCategory->id,
            'yayin_tipi_id' => $template->id,
            'aktiflik_durumu' => true,
            'display_order' => 0
        ]);

        // Create Feature
        $feature = Feature::factory()->create([
            'name' => 'Test Feature',
            'aktiflik_durumu' => true
        ]);

        // Assign Feature to Template
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $template->id,
            'is_required' => false
        ]);

        // 2. Arrange: Create Service
        $service = new UpsAnalyticsService();

        // 3. Act: Build Dashboard
        $data = $service->buildDashboard();

        // 4. Assert: Data Structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('heatmapData', $data);
        $this->assertArrayHasKey('coverageStats', $data);
        $this->assertArrayHasKey('metrics', $data);

        // 5. Assert: Categories Integrity
        $this->assertNotEmpty($data['categories'], 'Categories should not be empty.');
        $category = $data['categories']->first();
        $this->assertInstanceOf(IlanKategori::class, $category);
        $this->assertEquals($mainCategory->id, $category->id);

        // 6. Assert: Heatmap Integrity
        $this->assertNotEmpty($data['heatmapData'], 'Heatmap data should not be empty.');

        // Check sample feature in heatmap
        $this->assertArrayHasKey($feature->id, $data['heatmapData']);
        $featureHeatmap = $data['heatmapData'][$feature->id];

        // Assert proper count for the main category
        $this->assertArrayHasKey($mainCategory->id, $featureHeatmap);
        $this->assertEquals(1, $featureHeatmap[$mainCategory->id], 'Heatmap should show 1 assignment for the main category via recursion.');

        // 7. Assert: Feature Utilization
        // The feature is assigned to 1 template. Total templates = 1.
        // utilization % should be 100% since we have 1 feature and it is assigned.
        // Actually, calculation is: (Feature::has('assignments')->count() / Feature::count()) * 100
        // 1 / 1 * 100 = 100
        $utilization = collect($data['coverageStats'])->firstWhere('name', 'Feature Utilization')['percentage'];
        $this->assertEquals(100, $utilization);
    }
}
