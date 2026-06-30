<?php

namespace Tests\Unit\Services\Property;

use App\Enums\AktiflikDurumu;
use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Property\PropertyBulkOperationsService;
use Tests\TestCase;

/**
 * Context7 TDD: PropertyBulkOperationsService Test
 *
 * Tests MUST use canonical fields:
 * - aktiflik_durumu (NOT status/active)
 * - display_order (NOT sort_order)
 */
class PropertyBulkOperationsServiceTest extends TestCase
{

    private PropertyBulkOperationsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PropertyBulkOperationsService::class);
    }

    /** @test */
    public function it_assigns_single_feature_to_yayin_tipi()
    {
        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $result = $this->service->assignFeature($yayinTipi->id, $feature->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, FeatureAssignment::count());

        $assignment = FeatureAssignment::first();
        $this->assertEquals($feature->id, $assignment->feature_id);
        $this->assertEquals($yayinTipi->id, $assignment->assignable_id);
        $this->assertEquals(YayinTipiSablonu::class, $assignment->assignable_type);
        $this->assertEquals(AktiflikDurumu::AKTIF, $assignment->aktiflik_durumu);
    }

    /** @test */
    public function it_prevents_duplicate_feature_assignment()
    {
        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        // First assignment
        $this->service->assignFeature($yayinTipi->id, $feature->id);

        // Try duplicate
        $result = $this->service->assignFeature($yayinTipi->id, $feature->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('zaten atanmış', $result['message']);
        $this->assertEquals(1, FeatureAssignment::count()); // Still 1
    }

    /** @test */
    public function it_bulk_assigns_multiple_features()
    {
        $features = Feature::factory()->count(5)->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $result = $this->service->bulkAssign(
            $yayinTipi->id,
            $features->pluck('id')->toArray()
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['added']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(5, FeatureAssignment::count());
    }

    /** @test */
    public function it_skips_already_assigned_features_in_bulk()
    {
        $features = Feature::factory()->count(5)->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        // Pre-assign 2 features
        $this->service->assignFeature($yayinTipi->id, $features[0]->id);
        $this->service->assignFeature($yayinTipi->id, $features[1]->id);

        // Bulk assign all 5
        $result = $this->service->bulkAssign(
            $yayinTipi->id,
            $features->pluck('id')->toArray()
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['added']); // Only 3 new
        $this->assertEquals(2, $result['skipped']); // 2 already existed
        $this->assertEquals(5, FeatureAssignment::count()); // Total 5
    }

    /** @test */
    public function it_unassigns_feature_from_yayin_tipi()
    {
        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        // Create assignment
        FeatureAssignment::create([
            'feature_id' => $feature->id,
            'assignable_type' => YayinTipiSablonu::class,
            'assignable_id' => $yayinTipi->id,
            'aktiflik_durumu' => true,
            'source_type' => 'manual',
        ]);

        $result = $this->service->unassignFeature($yayinTipi->id, $feature->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, FeatureAssignment::count());
    }

    /** @test */
    public function it_handles_unassign_of_non_existent_assignment()
    {
        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $result = $this->service->unassignFeature($yayinTipi->id, $feature->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('bulunamadı', $result['message']);
    }

    /** @test */
    public function it_bulk_unassigns_multiple_features()
    {
        $features = Feature::factory()->count(5)->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        // Create assignments
        foreach ($features as $feature) {
            FeatureAssignment::create([
                'feature_id' => $feature->id,
                'assignable_type' => YayinTipiSablonu::class,
                'assignable_id' => $yayinTipi->id,
                'aktiflik_durumu' => true,
                'source_type' => 'manual',
            ]);
        }

        $result = $this->service->bulkUnassign(
            $yayinTipi->id,
            $features->pluck('id')->toArray()
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['removed']);
        $this->assertEquals(0, FeatureAssignment::count());
    }

    /** @test */
    public function it_uses_context7_compliant_fields()
    {
        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $this->service->assignFeature($yayinTipi->id, $feature->id, [
            'display_order' => 10, // ✅ SAB compliant
        ]);

        $assignment = FeatureAssignment::first();
        $this->assertEquals(10, $assignment->display_order);
        $this->assertEquals(AktiflikDurumu::AKTIF, $assignment->aktiflik_durumu);
    }

    /** @test */
    public function it_rejects_forbidden_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Forbidden field'); // context7-ignore: intentional forbidden field usage for negative test

        $feature = Feature::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $this->service->assignFeature($yayinTipi->id, $feature->id, [
            'sort_order' => 10, // context7-ignore: intentional forbidden field usage for negative test
        ]);
    }
}
