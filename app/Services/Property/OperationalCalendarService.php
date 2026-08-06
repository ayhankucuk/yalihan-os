<?php

namespace App\Services\Property;

use App\Contracts\Property\OperationalCalendarContract;
use App\DTOs\Property\CalendarEntry;
use App\DTOs\Property\CalendarView;
use App\Models\PropertyAvailability;
use Carbon\Carbon;

/**
 * OperationalCalendarService
 *
 * OPERATIONAL_CALENDAR — Implementation Sprint
 *
 * Read-only, deterministic operational calendar service.
 *
 * CONTRACT (ADR-004):
 * - READ-ONLY: never writes to the database
 * - Reads from PropertyAvailability projection (canonical SSOT)
 * - Deterministic: same input → same CalendarView
 * - Tenant-isolated: all queries scoped to tenant_id
 * - NO business rule ownership
 * - NO conflict resolution
 * - NO priority recalculation
 * - Presents canonical projection as-is
 *
 * Date semantics: [startDate, endDate) — inclusive-exclusive
 */
class OperationalCalendarService implements OperationalCalendarContract
{
    /**
     * Get the operational calendar for a property within a date range.
     *
     * @throws \InvalidArgumentException When startDate >= endDate
     */
    public function getCalendar(
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate
    ): CalendarView {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new \InvalidArgumentException(
                "startDate must be strictly before endDate. Got: {$startDate} >= {$endDate}"
            );
        }

        // Generate all dates in range [startDate, endDate)
        $allDates = $this->generateDateRange($start, $end);

        // Query PropertyAvailability projection — tenant-scoped, ordered by date
        $rows = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $allDates)
            ->orderBy('date')
            ->get(['date', 'is_available', 'origin', 'priority_tier', 'block_reason',
                   'reservation_id', 'source_system', 'projection_source'])
            ->keyBy(fn($r) => Carbon::parse($r->date)->format('Y-m-d'));

        // Build entries for EVERY date in range — dates without rows get AVAILABLE entry
        $entries = [];
        foreach ($allDates as $dateStr) {
            if (isset($rows[$dateStr])) {
                $row = $rows[$dateStr];
                if ($row->is_available) {
                    $entries[] = CalendarEntry::available($dateStr);
                } else {
                    $entries[] = CalendarEntry::fromAvailabilityRow(
                        date:          $dateStr,
                        origin:        $row->origin ?? 'system',
                        priorityTier:  (int) $row->priority_tier,
                        blockReason:   $row->block_reason,
                        reservationId: $row->reservation_id ? (int) $row->reservation_id : null,
                        sourceSystem:  $row->source_system ?? 'internal',
                        correlationId: null
                    );
                }
            } else {
                // No row in projection → date is available
                $entries[] = CalendarEntry::available($dateStr);
            }
        }

        return CalendarView::fromEntries(
            $tenantId,
            $propertyId,
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $entries
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
