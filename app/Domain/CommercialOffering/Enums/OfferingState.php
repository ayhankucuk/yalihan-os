<?php

namespace App\Domain\CommercialOffering\Enums;

/**
 * Commercial Offering State.
 * Defines the lifecycle states of a commercial offering.
 */
enum OfferingState: string
{
    case DRAFT = 'DRAFT';
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';

    public function canTransitionTo(self $target): bool
    {
        return match($this) {
            self::DRAFT => in_array($target, [self::ACTIVE, self::ARCHIVED]),
            self::ACTIVE => in_array($target, [self::ARCHIVED]),
            self::ARCHIVED => false, // Terminal state
        };
    }

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Taslak',
            self::ACTIVE => 'Aktif',
            self::ARCHIVED => 'Arşivlenmiş',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }
}
