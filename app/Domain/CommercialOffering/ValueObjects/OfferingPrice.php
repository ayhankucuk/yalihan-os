<?php

namespace App\Domain\CommercialOffering\ValueObjects;

use App\Domain\CommercialOffering\Enums\OfferingType;

/**
 * OfferingPrice Value Object.
 * Combines Money + optional Deposit.
 */
readonly class OfferingPrice
{
    public function __construct(
        private float $amount,
        private string $currency,
        private ?float $depozito = null
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Fiyat negatif olamaz.');
        }
        if ($depozito !== null && $depozito < 0) {
            throw new \InvalidArgumentException('Depozito negatif olamaz.');
        }
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDepozito(): ?float
    {
        return $this->depozito;
    }

    public function hasDepozito(): bool
    {
        return $this->depozito !== null && $this->depozito > 0;
    }

    public function withDepozito(float $depozito): self
    {
        return new self($this->amount, $this->currency, $depozito);
    }

    public function withUpdatedAmount(float $newAmount): self
    {
        return new self($newAmount, $this->currency, $this->depozito);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency
            && abs(($this->depozito ?? 0) - ($other->depozito ?? 0)) < 0.01;
    }

    public function toArray(): array
    {
        return [
            'fiyat' => $this->amount,
            'para_birimi' => $this->currency,
            'depozito' => $this->depozito,
        ];
    }
}
