<?php

namespace App\Domain\CommercialOffering\Enums;

/**
 * Commercial Offering Type.
 * Defines the commercial model for a property.
 */
enum OfferingType: string
{
    case SATILIK = 'SATILIK';
    case KIRALIK = 'KIRALIK';
    case SEZONLUK = 'SEZONLUK';

    public function label(): string
    {
        return match($this) {
            self::SATILIK => 'Satılık',
            self::KIRALIK => 'Kiralık',
            self::SEZONLUK => 'Sezonluk',
        };
    }

    public function isSale(): bool
    {
        return $this === self::SATILIK;
    }

    public function isRental(): bool
    {
        return $this === self::KIRALIK || $this === self::SEZONLUK;
    }
}
