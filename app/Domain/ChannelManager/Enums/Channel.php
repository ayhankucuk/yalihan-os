<?php

namespace App\Domain\ChannelManager\Enums;

/**
 * Channel — Canonical external channel identifiers.
 *
 * CHANNEL_MANAGER_PROVIDER Wave 1 — ADR-006
 *
 * Used by ChannelSyncContract and all adapters to identify the OTA channel.
 * These are Yalihan's canonical channel names, NOT Channex platform names.
 *
 * ADR-006 invariants:
 * - Channel enum does NOT reference Channex — transport is channel-agnostic
 * - Each case maps to a display label for UI
 * - New channels require SAAB approval (ADR process)
 */
enum Channel: string
{
    case AIRBNB     = 'airbnb';
    case BOOKING    = 'booking';
    case EXPEDIA    = 'expedia';
    case TRIPADVISOR = 'tripadvisor';
    case HOMEAWAY   = 'homeaway';
    case VRBO       = 'vrbo';
    case SAHIBINDEN = 'sahibinden';
    case OTHER      = 'other';

    /**
     * Get human-readable display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::AIRBNB      => 'Airbnb',
            self::BOOKING     => 'Booking.com',
            self::EXPEDIA     => 'Expedia',
            self::TRIPADVISOR => 'TripAdvisor',
            self::HOMEAWAY    => 'HomeAway',
            self::VRBO        => 'VRBO',
            self::SAHIBINDEN  => 'Sahibinden',
            self::OTHER       => 'Diğer',
        };
    }

    /**
     * Check if this channel supports push sync (Yalihan → channel).
     */
    public function supportsPush(): bool
    {
        return match ($this) {
            self::AIRBNB,
            self::BOOKING,
            self::EXPEDIA,
            self::TRIPADVISOR,
            self::HOMEAWAY,
            self::VRBO => true,
            self::SAHIBINDEN,
            self::OTHER => false,
        };
    }

    /**
     * Check if this channel supports pull sync (channel → Yalihan).
     */
    public function supportsPull(): bool
    {
        return match ($this) {
            self::AIRBNB,
            self::BOOKING,
            self::EXPEDIA,
            self::TRIPADVISOR => true,
            self::HOMEAWAY,
            self::VRBO,
            self::SAHIBINDEN,
            self::OTHER => false,
        };
    }
}
