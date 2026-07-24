<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'TRY')
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Money amount must be greater than zero.');
        }

        $this->amount = round($amount, 2);
        $this->currency = strtoupper(trim($currency));
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->getAmount() && $this->currency === $other->getCurrency();
    }
}
