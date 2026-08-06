<?php

namespace App\Services\Reservation;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Contracts\Reservation\ConflictDetectionServiceContract;
use App\Contracts\Reservation\ConflictReport;
use App\Contracts\Reservation\ReservationConflictException;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ConflictDetectionService
 *
 * RESERVATION_CORE Phase 3: Deterministic Conflict Detection
 *
 * Unified conflict detection API combining:
 * - Reservation overlap detection (PENDING + CONFIRMED)
 * - Availability priority conflict detection
 *
 * SAAB Priority Matrix (v2):
 * Priority | Source              | Value | Overridable
 * ---------|---------------------|-------|-------------
 * 1        | Maintenance        | 1     | NO (never)
 * 2        | Owner Block        | 2     | admin/owner
 * 3        | Confirmed Reserve | 3     | admin
 * 4        | External Channel   | 4     | admin/channel
 * 5        | Pending Hold      | 5     | always
 *
 * Date Semantics: [start, end) exclusive
 * - start_date: check-in (dahil)
 * - end_date: check-out (hariç)
 *
 * State Participation:
 * - PENDING + CONFIRMED → block
 * - CANCELLED + COMPLETED + NO_SHOW → no block
 */
class ConflictDetectionService implements ConflictDetectionServiceContract
{
    use GuardsAgentWrites;

    public function __construct(
        private PropertyAvailabilityContract $availabilityService
    ) {}

    /**
     * Check if ANY conflict exists for date range.
     */
    public function hasConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): bool {
        $report = $this->detectConflicts($tenantId, $propertyId, $startDate, $endDate, $excludeReservationId);
        return $report->hasConflict;
    }

    /**
     * Get detailed conflict report.
     */
    public function detectConflicts(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): ConflictReport {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        // Validate date range
        if ($start->gte($end)) {
            return ConflictReport::clean($tenantId, $propertyId, $startDate, $endDate);
        }

        // Generate date array [start, end)
        $dates = $this->generateDateRange($startDate, $endDate);

        // 1. Check reservation overlaps
        $conflictingReservations = $this->findConflictingReservations(
            $tenantId, $propertyId, $startDate, $endDate, $excludeReservationId
        );

        // 2. Check availability priority conflicts
        $conflictingBlocks = $this->findConflictingBlocks(
            $tenantId, $propertyId, $dates
        );

        // Merge conflict dates
        $conflictDates = $this->computeConflictDates(
            $dates,
            $conflictingReservations,
            $conflictingBlocks,
            $startDate,
            $endDate
        );

        $hasConflict = !empty($conflictingReservations) || !empty($conflictingBlocks);
        $conflictType = $this->determineConflictType($conflictingReservations, $conflictingBlocks);
        $highestPriority = $this->computeHighestPriority($conflictingBlocks);

        return new ConflictReport(
            hasConflict: $hasConflict,
            tenantId: $tenantId,
            propertyId: $propertyId,
            startDate: $startDate,
            endDate: $endDate,
            conflictingReservations: $conflictingReservations,
            conflictingBlocks: $conflictingBlocks,
            conflictDates: $conflictDates,
            conflictType: $conflictType,
            highestPriority: $highestPriority
        );
    }

    /**
     * Validate no conflicts exist (throws on conflict).
     */
    public function validateNoConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): void {
        $report = $this->detectConflicts($tenantId, $propertyId, $startDate, $endDate, $excludeReservationId);

        if ($report->hasConflict) {
            throw new ReservationConflictException(
                $this->buildErrorMessage($report),
                $report
            );
        }
    }

    /*=======================================================================
     * Private Helpers
     *=======================================================================*/

    /**
     * Find reservations that overlap with the date range.
     *
     * Overlap rule: [s1, e1) overlaps [s2, e2) iff s1 < e2 AND s2 < e1
     */
    private function findConflictingReservations(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId
    ): array {
        // Terminal states that don't block
        $terminalStates = array_map(
            fn(ReservationState $s) => $s->value,
            array_filter(ReservationState::cases(), fn(ReservationState $s) => $s->isTerminal())
        );

        $query = PropertyReservation::where('property_id', $propertyId)
            ->where('tenant_id', $tenantId)
            // Overlap: existing.start < new.end AND new.start < existing.end
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            // Only PENDING and CONFIRMED block
            ->whereNotIn('reservation_state', $terminalStates);

        if ($excludeReservationId !== null) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->get()->all();
    }

    /**
     * Find availability blocks that would conflict with a new reservation.
     *
     * Priority conflict: existing.priority_tier <= reservation.priority_tier
     * (Lower number = higher priority)
     */
    private function findConflictingBlocks(
        int $tenantId,
        int $propertyId,
        array $dates
    ): array {
        // A new reservation has PRIORITY_RESERVATION (3)
        $incomingPriority = self::PRIORITY_RESERVATION;

        return PropertyAvailability::where('property_id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->whereIn('date', $dates)
            ->where('is_available', false)
            // Conflict: existing tier <= incoming tier
            // Maintenance (1) blocks Reservation (3) → 1 <= 3 = true
            // Owner (2) blocks Reservation (3) → 2 <= 3 = true
            // External (4) blocked by Reservation (3) → 4 <= 3 = false
            // Pending (5) blocked by Reservation (3) → 5 <= 3 = false
            ->where('priority_tier', '<=', $incomingPriority)
            ->get()
            ->all();
    }

    /**
     * Compute list of conflict dates from both sources.
     */
    private function computeConflictDates(
        array $allDates,
        array $conflictingReservations,
        array $conflictingBlocks,
        string $startDate,
        string $endDate
    ): array {
        $conflictDates = [];

        // From reservations: dates covered by any conflicting reservation
        foreach ($conflictingReservations as $res) {
            $resStart = Carbon::parse($res->start_date);
            $resEnd   = Carbon::parse($res->end_date);

            foreach ($allDates as $dateStr) {
                $d = Carbon::parse($dateStr);
                if ($d->gte($resStart) && $d->lt($resEnd)) {
                    $conflictDates[$dateStr] = [
                        'date' => $dateStr,
                        'source' => 'reservation',
                        'reservation_id' => $res->id,
                    ];
                }
            }
        }

        // From availability blocks
        foreach ($conflictingBlocks as $block) {
            $dateStr = Carbon::parse($block->date)->format('Y-m-d');
            if (!isset($conflictDates[$dateStr])) {
                $conflictDates[$dateStr] = [
                    'date' => $dateStr,
                    'source' => 'availability',
                    'block_id' => $block->id,
                    'origin' => $block->origin,
                    'priority_tier' => $block->priority_tier,
                ];
            }
        }

        return array_values($conflictDates);
    }

    /**
     * Determine the type of conflict.
     */
    private function determineConflictType(array $reservations, array $blocks): ?string
    {
        if (!empty($reservations) && !empty($blocks)) {
            return 'MIXED';
        }
        if (!empty($reservations)) {
            return 'RESERVATION_OVERLAP';
        }
        if (!empty($blocks)) {
            return 'AVAILABILITY_CONFLICT';
        }
        return null;
    }

    /**
     * Compute highest (lowest number) priority among conflicting blocks.
     */
    private function computeHighestPriority(array $blocks): ?int
    {
        if (empty($blocks)) {
            return null;
        }

        $min = PHP_INT_MAX;
        foreach ($blocks as $block) {
            if ($block->priority_tier < $min) {
                $min = $block->priority_tier;
            }
        }

        return $min === PHP_INT_MAX ? null : $min;
    }

    /**
     * Build user-friendly error message.
     */
    private function buildErrorMessage(ConflictReport $report): string
    {
        $parts = [];

        if (!empty($report->conflictingReservations)) {
            $count = count($report->conflictingReservations);
            $parts[] = "{$count} çakışan rezervasyon";
        }

        if (!empty($report->conflictingBlocks)) {
            $origins = array_unique(array_map(fn($b) => $b->origin ?? 'unknown', $report->conflictingBlocks));
            $parts[] = "bloke: " . implode(', ', $origins);
        }

        return "Çakışma tespit edildi: " . implode('; ', $parts) . ". Tarih aralığı: {$report->startDate} - {$report->endDate}";
    }

    /**
     * Generate date range [start, end).
     */
    private function generateDateRange(string $startDate, string $endDate): array
    {
        $dates = [];
        $current = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }
}
