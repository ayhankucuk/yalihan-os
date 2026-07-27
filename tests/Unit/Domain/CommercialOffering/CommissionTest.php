<?php

namespace Tests\Unit\Domain\CommercialOffering;

use App\Domain\CommercialOffering\ValueObjects\Commission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CommissionTest extends TestCase
{
    public function test_valid_commission_rate(): void
    {
        $commission = new Commission(5.0);

        $this->assertEquals(5.0, $commission->getRate());
        $this->assertFalse($commission->isNull());
    }

    public function test_zero_commission(): void
    {
        $commission = new Commission(0.0);

        $this->assertEquals(0.0, $commission->getRate());
        $this->assertFalse($commission->isNull());
    }

    public function test_null_commission(): void
    {
        $commission = new Commission(null);

        $this->assertNull($commission->getRate());
        $this->assertTrue($commission->isNull());
    }

    public function test_negative_rate_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/0.*100/i');

        new Commission(-5.0);
    }

    public function test_rate_over_100_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/0.*100/i');

        new Commission(150.0);
    }

    public function test_calculate(): void
    {
        $commission = new Commission(5.0);

        $result = $commission->calculate(100000.0);

        $this->assertEquals(5000.0, $result);
    }

    public function test_calculate_with_zero_amount(): void
    {
        $commission = new Commission(5.0);

        $result = $commission->calculate(0);

        $this->assertEquals(0.0, $result);
    }

    public function test_calculate_with_null_rate(): void
    {
        $commission = new Commission(null);

        $result = $commission->calculate(100000.0);

        $this->assertEquals(0.0, $result);
    }

    public function test_from_decimal(): void
    {
        $commission = Commission::fromDecimal('5.50');

        $this->assertEquals(5.5, $commission->getRate());
    }

    public function test_from_decimal_with_empty_string(): void
    {
        $commission = Commission::fromDecimal('');

        $this->assertTrue($commission->isNull());
    }

    public function test_from_decimal_with_null(): void
    {
        $commission = Commission::fromDecimal(null);

        $this->assertTrue($commission->isNull());
    }

    public function test_equals(): void
    {
        $c1 = new Commission(5.5);
        $c2 = new Commission(5.5);

        $this->assertTrue($c1->equals($c2));
    }

    public function test_equals_with_different_rates(): void
    {
        $c1 = new Commission(5.0);
        $c2 = new Commission(3.0);

        $this->assertFalse($c1->equals($c2));
    }

    public function test_equals_null_vs_value(): void
    {
        $c1 = new Commission(null);
        $c2 = new Commission(5.0);

        $this->assertFalse($c1->equals($c2));
    }
}
