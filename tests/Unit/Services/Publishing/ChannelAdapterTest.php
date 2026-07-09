<?php

namespace Tests\Unit\Services\Publishing;

use App\Services\Publishing\Transformers\AmenityMapper;
use App\Services\Publishing\Transformers\TitleTransformer;
use PHPUnit\Framework\TestCase;

/**
 * Channel Adapter Unit Tests — Sprint 6.5
 *
 * Supports() ve validate() metodları Ilan model type-hint kullandığı için
 * pure function testleri burada yapılır.
 * Adapter'ların tam testi Feature/PublishingIntelligenceTest'tedir.
 */
class ChannelAdapterTest extends TestCase
{
    private AmenityMapper $amenityMapper;
    private TitleTransformer $titleTransformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->amenityMapper = new AmenityMapper();
        $this->titleTransformer = new TitleTransformer();
    }

    // ─── AmenityMapper: pure transformation ───────────────────────────────

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

    // ─── TitleTransformer: pure transformation ────────────────────────────────

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
        $hints = $this->titleTransformer->extractHints(['', '  ']);

        $this->assertCount(0, $hints);
    }
}
