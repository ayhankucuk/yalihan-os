<?php

namespace App\DTOs\Property;

/**
 * ConflictResult
 *
 * CONFLICT_DETECTION Phase 3A — E01
 *
 * Immutable value object returned by ConflictDetectionContract::detect().
 *
 * This DTO carries the full result of a conflict check. It is:
 * - Read-only (readonly properties)
 * - Serializable
 * - Free of personal/financial data
 *
 * SAAB ADR-003: Detection result — not a rejection, not an override decision.
 */
final class ConflictResult
{
    /**
     * @param bool   $hasConflict       Whether any conflicts were found
     * @param int    $tenantId          Tenant that performed the check
     * @param int    $propertyId        Property that was checked
     * @param string $startDate         Inclusive start date (YYYY-MM-DD)
     * @param string $endDate           Exclusive end date (YYYY-MM-DD)
     * @param array  $conflictDates     Blocked date strings e.g. ['2026-08-10', '2026-08-11']
     * @param array  $blockingSources   [{date, origin, reservation_id, block_reason, priority_tier}]
     * @param int    $checkedNights     Total nights in the requested range
     * @param string $summary           Human-readable summary
     */
    public function __construct(
        public readonly bool   $hasConflict,
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array  $conflictDates,
        public readonly array  $blockingSources,
        public readonly int    $checkedNights,
        public readonly string $summary,
    ) {}

    /**
     * Factory: no conflict found.
     */
    public static function noConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        int $checkedNights
    ): self {
        return new self(
            hasConflict:    false,
            tenantId:       $tenantId,
            propertyId:     $propertyId,
            startDate:      $startDate,
            endDate:        $endDate,
            conflictDates:  [],
            blockingSources: [],
            checkedNights:  $checkedNights,
            summary:        sprintf('No conflict detected across %d checked night(s).', $checkedNights),
        );
    }

    /**
     * Factory: conflict found.
     *
     * @param array $conflictDates   Blocked date strings
     * @param array $blockingSources [{date, origin, reservation_id, block_reason, priority_tier}]
     */
    public static function conflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        int $checkedNights,
        array $conflictDates,
        array $blockingSources
    ): self {
        $count = count($conflictDates);

        return new self(
            hasConflict:    true,
            tenantId:       $tenantId,
            propertyId:     $propertyId,
            startDate:      $startDate,
            endDate:        $endDate,
            conflictDates:  $conflictDates,
            blockingSources: $blockingSources,
            checkedNights:  $checkedNights,
            summary:        sprintf(
                'CONFLICT DETECTED: %d blocked date(s) out of %d requested night(s). First conflict: %s.',
                $count,
                $checkedNights,
                $conflictDates[0] ?? 'unknown'
            ),
        );
    }

    /**
     * Serialize to array (for logging, events, API responses).
     * Does NOT include personal/financial data.
     */
    public function toArray(): array
    {
        return [
            'has_conflict'     => $this->hasConflict,
            'tenant_id'        => $this->tenantId,
            'property_id'      => $this->propertyId,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate,
            'conflict_dates'   => $this->conflictDates,
            'blocking_sources' => $this->blockingSources,
            'checked_nights'   => $this->checkedNights,
            'summary'          => $this->summary,
        ];
    }
}
