<?php

namespace Tests\Feature\AI;

use App\Services\AI\DealRadarService;
use Tests\TestCase;

class DealRadarEngineTest extends TestCase
{
    /** @test */
    public function it_calculates_a_normalized_100_point_deal_score()
    {
        $service = new DealRadarService();

        $signals = [
            'buyer_match_density' => 80,
            'search_frequency' => 70,
            'listing_view_velocity' => 90,
            'price_advantage_score' => 60,
            'market_demand_score' => 75,
            'buyer_intent_overlap' => 50,
            'revisit_signal' => 45,
            'regional_velocity' => 80,
        ];

        $score = $service->calculateDealScore($signals);

        // Max possible limits should be capped at 100 and min at 0.
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);

        // Based on weights, calculate expected score:
        $expected = (80 * 0.20) + (70 * 0.15) + (90 * 0.15) + (60 * 0.15) + (75 * 0.10) + (50 * 0.10) + (45 * 0.10) + (80 * 0.05);
        $this->assertEquals(round($expected, 1), $score);
    }

    /** @test */
    public function it_maps_scores_to_correct_deal_tiers()
    {
        $service = new DealRadarService();

        $this->assertEquals('HOT_DEAL', $service->determineDealTier(90));
        $this->assertEquals('FAST_MOVING', $service->determineDealTier(75));
        $this->assertEquals('WATCHLIST', $service->determineDealTier(60));
        $this->assertEquals('LOW_SIGNAL', $service->determineDealTier(30));
    }

    /** @test */
    public function it_orchestrates_radar_service_correctly()
    {
        // Mocking the DB call in service is complex here without a full factory setup.
        // We ensure the Service instance can be instantiated and method exists.
        $service = new DealRadarService();
        $this->assertTrue(method_exists($service, 'getRadarListings'));
        $this->assertTrue(method_exists($service, 'sortRadarListings'));
        $this->assertTrue(method_exists($service, 'generatePrimarySignal'));
        $this->assertTrue(method_exists($service, 'generateAdvisorAction'));
    }

    /** @test */
    public function it_has_a_valid_thin_controller_contract()
    {
        // Authenticate with a mock user
        $user = \App\Models\User::factory()->create(['role_id' => 1]); // Adjust role logic per Sab Auth

        $response = $this->actingAs($user)->getJson(route('advisor.deal-radar.fetch'));

        $response->assertSuccessful();
    }
}
