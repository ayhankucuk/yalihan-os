<?php

namespace Tests\Unit\AI;

use App\Services\AI\Optimization\ProviderScoreCalculator;
use PHPUnit\Framework\TestCase;

class ProviderScoreCalculatorTest extends TestCase
{
    protected ProviderScoreCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ProviderScoreCalculator();
    }

    public function test_it_sorts_providers_by_score_correctly()
    {
        // Setup scenarios
        $candidates = [
            'provider_a' => ['roi' => 0.95, 'latency' => 1200, 'cost' => 0.03],
            'provider_b' => ['roi' => 0.92, 'latency' => 800, 'cost' => 0.01],
            'provider_c' => ['roi' => 0.80, 'latency' => 200, 'cost' => 0.00],
        ];

        // Calculation Logic Verification:
        // A (High Quality, Expensive/Slow)
        // ROI: 0.475 + Lat: ~0.23 + Cost: 0.05 = ~0.755

        // B (Balanced)
        // ROI: 0.46 + Lat: 0.3 (Max) + Cost: 0.1 = 0.86

        // C (Fast/Cheap/Local)
        // ROI: 0.40 + Lat: 0.3 (Max) + Cost: 0.2 (Max) = 0.90

        $scores = $this->calculator->calculateScores($candidates);

        // Assert sorting sequence
        $keys = array_keys($scores);
        $this->assertEquals('provider_c', $keys[0], 'Local/Fast provider should win due to cost/speed benefit');
        $this->assertEquals('provider_b', $keys[1], 'Balanced provider should come second');
        $this->assertEquals('provider_a', $keys[2], 'Expensive/Slow provider should come last despite high ROI');

        // Assert scores are float
        $this->assertIsFloat($scores['provider_a']);
    }

    public function test_fallback_chain_returns_correct_order()
    {
        $candidates = [
            'p1' => ['roi' => 0.9],
            'p2' => ['roi' => 0.1],
        ];

        $scores = $this->calculator->calculateScores($candidates);
        $chain = $this->calculator->getFallbackChain($scores);

        $this->assertEquals(['p1', 'p2'], $chain);
    }
}
