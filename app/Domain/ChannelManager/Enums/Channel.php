<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * Channel — External platform identifier
 *
 * CHANNEL_MANAGER Wave 1: Foundation
 *
 * Represents external platforms that YALIHAN synchronizes with.
 * Each channel has a corresponding adapter implementing ChannelSyncContract.
 */
enum Channel: string
{
    case AIRBNB  = 'airbnb';
    case BOOKING = 'booking';
    case ICAL    = 'ical';
    case VRBO    = 'vrbo';
    case MANUAL   = 'manual'; // Edge case: admin manually imported

    /**
     * Get display name for the channel
     */
    public function label(): string
    {
        return match ($this) {
            self::AIRBNB  => 'Airbnb',
            self::BOOKING => 'Booking.com',
            self::ICAL    => 'iCal',
            self::VRBO    => 'VRBO',
            self::MANUAL  => 'Manual',
        };
    }

    /**
     * Get the priority tier for blocks from this channel
     * Used when importing external blocks into PropertyAvailability
     */
    public function priorityTier(): int
    {
        return match ($this) {
            self::MANUAL  => 3,  // TIER_OWNER_BLOCK
            self::AIRBNB,
            self::BOOKING,
            self::VRBO,
            self::ICAL    => 4,  // TIER_EXTERNAL_SYNC
        };
    }

    /**
     * Get the origin value for PropertyAvailability rows
     */
    public function originValue(): string
    {
        return $this->value;
    }

    /**
     * Check if this channel supports push (export)
     */
    public function supportsPush(): bool
    {
        return match ($this) {
            self::AIRBNB,
            self::BOOKING,
            self::VRBO,
            self::ICAL    => true,
            self::MANUAL  => false,
        };
    }

    /**
     * Check if this channel supports pull (import)
     */
    public function supportsPull(): bool
    {
        return match ($this) {
            self::AIRBNB,
            self::BOOKING,
            self::VRBO,
            self::ICAL    => true,
            self::MANUAL  => false,
        };
    }
}
