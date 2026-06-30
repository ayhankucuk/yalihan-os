<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\DemandScoreService;
use PHPUnit\Framework\TestCase;

/**
 * DemandScoreService Unit Tests — MIE v1.2
 *
 * Deterministic. No DB, no HTTP, no rand().
 */
class DemandScoreServiceTest extends TestCase
{
    private DemandScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DemandScoreService();
    }

    // ─── Test 1: Fast sales + rising listings + low drops → HOT ───

    public function test_hot_demand_with_fast_sales_and_rising_listings(): void
    {
        $data = [
            'avg_days_on_market' => 10,   // <15 → 40
            'trend_ratio' => 1.5,          // >1.2 → 30
            'drop_ratio' => 0.1,           // <0.2 → 30
        ];

        $score = $this->service->calculate($data);

        $this->assertEquals(100, $score); // 40+30+30 = 100
        $this->assertEquals('HOT', $this->service->label($score));
    }

    // ─── Test 2: Slow sales + declining + heavy drops → WEAK ───

    public function test_weak_demand_with_slow_sales_and_decline(): void
    {
        $data = [
            'avg_days_on_market' => 90,   // >60 → 10
            'trend_ratio' => 0.5,          // <0.7 → 0
            'drop_ratio' => 0.8,           // >0.6 → 0
        ];

        $score = $this->service->calculate($data);

        $this->assertEquals(10, $score); // 10+0+0 = 10
        $this->assertEquals('WEAK', $this->service->label($score));
    }

    // ─── Test 3: Moderate velocity + stable trend → ACTIVE ───

    public function test_active_demand_with_moderate_signals(): void
    {
        $data = [
            'avg_days_on_market' => 20,   // 15-30 → 30
            'trend_ratio' => 1.0,          // 0.9-1.2 → 20
            'drop_ratio' => 0.15,          // <0.2 → 30
        ];

        $score = $this->service->calculate($data);

        $this->assertEquals(80, $score); // 30+20+30 = 80
        $this->assertEquals('HOT', $this->service->label($score));
    }

    // ─── Test 4: No data available → 0 / WEAK ───

    public function test_zero_score_when_no_data(): void
    {
        $data = [
            'avg_days_on_market' => null,
            'trend_ratio' => null,
            'drop_ratio' => null,
        ];

        $score = $this->service->calculate($data);

        $this->assertEquals(0, $score);
        $this->assertEquals('WEAK', $this->service->label($score));
    }

    // ─── Test 5: Trend decline penalizes score ───

    public function test_trend_decline_penalizes_score(): void
    {
        $risingData = [
            'avg_days_on_market' => 25,
            'trend_ratio' => 1.3,
            'drop_ratio' => 0.3,
        ];

        $decliningData = [
            'avg_days_on_market' => 25,
            'trend_ratio' => 0.6,
            'drop_ratio' => 0.3,
        ];

        $risingScore = $this->service->calculate($risingData);
        $decliningScore = $this->service->calculate($decliningData);

        $this->assertGreaterThan($decliningScore, $risingScore);
    }

    // ─── Test 6: High price drops reduce score ───

    public function test_high_price_drops_reduce_score(): void
    {
        $lowDrop = [
            'avg_days_on_market' => 20,
            'trend_ratio' => 1.0,
            'drop_ratio' => 0.1,
        ];

        $highDrop = [
            'avg_days_on_market' => 20,
            'trend_ratio' => 1.0,
            'drop_ratio' => 0.7,
        ];

        $lowDropScore = $this->service->calculate($lowDrop);
        $highDropScore = $this->service->calculate($highDrop);

        $this->assertGreaterThan($highDropScore, $lowDropScore);
    }

    // ─── Test 7: Reason string is deterministic ───

    public function test_reason_is_deterministic(): void
    {
        $data = [
            'avg_days_on_market' => 10,
            'trend_ratio' => 1.5,
            'drop_ratio' => 0.1,
        ];

        $reason1 = $this->service->reason($data);
        $reason2 = $this->service->reason($data);

        $this->assertEquals($reason1, $reason2);
        $this->assertStringContainsString('10 days', $reason1);
        $this->assertStringContainsString('rising listings', $reason1);
        $this->assertStringContainsString('low price drops', $reason1);
    }

    // ─── Test 8: Label boundaries ───

    public function test_label_boundaries(): void
    {
        $this->assertEquals('HOT', $this->service->label(75));
        $this->assertEquals('HOT', $this->service->label(100));
        $this->assertEquals('ACTIVE', $this->service->label(74));
        $this->assertEquals('ACTIVE', $this->service->label(50));
        $this->assertEquals('SLOW', $this->service->label(49));
        $this->assertEquals('SLOW', $this->service->label(25));
        $this->assertEquals('WEAK', $this->service->label(24));
        $this->assertEquals('WEAK', $this->service->label(0));
    }

    // ─── Test 9: Empty keys default to null ───

    public function test_empty_array_defaults(): void
    {
        $score = $this->service->calculate([]);

        $this->assertEquals(0, $score);
    }

    // ─── Test 10: Score clamped to 0-100 ───

    public function test_score_clamped(): void
    {
        // Maximum possible: 40+30+30 = 100, clamped
        $data = [
            'avg_days_on_market' => 1,
            'trend_ratio' => 5.0,
            'drop_ratio' => 0.01,
        ];

        $score = $this->service->calculate($data);

        $this->assertLessThanOrEqual(100, $score);
        $this->assertGreaterThanOrEqual(0, $score);
    }
}
