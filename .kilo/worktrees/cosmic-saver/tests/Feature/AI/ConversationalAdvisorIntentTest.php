<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Services\AI\ConversationalAdvisorService;

/**
 * ConversationalAdvisorIntentTest
 *
 * Verifies intent parsing and entity extraction for all 8 intents plus fallback.
 * Does NOT call real AI engines — focuses on the orchestration layer logic.
 */
class ConversationalAdvisorIntentTest extends TestCase
{

    private ConversationalAdvisorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all engine dependencies to avoid real DB calls and ensure stable test output
        $this->mock(\App\Services\AI\MarketValuationService::class, function ($mock) {
            $mock->shouldReceive('evaluateQuery')->andReturn(['is_success' => true, 'data' => ['estimated_value' => 1000000, 'confidence_score' => 85]]);
        });

        $this->mock(\App\Services\Market\MarketIntelligenceService::class, function ($mock) {
            $mock->shouldReceive('calculateMarketValue')->andReturn(['trend' => 'up']);
        });

        $this->mock(\App\Services\AI\DealRadarService::class, function ($mock) {
            $mock->shouldReceive('getRadarListings')->andReturn(['listings' => [['id' => 1]]]);
        });

        $this->mock(\App\Services\AI\SellerStrategyService::class, function ($mock) {
            $mock->shouldReceive('generateSellerStrategy')->andReturn(['advisor_recommendation' => 'Mock recommendation']);
        });

        $this->mock(\App\Services\AI\PortfolioDoctorService::class, function ($mock) {
            $mock->shouldReceive('analyzePortfolio')->andReturn(['summary' => ['total_listings_analyzed' => 10]]);
        });

        $this->mock(\App\Services\AI\BuyerMatchQueueService::class, function ($mock) {
            $mock->shouldReceive('getMatchesForQueue')->andReturn(['matches' => [['id' => 1]]]);
        });

        $this->mock(\App\Services\AI\OwnerDiscoveryService::class, function ($mock) {
            $mock->shouldReceive('generateOwnerOpportunityList')->andReturn(collect([['id' => 1]]));
        });

        $this->mock(\App\Services\AI\OpportunityEngineService::class);

        // Bind the service with mocked engine dependencies
        $this->service = $this->app->make(ConversationalAdvisorService::class);
    }

    // ────────────────────────────────────────────────────────────
    // INTENT DETECTION TESTS
    // ────────────────────────────────────────────────────────────

    public function test_detects_market_valuation_intent(): void
    {
        $intent = $this->service->parseIntent('Bodrum Bitez 500m2 arsa kaç para eder?');
        $this->assertEquals('MARKET_VALUATION', $intent);
    }

    public function test_detects_market_intelligence_intent(): void
    {
        $intent = $this->service->parseIntent('Çeşme bölgesinde piyasa trendi nasıl?');
        $this->assertEquals('MARKET_INTELLIGENCE', $intent);
    }

    public function test_detects_investment_opportunity_intent(): void
    {
        $intent = $this->service->parseIntent('Bodrum\'da iyi bir yatırım fırsatı var mı?');
        $this->assertEquals('INVESTMENT_OPPORTUNITY', $intent);
    }

    public function test_detects_seller_pricing_intent(): void
    {
        $intent = $this->service->parseIntent('Evimi kaçtan satmalıyım?');
        $this->assertEquals('SELLER_PRICING', $intent);
    }

    public function test_detects_listing_diagnostic_intent(): void
    {
        $intent = $this->service->parseIntent('Bu ilan neden satılmıyor?');
        $this->assertEquals('LISTING_DIAGNOSTIC', $intent);
    }

    public function test_detects_owner_acquisition_intent(): void
    {
        $intent = $this->service->parseIntent('Bu bölgedeki portföy sahibi hedefler kimler?');
        $this->assertEquals('OWNER_ACQUISITION', $intent);
    }

    public function test_detects_buyer_match_intent(): void
    {
        $intent = $this->service->parseIntent('Bu ilana uygun alıcı var mı?');
        $this->assertEquals('BUYER_MATCH', $intent);
    }

    public function test_detects_portfolio_health_intent(): void
    {
        $intent = $this->service->parseIntent('Portföy analizi yap, portföy kalitesi nasıl?');
        $this->assertEquals('PORTFOLIO_HEALTH', $intent);
    }

    public function test_detects_unknown_intent_for_unrelated_query(): void
    {
        $intent = $this->service->parseIntent('Merhaba nasılsın?');
        $this->assertEquals('UNKNOWN', $intent);
    }

    // ────────────────────────────────────────────────────────────
    // ENTITY EXTRACTION TESTS
    // ────────────────────────────────────────────────────────────

    public function test_extracts_m2_from_query(): void
    {
        $entities = $this->service->extractEntities('Bodrum Bitez 500m2 arsa kaç para eder?');
        $this->assertEquals(500, $entities['m2_brut']);
    }

    public function test_converts_donum_to_m2(): void
    {
        $entities = $this->service->extractEntities('1 dönüm tarla fiyatı nedir?');
        $this->assertEquals(1000, $entities['m2_brut']);
        $this->assertEquals(1.0, $entities['area_donum']);
    }

    public function test_extracts_neighborhood(): void
    {
        $entities = $this->service->extractEntities('Yalıkavak\'ta villa fiyatları?');
        $this->assertEquals('Yalıkavak', $entities['location_mahalle']);
        $this->assertEquals('Bodrum', $entities['location_ilce']);
    }

    public function test_extracts_asset_type(): void
    {
        $entities = $this->service->extractEntities('Bodrum arsa fiyatı nedir?');
        $this->assertEquals('arsa', $entities['asset_type']);
    }

    // ────────────────────────────────────────────────────────────
    // PROCESS QUERY CONTRACT TESTS
    // ────────────────────────────────────────────────────────────

    public function test_process_query_returns_correct_envelope(): void
    {
        $result = $this->service->processQuery('Merhaba naber?');

        $this->assertArrayHasKey('is_success', $result);
        $this->assertArrayHasKey('intent_detected', $result);
        $this->assertArrayHasKey('entities_parsed', $result);
        $this->assertArrayHasKey('advisor_response', $result);
        $this->assertArrayHasKey('data_payload', $result);
        $this->assertArrayHasKey('source_engines', $result);
    }

    public function test_unknown_intent_returns_graceful_response(): void
    {
        $result = $this->service->processQuery('asdfghjkl');

        $this->assertTrue($result['is_success']);
        $this->assertEquals('UNKNOWN', $result['intent_detected']);
        $this->assertNotEmpty($result['advisor_response']);
    }

    public function test_market_valuation_intent_returns_correct_intent(): void
    {
        $result = $this->service->processQuery('Bodrum 500m2 arsa kaç para eder?');

        $this->assertEquals('MARKET_VALUATION', $result['intent_detected']);
        $this->assertContains('market_valuation', $result['source_engines']);
    }

    public function test_investment_opportunity_routes_to_deal_radar(): void
    {
        $result = $this->service->processQuery('Bodrum\'da yatırım fırsatı var mı?');

        $this->assertEquals('INVESTMENT_OPPORTUNITY', $result['intent_detected']);
        $this->assertContains('deal_radar', $result['source_engines']);
    }

    public function test_portfolio_health_routes_to_portfolio_doctor(): void
    {
        $result = $this->service->processQuery('Portföy sağlığı ve kalitesi nasıl?');

        $this->assertEquals('PORTFOLIO_HEALTH', $result['intent_detected']);
        $this->assertContains('portfolio_doctor', $result['source_engines']);
    }
}
