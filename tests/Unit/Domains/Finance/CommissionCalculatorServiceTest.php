<?php

namespace Tests\Unit\Domains\Finance;

use App\Domains\Finance\Services\CommissionCalculatorService;
use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\Money;
use Tests\TestCase;

/**
 * CommissionCalculatorServiceTest
 *
 * EX-002 Finance Agent — WAVE 5
 *
 * CommissionCalculatorService'in hesaplama mantığını test eder.
 * Database bağlantısı gerektirmez — pure unit tests.
 */
class CommissionCalculatorServiceTest extends TestCase
{
    private CommissionCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculatorService();
    }

    /** @test */
    public function calculates_commission_and_owner_net_correctly(): void
    {
        $amount = Money::of(1000.0, 'TRY');
        $rate   = CommissionRate::of(10.0);

        $result = $this->calculator->calculate($amount, $rate);

        $this->assertEquals(100.0, $result['commission']->getAmount());
        $this->assertEquals(900.0, $result['owner_net']->getAmount());
        $this->assertEquals(10.0, $result['rate']->getRate());
    }

    /** @test */
    public function calculates_with_default_rate(): void
    {
        $amount = Money::of(5000.0, 'TRY');

        $result = $this->calculator->calculateWithDefaultRate($amount);

        $this->assertEquals(500.0, $result['commission']->getAmount());
        $this->assertEquals(4500.0, $result['owner_net']->getAmount());
    }

    /** @test */
    public function batch_calculation_sums_correctly(): void
    {
        $items = [
            ['amount' => 1000.0, 'currency' => 'TRY', 'rate' => 10.0],
            ['amount' => 2000.0, 'currency' => 'TRY', 'rate' => 10.0],
            ['amount' => 3000.0, 'currency' => 'TRY', 'rate' => 10.0],
        ];

        $result = $this->calculator->calculateBatch($items, 'TRY');

        $this->assertEquals(6000.0, $result['total_gross']->getAmount());
        $this->assertEquals(600.0, $result['total_commission']->getAmount());
        $this->assertEquals(5400.0, $result['total_owner_net']->getAmount());
    }

    /** @test */
    public function commission_amount_plus_owner_net_equals_gross(): void
    {
        $grossAmount = Money::of(3750.50, 'TRY');
        $rate        = CommissionRate::of(10.0);

        $result = $this->calculator->calculate($grossAmount, $rate);

        $reconstructed = $result['commission']->add($result['owner_net']);
        $this->assertEquals($grossAmount->getAmount(), $reconstructed->getAmount());
    }

    /** @test */
    public function zero_commission_rate_returns_full_amount_to_owner(): void
    {
        $amount = Money::of(1000.0, 'TRY');
        $rate   = CommissionRate::of(0.0);

        $result = $this->calculator->calculate($amount, $rate);

        $this->assertEquals(0.0, $result['commission']->getAmount());
        $this->assertEquals(1000.0, $result['owner_net']->getAmount());
    }

    /** @test */
    public function different_commission_rates_produce_correct_results(): void
    {
        $amount = Money::of(1000.0, 'TRY');

        $cases = [
            5.0  => [50.0, 950.0],
            10.0 => [100.0, 900.0],
            15.0 => [150.0, 850.0],
            20.0 => [200.0, 800.0],
        ];

        foreach ($cases as $rateValue => [$expectedCommission, $expectedNet]) {
            $result = $this->calculator->calculate($amount, CommissionRate::of($rateValue));
            $this->assertEquals($expectedCommission, $result['commission']->getAmount(), "Commission mismatch for rate {$rateValue}%");
            $this->assertEquals($expectedNet, $result['owner_net']->getAmount(), "Owner net mismatch for rate {$rateValue}%");
        }
    }
}
