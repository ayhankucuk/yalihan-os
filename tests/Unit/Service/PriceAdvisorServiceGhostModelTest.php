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
 * PriceAdvisorService Ghost Model Guard Unit Tests
 *
 * Verifies the ghost model bypass logic added to fix HTTP 500 on wizard draft data.
 * Key scenarios:
 *   1. id=0 → buildGhostModelResponse (no downstream service calls)
 *   2. alan_m2=null → defaults to 1 (price_estimate stays valid)
 *   3. CompetitorMapService returns median_price key (was missing → undefined → zero)
 */
class PriceAdvisorServiceGhostModelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function ghost_model_bypasses_downstream_services_returns_degraded_response(): void
    {
        $marketMock = Mockery::mock(MarketIntelligenceService::class);
        $marketMock->shouldReceive('calculateMarketValue')
            ->once()
            ->andReturn(['ortalama' => 25000, 'min' => 20000, 'max' => 30000]);

        // These must NEVER be called for ghost model
        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldNotReceive('analyzeCompetitors');

        $forecastMock = Mockery::mock(CortexPriceForecastService::class);
        $forecastMock->shouldNotReceive('forecast');

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
            'alan_m2' => 320,
        ]);

        $result = $service->analyze($ilan);

        $this->assertEquals(0, $result['listing_id']);
        $this->assertEquals(0, $result['meta']['competitor_count']);
        $this->assertTrue($result['meta']['is_draft']);
        $this->assertContains($result['market_position'], ['neutral', 'above_market', 'below_market', 'fair_market']);
        $this->assertNotEmpty($result['explanation']);
    }

    /** @test */
    public function ghost_model_with_null_alan_m2_does_not_crash(): void
    {
        $marketMock = Mockery::mock(MarketIntelligenceService::class);
        $marketMock->shouldReceive('calculateMarketValue')
            ->once()
            ->andReturn(['ortalama' => 25000, 'min' => 20000, 'max' => 30000]);

        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldNotReceive('analyzeCompetitors');

        $forecastMock = Mockery::mock(CortexPriceForecastService::class);
        $forecastMock->shouldNotReceive('forecast');

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
            // alan_m2 intentionally absent → forceFill skips it
        ]);

        // Must not throw DivisionByZeroError or any exception
        $result = $service->analyze($ilan);

        $this->assertEquals(0, $result['listing_id']);
        $this->assertIsFloat($result['price_estimate']);
        $this->assertGreaterThan(0, $result['price_estimate']);
    }

    /** @test */
    public function ghost_model_with_zero_fiyat_returns_valid_structure(): void
    {
        $marketMock = Mockery::mock(MarketIntelligenceService::class);
        $marketMock->shouldReceive('calculateMarketValue')
            ->andReturn(['ortalama' => 0, 'min' => 0, 'max' => 0]);

        $competitorMock = Mockery::mock(CompetitorMapService::class);
        $competitorMock->shouldNotReceive('analyzeCompetitors');

        $forecastMock = Mockery::mock(CortexPriceForecastService::class);
        $dealMock = Mockery::mock(DealPredictionService::class);

        $service = new PriceAdvisorService(
            $marketMock,
            $competitorMock,
            $forecastMock,
            $dealMock
        );

        $ilan = new Ilan();
        $ilan->forceFill(['id' => 0]);

        $result = $service->analyze($ilan);

        $this->assertEquals(0, $result['listing_id']);
        $this->assertIsFloat($result['price_estimate']);
        $this->assertEquals('neutral', $result['market_position']);
    }
}
