<?php

namespace Tests\Unit\MarketIntelligence;

use App\Enums\MarketIntelligence\PricingPosition;
use App\Services\MarketIntelligence\OpportunityScoreService;
use PHPUnit\Framework\TestCase;

/**
 * OpportunityScoreService Unit Tests — MIE v1.3
 *
 * Deterministic. No DB, no HTTP, no rand(), no AI.
 */
class OpportunityScoreServiceTest extends TestCase
{
    private OpportunityScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpportunityScoreService();
    }

    // ─── Test 1: Underpriced + HOT demand + HIGH confidence → BUY ───

    public function test_buy_when_underpriced_hot_demand_high_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            85,  // high pricing score
            80,  // HOT demand (≥75)
            90,  // HIGH confidence (≥80)
        );

        $this->assertEquals('BUY', $result['opportunity_action']);
        $this->assertGreaterThanOrEqual(70, $result['opportunity_score']);
        $this->assertNotEmpty($result['opportunity_reason']);
        $this->assertStringContainsString('Fırsat', $result['opportunity_reason']);
    }

    // ─── Test 2: Fair + ACTIVE demand + MEDIUM confidence → WAIT ───

    public function test_wait_when_fair_active_demand_medium_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::FAIR->value,
            70,  // decent pricing score
            60,  // ACTIVE demand (≥50)
            55,  // MEDIUM confidence (≥50)
        );

        $this->assertEquals('WAIT', $result['opportunity_action']);
        $this->assertNotEmpty($result['opportunity_reason']);
        $this->assertStringContainsString('Bekle', $result['opportunity_reason']);
    }

    // ─── Test 3: Overpriced + MEDIUM confidence → SELL ───

    public function test_sell_when_overpriced_medium_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::OVERPRICED->value,
            40,
            50,  // ACTIVE demand
            55,  // MEDIUM confidence
        );

        $this->assertEquals('SELL', $result['opportunity_action']);
        $this->assertNotEmpty($result['opportunity_reason']);
        $this->assertStringContainsString('revizyon', mb_strtolower($result['opportunity_reason']));
    }

    // ─── Test 4: Aggressively overpriced + HIGH confidence → SELL ───

    public function test_sell_when_aggressively_overpriced_high_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::AGGRESSIVELY_OVERPRICED->value,
            20,
            80,  // HOT demand
            90,  // HIGH confidence
        );

        $this->assertEquals('SELL', $result['opportunity_action']);
        $this->assertNotEmpty($result['opportunity_reason']);
    }

    // ─── Test 5: Underpriced + VERY_LOW confidence → INSUFFICIENT_DATA ───

    public function test_insufficient_data_when_very_low_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            90,
            80,  // HOT demand
            10,  // VERY_LOW confidence (<20)
        );

        $this->assertEquals('INSUFFICIENT_DATA', $result['opportunity_action']);
        $this->assertStringContainsString('güven seviyesi yetersiz', $result['opportunity_reason']);
    }

    // ─── Test 6: Insufficient pricing data → INSUFFICIENT_DATA ───

    public function test_insufficient_data_when_no_pricing_position(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::INSUFFICIENT_DATA->value,
            0,
            50,
            60,
        );

        $this->assertEquals('INSUFFICIENT_DATA', $result['opportunity_action']);
        $this->assertStringContainsString('Fiyat pozisyonu belirlenemedi', $result['opportunity_reason']);
    }

    // ─── Test 7: Deterministic — same input → same output ───

    public function test_deterministic_same_input_same_output(): void
    {
        $args = [
            PricingPosition::UNDERPRICED->value,
            85,
            75,
            80,
        ];

        $result1 = $this->service->evaluate(...$args);
        $result2 = $this->service->evaluate(...$args);

        $this->assertEquals($result1, $result2);
    }

    // ─── Test 8: Score is clamped 0-100 ───

    public function test_score_clamped_0_to_100(): void
    {
        // Max scenario: underpriced(40) + HOT(30) + HIGH(30) = 100
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            100,
            100,
            100,
        );

        $this->assertLessThanOrEqual(100, $result['opportunity_score']);
        $this->assertGreaterThanOrEqual(0, $result['opportunity_score']);

        // Min scenario: insufficient(0) + WEAK(0) + VERY_LOW(0) = 0
        $resultMin = $this->service->evaluate(
            PricingPosition::INSUFFICIENT_DATA->value,
            0,
            0,
            0,
        );

        $this->assertEquals(0, $resultMin['opportunity_score']);
    }

    // ─── Test 9: BUY requires ACTIVE or HOT demand ───

    public function test_no_buy_when_underpriced_but_weak_demand(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            90,
            10,  // WEAK demand (<25)
            85,  // HIGH confidence
        );

        // Not BUY because demand is WEAK
        $this->assertNotEquals('BUY', $result['opportunity_action']);
    }

    // ─── Test 10: BUY requires HIGH or MEDIUM confidence ───

    public function test_no_buy_when_underpriced_hot_demand_but_low_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            90,
            80,  // HOT demand
            25,  // LOW confidence (≥20 but <50)
        );

        // Not BUY because confidence is LOW (only HIGH/MEDIUM qualify)
        $this->assertNotEquals('BUY', $result['opportunity_action']);
    }

    // ─── Test 11: Underpriced + ACTIVE + MEDIUM → BUY ───

    public function test_buy_underpriced_active_demand_medium_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::UNDERPRICED->value,
            80,
            55,  // ACTIVE demand (≥50)
            55,  // MEDIUM confidence (≥50)
        );

        $this->assertEquals('BUY', $result['opportunity_action']);
    }

    // ─── Test 12: Return array has expected keys ───

    public function test_return_array_has_expected_keys(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::FAIR->value,
            50,
            50,
            50,
        );

        $this->assertArrayHasKey('opportunity_score', $result);
        $this->assertArrayHasKey('opportunity_action', $result);
        $this->assertArrayHasKey('opportunity_reason', $result);
        $this->assertIsInt($result['opportunity_score']);
        $this->assertIsString($result['opportunity_action']);
        $this->assertIsString($result['opportunity_reason']);
    }

    // ─── Test 13: SELL reason mentions revision ───

    public function test_sell_reason_mentions_revision(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::OVERPRICED->value,
            30,
            40,
            60,
        );

        $this->assertEquals('SELL', $result['opportunity_action']);
        $this->assertStringContainsString('revizyon', mb_strtolower($result['opportunity_reason']));
    }

    // ─── Test 14: Fair + WEAK demand + HIGH confidence → WAIT (not BUY) ───

    public function test_wait_fair_weak_demand_high_confidence(): void
    {
        $result = $this->service->evaluate(
            PricingPosition::FAIR->value,
            70,
            10,  // WEAK demand
            90,  // HIGH confidence
        );

        $this->assertEquals('WAIT', $result['opportunity_action']);
    }
}
