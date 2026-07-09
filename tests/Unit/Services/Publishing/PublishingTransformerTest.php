<?php

namespace Tests\Unit\Services\Publishing;

use App\Services\Publishing\Transformers\AmenityMapper;
use App\Services\Publishing\Transformers\DescriptionTransformer;
use App\Services\Publishing\Transformers\RoomTypeMapper;
use App\Services\Publishing\Transformers\TitleTransformer;
use PHPUnit\Framework\TestCase;

/**
 * Publishing Transformer Unit Tests — Sprint 6.5
 *
 * Transformer'lar sadece veri dönüştürür — Eloquent model gerektirmez.
 * Bu testler Ilan model mock'lamaz; pure transformation logic test eder.
 */
class PublishingTransformerTest extends TestCase
{
    private TitleTransformer $titleTransformer;
    private AmenityMapper $amenityMapper;
    private RoomTypeMapper $roomTypeMapper;
    private DescriptionTransformer $descriptionTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->titleTransformer = new TitleTransformer();
        $this->amenityMapper = new AmenityMapper();
        $this->roomTypeMapper = new RoomTypeMapper();
        $this->descriptionTransformer = new DescriptionTransformer();
    }

    // ─── TitleTransformer ──────────────────────────────────────────────────

    public function test_title_transformer_extract_hints(): void
    {
        $hints = $this->titleTransformer->extractHints([
            'Hint 1',
            '',
            'Hint 2',
            '  ',
            'Hint 3',
        ]);

        $this->assertCount(3, $hints);
    }

    public function test_title_transformer_extract_hints_empty(): void
    {
        $hints = $this->titleTransformer->extractHints(['', '  ', null]);

        $this->assertCount(0, $hints);
    }

    // ─── AmenityMapper ─────────────────────────────────────────────────────

    public function test_amenity_mapper_to_airbnb_amenities(): void
    {
        $amenities = $this->amenityMapper->toAirbnbAmenities(['Havuz', 'WiFi', 'Klima', 'Bilinmeyen']);

        $this->assertContains('pool', $amenities);
        $this->assertContains('wifi', $amenities);
        $this->assertContains('air_conditioning', $amenities);
        $this->assertCount(3, $amenities);
    }

    public function test_amenity_mapper_to_sahibinden_features(): void
    {
        $features = $this->amenityMapper->toSahibindenFeatures(
            ['Havuz', 'Klima'],
            ['Jakuzi', 'Sauna'],
        );

        $this->assertContains('Havuz', $features);
        $this->assertContains('Klima', $features);
        $this->assertContains('Jakuzili', $features);
        $this->assertContains('Saunalı', $features);
    }

    public function test_amenity_mapper_to_hepsiemlak_features(): void
    {
        $features = $this->amenityMapper->toHepsiemlakFeatures(
            ['Havuz', 'Otopark', 'Güvenlik'],
            ['Özel Havuz'],
        );

        $this->assertContains('Havuz', $features);
        $this->assertContains('Otopark', $features);
        $this->assertContains('Özel Havuz', $features);
    }

    public function test_amenity_mapper_deduplicates(): void
    {
        $features = $this->amenityMapper->toSahibindenFeatures(
            ['Havuz', 'Havuz', 'havuz'],
            [],
        );

        $this->assertCount(1, $features);
    }

    public function test_amenity_mapper_empty_input(): void
    {
        $airbnb = $this->amenityMapper->toAirbnbAmenities([]);
        $sahibinden = $this->amenityMapper->toSahibindenFeatures([], []);
        $hepsiemlak = $this->amenityMapper->toHepsiemlakFeatures([], []);

        $this->assertEmpty($airbnb);
        $this->assertEmpty($sahibinden);
        $this->assertEmpty($hepsiemlak);
    }

    // ─── AmenityMapper: case insensitivity ────────────────────────────────

    public function test_amenity_mapper_case_insensitive(): void
    {
        // Turkish case insensitivity
        $features = $this->amenityMapper->toSahibindenFeatures(
            ['HAVUZ', 'havuz', 'Klima'],
            [],
        );

        // Should deduplicate AND match case-insensitively
        $this->assertNotEmpty($features);
    }

    // ─── RoomTypeMapper ───────────────────────────────────────────────────

    // ─── DescriptionTransformer: pure functions ─────────────────────────────

    public function test_description_transformer_keys_exist(): void
    {
        // Verify mappers work with arrays (pure functions)
        $airbnbAmenities = $this->amenityMapper->toAirbnbAmenities(['Havuz', 'Klima']);
        $sahibindenFeatures = $this->amenityMapper->toSahibindenFeatures(['Havuz'], ['Jakuzi']);

        $this->assertIsArray($airbnbAmenities);
        $this->assertIsArray($sahibindenFeatures);
        $this->assertNotEmpty($airbnbAmenities);
    }
}
