<?php

namespace App\Events\Reservation;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationConflictDetectedEvent
 *
 * RESERVATION_CORE Phase 3: Deterministic Conflict Detection
 *
 * Fired when a new reservation conflicts with:
 * - Existing PENDING or CONFIRMED reservations
 * - Existing availability blocks (maintenance, owner block, etc.)
 *
 * This event is for OBSERVABILITY only.
 * Override/remediation is NOT automatic in this phase.
 *
 * Usage:
 *   Listener → Dashboard notification
 *   Listener → Audit log
 *   Listener → Admin alert
 */
class ReservationConflictDetectedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly int $newReservationId,
        public readonly string $startDate,          // YYYY-MM-DD
        public readonly string $endDate,            // YYYY-MM-DD
        public readonly string $conflictType,       // 'RESERVATION_OVERLAP' | 'AVAILABILITY_CONFLICT' | 'MIXED'
        public readonly array $conflictingReservationIds,  // [id, id, ...]
        public readonly array $conflictDates,       // [{date, source, ...}, ...]
        public readonly ?int $highestPriority = null,      // Lowest number = highest priority
    ) {}
}
