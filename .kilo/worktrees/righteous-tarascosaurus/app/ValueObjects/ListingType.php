<?php

namespace App\ValueObjects;

/**
 * 🛡️ SAB Core v6.2 - Domain Hardening
 * Value Object for Listing Types
 */
final class ListingType
{
    public const SATILIK = 'satilik';
    public const KIRALIK = 'kiralik';
    public const YUKSEK_GETIRILI = 'yuksek_getirili';
    public const DEVREN = 'devren';

    private string $value;

    public function __construct(string $value)
    {
        $validTypes = [
            self::SATILIK,
            self::KIRALIK,
            self::YUKSEK_GETIRILI,
            self::DEVREN
        ];

        if (!in_array($value, $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid listing type: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isSale(): bool
    {
        return $this->value === self::SATILIK;
    }

    public function isRental(): bool
    {
        return $this->value === self::KIRALIK;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
