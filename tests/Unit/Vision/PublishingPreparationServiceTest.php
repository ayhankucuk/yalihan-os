<?php

namespace Tests\Unit\Vision;

use App\DTOs\Vision\PublishingMediaDTO;
use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Models\Ilan;
use App\Services\Vision\Providers\MockVisionProvider;
use App\Services\Vision\PublishingPreparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishingPreparationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_returns_empty_result_when_no_photos(): void
    {
        $ilan = new Ilan(['id' => 999]);
        $ilan->id = 999;

        $service = new PublishingPreparationService();
        $result = $service->prepare($ilan, []);

        $this->assertEquals(999, $result->ilan_id);
        $this->assertNull($result->hero_fotograf_id);
        $this->assertFalse($result->is_publishing_ready);
        $this->assertContains('Fotoğraf bulunamadı', $result->readiness_issues);
    }

    public function test_publishing_media_dto_to_array(): void
    {
        $dto = new PublishingMediaDTO(
            ilan_id: 1,
            hero_fotograf_id: 10,
            hero_reason: 'Best quality',
            photo_order: [10, 2, 3],
            title_hints: ['Manzaralı', 'Lüks'],
            photo_captions: [10 => ['baslik' => 'Havuz', 'aciklama' => 'Test']],
            room_metadata: [10 => ['odalar' => []]],
            is_publishing_ready: true,
            readiness_issues: [],
            detected_rooms: ['Havuz' => 2, 'Salon' => 1],
            detected_amenities: ['Klima', 'WiFi'],
            detected_luxury_features: ['Mermer'],
            vision_score: 85,
            avg_ai_confidence: 0.87,
        );

        $arr = $dto->toArray();

        $this->assertEquals(1, $arr['ilan_id']);
        $this->assertEquals(10, $arr['hero_fotograf_id']);
        $this->assertEquals([10, 2, 3], $arr['photo_order']);
        $this->assertTrue($arr['is_publishing_ready']);
        $this->assertEquals(85, $arr['vision_score']);
        $this->assertEquals(0.87, $arr['avg_ai_confidence']);
        $this->assertEquals(['Manzaralı', 'Lüks'], $arr['title_hints']);
    }

    public function test_publishing_dto_for_photo(): void
    {
        $dto = new PublishingMediaDTO(
            ilan_id: 1,
            hero_fotograf_id: 5,
            hero_reason: 'Best',
            photo_order: [5, 3],
            photo_captions: [5 => ['baslik' => 'Havuz', 'aciklama' => 'Desc']],
            room_metadata: [5 => ['odalar' => []]],
            is_publishing_ready: true,
            vision_score: 80,
            avg_ai_confidence: 0.85,
        );

        $info = $dto->forPhoto(5);

        $this->assertTrue($info['is_hero']);
        $this->assertEquals(['baslik' => 'Havuz', 'aciklama' => 'Desc'], $info['caption']);
    }
}
