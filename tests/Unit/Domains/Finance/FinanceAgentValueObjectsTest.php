<?php

namespace Tests\Unit\Domains\Finance;

use App\Domains\Finance\ValueObjects\CommissionRate;
use App\Domains\Finance\ValueObjects\Money;
use App\Domains\Finance\ValueObjects\PayoutPeriod;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * FinanceAgentValueObjectsTest
 *
 * EX-002 Finance Agent — WAVE 5
 *
 * Value Object'lerin tüm business rule'larını test eder.
 * Database bağlantısı gerektirmez — pure unit tests.
 */
class FinanceAgentValueObjectsTest extends TestCase
{
    // ─── Money Tests ─────────────────────────────────────────────────────────

    /** @test */
    public function money_creates_with_valid_amount(): void
    {
        $money = Money::of(1000.0, 'TRY');
        $this->assertEquals(1000.0, $money->getAmount());
        $this->assertEquals('TRY', $money->getCurrency());
    }

    /** @test */
    public function money_zero_creates_zero_amount(): void
    {
        $money = Money::zero('TRY');
        $this->assertTrue($money->isZero());
        $this->assertEquals(0.0, $money->getAmount());
    }

    /** @test */
    public function money_add_returns_correct_sum(): void
    {
        $a = Money::of(1000.0, 'TRY');
        $b = Money::of(500.0, 'TRY');
        $result = $a->add($b);
        $this->assertEquals(1500.0, $result->getAmount());
    }

    /** @test */
    public function money_subtract_returns_correct_difference(): void
    {
        $a = Money::of(1000.0, 'TRY');
        $b = Money::of(100.0, 'TRY');
        $result = $a->subtract($b);
        $this->assertEquals(900.0, $result->getAmount());
    }

    /** @test */
    public function money_subtract_throws_on_negative_result(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(100.0)->subtract(Money::of(200.0));
    }

    /** @test */
    public function money_percentage_calculates_correctly(): void
    {
        $money = Money::of(1000.0, 'TRY');
        $result = $money->percentage(10.0);
        $this->assertEquals(100.0, $result->getAmount());
    }

    /** @test */
    public function money_throws_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(-1.0, 'TRY');
    }

    /** @test */
    public function money_throws_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of(100.0, 'TRY')->add(Money::of(100.0, 'USD'));
    }

    // ─── CommissionRate Tests ────────────────────────────────────────────────

    /** @test */
    public function commission_rate_default_is_ten_percent(): void
    {
        $rate = CommissionRate::default();
        $this->assertEquals(10.0, $rate->getRate());
    }

    /** @test */
    public function commission_rate_calculates_commission_correctly(): void
    {
        $rate  = CommissionRate::of(10.0);
        $gross = Money::of(1000.0, 'TRY');

        $commission = $rate->calculateCommission($gross);
        $this->assertEquals(100.0, $commission->getAmount());
    }

    /** @test */
    public function commission_rate_calculates_owner_net_correctly(): void
    {
        $rate  = CommissionRate::of(10.0);
        $gross = Money::of(1000.0, 'TRY');

        $ownerNet = $rate->calculateOwnerNet($gross);
        $this->assertEquals(900.0, $ownerNet->getAmount());
    }

    /** @test */
    public function commission_rate_throws_on_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CommissionRate::of(101.0);
    }

    /** @test */
    public function commission_rate_throws_on_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CommissionRate::of(-1.0);
    }

    // ─── PayoutPeriod Tests ──────────────────────────────────────────────────

    /** @test */
    public function payout_period_creates_from_strings(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');
        $this->assertEquals('2026-07-01', $period->getStartDateString());
        $this->assertEquals('2026-07-31', $period->getEndDateString());
    }

    /** @test */
    public function payout_period_for_month_creates_correct_range(): void
    {
        $period = PayoutPeriod::forMonth('2026-07');
        $this->assertEquals('2026-07-01', $period->getStartDateString());
        $this->assertEquals('2026-07-31', $period->getEndDateString());
    }

    /** @test */
    public function payout_period_throws_on_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PayoutPeriod::of('2026-07-31', '2026-07-01');
    }

    /** @test */
    public function payout_period_generates_idempotency_key(): void
    {
        $period = PayoutPeriod::of('2026-07-01', '2026-07-31');
        $key    = $period->toIdempotencyKey(1, 42);

        $this->assertStringContainsString('owner-payout', $key);
        $this->assertStringContainsString('1', $key);
        $this->assertStringContainsString('42', $key);
        $this->assertStringContainsString('20260701', $key);
    }

    /** @test */
    public function payout_period_equality_works(): void
    {
        $a = PayoutPeriod::of('2026-07-01', '2026-07-31');
        $b = PayoutPeriod::of('2026-07-01', '2026-07-31');
        $this->assertTrue($a->equals($b));
    }

    /** @test */
    public function payout_period_overlap_detection_works(): void
    {
        $a = PayoutPeriod::of('2026-07-01', '2026-07-31');
        $b = PayoutPeriod::of('2026-07-15', '2026-08-15');
        $this->assertTrue($a->overlaps($b));
    }
}
