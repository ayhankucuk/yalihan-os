<?php

namespace Tests\Feature\AI;

use App\Services\AI\AdvisorCommandCenterService;
use App\Services\AI\DealRadarService;
use App\Services\AI\OpportunityEngineService;
use App\Services\AI\PortfolioDoctorService;
use App\Services\AI\BuyerMatchQueueService;
use Tests\TestCase;
use App\Models\User;

class AdvisorCommandCenterTest extends TestCase
{
    /** @test */
    public function command_center_payload_contract_includes_all_top_level_keys()
    {
        $service = app(AdvisorCommandCenterService::class);
        $data = $service->getCommandCenterData();

        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('hot_deals', $data);
        $this->assertArrayHasKey('opportunities', $data);
        $this->assertArrayHasKey('portfolio_health', $data);
        $this->assertArrayHasKey('buyer_matches', $data);
        $this->assertArrayHasKey('priority_actions', $data);
    }

    /** @test */
    public function priority_actions_are_normalized_correctly()
    {
        // We will reflectively call the private method normalizeActionPriority to test
        $service = app(AdvisorCommandCenterService::class);
        $reflection = new \ReflectionClass(AdvisorCommandCenterService::class);
        $method = $reflection->getMethod('normalizeActionPriority');
        $method->setAccessible(true);

        $mockActions = [
            [
                'action_source' => 'deal_radar',
                'listing_id' => 1,
                'title' => 'Test',
                'action_label' => 'Do it',
                'reason' => 'Because',
                'raw_tier' => 'HOT_DEAL' // Should map to CRITICAL (4)
            ],
            [
                'action_source' => 'opportunity_engine',
                'listing_id' => 2,
                'title' => 'Test 2',
                'action_label' => 'Wait',
                'reason' => 'Because',
                'raw_tier' => 'LOW_VISIBILITY' // Should map to MEDIUM (2)
            ]
        ];

        $normalized = $method->invokeArgs($service, [$mockActions]);

        // It should sort descending by urgency level
        $this->assertEquals('CRITICAL', $normalized[0]['execution_priority']);
        $this->assertEquals(4, $normalized[0]['urgency_level']);

        $this->assertEquals('MEDIUM', $normalized[1]['execution_priority']);
        $this->assertEquals(2, $normalized[1]['urgency_level']);
    }

    /** @test */
    public function test_kpi_summary_generation()
    {
        $service = app(AdvisorCommandCenterService::class);
        $reflection = new \ReflectionClass(AdvisorCommandCenterService::class);
        $method = $reflection->getMethod('buildKpiSummary');
        $method->setAccessible(true);

        $mockModules = [
            'deal_radar' => [
                ['listing_id' => 1, 'listing_title' => 'Test', 'suggested_action' => 'Sell', 'primary_signal' => 'Signal', 'deal_tier' => 'HOT_DEAL'],
                ['listing_id' => 2, 'listing_title' => 'Test', 'suggested_action' => 'Sell', 'primary_signal' => 'Signal', 'deal_tier' => 'FAST_MOVING'],
                ['listing_id' => 3, 'listing_title' => 'Test', 'suggested_action' => 'Sell', 'primary_signal' => 'Signal', 'deal_tier' => 'WATCHLIST'],
            ],
            'opportunity_engine' => [
                ['listing_id' => 4, 'title' => 'Test', 'suggested_action' => 'Review', 'reason' => 'Reason', 'opportunity_type' => 'LOW_VISIBILITY'],
                ['listing_id' => 8, 'title' => 'Test 2', 'suggested_action' => 'Call', 'reason' => 'Reason', 'opportunity_type' => 'HIGH_DEMAND'],
            ],
            'portfolio_doctor' => [
                ['listing_id' => 5, 'listing_title' => 'Test', 'primary_problem' => 'OVERPRICED', 'suggested_actions' => ['action_type' => 'PRICE_DROP', 'description' => 'Drop by 5%']],
                ['listing_id' => 6, 'listing_title' => 'Test', 'primary_problem' => 'LOW_VISIBILITY', 'suggested_actions' => ['action_type' => 'BOOST', 'description' => 'Boost listing']]
            ],
            'buyer_match' => [
                ['listing_id' => 7, 'listing_title' => 'Test', 'buyer_name' => 'John', 'suggested_action' => 'Call', 'urgency_signal' => 'HIGH_INTENT', 'match_tier' => 'EXCELLENT'],
            ]
        ];

        $kpis = $method->invokeArgs($service, [$mockModules]);

        $this->assertEquals(2, $kpis['total_hot_deals']); // HOT + FAST
        $this->assertEquals(2, $kpis['total_opportunities']);
        $this->assertEquals(1, $kpis['critical_portfolio_issues']); // OVERPRICED
        $this->assertEquals(1, $kpis['high_intent_buyers']); // HIGH_INTENT

        // At least 2 critical/high actions generated from the above mocks due to mapping
        $this->assertTrue($kpis['today_priority_actions'] >= 2);
    }

    /** @test */
    public function it_has_a_valid_thin_controller_contract()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($user)->getJson(route('advisor.command-center.fetch'));
        $response->assertSuccessful();

        $htmlResponse = $this->actingAs($user)->get(route('advisor.command-center'));
        $htmlResponse->assertSuccessful();
    }
}
