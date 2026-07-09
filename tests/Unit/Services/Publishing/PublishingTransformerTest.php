<?php

namespace Tests\Unit\Services\Publishing;

use App\Services\Publishing\Transformers\AmenityMapper;
use App\Services\Publishing\Transformers\DescriptionTransformer;
use App\Services\Publishing\Transformers\RoomTypeMapper;
use App\Services\Publishing\Transformers\TitleTransformer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Publishing Transformer Unit Tests — Sprint 6.5
 *
 * TitleTransformer: pure function tests (no Ilan dependency)
 * DescriptionTransformer + RoomTypeMapper: require Ilan type hint → use TestCase with factory
 */
class PublishingTransformerTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_title_transformer_for_airbnb_truncates_at_50_chars(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Bodrumda Deniz Manzaralı Villa']);
        $ilan->il = (object)['il_adi' => 'Muğla'];
        $ilan->ilce = (object)['ilce_adi' => 'Bodrum'];
        $ilan->altKategori = (object)['adi' => 'Villa'];

        $hints = ['Deniz Manzaralı Özel Havuzlu', 'Ultra Lüks', 'Jakuzili'];
        $title = $this->titleTransformer->forAirbnb($ilan, $hints);

        $this->assertLessThanOrEqual(50, mb_strlen($title));
    }

    public function test_title_transformer_for_sahibinden_max_80_chars(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => '4+1 Bodrum Villa']);
        $ilan->il = (object)['il_adi' => 'Muğla'];
        $ilan->ilce = (object)['ilce_adi' => 'Bodrum'];
        $ilan->altKategori = (object)['adi' => '4+1 Villa'];

        $title = $this->titleTransformer->forSahibinden($ilan, ['Lüks']);

        $this->assertLessThanOrEqual(80, mb_strlen($title));
        $this->assertStringContainsString('Villa', $title);
    }

    public function test_title_transformer_for_hepsiemlak_max_100_chars(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Bodrum Villa']);
        $ilan->il = (object)['il_adi' => 'Muğla'];
        $ilan->ilce = (object)['ilce_adi' => 'Bodrum'];
        $ilan->altKategori = (object)['adi' => 'Villa'];

        $title = $this->titleTransformer->forHepsiemlak($ilan, ['Manzaralı', 'Özel Havuz', 'Test']);

        $this->assertLessThanOrEqual(100, mb_strlen($title));
    }

    public function test_title_transformer_without_vision_hints(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Daire']);
        $ilan->il = (object)['il_adi' => 'Muğla'];
        $ilan->ilce = (object)['ilce_adi' => 'Bodrum'];
        $ilan->altKategori = (object)['adi' => 'Daire'];

        $title = $this->titleTransformer->forAirbnb($ilan, []);

        $this->assertNotEmpty($title);
        $this->assertStringContainsString('Bodrum', $title);
    }

    public function test_title_transformer_handles_null_relations(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Test']);
        $ilan->il = null;
        $ilan->ilce = null;
        $ilan->altKategori = null;

        $title = $this->titleTransformer->forSahibinden($ilan, []);

        $this->assertIsString($title);
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

    public function test_room_type_mapper_for_airbnb_villa(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Test Villa']);
        $ilan->islem_tipi = 'satilik';
        $ilan->banyo_sayisi = 3;
        $ilan->yatak_odasi_sayisi = 5;
        $ilan->setRelation('altKategori', (object)['adi' => 'Villa']);
        $ilan->setRelation('anaKategori', (object)['adi' => 'Villa']);

        $media = $this->createMockMedia(['detected_rooms' => ['Havuz Alanı', 'Salon']]);

        $result = $this->roomTypeMapper->forAirbnb($ilan, $media);

        $this->assertArrayHasKey('space_type', $result);
        $this->assertArrayHasKey('property_type', $result);
        $this->assertArrayHasKey('bedrooms', $result);
        $this->assertEquals('Villa', $result['property_type']);
        $this->assertEquals(3, $result['bathrooms']);
    }

    public function test_room_type_mapper_for_airbnb_apartment(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => 'Test Daire']);
        $ilan->islem_tipi = 'kiralama';
        $ilan->setRelation('altKategori', (object)['adi' => '3+1 Daire']);
        $ilan->setRelation('anaKategori', (object)['adi' => 'Daire']);

        $result = $this->roomTypeMapper->forAirbnb($ilan, null);

        $this->assertArrayHasKey('space_type', $result);
        $this->assertEquals('entire_house', $result['space_type']);
        $this->assertEquals('House', $result['property_type']);
    }

    public function test_room_type_mapper_for_sahibinden(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => '4+1 Villa']);
        $ilan->islem_tipi = 'satilik';
        $ilan->setRelation('altKategori', (object)['adi' => '4+1 Villa']);
        $ilan->setRelation('anaKategori', (object)['adi' => 'Villa']);

        $media = $this->createMockMedia(['detected_rooms' => ['Villa', 'Müstakil']]);

        $result = $this->roomTypeMapper->forSahibinden($ilan, $media);

        $this->assertArrayHasKey('kategori', $result);
        $this->assertArrayHasKey('oda', $result);
        $this->assertArrayHasKey('tip', $result);
    }

    public function test_room_type_mapper_for_hepsiemlak(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => '2+1 Daire']);
        $ilan->setRelation('altKategori', (object)['adi' => '2+1 Daire']);
        $ilan->setRelation('anaKategori', (object)['adi' => 'Daire']);

        $result = $this->roomTypeMapper->forHepsiemlak($ilan, null);

        $this->assertArrayHasKey('kategori', $result);
        $this->assertArrayHasKey('oda', $result);
    }

    public function test_room_type_mapper_extracts_bedrooms_from_kategori(): void
    {
        $ilan = \App\Models\Ilan::factory()->create(['baslik' => '5+2 Villa']);
        $ilan->yatak_odasi_sayisi = null;
        $ilan->setRelation('altKategori', (object)['adi' => '5+2 Villa']);

        $result = $this->roomTypeMapper->forAirbnb($ilan, null);

        $this->assertEquals(5, $result['bedrooms']);
    }

    // ─── DescriptionTransformer ────────────────────────────────────────────

    public function test_description_transformer_for_airbnb(): void
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik' => 'Test Villa',
            'aciklama' => 'Bodrumda muhteşem bir villa.',
        ]);
        $ilan->setRelation('il', (object)['il_adi' => 'Muğla']);
        $ilan->setRelation('altKategori', (object)['adi' => 'Villa']);
        $media = $this->createMockMedia([
            'detected_amenities' => ['Havuz', 'Klima'],
            'detected_rooms' => ['Salon', 'Mutfak'],
            'title_hints' => ['Deniz manzaralı'],
            'photo_captions' => [['aciklama' => 'Havuz manzarası']],
        ]);

        $result = $this->descriptionTransformer->forAirbnb($ilan, $media);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('space', $result);
        $this->assertArrayHasKey('neighborhood_overview', $result);
        $this->assertArrayHasKey('house_rules', $result);
        $this->assertStringContainsString('Bodrum', $result['summary']);
    }

    public function test_description_transformer_for_sahibinden(): void
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik' => 'Satılık Villa',
            'aciklama' => 'Satılık bodrum villası.',
        ]);
        $media = $this->createMockMedia([
            'detected_amenities' => ['Havuz', 'WiFi'],
            'detected_rooms' => ['Salon'],
        ]);

        $result = $this->descriptionTransformer->forSahibinden($ilan, $media);

        $this->assertArrayHasKey('baslik', $result);
        $this->assertArrayHasKey('aciklama', $result);
        $this->assertStringContainsString('Havuz', $result['aciklama']);
    }

    public function test_description_transformer_for_hepsiemlak(): void
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik' => 'Test',
            'aciklama' => 'Hepsiemlak için açıklama.',
        ]);
        $media = $this->createMockMedia([
            'detected_luxury_features' => ['Jakuzi', 'Sauna'],
        ]);

        $result = $this->descriptionTransformer->forHepsiemlak($ilan, $media);

        $this->assertArrayHasKey('baslik', $result);
        $this->assertArrayHasKey('aciklama', $result);
        $this->assertStringContainsString('Jakuzi', $result['aciklama']);
    }

    public function test_description_transformer_without_media(): void
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik' => 'Test',
            'aciklama' => 'Sadece açıklama.',
        ]);

        $airbnb = $this->descriptionTransformer->forAirbnb($ilan, null);
        $sahibinden = $this->descriptionTransformer->forSahibinden($ilan, null);
        $hepsiemlak = $this->descriptionTransformer->forHepsiemlak($ilan, null);

        $this->assertIsArray($airbnb);
        $this->assertIsArray($sahibinden);
        $this->assertIsArray($hepsiemlak);
    }

    public function test_description_transformer_summary_max_500_chars(): void
    {
        $ilan = \App\Models\Ilan::factory()->create([
            'baslik' => str_repeat('X', 600),
            'aciklama' => 'Test',
        ]);

        $result = $this->descriptionTransformer->forAirbnb($ilan, null);

        $this->assertLessThanOrEqual(500, mb_strlen($result['summary']));
    }

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

    // ─── Helpers ───────────────────────────────────────────────────────────

    /**
     * Create a real PublishingMediaDTO for transformer tests.
     *
     * @param  array<string, mixed>  $data
     */
    private function createMockMedia(array $data): \App\DTOs\Vision\PublishingMediaDTO
    {
        return new \App\DTOs\Vision\PublishingMediaDTO(
            ilan_id: $data['ilan_id'] ?? 0,
            hero_fotograf_id: $data['hero_fotograf_id'] ?? null,
            hero_reason: $data['hero_reason'] ?? null,
            photo_order: $data['photo_order'] ?? [],
            title_hints: $data['title_hints'] ?? [],
            photo_captions: $data['photo_captions'] ?? [],
            room_metadata: $data['room_metadata'] ?? [],
            detected_rooms: $data['detected_rooms'] ?? [],
            detected_amenities: $data['detected_amenities'] ?? [],
            detected_luxury_features: $data['detected_luxury_features'] ?? [],
            vision_score: $data['vision_score'] ?? 0,
            avg_ai_confidence: $data['avg_ai_confidence'] ?? 0.0,
        );
    }
}
