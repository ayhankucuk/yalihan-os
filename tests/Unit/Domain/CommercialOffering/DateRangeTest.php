<?php

namespace Tests\Unit\Domain\CommercialOffering;

use App\Domain\CommercialOffering\ValueObjects\DateRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function test_valid_range_accepted(): void
    {
        $start = new \DateTimeImmutable('2026-01-01');
        $end = new \DateTimeImmutable('2026-12-31');

        $range = new DateRange($start, $end);

        $this->assertEquals($start, $range->getBaslangicTarihi());
        $this->assertEquals($end, $range->getBitisTarihi());
    }

    public function test_same_day_range_is_valid(): void
    {
        $date = new \DateTimeImmutable('2026-06-15');

        $range = new DateRange($date, $date);

        $this->assertTrue($range->isValid($date));
    }

    public function test_invalid_range_throws_exception(): void
    {
        $start = new \DateTimeImmutable('2026-12-31');
        $end = new \DateTimeImmutable('2026-01-01');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/bitiş.*başlangıç/i');

        new DateRange($start, $end);
    }

    public function test_null_dates_are_valid(): void
    {
        $range = new DateRange(null, null);

        $this->assertNull($range->getBaslangicTarihi());
        $this->assertNull($range->getBitisTarihi());
        $this->assertTrue($range->isActive());
    }

    public function test_is_valid_within_range(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-12-31')
        );

        $this->assertTrue($range->isValid(new \DateTimeImmutable('2026-06-15')));
    }

    public function test_is_valid_before_start(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-12-31')
        );

        $this->assertFalse($range->isValid(new \DateTimeImmutable('2026-01-01')));
    }

    public function test_is_valid_after_end(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('2026-01-01'),
            new \DateTimeImmutable('2026-06-30')
        );

        $this->assertFalse($range->isValid(new \DateTimeImmutable('2026-12-31')));
    }

    public function test_to_array(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-08-31')
        );

        $array = $range->toArray();

        $this->assertEquals('2026-06-01', $array['baslangic_tarihi']);
        $this->assertEquals('2026-08-31', $array['bitis_tarihi']);
    }

    public function test_equals_with_same_dates(): void
    {
        $range1 = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-08-31')
        );
        $range2 = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-08-31')
        );

        $this->assertTrue($range1->equals($range2));
    }

    public function test_equals_with_different_dates(): void
    {
        $range1 = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-08-31')
        );
        $range2 = new DateRange(
            new \DateTimeImmutable('2026-07-01'),
            new \DateTimeImmutable('2026-08-31')
        );

        $this->assertFalse($range1->equals($range2));
    }
}
