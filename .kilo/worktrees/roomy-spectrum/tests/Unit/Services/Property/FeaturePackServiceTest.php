<?php

namespace Tests\Unit\Services\Property;

use App\Enums\AktiflikDurumu;
use App\Models\Feature;
use App\Models\FeaturePack;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Services\Property\FeaturePackService;
use Tests\TestCase;

/**
 * Context7 TDD: FeaturePackService Test
 *
 * Tests MUST use canonical fields:
 * - aktiflik_durumu (NOT status/active)
 * - display_order (NOT sort_order)
 */
class FeaturePackServiceTest extends TestCase
{

    private FeaturePackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeaturePackService::class);
    }

    /** @test */
    public function it_creates_feature_pack_with_context7_compliant_fields()
    {
        $packData = [
            'name' => 'Lüks Villa Paketi',
            'description' => 'Lüks villalar için standart özellikler',
            'aktiflik_durumu' => true, // ✅ SAB compliant
        ];

        $pack = $this->service->createPack($packData);

        $this->assertInstanceOf(FeaturePack::class, $pack);
        $this->assertEquals('Lüks Villa Paketi', $pack->name);
        $this->assertEquals(AktiflikDurumu::AKTIF, $pack->aktiflik_durumu);
    }

    /** @test */
    public function it_rejects_forbidden_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Forbidden field: status'); // context7-ignore: intentional forbidden field usage for negative test

        $packData = [
            'name' => 'Test Pack',
            'status' => 'active', // context7-ignore: intentional forbidden field usage for negative test
        ];

        $this->service->createPack($packData);
    }

    /** @test */
    public function it_attaches_features_to_pack()
    {
        $pack = FeaturePack::factory()->create();
        $features = Feature::factory()->count(3)->create();

        $result = $this->service->attachFeatures($pack->id, $features->pluck('id')->toArray());

        $this->assertTrue($result);
        $this->assertEquals(3, $pack->features()->count());
    }

    /** @test */
    public function it_applies_pack_to_category_yayin_tipi()
    {
        $pack = FeaturePack::factory()->create();
        $features = Feature::factory()->count(5)->create();
        $pack->features()->attach($features->pluck('id'));

        $kategori = IlanKategori::factory()->create();
        $yayinTipi = YayinTipiSablonu::factory()->create();

        $result = $this->service->applyPackToYayinTipi($pack->id, $yayinTipi->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['assigned_count']);
    }

    /** @test */
    public function it_validates_pack_data_structure()
    {
        $invalidData = [
            'name' => '', // Empty name
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->service->createPack($invalidData);
    }

    /** @test */
    public function it_updates_pack_preserving_context7_compliance()
    {
        $pack = FeaturePack::factory()->create([
            'aktiflik_durumu' => false,
        ]);

        $updateData = [
            'name' => 'Updated Pack',
            'aktiflik_durumu' => true,
        ];

        $updated = $this->service->updatePack($pack->id, $updateData);

        $this->assertEquals('Updated Pack', $updated->name);
        $this->assertEquals(AktiflikDurumu::AKTIF, $updated->aktiflik_durumu);
    }

    /** @test */
    public function it_lists_active_packs_only()
    {
        FeaturePack::factory()->create(['aktiflik_durumu' => true]);
        FeaturePack::factory()->create(['aktiflik_durumu' => true]);
        FeaturePack::factory()->create(['aktiflik_durumu' => false]);

        $activePacks = $this->service->getActivePacks();

        $this->assertCount(2, $activePacks);
    }

    /** @test */
    public function it_prevents_duplicate_feature_assignment()
    {
        $pack = FeaturePack::factory()->create();
        $feature = Feature::factory()->create();

        $this->service->attachFeatures($pack->id, [$feature->id]);

        // Try to attach same feature again
        $result = $this->service->attachFeatures($pack->id, [$feature->id]);

        $this->assertTrue($result);
        $this->assertEquals(1, $pack->features()->count()); // Still 1, not 2
    }
}
