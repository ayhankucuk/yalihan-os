<?php

namespace App\Domains\Finance\ValueObjects;

use InvalidArgumentException;

/**
 * CommissionRate Value Object
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * YALIHAN'ın rezervasyon üzerinden aldığı komisyon oranını temsil eder.
 * Oran %0 ile %100 arasında olmalıdır. Immutable.
 */
final class CommissionRate
{
    private const MIN_RATE = 0.0;
    private const MAX_RATE = 100.0;

    public function __construct(
        private readonly float $rate,
    ) {
        if ($rate < self::MIN_RATE || $rate > self::MAX_RATE) {
            throw new InvalidArgumentException(
                "Commission rate must be between " . self::MIN_RATE . " and " . self::MAX_RATE . ": {$rate}"
            );
        }
    }

    // ─── Factory ─────────────────────────────────────────────────────────────

    public static function of(float $rate): self
    {
        return new self($rate);
    }

    /**
     * YALIHAN varsayılan Airbnb komisyon oranı: %10
     */
    public static function default(): self
    {
        return new self(10.0);
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getRate(): float
    {
        return round($this->rate, 2);
    }

    public function asDecimal(): float
    {
        return $this->rate / 100;
    }

    // ─── Calculation ─────────────────────────────────────────────────────────

    /**
     * Verilen tutardan komisyon miktarını hesaplar.
     */
    public function calculateCommission(Money $grossAmount): Money
    {
        return $grossAmount->percentage($this->rate);
    }

    /**
     * Verilen tutardan ev sahibi net miktarını hesaplar.
     */
    public function calculateOwnerNet(Money $grossAmount): Money
    {
        $commission = $this->calculateCommission($grossAmount);

        return $grossAmount->subtract($commission);
    }

    // ─── Comparison ──────────────────────────────────────────────────────────

    public function equals(self $other): bool
    {
        return abs($this->rate - $other->rate) < 0.001;
    }

    public function isHigherThan(self $other): bool
    {
        return $this->rate > $other->rate;
    }

    // ─── Formatting ──────────────────────────────────────────────────────────

    public function format(): string
    {
        return number_format($this->rate, 2) . '%';
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
