<?php

namespace Tests\Unit\Vision;

use App\DTOs\Vision\VisionAnalysisDTO;
use App\DTOs\Vision\VisionObjectDTO;
use App\Models\IlanFotografi;
use App\Services\Media\RoomDetectionService;
use App\Services\Vision\VisionFusionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisionFusionEngineTest extends TestCase
{
    use RefreshDatabase;

    private VisionFusionEngine $engine;
    private RoomDetectionService $ruleEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ruleEngine = new RoomDetectionService();
        $this->engine = new VisionFusionEngine($this->ruleEngine);
    }

    public function test_fuse_returns_rule_result_when_ai_is_null(): void
    {
        $foto = $this->makeFakeFotograf(['dosya_adi' => 'havuz_1.jpg']);

        $result = $this->engine->fuse($foto, null);

        $this->assertEquals('pool', $result['oda_turu']);
        $this->assertEquals('Havuz', $result['label']);
        $this->assertEquals(0.0, $result['ai_confidence']);
        $this->assertEquals('rule', $result['provider']);
        $this->assertFalse($result['fused']);
    }

    public function test_fuse_returns_rule_result_when_ai_has_error(): void
    {
        $foto = $this->makeFakeFotograf(['dosya_adi' => 'salon.jpg']);

        $aiResult = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [],
            rooms: [],
            furniture: [],
            amenities: [],
            luxuryFeatures: [],
            views: [],
            architecturalStyles: [],
            ai_quality_score: 0,
            ai_quality_breakdown: [],
            overall_confidence: 0.0,
            provider: 'openai',
            final_room_type: null,
            fusion_confidence: 0.0,
            error: 'API timeout',
        );

        $result = $this->engine->fuse($foto, $aiResult);

        $this->assertEquals('rule', $result['provider']);
        $this->assertFalse($result['fused']);
    }

    public function test_fuse_prefers_ai_when_high_confidence(): void
    {
        $foto = $this->makeFakeFotograf(['dosya_adi' => 'generic.jpg']);

        $aiRoom = new VisionObjectDTO(
            type: 'oda',
            label: 'Mutfak',
            confidence: 0.92,
            provider: 'openai',
            reason: 'OpenAI: Modern kitchen with island',
        );

        $aiResult = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [],
            rooms: [$aiRoom],
            furniture: [],
            amenities: [],
            luxuryFeatures: [],
            views: [],
            architecturalStyles: [],
            ai_quality_score: 85,
            ai_quality_breakdown: ['composition' => 85],
            overall_confidence: 0.92,
            provider: 'openai',
            final_room_type: 'kitchen',
            fusion_confidence: 0.92,
        );

        $result = $this->engine->fuse($foto, $aiResult);

        $this->assertEquals('kitchen', $result['oda_turu']);
        $this->assertEquals('Mutfak', $result['label']);
        $this->assertGreaterThanOrEqual(0.85, $result['ai_confidence']);
        $this->assertTrue($result['fused'] ?? false);
    }

    public function test_fuse_batch_processes_multiple_photos(): void
    {
        $foto1 = $this->makeFakeFotograf(['id' => 1, 'dosya_adi' => 'havuz_1.jpg']);
        $foto2 = $this->makeFakeFotograf(['id' => 2, 'dosya_adi' => 'salon_genel.jpg']);

        $aiResult1 = new VisionAnalysisDTO(
            fotograf_id: 1,
            objects: [], rooms: [], furniture: [], amenities: [],
            luxuryFeatures: [], views: [], architecturalStyles: [],
            ai_quality_score: 80, ai_quality_breakdown: [],
            overall_confidence: 0.80, provider: 'openai',
            final_room_type: 'pool', fusion_confidence: 0.80,
        );

        $results = $this->engine->fuseBatch([$foto1, $foto2], [
            1 => $aiResult1,
            2 => null,
        ]);

        $this->assertCount(2, $results);
        $this->assertEquals('pool', $results[1]['oda_turu']);
        $this->assertEquals('Salon', $results[2]['label']);
    }

    private function makeFakeFotograf(array $attrs = []): IlanFotografi
    {
        $defaults = [
            'id' => $attrs['id'] ?? 1,
            'ilan_id' => 1,
            'dosya_adi' => 'test.jpg',
            'dosya_yolu' => '/photos/test.jpg',
        ];

        $foto = new IlanFotografi(array_merge($defaults, $attrs));
        $foto->id = $defaults['id'];

        return $foto;
    }
}
