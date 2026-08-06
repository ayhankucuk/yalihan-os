<?php

namespace App\Services\Property;

use App\Contracts\Property\ConflictDetectionContract;
use App\DTOs\Property\ConflictResult;
use App\Models\PropertyAvailability;
use Carbon\Carbon;

/**
 * ConflictDetectionService
 *
 * CONFLICT_DETECTION Phase 3A — E01/E02
 *
 * Canonical, read-only, deterministic conflict detection service.
 *
 * CONTRACT (ADR-003):
 * - READ-ONLY: never writes to the database
 * - DETERMINISTIC: same input → same ConflictResult
 * - TENANT-ISOLATED: all queries scoped to tenant_id
 * - DATE SEMANTICS: inclusive-exclusive [startDate, endDate)
 * - ALL ORIGINS: owner, maintenance, external, reservation all produce conflicts
 * - PENDING: not projected → not a conflict (two-layer architecture)
 *
 * Detection ≠ Rejection ≠ Override
 */
class ConflictDetectionService implements ConflictDetectionContract
{
    /**
     * Detect availability conflicts for a date range on a property.
     *
     * Queries PropertyAvailability projection for blocked dates.
     * Returns ConflictResult with all blocking sources.
     *
     * @throws \InvalidArgumentException When startDate >= endDate
     */
    public function detect(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): ConflictResult {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new \InvalidArgumentException(
                "startDate must be strictly before endDate. Got: {$startDate} >= {$endDate}"
            );
        }

        // Generate date range: [startDate, endDate) — inclusive-exclusive
        $dates = $this->generateDateRange($start, $end);
        $checkedNights = count($dates);

        // Query PropertyAvailability projection for blocked dates.
        // Scope to tenant_id — cross-tenant data invisible.
        // Respect ALL origins (reservation, owner, maintenance, external, etc.)
        $query = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->where('is_available', false);

        // Optionally exclude a specific reservation's own blocks
        // (for re-confirmation idempotency — the reservation is being re-confirmed,
        // its own blocks must not count as a conflict against itself)
        if ($excludeReservationId !== null) {
            $query->where(function ($q) use ($excludeReservationId) {
                $q->where('reservation_id', '!=', $excludeReservationId)
                  ->orWhereNull('reservation_id');
            });
        }

        $blockedRows = $query
            ->orderBy('date')
            ->get(['date', 'origin', 'reservation_id', 'block_reason', 'priority_tier', 'source_system']);

        if ($blockedRows->isEmpty()) {
            return ConflictResult::noConflict(
                $tenantId,
                $propertyId,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $checkedNights
            );
        }

        // Build conflict dates array (deduplicated, ordered)
        $conflictDates   = [];
        $blockingSources = [];
        $seenDates       = [];

        foreach ($blockedRows as $row) {
            $dateStr = Carbon::parse($row->date)->format('Y-m-d');

            if (isset($seenDates[$dateStr])) {
                continue; // deduplicate per date
            }
            $seenDates[$dateStr] = true;

            $conflictDates[] = $dateStr;
            $blockingSources[] = [
                'date'          => $dateStr,
                'origin'        => $row->origin,
                'reservation_id' => $row->reservation_id,
                'block_reason'  => $row->block_reason,
                'priority_tier' => $row->priority_tier,
                'source_system' => $row->source_system,
            ];
        }

        return ConflictResult::conflict(
            $tenantId,
            $propertyId,
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $checkedNights,
            $conflictDates,
            $blockingSources
        );
    }

    /**
     * Generate date range array: [start, end) — inclusive-exclusive.
     *
     * @return string[]  YYYY-MM-DD formatted dates
     */
    private function generateDateRange(Carbon $start, Carbon $end): array
    {
        $dates  = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dates;
    }
}
