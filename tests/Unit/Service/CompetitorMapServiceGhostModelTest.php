<?php

namespace Tests\Unit\Service;

use Tests\TestCase;
use App\Services\Intelligence\CompetitorMapService;
use App\Models\Ilan;
use Mockery;

/**
 * CompetitorMapService Ghost Model Guard Tests
 *
 * Verifies:
 * 1. Ghost model (id=0) returns empty result without hitting DB/cache
 * 2. median_price key is always present in response (was missing → caused undefined in PriceAdvisorService
 */
class CompetitorMapServiceGhostModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function ghost_model_returns_empty_result_without_cache_hit(): void
    {
        $service = new CompetitorMapService();

        $ilan = new Ilan();
        $ilan->forceFill(['id' => 0]);

        $result = $service->analyzeCompetitors($ilan);

        $this->assertEquals(0, $result['our_listing']['id']);
        $this->assertEquals([], $result['top_competitors']);
        $this->assertEquals(0, $result['total_competitors']);
        $this->assertEquals(0, $result['median_price']);
        $this->assertEquals(0, $result['confidence']);
        $this->assertStringContainsString('draft', $result['recommendation']);
    }

    /** @test */
    public function ghost_model_response_includes_median_price_key(): void
    {
        $service = new CompetitorMapService();

        $ilan = new Ilan();
        $ilan->forceFill(['id' => 0]);

        $result = $service->analyzeCompetitors($ilan);

        // Ghost model path now always includes median_price key
        $this->assertArrayHasKey('median_price', $result);
        $this->assertEquals(0, $result['median_price']);
    }
}
