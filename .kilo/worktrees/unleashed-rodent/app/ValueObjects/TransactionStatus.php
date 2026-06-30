<?php

namespace App\ValueObjects;

/**
 * 🛡️ SAB Core v6.2 - Domain Hardening
 * Value Object for Transaction/Financial Status
 */
final class TransactionStatus
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const REFUNDED = 'refunded';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';
    public const FAILED = 'failed';

    private string $value;

    public function __construct(string $value)
    {
        $validStatuses = [
            self::PENDING,
            self::PAID,
            self::REFUNDED,
            self::CONFIRMED,
            self::CANCELLED,
            self::FAILED
        ];

        if (!in_array($value, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid transaction state: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, [self::REFUNDED, self::CANCELLED, self::FAILED], true);
    }

    public function isSuccess(): bool
    {
        return in_array($this->value, [self::PAID, self::CONFIRMED], true);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
