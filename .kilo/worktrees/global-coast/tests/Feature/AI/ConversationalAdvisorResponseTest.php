<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * ConversationalAdvisorResponseTest
 *
 * Verifies that all advisor controllers (Advisor Panel, Public Web)
 * return the same unified response schema as per SAB standards.
 */
class ConversationalAdvisorResponseTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);

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
    }

    /**
     * Test Advisor Panel response structure
     */
    public function test_advisor_panel_query_returns_unified_schema(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('advisor.conversational.query'), [
            'query' => 'Bodrum Bitez 500m2 arsa fiyatı'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_success',
                'intent_detected',
                'entities_parsed',
                'advisor_response',
                'data_payload',
                'source_engines'
            ]);
    }

    /**
     * Test Public Web response structure
     */
    public function test_public_web_query_returns_unified_schema(): void
    {
        $response = $this->postJson(route('public.conversational.query'), [
            'query' => 'Bodrum Bitez 500m2 arsa fiyatı'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_success',
                'intent_detected',
                'entities_parsed',
                'advisor_response',
                'data_payload',
                'source_engines'
            ]);
    }
}
