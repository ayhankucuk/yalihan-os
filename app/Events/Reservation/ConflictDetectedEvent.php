<?php

namespace App\Events\Reservation;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ConflictDetectedEvent
 *
 * CONFLICT_DETECTION Phase 3A — E03
 *
 * Domain fact: a conflict was detected during a reservation request.
 *
 * ADR-003 Separation:
 * - ConflictDetectionService fires this event (detection layer)
 * - ReservationService fires ReservationRejectedForConflictEvent (application layer)
 * - These are different events serving different purposes
 *
 * IMPORTANT: No personal data, no financial data in this event.
 * Carries only identifiers and conflict metadata for audit/alerting.
 */
class ConflictDetectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $requestedStart,   // inclusive
        public readonly string $requestedEnd,     // exclusive [start, end)
        public readonly array  $conflictDates,    // blocked date strings
        public readonly string $conflictingSource, // origin of the primary blocking record
        public readonly ?int   $conflictingRecordId, // reservation_id if applicable
        public readonly string $conflictType,     // 'reservation', 'owner_block', 'maintenance', etc.
        public readonly string $correlationId,    // for audit tracing
        public readonly \DateTimeImmutable $detectedAt,
    ) {}
}
