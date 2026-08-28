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

    /**
     * @test
     * @group contract
     *
     * Full JSON response contract for /command-center/fetch
     * Validates every key, type, and enum value returned by the endpoint.
     */
    public function json_response_contract_matches_specification()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($user)->getJson(route('advisor.command-center.fetch'));
        $response->assertSuccessful();

        $json = $response->json();

        // --- Envelope ---
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);

        $data = $json['data'];

        // --- Top-level keys ---
        $expectedTopKeys = [
            'kpis',
            'hot_deals',
            'opportunities',
            'portfolio_health',
            'buyer_matches',
            'priority_actions',
        ];
        foreach ($expectedTopKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing top-level key: $key");
        }

        // =========================================================
        // KPIs
        // =========================================================
        $kpis = $data['kpis'];
        $this->assertIsArray($kpis);

        foreach (['total_hot_deals', 'total_opportunities', 'critical_portfolio_issues', 'high_intent_buyers', 'today_priority_actions'] as $kpiKey) {
            $this->assertArrayHasKey($kpiKey, $kpis, "Missing KPI key: $kpiKey");
            $this->assertIsInt($kpis[$kpiKey], "KPI $kpiKey must be integer, got " . gettype($kpis[$kpiKey]));
        }

        // =========================================================
        // hot_deals
        // =========================================================
        $this->assertIsArray($data['hot_deals']);
        if (!empty($data['hot_deals'])) {
            $deal = $data['hot_deals'][0];
            $this->assertArrayHasKey('listing_id', $deal);
            $this->assertArrayHasKey('listing_title', $deal);
            $this->assertArrayHasKey('price', $deal);
            $this->assertArrayHasKey('location', $deal);
            $this->assertArrayHasKey('deal_score', $deal);
            $this->assertArrayHasKey('deal_tier', $deal);
            $this->assertArrayHasKey('primary_signal', $deal);
            $this->assertArrayHasKey('signal_breakdown', $deal);
            $this->assertArrayHasKey('suggested_action', $deal);

            // deal_score: float 0.0–100.0, 1 decimal
            $this->assertIsNumeric($deal['deal_score']);
            $this->assertThat($deal['deal_score'], $this->logicalAnd(
                $this->greaterThanOrEqual(0.0),
                $this->lessThanOrEqual(100.0)
            ));

            // deal_tier: enum
            $this->assertContains($deal['deal_tier'], ['HOT_DEAL', 'FAST_MOVING', 'WATCHLIST', 'LOW_SIGNAL']);

            // signal_breakdown: all sub-keys present and integer 0–100
            $sbKeys = [
                'buyer_match_density',
                'search_frequency',
                'listing_view_velocity',
                'price_advantage_score',
                'market_demand_score',
                'buyer_intent_overlap',
                'revisit_signal',
                'regional_velocity',
            ];
            foreach ($sbKeys as $sbKey) {
                $this->assertArrayHasKey($sbKey, $deal['signal_breakdown'], "signal_breakdown missing: $sbKey");
                $val = $deal['signal_breakdown'][$sbKey];
                $this->assertIsInt($val, "signal_breakdown.$sbKey must be int, got " . gettype($val));
                $this->assertThat($val, $this->logicalAnd(
                    $this->greaterThanOrEqual(0),
                    $this->lessThanOrEqual(100)
                ), "signal_breakdown.$sbKey must be 0–100, got $val");
            }
        }

        // =========================================================
        // opportunities
        // =========================================================
        $this->assertIsArray($data['opportunities']);
        if (!empty($data['opportunities'])) {
            $opp = $data['opportunities'][0];
            $this->assertArrayHasKey('id', $opp);
            $this->assertArrayHasKey('listing_id', $opp);
            $this->assertArrayHasKey('title', $opp);
            $this->assertArrayHasKey('price', $opp);
            $this->assertArrayHasKey('opportunity_score', $opp);
            $this->assertArrayHasKey('opportunity_type', $opp);
            $this->assertArrayHasKey('reason', $opp);
            $this->assertArrayHasKey('suggested_action', $opp);

            // opportunity_type: enum
            $validOppTypes = [
                'UNDERPRICED_LISTING',
                'HIGH_BUYER_MATCH',
                'SEO_OPTIMIZATION',
                'LOW_QUALITY_HIGH_POTENTIAL',
                'STALE_LISTING_RECOVERY',
            ];
            $this->assertContains($opp['opportunity_type'], $validOppTypes);

            // opportunity_score: integer 0–100
            $this->assertIsInt($opp['opportunity_score']);
            $this->assertThat($opp['opportunity_score'], $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(100)
            ));
        }

        // =========================================================
        // portfolio_health
        // =========================================================
        $this->assertIsArray($data['portfolio_health']);
        if (!empty($data['portfolio_health'])) {
            $ph = $data['portfolio_health'][0];
            $this->assertArrayHasKey('listing_id', $ph);
            $this->assertArrayHasKey('listing_title', $ph);
            $this->assertArrayHasKey('price', $ph);
            $this->assertArrayHasKey('listing_health_score', $ph);
            $this->assertArrayHasKey('primary_problem', $ph);
            $this->assertArrayHasKey('problem_signals', $ph);
            $this->assertArrayHasKey('suggested_actions', $ph);
            $this->assertArrayHasKey('optimization_priority', $ph);

            // listing_health_score: float 0.0–100.0
            $this->assertIsNumeric($ph['listing_health_score']);
            $this->assertThat($ph['listing_health_score'], $this->logicalAnd(
                $this->greaterThanOrEqual(0.0),
                $this->lessThanOrEqual(100.0)
            ));

            // primary_problem: enum
            $validProblems = [
                'HEALTHY',
                'STALE_LISTING',
                'OVERPRICED',
                'HIGH_DEMAND_LOW_CONVERSION',
                'NO_BUYER_MATCH',
                'LOW_DEMAND_AREA',
                'LOW_VISIBILITY',
                'LOW_IMAGE_QUALITY',
                'GENERAL_OPTIMIZATION_NEEDED',
            ];
            $this->assertContains($ph['primary_problem'], $validProblems);

            // problem_signals sub-keys
            $psKeys = [
                'listing_view_velocity',
                'buyer_match_density',
                'inquiry_conversion_rate',
                'price_position_index',
                'seo_visibility_score',
                'image_quality_score',
                'listing_age_days',
                'regional_demand_score',
                'revisit_signal',
            ];
            foreach ($psKeys as $psKey) {
                $this->assertArrayHasKey($psKey, $ph['problem_signals'], "problem_signals missing: $psKey");
                $val = $ph['problem_signals'][$psKey];
                $this->assertIsInt($val, "problem_signals.$psKey must be int, got " . gettype($val));
            }

            // suggested_actions: object with action_type, description, impact
            $sa = $ph['suggested_actions'];
            $this->assertArrayHasKey('action_type', $sa);
            $this->assertArrayHasKey('description', $sa);
            $this->assertArrayHasKey('impact', $sa);
            $this->assertContains($sa['impact'], ['HIGH', 'MEDIUM', 'LOW', 'CRITICAL']);

            // optimization_priority: float
            $this->assertIsNumeric($ph['optimization_priority']);
        }

        // =========================================================
        // buyer_matches
        // =========================================================
        $this->assertIsArray($data['buyer_matches']);
        if (!empty($data['buyer_matches'])) {
            $bm = $data['buyer_matches'][0];
            $this->assertArrayHasKey('buyer_id', $bm);
            $this->assertArrayHasKey('buyer_name', $bm);
            $this->assertArrayHasKey('buyer_phone', $bm);
            $this->assertArrayHasKey('match_score', $bm);
            $this->assertArrayHasKey('match_tier', $bm);
            $this->assertArrayHasKey('primary_reason', $bm);
            $this->assertArrayHasKey('match_reasons', $bm);
            $this->assertArrayHasKey('urgency_signal', $bm);
            $this->assertArrayHasKey('suggested_action', $bm);
            $this->assertArrayHasKey('contact_priority', $bm);
            $this->assertArrayHasKey('listing_id', $bm);
            $this->assertArrayHasKey('listing_title', $bm);

            // match_score: float 0.0–100.0
            $this->assertIsNumeric($bm['match_score']);
            $this->assertThat($bm['match_score'], $this->logicalAnd(
                $this->greaterThanOrEqual(0.0),
                $this->lessThanOrEqual(100.0)
            ));

            // match_tier: enum
            $this->assertContains($bm['match_tier'], ['HOT', 'WARM', 'WATCH', 'LOW']);

            // urgency_signal: enum
            $this->assertContains($bm['urgency_signal'], ['AT_RISK', 'HIGH_INTENT', 'ACTIVE_SEARCH', 'PASSIVE']);

            // match_reasons: array of strings
            $this->assertIsArray($bm['match_reasons']);

            // contact_priority: integer 1–7
            $this->assertIsInt($bm['contact_priority']);
            $this->assertThat($bm['contact_priority'], $this->logicalAnd(
                $this->greaterThanOrEqual(1),
                $this->lessThanOrEqual(7)
            ));
        }

        // =========================================================
        // priority_actions
        // =========================================================
        $this->assertIsArray($data['priority_actions']);
        if (!empty($data['priority_actions'])) {
            $pa = $data['priority_actions'][0];
            $this->assertArrayHasKey('action_source', $pa);
            $this->assertArrayHasKey('listing_id', $pa);
            $this->assertArrayHasKey('title', $pa);
            $this->assertArrayHasKey('action_label', $pa);
            $this->assertArrayHasKey('urgency_level', $pa);
            $this->assertArrayHasKey('execution_priority', $pa);
            $this->assertArrayHasKey('reason', $pa);

            // urgency_level: integer 1–4
            $this->assertIsInt($pa['urgency_level']);
            $this->assertThat($pa['urgency_level'], $this->logicalAnd(
                $this->greaterThanOrEqual(1),
                $this->lessThanOrEqual(4)
            ));

            // execution_priority: enum
            $this->assertContains($pa['execution_priority'], ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW']);

            // action_source: enum
            $this->assertContains($pa['action_source'], [
                'deal_radar',
                'opportunity_engine',
                'portfolio_doctor',
                'buyer_match',
            ]);
        }
    }

    /**
     * @test
     * @group contract
     *
     * Validates priority_filter=today query parameter filters priority_actions
     * to only CRITICAL and HIGH urgency levels.
     */
    public function priority_filter_today_returns_only_critical_and_high_actions()
    {
        $user = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($user)
            ->getJson(route('advisor.command-center.fetch', ['priority_filter' => 'today']));
        $response->assertSuccessful();

        $data = $response->json()['data'];
        $actions = $data['priority_actions'];

        foreach ($actions as $action) {
            $this->assertContains(
                $action['execution_priority'],
                ['CRITICAL', 'HIGH'],
                "priority_filter=today returned a non-HIGH/CRITICAL action: "
                    . json_encode($action)
            );
        }
    }
}
