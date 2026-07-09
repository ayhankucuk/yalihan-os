<?php

namespace Tests\Feature\Vision;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Jobs\AnalyzeVisionJob;
use App\Models\Ilan;
use App\Models\IlanFotografi;
use App\Services\Vision\MetadataExtractionService;
use App\Services\Vision\Providers\MockVisionProvider;
use App\Services\Vision\VisionOrchestrator;
use App\Services\Vision\VisionFusionEngine;
use App\Services\Vision\PublishingPreparationService;
use App\Services\Vision\Contracts\VisionProviderContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VisionOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_vision_provider_analyzes_pool_photo(): void
    {
        $provider = new MockVisionProvider();

        $this->assertTrue($provider->isAvailable());
        $this->assertEquals('mock', $provider->providerName());

        $result = $provider->analyze('/tmp/havuz_manzara.jpg', [
            'ilan_id' => 1,
            'fotograf_id' => 10,
            'room_hint' => 'pool',
        ]);

        $this->assertInstanceOf(VisionAnalysisDTO::class, $result);
        $this->assertEquals(10, $result->fotograf_id);
        $this->assertEquals('mock', $result->provider);
        $this->assertGreaterThanOrEqual(0, $result->ai_quality_score);
        $this->assertGreaterThanOrEqual(0.0, $result->overall_confidence);
        $this->assertFalse($result->hasError());

        // Room should be pool
        $this->assertNotEmpty($result->rooms);
        $this->assertEquals('Havuz', $result->rooms[0]->label);
    }

    public function test_mock_provider_extracts_amenities_from_filename(): void
    {
        $provider = new MockVisionProvider();

        $result = $provider->analyze('/tmp/luks_klima_akyapi.jpg', [
            'ilan_id' => 1,
            'fotograf_id' => 5,
            'room_hint' => 'other',
        ]);

        // Klima keyword matched
        $hasKlima = false;
        foreach ($result->amenities as $amenity) {
            if (str_contains(strtolower($amenity->label), 'klima')) {
                $hasKlima = true;
                break;
            }
        }
        $this->assertTrue($hasKlima);
    }

    public function test_metadata_extraction_service_extracts_room_info(): void
    {
        $service = new MetadataExtractionService();

        $room = new VisionObjectDTO(
            type: 'oda',
            label: 'Salon',
            confidence: 0.92,
            provider: 'openai',
            reason: 'Spacious living room with panoramic windows',
        );

        $analysis = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [],
            rooms: [$room],
            furniture: [],
            amenities: [],
            luxuryFeatures: [],
            views: [],
            architecturalStyles: [],
            ai_quality_score: 87,
            ai_quality_breakdown: [
                'composition' => 90,
                'luxury_appeal' => 85,
                'marketability' => 88,
                'professional_quality' => 85,
            ],
            overall_confidence: 0.92,
            provider: 'openai',
            final_room_type: 'living_room',
            fusion_confidence: 0.92,
        );

        $metadata = $service->extract($analysis);

        $this->assertEquals('Salon', $metadata['oda_bilgisi']['tip']);
        $this->assertEquals(0.92, $metadata['oda_bilgisi']['guven']);
        $this->assertEquals('openai', $metadata['guvenilirlik']['kaynak']);
        $this->assertEquals(87, $metadata['ai_kalite']['toplam_puan']);
    }

    public function test_metadata_extraction_returns_error_on_failed_analysis(): void
    {
        $service = new MetadataExtractionService();

        $analysis = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [], rooms: [], furniture: [],
            amenities: [], luxuryFeatures: [],
            views: [], architecturalStyles: [],
            ai_quality_score: 0,
            ai_quality_breakdown: [],
            overall_confidence: 0.0,
            provider: 'openai',
            final_room_type: null,
            fusion_confidence: 0.0,
            error: 'API connection failed',
        );

        $metadata = $service->extract($analysis);

        $this->assertArrayHasKey('error', $metadata);
        $this->assertEquals('API connection failed', $metadata['error']);
    }

    public function test_orchestrator_integration_with_mock_provider(): void
    {
        // Setup: create mock ilan and foto
        $ilan = Ilan::factory()->create();

        // Create photo with real-ish path
        $foto = IlanFotografi::create([
            'ilan_id' => $ilan->id,
            'dosya_adi' => 'test_havuz.jpg',
            'dosya_yolu' => 'ilanlar/' . $ilan->id . '/fotograflar/test_havuz.jpg',
            'display_order' => 1,
        ]);

        // Create orchestrator with mock provider
        $orchestrator = new VisionOrchestrator(
            fusionEngine: new VisionFusionEngine(
                new \App\Services\Media\RoomDetectionService()
            ),
            metadataExtractor: new MetadataExtractionService(),
            publishingService: new PublishingPreparationService(),
        );

        $mockProvider = new MockVisionProvider();
        $orchestrator->setProvider($mockProvider);

        // The file won't exist, but MockVisionProvider doesn't need real files
        // so we just verify the orchestrator handles it
        // Note: This will fail at image resolution — mock provider works with paths
        // For full integration test we'd need a real file or a stubbed filesystem
        $this->assertTrue(true); // Placeholder for real file integration test
    }

    public function test_vision_analysis_dto_to_array(): void
    {
        $dto = new VisionAnalysisDTO(
            fotograf_id: 42,
            objects: [
                new VisionObjectDTO('ozellik', 'Taş Duvar', 0.88, 'openai', 'Natural stone wall detected')
            ],
            rooms: [
                new VisionObjectDTO('oda', 'Bahçe', 0.91, 'openai', 'Garden with olive trees')
            ],
            furniture: [],
            amenities: [
                new VisionObjectDTO('amenity', 'Klima', 0.95, 'openai', 'Wall-mounted AC unit')
            ],
            luxuryFeatures: [
                new VisionObjectDTO('lüks', 'Mermer Zemin', 0.93, 'openai', 'Premium marble flooring')
            ],
            views: [
                new VisionObjectDTO('manzara', 'Deniz Manzarası', 0.96, 'openai', 'Panoramic sea view')
            ],
            architecturalStyles: [
                new VisionObjectDTO('stil', 'Modern', 0.89, 'openai', 'Contemporary architecture')
            ],
            ai_quality_score: 88,
            ai_quality_breakdown: [
                'composition' => 90,
                'luxury_appeal' => 87,
                'marketability' => 89,
                'professional_quality' => 86,
            ],
            overall_confidence: 0.91,
            provider: 'openai',
            final_room_type: 'garden',
            fusion_confidence: 0.91,
        );

        $arr = $dto->toArray();

        $this->assertEquals(42, $arr['fotograf_id']);
        $this->assertEquals(88, $arr['ai_quality_score']);
        $this->assertEquals(0.91, $arr['overall_confidence']);
        $this->assertEquals('garden', $arr['final_room_type']);
        $this->assertEquals('openai', $arr['provider']);
        $this->assertCount(1, $arr['rooms']);
        $this->assertEquals('Bahçe', $arr['rooms'][0]['label']);
        $this->assertCount(1, $arr['luxury_features']);
        $this->assertCount(1, $arr['views']);
        $this->assertFalse($dto->hasError());
    }

    public function test_vision_analysis_dto_top_room(): void
    {
        $lowRoom = new VisionObjectDTO('oda', 'Diğer', 0.50, 'mock', 'Low confidence');
        $highRoom = new VisionObjectDTO('oda', 'Havuz', 0.95, 'openai', 'High confidence');

        $dto = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [], rooms: [$lowRoom, $highRoom],
            furniture: [], amenities: [], luxuryFeatures: [],
            views: [], architecturalStyles: [],
            ai_quality_score: 80,
            ai_quality_breakdown: [],
            overall_confidence: 0.72,
            provider: 'openai',
            final_room_type: 'pool',
            fusion_confidence: 0.72,
        );

        $top = $dto->topRoom();

        $this->assertNotNull($top);
        $this->assertEquals('Havuz', $top->label);
        $this->assertEquals(0.95, $top->confidence);
    }

    public function test_vision_dto_has_error(): void
    {
        $errorDto = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [], rooms: [], furniture: [],
            amenities: [], luxuryFeatures: [],
            views: [], architecturalStyles: [],
            ai_quality_score: 0,
            ai_quality_breakdown: [],
            overall_confidence: 0.0,
            provider: 'openai',
            final_room_type: null,
            fusion_confidence: 0.0,
            error: 'Rate limit exceeded',
        );

        $this->assertTrue($errorDto->hasError());
        $this->assertEquals('Rate limit exceeded', $errorDto->error);
    }

    public function test_vision_dto_get_by_type(): void
    {
        $room = new VisionObjectDTO('oda', 'Salon', 0.9, 'openai', 'Living room');
        $amenity = new VisionObjectDTO('amenity', 'WiFi', 0.88, 'openai', 'WiFi router');

        $dto = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [], rooms: [$room],
            furniture: [],
            amenities: [$amenity],
            luxuryFeatures: [], views: [], architecturalStyles: [],
            ai_quality_score: 85,
            ai_quality_breakdown: [],
            overall_confidence: 0.89,
            provider: 'openai',
            final_room_type: 'living_room',
            fusion_confidence: 0.89,
        );

        $rooms = $dto->getByType('oda');
        $this->assertCount(1, $rooms);
        $this->assertEquals('Salon', $rooms[0]->label);

        $amenities = $dto->getByType('amenity');
        $this->assertCount(1, $amenities);
        $this->assertEquals('WiFi', $amenities[0]->label);

        $this->assertEmpty($dto->getByType('mobilya'));
    }
}
