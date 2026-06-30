<?php

namespace App\Enums;

use App\Enums\IlanDurumu;

/**
 * UPS Feature Lifecycle States
 *
 * Governance lifecycle for feature management
 *
 * Context7: Centralized enum for lifecycle validation
 */
enum UpsFeatureLifecycle: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case DEPRECATED = 'deprecated';
    case ARCHIVED = 'archived';

    /**
     * Get all lifecycle values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get lifecycle label for UI display
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Taslak',
            self::ACTIVE => IlanDurumu::YAYINDA->value,
            self::DEPRECATED => 'Kullanımdan Kaldırıldı',
            self::ARCHIVED => 'Arşivlendi',
        };
    }

    /**
     * Get lifecycle badge color (Tailwind)
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::DEPRECATED => 'yellow',
            self::ARCHIVED => 'red',
        };
    }

    /**
     * Check if feature can be assigned in this lifecycle state
     */
    public function isAssignable(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            self::DEPRECATED => true, // Allowed with warning
            self::DRAFT => false,
            self::ARCHIVED => false,
        };
    }

    /**
     * Get valid transitions from current state
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::ACTIVE],
            self::ACTIVE => [self::DEPRECATED, self::ARCHIVED],
            self::DEPRECATED => [self::ACTIVE, self::ARCHIVED],
            self::ARCHIVED => [], // One-way (intentional)
        };
    }

    /**
     * Check if transition is allowed
     */
    public function canTransitionTo(UpsFeatureLifecycle $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
