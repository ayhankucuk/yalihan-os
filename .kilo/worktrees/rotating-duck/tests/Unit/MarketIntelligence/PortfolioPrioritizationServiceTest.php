<?php

namespace Tests\Unit\MarketIntelligence;

use App\DTOs\MarketIntelligence\PortfolioPriorityDTO;
use App\Services\MarketIntelligence\PortfolioPrioritizationService;
use PHPUnit\Framework\TestCase;

/**
 * PortfolioPrioritizationService Unit Tests — MIE v1.4
 *
 * Deterministic. No DB, no HTTP, no rand(), no AI.
 */
class PortfolioPrioritizationServiceTest extends TestCase
{
    private PortfolioPrioritizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PortfolioPrioritizationService();
    }

    // ─── Test 1: Overpriced + stale + high confidence → CRITICAL/HIGH ───

    public function test_overpriced_stale_high_confidence_is_critical_or_high(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 1,
            'opportunity_action' => 'SELL',
            'opportunity_score' => 45,
            'demand_score' => 60,      // ACTIVE
            'confidence_score' => 85,  // HIGH
            'pricing_position' => 'overpriced',
            'days_on_market' => 97,    // >90 → stale
        ]);

        $this->assertInstanceOf(PortfolioPriorityDTO::class, $result);
        $this->assertContains($result->priority_label, ['CRITICAL', 'HIGH']);
        $this->assertGreaterThanOrEqual(55, $result->priority_score);
        $this->assertEquals('SELL', $result->opportunity_action);
    }

    // ─── Test 2: Underpriced + active demand + high confidence → HIGH ───

    public function test_underpriced_active_demand_high_confidence_is_high(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 2,
            'opportunity_action' => 'BUY',
            'opportunity_score' => 80,
            'demand_score' => 65,      // ACTIVE
            'confidence_score' => 82,  // HIGH
            'pricing_position' => 'underpriced',
            'days_on_market' => 15,
        ]);

        $this->assertContains($result->priority_label, ['CRITICAL', 'HIGH']);
        $this->assertGreaterThanOrEqual(55, $result->priority_score);
        $this->assertEquals('BUY', $result->opportunity_action);
    }

    // ─── Test 3: Fair + medium demand + young → MEDIUM or LOW ───

    public function test_fair_medium_demand_young_listing_is_medium_or_low(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 3,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 40,
            'demand_score' => 55,      // ACTIVE
            'confidence_score' => 55,  // MEDIUM
            'pricing_position' => 'fair',
            'days_on_market' => 10,
        ]);

        $this->assertContains($result->priority_label, ['MEDIUM', 'LOW']);
        $this->assertLessThan(75, $result->priority_score);
    }

    // ─── Test 4: Insufficient data + stale → MEDIUM/HIGH ───

    public function test_insufficient_data_stale_is_medium_or_higher(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 4,
            'opportunity_action' => 'INSUFFICIENT_DATA',
            'opportunity_score' => 10,
            'demand_score' => 15,      // WEAK
            'confidence_score' => 10,  // VERY_LOW
            'pricing_position' => 'insufficient_data',
            'days_on_market' => 120,   // very stale
        ]);

        $this->assertGreaterThanOrEqual(35, $result->priority_score);
        $this->assertContains($result->priority_label, ['MEDIUM', 'HIGH']);
    }

    // ─── Test 5: Deterministic — same input → identical output ───

    public function test_deterministic_same_input_same_output(): void
    {
        $payload = [
            'listing_id' => 5,
            'opportunity_action' => 'BUY',
            'opportunity_score' => 70,
            'demand_score' => 80,
            'confidence_score' => 90,
            'pricing_position' => 'underpriced',
            'days_on_market' => 45,
        ];

        $result1 = $this->service->evaluateListing($payload);
        $result2 = $this->service->evaluateListing($payload);

        $this->assertEquals($result1->priority_score, $result2->priority_score);
        $this->assertEquals($result1->priority_label, $result2->priority_label);
        $this->assertEquals($result1->priority_reason, $result2->priority_reason);
        $this->assertEquals($result1->toArray(), $result2->toArray());
    }

    // ─── Test 6: Collection sort order correct ───

    public function test_collection_sort_order_is_priority_desc(): void
    {
        $payloads = [
            [
                'listing_id' => 10,
                'opportunity_action' => 'WAIT',
                'opportunity_score' => 30,
                'demand_score' => 20,
                'confidence_score' => 40,
                'pricing_position' => 'fair',
                'days_on_market' => 5,
            ],
            [
                'listing_id' => 11,
                'opportunity_action' => 'SELL',
                'opportunity_score' => 50,
                'demand_score' => 70,
                'confidence_score' => 85,
                'pricing_position' => 'overpriced',
                'days_on_market' => 100,
            ],
            [
                'listing_id' => 12,
                'opportunity_action' => 'BUY',
                'opportunity_score' => 75,
                'demand_score' => 80,
                'confidence_score' => 90,
                'pricing_position' => 'underpriced',
                'days_on_market' => 20,
            ],
        ];

        $sorted = $this->service->prioritize($payloads);

        $this->assertCount(3, $sorted);

        // Must be sorted by priority_score DESC
        for ($i = 0; $i < count($sorted) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $sorted[$i + 1]->priority_score,
                $sorted[$i]->priority_score,
                "Item {$i} should have >= priority_score than item " . ($i + 1),
            );
        }
    }

    // ─── Test 7: SELL has higher urgency than WAIT ───

    public function test_sell_higher_urgency_than_wait(): void
    {
        $base = [
            'listing_id' => 20,
            'opportunity_score' => 50,
            'demand_score' => 50,
            'confidence_score' => 50,
            'pricing_position' => 'overpriced',
            'days_on_market' => 50,
        ];

        $sellResult = $this->service->evaluateListing(array_merge($base, [
            'opportunity_action' => 'SELL',
        ]));

        $waitResult = $this->service->evaluateListing(array_merge($base, [
            'listing_id' => 21,
            'opportunity_action' => 'WAIT',
        ]));

        $this->assertGreaterThan($waitResult->priority_score, $sellResult->priority_score);
    }

    // ─── Test 8: Stale listings get higher priority ───

    public function test_stale_listings_get_higher_priority(): void
    {
        $base = [
            'listing_id' => 30,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 40,
            'demand_score' => 40,
            'confidence_score' => 50,
            'pricing_position' => 'fair',
        ];

        $staleResult = $this->service->evaluateListing(array_merge($base, [
            'days_on_market' => 120,
        ]));

        $freshResult = $this->service->evaluateListing(array_merge($base, [
            'listing_id' => 31,
            'days_on_market' => 5,
        ]));

        $this->assertGreaterThan($freshResult->priority_score, $staleResult->priority_score);
    }

    // ─── Test 9: DTO toArray has expected keys ───

    public function test_dto_to_array_has_expected_keys(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 99,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 40,
            'demand_score' => 30,
            'confidence_score' => 50,
            'pricing_position' => 'fair',
            'days_on_market' => 20,
        ]);

        $array = $result->toArray();

        $expectedKeys = [
            'listing_id',
            'priority_score',
            'priority_label',
            'priority_reason',
            'opportunity_action',
            'opportunity_score',
            'confidence_score',
            'demand_score',
            'pricing_position',
            'days_on_market',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }

    // ─── Test 10: Label boundaries ───

    public function test_label_boundaries(): void
    {
        $this->assertEquals('CRITICAL', $this->service->label(75));
        $this->assertEquals('CRITICAL', $this->service->label(100));
        $this->assertEquals('HIGH', $this->service->label(74));
        $this->assertEquals('HIGH', $this->service->label(55));
        $this->assertEquals('MEDIUM', $this->service->label(54));
        $this->assertEquals('MEDIUM', $this->service->label(35));
        $this->assertEquals('LOW', $this->service->label(34));
        $this->assertEquals('LOW', $this->service->label(0));
    }

    // ─── Test 11: Score is clamped 0-100 ───

    public function test_score_clamped_0_to_100(): void
    {
        // Max scenario
        $maxResult = $this->service->evaluateListing([
            'listing_id' => 100,
            'opportunity_action' => 'SELL',      // 35
            'opportunity_score' => 100,           // 25
            'demand_score' => 100,                // 15
            'confidence_score' => 100,            // 15
            'pricing_position' => 'overpriced',
            'days_on_market' => 200,              // 10
        ]);

        $this->assertLessThanOrEqual(100, $maxResult->priority_score);
        $this->assertGreaterThanOrEqual(0, $maxResult->priority_score);

        // Min scenario
        $minResult = $this->service->evaluateListing([
            'listing_id' => 101,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 0,
            'demand_score' => 0,
            'confidence_score' => 0,
            'pricing_position' => 'insufficient_data',
            'days_on_market' => null,
        ]);

        $this->assertGreaterThanOrEqual(0, $minResult->priority_score);
    }

    // ─── Test 12: Priority reason is non-empty and deterministic ───

    public function test_reason_is_nonempty_and_deterministic(): void
    {
        $payload = [
            'listing_id' => 50,
            'opportunity_action' => 'SELL',
            'opportunity_score' => 40,
            'demand_score' => 60,
            'confidence_score' => 85,
            'pricing_position' => 'overpriced',
            'days_on_market' => 97,
        ];

        $result1 = $this->service->evaluateListing($payload);
        $result2 = $this->service->evaluateListing($payload);

        $this->assertNotEmpty($result1->priority_reason);
        $this->assertEquals($result1->priority_reason, $result2->priority_reason);
        $this->assertStringContainsString('benchmark üstü fiyat', $result1->priority_reason);
        $this->assertStringContainsString('97 gün', $result1->priority_reason);
    }

    // ─── Test 13: No days_on_market → age pressure = 0 ───

    public function test_null_days_on_market_gives_zero_age_pressure(): void
    {
        $withAge = $this->service->evaluateListing([
            'listing_id' => 60,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 40,
            'demand_score' => 40,
            'confidence_score' => 50,
            'pricing_position' => 'fair',
            'days_on_market' => 100,
        ]);

        $withoutAge = $this->service->evaluateListing([
            'listing_id' => 61,
            'opportunity_action' => 'WAIT',
            'opportunity_score' => 40,
            'demand_score' => 40,
            'confidence_score' => 50,
            'pricing_position' => 'fair',
            'days_on_market' => null,
        ]);

        $this->assertGreaterThan($withoutAge->priority_score, $withAge->priority_score);
    }

    // ─── Test 14: Aggressively overpriced + very stale → CRITICAL ───

    public function test_aggressively_overpriced_very_stale_is_critical(): void
    {
        $result = $this->service->evaluateListing([
            'listing_id' => 70,
            'opportunity_action' => 'SELL',
            'opportunity_score' => 30,
            'demand_score' => 75,
            'confidence_score' => 85,
            'pricing_position' => 'aggressively_overpriced',
            'days_on_market' => 150,
        ]);

        // SELL(35) + opp(8) + demand(15) + conf(15) + age(10) = 83
        $this->assertEquals('CRITICAL', $result->priority_label);
        $this->assertGreaterThanOrEqual(75, $result->priority_score);
    }
}
