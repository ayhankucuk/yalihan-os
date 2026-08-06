<?php

namespace App\Events\Reservation;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ConflictOverriddenEvent
 *
 * CONFLICT_DETECTION Phase 3C — Override Authorization
 *
 * Application decision event: an authorized actor has overridden a conflict
 * and proceeding with reservation creation despite the detected conflict.
 *
 * ADR-003 Separation:
 * - ConflictDetectedEvent    → domain fact (conflict exists)
 * - ReservationRejectedForConflictEvent → app decision (rejected)
 * - ConflictOverriddenEvent  → app decision (authorized override)
 *
 * IMPORTANT: No personal data, no financial data.
 */
class ConflictOverriddenEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $overrideId,       // Links to OverrideAuditRecord
        public readonly int    $actorUserId,      // Authorized actor who overrode
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $reason,           // Mandatory justification
        public readonly array  $conflictDates,    // Dates that had conflicts
        public readonly string $correlationId,    // Links to ConflictDetectedEvent
        public readonly \DateTimeImmutable $overriddenAt,
    ) {}
}
