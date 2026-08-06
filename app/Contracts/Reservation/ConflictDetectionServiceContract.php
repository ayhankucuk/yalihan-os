<?php

namespace App\Contracts\Reservation;

/**
 * ConflictDetectionServiceContract
 *
 * RESERVATION_CORE Phase 3: Deterministic Conflict Detection
 *
 * Unified conflict detection API that combines:
 * - Reservation overlap detection
 * - Availability priority conflict detection
 *
 * SAAB Phase 3 Authorization: DISCOVERY APPROVED, IMPLEMENTATION AUTHORIZED
 *
 * Date Semantics: [start, end) exclusive
 * - start_date günü dahil (check-in)
 * - end_date günü hariç (check-out)
 * - Aynı gün çıkış/yeni giriş = ÇAKIŞMA YOK
 *
 * Priority Matrix (SAAB v2):
 * Priority | Source              | Value
 * ---------|---------------------|-------
 * 1        | Maintenance        | 1 (highest)
 * 2        | Owner Block        | 2
 * 3        | Confirmed Reserve  | 3
 * 4        | External Channel   | 4
 * 5        | Pending Hold      | 5 (lowest)
 *
 * Conflict Rule: priority_tier <= incoming_priority_tier → CONFLICT
 * (Lower number = higher priority, blocks incoming)
 *
 * State Participation:
 * - PENDING + CONFIRMED → block availability
 * - CANCELLED + COMPLETED + NO_SHOW → no block
 */
interface ConflictDetectionServiceContract
{
    /*=======================================================================
     * Detection
     *=======================================================================*/

    /**
     * Check if there is ANY conflict for a date range.
     *
     * Combines:
     * - Reservation overlap (PENDING + CONFIRMED)
     * - Availability priority conflict (blocks with higher/equal priority)
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate YYYY-MM-DD (check-in, inclusive)
     * @param string $endDate   YYYY-MM-DD (check-out, exclusive)
     * @param int|null $excludeReservationId Skip this reservation (for updates)
     * @return bool True = conflict exists
     */
    public function hasConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): bool;

    /**
     * Get detailed conflict report for a date range.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeReservationId
     * @return ConflictReport
     */
    public function detectConflicts(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): ConflictReport;

    /*=======================================================================
     * Validation (throws on conflict)
     *=======================================================================*/

    /**
     * Validate date range has no conflicts.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate
     * @param int|null $endDate
     * @param int|null $excludeReservationId
     * @throws ReservationConflictException
     */
    public function validateNoConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): void;

    /*=======================================================================
     * Priority Constants (SAAB v2)
     *=======================================================================*/

    public const PRIORITY_MAINTENANCE = 1;
    public const PRIORITY_OWNER_BLOCK = 2;
    public const PRIORITY_RESERVATION = 3;
    public const PRIORITY_EXTERNAL = 4;
    public const PRIORITY_PENDING = 5;
}

/**
 * Immutable conflict detection result.
 */
class ConflictReport
{
    public function __construct(
        public readonly bool $hasConflict,
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array $conflictingReservations = [], // PropertyReservation[]
        public readonly array $conflictingBlocks = [],        // PropertyAvailability[]
        public readonly array $conflictDates = [],
        public readonly ?string $conflictType = null,        // 'RESERVATION_OVERLAP' | 'AVAILABILITY_CONFLICT' | 'MIXED'
        public readonly ?int $highestPriority = null,        // Lowest number = highest priority
    ) {}

    public static function clean(int $tenantId, int $propertyId, string $startDate, string $endDate): self
    {
        return new self(
            hasConflict: false,
            tenantId: $tenantId,
            propertyId: $propertyId,
            startDate: $startDate,
            endDate: $endDate,
            conflictingReservations: [],
            conflictingBlocks: [],
            conflictDates: [],
            conflictType: null,
            highestPriority: null
        );
    }

    public function toArray(): array
    {
        return [
            'has_conflict' => $this->hasConflict,
            'tenant_id' => $this->tenantId,
            'property_id' => $this->propertyId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'conflict_type' => $this->conflictType,
            'conflict_dates' => $this->conflictDates,
            'conflicting_reservations' => array_map(
                fn($r) => ['id' => $r->id, 'state' => $r->reservation_state->value ?? $r->reservation_state],
                $this->conflictingReservations
            ),
            'conflicting_blocks' => array_map(
                fn($b) => [
                    'id' => $b->id,
                    'date' => $b->date,
                    'origin' => $b->origin,
                    'priority_tier' => $b->priority_tier,
                ],
                $this->conflictingBlocks
            ),
            'highest_priority' => $this->highestPriority,
        ];
    }
}

/**
 * Exception thrown when a reservation conflict is detected.
 */
class ReservationConflictException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ConflictReport $report
    ) {
        parent::__construct($message);
    }

    public function getConflictDates(): array
    {
        return $this->report->conflictDates;
    }

    public function getConflictType(): ?string
    {
        return $this->report->conflictType;
    }
}
