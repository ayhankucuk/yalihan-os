<?php

namespace App\Domains\Finance\ValueObjects;

use InvalidArgumentException;

/**
 * Money Value Object
 *
 * EX-002 Finance Agent — WAVE 1
 *
 * Para miktarını ve para birimini immutable olarak temsil eder.
 * Tüm aritmetik işlemler yeni Money instance döner (immutability).
 */
final class Money
{
    public function __construct(
        private readonly float $amount,
        private readonly string $currency = 'TRY',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Money amount cannot be negative: {$amount}");
        }

        if (empty($currency) || strlen($currency) !== 3) {
            throw new InvalidArgumentException("Invalid currency code: {$currency}");
        }
    }

    // ─── Factory ─────────────────────────────────────────────────────────────

    public static function of(float $amount, string $currency = 'TRY'): self
    {
        return new self($amount, strtoupper($currency));
    }

    public static function zero(string $currency = 'TRY'): self
    {
        return new self(0.0, strtoupper($currency));
    }

    // ─── Accessors ───────────────────────────────────────────────────────────

    public function getAmount(): float
    {
        return round($this->amount, 2);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    // ─── Arithmetic ──────────────────────────────────────────────────────────

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->amount - $other->amount;

        if ($result < 0) {
            throw new InvalidArgumentException(
                "Subtraction would result in negative money: {$this->amount} - {$other->amount}"
            );
        }

        return new self($result, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException("Multiplier cannot be negative: {$multiplier}");
        }

        return new self($this->amount * $multiplier, $this->currency);
    }

    public function percentage(float $rate): self
    {
        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException("Percentage rate must be between 0 and 100: {$rate}");
        }

        return new self($this->amount * ($rate / 100), $this->currency);
    }

    // ─── Comparison ──────────────────────────────────────────────────────────

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && abs($this->amount - $other->amount) < 0.001;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function isZero(): bool
    {
        return abs($this->amount) < 0.001;
    }

    // ─── Formatting ──────────────────────────────────────────────────────────

    public function format(): string
    {
        return number_format($this->amount, 2, '.', ',') . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount'   => $this->getAmount(),
            'currency' => $this->currency,
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }

    // ─── Guards ──────────────────────────────────────────────────────────────

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
