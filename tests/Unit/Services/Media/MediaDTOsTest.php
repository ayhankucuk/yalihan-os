<?php

namespace Tests\Unit\Services\Media;

use App\DTOs\Media\MediaAnalysisDTO;
use App\DTOs\Media\MediaPhotoDTO;
use App\DTOs\Media\MediaRoomDTO;
use App\DTOs\Media\MediaSummaryDTO;
use PHPUnit\Framework\TestCase;

/**
 * Media DTO Unit Tests — Sprint 6.3
 */
class MediaDTOsTest extends TestCase
{
    public function test_media_room_dto_to_array(): void
    {
        $dto = new MediaRoomDTO('pool', 'Havuz', 88, 2, [10, 11]);

        $arr = $dto->toArray();

        $this->assertEquals('pool', $arr['oda_turu']);
        $this->assertEquals('Havuz', $arr['label']);
        $this->assertEquals(88, $arr['guven_skoru']);
        $this->assertEquals(2, $arr['fotograf_sayisi']);
        $this->assertEquals([10, 11], $arr['fotograf_ids']);
    }

    public function test_media_photo_dto_to_array(): void
    {
        $dto = new MediaPhotoDTO(
            fotograf_id: 123,
            oda_turu: 'living_room',
            oda_guven_skoru: 85,
            kalite_puani: 91,
            hero_skoru: 82.5,
            kalite_ayrinti: ['sharpness' => 95, 'brightness' => 88, 'exposure' => 90],
        );

        $arr = $dto->toArray();

        $this->assertEquals(123, $arr['id']);
        $this->assertEquals('living_room', $arr['oda_turu']);
        $this->assertEquals(85, $arr['oda_guven_skoru']);
        $this->assertEquals(91, $arr['kalite_puani']);
        $this->assertEquals(82.5, $arr['hero_skoru']);
        $this->assertEquals(95, $arr['kalite_ayrinti']['sharpness']);
    }

    public function test_media_analysis_dto_health_labels(): void
    {
        $dto = fn($score) => new MediaAnalysisDTO(
            ilan_id: 1,
            toplam_fotograf: 10,
            media_health_score: $score,
            media_quality_score: 80,
            tamamlanma_oran: 0.8,
            oda_detaylari: [],
            eksik_odalar: [],
            hero_fotograf_id: 5,
            tum_fotograflar: [],
        );

        $this->assertEquals('EXCELLENT', $dto(85)->getHealthLabel());
        $this->assertEquals('EXCELLENT', $dto(95)->getHealthLabel());
        $this->assertEquals('GOOD', $dto(65)->getHealthLabel());
        $this->assertEquals('FAIR', $dto(45)->getHealthLabel());
        $this->assertEquals('POOR', $dto(25)->getHealthLabel());
        $this->assertEquals('MISSING', $dto(5)->getHealthLabel());
    }

    public function test_media_analysis_dto_to_array(): void
    {
        $dto = new MediaAnalysisDTO(
            ilan_id: 1,
            toplam_fotograf: 5,
            media_health_score: 72,
            media_quality_score: 85,
            tamamlanma_oran: 0.5,
            oda_detaylari: [],
            eksik_odalar: ['bathroom', 'kitchen'],
            hero_fotograf_id: 10,
            tum_fotograflar: [],
        );

        $arr = $dto->toArray();

        $this->assertEquals(1, $arr['ilan_id']);
        $this->assertEquals(72, $arr['media_health_score']);
        $this->assertEquals('GOOD', $arr['health']);
        $this->assertEquals(85, $arr['media_quality_score']);
        $this->assertEquals(0.5, $arr['tamamlanma_oran']);
        $this->assertEquals(['bathroom', 'kitchen'], $arr['eksik_odalar']);
        $this->assertEquals(10, $arr['hero_fotograf_id']);
    }

    public function test_media_summary_dto_empty(): void
    {
        $dto = MediaSummaryDTO::empty();

        $this->assertEquals('MISSING', $dto->health);
        $this->assertEquals(0, $dto->health_score);
        $this->assertEquals(0, $dto->quality_score);
        $this->assertEquals(0.0, $dto->coverage);
        $this->assertNull($dto->hero_image_url);
        $this->assertEmpty($dto->detected_rooms);
        $this->assertEmpty($dto->missing_rooms);
        $this->assertEquals(0, $dto->total_photos);
    }

    public function test_media_summary_dto_with_data(): void
    {
        $dto = new MediaSummaryDTO(
            health: 'GOOD',
            health_score: 75,
            quality_score: 88,
            coverage: 0.67,
            hero_image_url: 'https://cdn.example.com/hero.jpg',
            detected_rooms: [['oda_turu' => 'pool', 'label' => 'Havuz']],
            missing_rooms: ['bathroom', 'kitchen'],
            total_photos: 12,
        );

        $arr = $dto->toArray();

        $this->assertEquals('GOOD', $arr['health']);
        $this->assertEquals(75, $arr['health_score']);
        $this->assertEquals(88, $arr['quality_score']);
        $this->assertEquals(0.67, $arr['coverage']);
        $this->assertEquals('https://cdn.example.com/hero.jpg', $arr['hero_image_url']);
        $this->assertCount(1, $arr['detected_rooms']);
        $this->assertCount(2, $arr['missing_rooms']);
        $this->assertEquals(12, $arr['total_photos']);
    }
}
