<?php

namespace App\DTOs\Property;

/**
 * CalendarEntry
 *
 * OPERATIONAL_CALENDAR — Implementation Sprint
 *
 * Immutable representation of a single day in the operational calendar.
 *
 * Derived from PropertyAvailability projection — no business rule ownership.
 * Origin, priority, and type come directly from the canonical projection.
 *
 * Entry Types (origin → entryType mapping):
 * reservation  → CONFIRMED_RESERVATION
 * yazlik       → LEGACY_RESERVATION
 * owner        → OWNER_BLOCK
 * maintenance  → MAINTENANCE
 * airbnb       → AIRBNB_BLOCK
 * booking      → BOOKING_BLOCK
 * ical         → EXTERNAL_BLOCK
 * manual       → MANUAL_BLOCK
 * system       → SYSTEM_BLOCK
 * null/available → AVAILABLE
 */
final class CalendarEntry
{
    // Entry type constants
    public const TYPE_AVAILABLE             = 'AVAILABLE';
    public const TYPE_CONFIRMED_RESERVATION = 'CONFIRMED_RESERVATION';
    public const TYPE_LEGACY_RESERVATION    = 'LEGACY_RESERVATION';
    public const TYPE_OWNER_BLOCK           = 'OWNER_BLOCK';
    public const TYPE_MAINTENANCE           = 'MAINTENANCE';
    public const TYPE_AIRBNB_BLOCK          = 'AIRBNB_BLOCK';
    public const TYPE_BOOKING_BLOCK         = 'BOOKING_BLOCK';
    public const TYPE_EXTERNAL_BLOCK        = 'EXTERNAL_BLOCK';
    public const TYPE_MANUAL_BLOCK          = 'MANUAL_BLOCK';
    public const TYPE_SYSTEM_BLOCK          = 'SYSTEM_BLOCK';
    public const TYPE_UNKNOWN_BLOCK         = 'UNKNOWN_BLOCK';

    public function __construct(
        public readonly string  $date,
        public readonly bool    $isAvailable,
        public readonly string  $entryType,
        public readonly ?string $origin,
        public readonly int     $priorityTier,
        public readonly ?string $blockReason,
        public readonly ?int    $reservationId,
        public readonly string  $sourceSystem,
        public readonly ?string $correlationId = null, // Optional: for audit trail linkage
    ) {}

    /**
     * Factory: Available date entry.
     */
    public static function available(string $date): self
    {
        return new self(
            date:          $date,
            isAvailable:   true,
            entryType:     self::TYPE_AVAILABLE,
            origin:        null,
            priorityTier:  5, // TIER_HOLD_PENDING
            blockReason:   null,
            reservationId: null,
            sourceSystem:  'internal',
        );
    }

    /**
     * Factory: Blocked date entry from PropertyAvailability row.
     */
    public static function fromAvailabilityRow(
        string  $date,
        string  $origin,
        int     $priorityTier,
        ?string $blockReason,
        ?int    $reservationId,
        string  $sourceSystem,
        ?string $correlationId = null
    ): self {
        return new self(
            date:          $date,
            isAvailable:   false,
            entryType:     self::resolveEntryType($origin),
            origin:        $origin,
            priorityTier:  $priorityTier,
            blockReason:   $blockReason,
            reservationId: $reservationId,
            sourceSystem:  $sourceSystem,
            correlationId: $correlationId,
        );
    }

    /**
     * Map origin string to CalendarEntry type constant.
     * Single central mapping — UI/API must not perform their own mapping.
     */
    public static function resolveEntryType(string $origin): string
    {
        return match ($origin) {
            'reservation'                   => self::TYPE_CONFIRMED_RESERVATION,
            'yazlik'                        => self::TYPE_LEGACY_RESERVATION,
            'owner'                         => self::TYPE_OWNER_BLOCK,
            'maintenance'                   => self::TYPE_MAINTENANCE,
            'airbnb'                        => self::TYPE_AIRBNB_BLOCK,
            'booking'                       => self::TYPE_BOOKING_BLOCK,
            'ical', 'external'              => self::TYPE_EXTERNAL_BLOCK,
            'manual'                        => self::TYPE_MANUAL_BLOCK,
            'system'                        => self::TYPE_SYSTEM_BLOCK,
            default                         => self::TYPE_UNKNOWN_BLOCK,
        };
    }

    public function toArray(): array
    {
        return [
            'date'           => $this->date,
            'is_available'   => $this->isAvailable,
            'entry_type'     => $this->entryType,
            'origin'         => $this->origin,
            'priority_tier'  => $this->priorityTier,
            'block_reason'   => $this->blockReason,
            'reservation_id' => $this->reservationId,
            'source_system'  => $this->sourceSystem,
            'correlation_id' => $this->correlationId,
        ];
    }
}
