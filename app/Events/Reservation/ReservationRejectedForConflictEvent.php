<?php

namespace App\Events\Reservation;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationRejectedForConflictEvent
 *
 * CONFLICT_DETECTION Phase 3A — E03
 *
 * Application decision: a reservation was rejected because of a detected conflict.
 *
 * ADR-003 Separation:
 * - ConflictDetectionService fires ConflictDetectedEvent (detection layer)
 * - ReservationService fires THIS event (application layer)
 * - Detection and rejection are different responsibilities
 *
 * IMPORTANT: No personal data, no financial data.
 * Carries only identifiers and rejection metadata for audit/UX improvement.
 */
class ReservationRejectedForConflictEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $requestedStart,   // inclusive
        public readonly string $requestedEnd,     // exclusive [start, end)
        public readonly string $rejectionReason,  // 'conflict'
        public readonly int    $conflictCount,    // number of blocked dates
        public readonly string $correlationId,    // for audit tracing (links to ConflictDetectedEvent)
        public readonly \DateTimeImmutable $rejectedAt,
    ) {}
}
