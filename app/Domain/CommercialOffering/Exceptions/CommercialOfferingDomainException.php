<?php

namespace App\Domain\CommercialOffering\Exceptions;

/**
 * Domain Exception for CommercialOffering business rule violations.
 */
class CommercialOfferingDomainException extends \DomainException
{
    public static function cannotActivateArchived(): self
    {
        return new self('Arşivlenmiş Commercial Offering aktive edilemez.');
    }

    public static function cannotTransition(string $from, string $to): self
    {
        return new self("'{$from}' durumundan '{$to}' durumuna geçiş yapılamaz.");
    }

    public static function duplicateActiveOffering(string $offeringType): self
    {
        return new self("Aynı tipte ({$offeringType}) aktif Commercial Offering zaten mevcut.");
    }

    public static function cannotDeleteActive(): self
    {
        return new self('Aktif Commercial Offering silinemez. Önce arşivleyin.');
    }

    public static function invalidDateRange(): self
    {
        return new self('Bitiş tarihi başlangıç tarihinden önce olamaz.');
    }
}
