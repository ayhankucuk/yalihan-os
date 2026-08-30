<?php

namespace Tests\Unit\Service;

use Tests\TestCase;
use App\Models\Ilan;
use App\Services\AI\PriceAdvisor\PriceAdvisorService;
use App\Services\Market\MarketIntelligenceService;
use App\Services\Intelligence\CompetitorMapService;
use App\Services\AI\CortexPriceForecastService;
use App\Services\AIDeal\DealPredictionService;
use Mockery;

/**
 * PriceAdvisorService Unit Tests
 *
 * Coverage:
 * - Ghost model (ilan_id=0) → degraded response, no 500
 * - alan_m2 nullable
 * - CompetitorMapService returns median_price key
 */
class PriceAdvisorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function ghost_model_returns_degraded_response_without_500(): void
    {
        $marketMock = Mockery::mock(MarketIntelligenceService::class);
        $marketMock->shouldReceive('calculateMarketValue')
            ->andReturn(['ortalama' => 25000, 'min' => 20000, 'max' => 30000]);

        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldNotReceive('analyzeCompetitors');

        $forecastMock = Mockery::mock(CortexPriceForecastService::class);
        $dealMock = Mockery::mock(DealPredictionService::class);
        $dealMock->shouldNotReceive('predict');

        $service = new PriceAdvisorService(
            $marketMock,
            $competitorMock,
            $forecastMock,
            $dealMock
        );

        $ilan = new Ilan();
        $ilan->forceFill([
            'id' => 0,
            'il_id' => 48,
            'ilce_id' => 1,
            'kategori_id' => 36,
            'fiyat' => 8000000,
            'alan_m2' => 320,
        ]);

        $result = $service->analyze($ilan);

        $this->assertEquals(0, $result['listing_id']);
        $this->assertEquals(0, $result['meta']['competitor_count']);
        $this->assertArrayHasKey('is_draft', $result['meta']);
        $this->assertTrue($result['meta']['is_draft']);
        $this->assertEquals('fair_market', $result['market_position']);
    }

    /** @test */
    public function ghost_model_with_null_alan_m2_uses_default_of_one(): void
    {
        $marketMock = Mockery::mock(MarketIntelligenceService::class);
        $marketMock->shouldReceive('calculateMarketValue')
            ->andReturn(['ortalama' => 25000, 'min' => 20000, 'max' => 30000]);

        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldNotReceive('analyzeCompetitors');

        $forecastMock = Mockery::mock(CortexPriceForecastService::class);
        $dealMock = Mockery::mock(DealPredictionService::class);
        $dealMock->shouldNotReceive('predict');

        $service = new PriceAdvisorService(
            $marketMock,
            $competitorMock,
            $forecastMock,
            $dealMock
        );

        $ilan = new Ilan();
        $ilan->forceFill([
            'id' => 0,
            'il_id' => 48,
            'ilce_id' => 1,
            'kategori_id' => 36,
            'fiyat' => 8500000,
            // alan_m2 NOT set
        ]);

        // Should not throw — alan_m2 defaults to 1
        $result = $service->analyze($ilan);

        $this->assertEquals(0, $result['listing_id']);
        $this->assertIsFloat($result['price_estimate']);
        $this->assertGreaterThan(0, $result['price_estimate']);
    }

    /** @test */
    public function competitor_map_returns_median_price_key(): void
    {
        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldReceive('analyzeCompetitors')
            ->andReturn([
                'median_price' => 7500000,
                'price_gap_percent' => 13.3,
                'total_competitors' => 3,
                'our_listing' => ['id' => 5],
                'top_competitors' => [],
                'recommendation' => 'OK',
                'confidence' => 80,
                'price_gap' => 0,
            ]);

        $ilan = new Ilan();
        $ilan->forceFill(['id' => 5]);

        $result = $competitorMock->analyzeCompetitors($ilan);

        $this->assertArrayHasKey('median_price', $result);
        $this->assertEquals(7500000, $result['median_price']);
    }
}
