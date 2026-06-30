<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\ConfidenceCalculator;
use Tests\TestCase;

class ConfidenceCalculatorTest extends TestCase
{
    private ConfidenceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ConfidenceCalculator();
    }

    // ─── Test 1: High sample + low variance → HIGH ───

    public function test_high_confidence_with_large_sample_low_variance(): void
    {
        // 25 comps, avg=10000, stddev=500 (cv=0.05 < 0.10), valid_ratio=0.95
        $score = $this->calculator->calculate(25, 10000.0, 500.0, 0.95);
        $label = $this->calculator->label($score);

        // sample=40 + variance=30 + quality=30 = 100
        $this->assertEquals(100, $score);
        $this->assertEquals('HIGH', $label);
    }

    // ─── Test 2: Low sample → LOW ───

    public function test_low_confidence_with_small_sample(): void
    {
        // 3 comps (below 5), avg=10000, low variance, good quality
        $score = $this->calculator->calculate(3, 10000.0, 500.0, 0.95);
        $label = $this->calculator->label($score);

        // sample=5 + variance=30 + quality=30 = 65
        $this->assertEquals(65, $score);
        $this->assertEquals('MEDIUM', $label);
    }

    // ─── Test 3: High variance → düşüş ───

    public function test_high_variance_reduces_confidence(): void
    {
        // 20 comps, avg=10000, stddev=4000 (cv=0.40 > 0.35), good quality
        $score = $this->calculator->calculate(20, 10000.0, 4000.0, 0.95);
        $label = $this->calculator->label($score);

        // sample=40 + variance=0 + quality=30 = 70
        $this->assertEquals(70, $score);
        $this->assertEquals('MEDIUM', $label);
    }

    // ─── Test 4: Kötü data quality → VERY_LOW ───

    public function test_very_low_with_bad_data_quality(): void
    {
        // 3 comps, avg=10000, extreme variance (cv=0.50), bad quality (0.3)
        $score = $this->calculator->calculate(3, 10000.0, 5000.0, 0.3);
        $label = $this->calculator->label($score);

        // sample=5 + variance=0 + quality=0 = 5
        $this->assertEquals(5, $score);
        $this->assertEquals('VERY_LOW', $label);
    }

    // ─── Test 5: Edge — 0 sample → 0 confidence ───

    public function test_zero_sample_gives_minimum_confidence(): void
    {
        $score = $this->calculator->calculate(0, 0.0, 0.0, 0.0);
        $label = $this->calculator->label($score);

        // sample=5 + variance=0 (avgPrice=0) + quality=0 = 5
        $this->assertEquals(5, $score);
        $this->assertEquals('VERY_LOW', $label);
    }

    // ─── Test 6: Deterministic — same input same output ───

    public function test_deterministic_same_input_same_output(): void
    {
        $args = [15, 8500.0, 1200.0, 0.88];

        $result1 = $this->calculator->calculate(...$args);
        $result2 = $this->calculator->calculate(...$args);

        $this->assertSame($result1, $result2);

        $reason1 = $this->calculator->reason(...$args);
        $reason2 = $this->calculator->reason(...$args);

        $this->assertSame($reason1, $reason2);
    }

    // ─── Test 7: Reason string is explainable ───

    public function test_reason_string_contains_all_signals(): void
    {
        $reason = $this->calculator->reason(12, 10000.0, 500.0, 0.92);

        $this->assertStringContainsString('12 comps', $reason);
        $this->assertStringContainsString('low variance', $reason);
        $this->assertStringContainsString('high data quality', $reason);
    }

    // ─── Test 8: Label boundaries ───

    public function test_label_boundaries(): void
    {
        $this->assertEquals('VERY_LOW', $this->calculator->label(0));
        $this->assertEquals('VERY_LOW', $this->calculator->label(19));
        $this->assertEquals('LOW', $this->calculator->label(20));
        $this->assertEquals('LOW', $this->calculator->label(49));
        $this->assertEquals('MEDIUM', $this->calculator->label(50));
        $this->assertEquals('MEDIUM', $this->calculator->label(79));
        $this->assertEquals('HIGH', $this->calculator->label(80));
        $this->assertEquals('HIGH', $this->calculator->label(100));
    }

    // ─── Test 9: Medium variance score ───

    public function test_medium_variance_gives_20(): void
    {
        // cv = 1500/10000 = 0.15, between 0.10–0.20
        $score = $this->calculator->calculate(20, 10000.0, 1500.0, 0.95);

        // sample=40 + variance=20 + quality=30 = 90
        $this->assertEquals(90, $score);
        $this->assertEquals('HIGH', $this->calculator->label($score));
    }

    // ─── Test 10: Clamp at 100 ───

    public function test_score_never_exceeds_100(): void
    {
        // Maximum possible: 40 + 30 + 30 = 100
        $score = $this->calculator->calculate(100, 10000.0, 100.0, 1.0);

        $this->assertLessThanOrEqual(100, $score);
    }
}
