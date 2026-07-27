<?php

namespace App\Domain\CommercialOffering\ValueObjects;

use InvalidArgumentException;

/**
 * Commission Value Object for CommercialOffering.
 * Enforces commission rate between 0 and 100.
 */
readonly class Commission
{
    public function __construct(
        private ?float $rate
    ) {
        if ($rate !== null && ($rate < 0 || $rate > 100)) {
            throw new InvalidArgumentException(
                'Komisyon oranı 0 ile 100 arasında olmalıdır.'
            );
        }
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function calculate(float $amount): float
    {
        if ($this->rate === null || $amount <= 0) {
            return 0.0;
        }

        return $amount * ($this->rate / 100);
    }

    public function isNull(): bool
    {
        return $this->rate === null;
    }

    public function equals(self $other): bool
    {
        if ($this->rate === null && $other->rate !== null) {
            return false;
        }
        if ($this->rate !== null && $other->rate === null) {
            return false;
        }
        if ($this->rate === null && $other->rate === null) {
            return true;
        }

        return abs($this->rate - $other->rate) < 0.001;
    }

    public static function fromDecimal(?string $rate): self
    {
        if ($rate === null || $rate === '') {
            return new self(null);
        }

        return new self((float) $rate);
    }
}
