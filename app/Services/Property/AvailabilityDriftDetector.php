<?php

namespace App\Services\Property;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AvailabilityDriftDetector — RESERVATION_CORE Phase 2
 *
 * Detects divergence between the canonical reservation aggregate
 * (property_reservations) and the availability projection
 * (property_availabilities).
 *
 * A "drift" means: a CONFIRMED reservation exists in
 * property_reservations but its dates are NOT blocked in
 * property_availabilities, or vice versa (a blocked row exists
 * with no matching active reservation).
 *
 * Usage:
 *   $report = $detector->detect($tenantId, $propertyId, $start, $end);
 *   if ($report['has_drift']) { ... }
 *
 * This service is read-only — it never writes. Repair is done by
 * calling CanonicalAvailabilityService::rebuildAvailabilityProjection().
 */
class AvailabilityDriftDetector
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Detect projection drift for a property within a date range.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate  YYYY-MM-DD (inclusive)
     * @param string $endDate    YYYY-MM-DD (exclusive)
     * @return array {
     *   has_drift: bool,
     *   tenant_id: int,
     *   property_id: int,
     *   start_date: string,
     *   end_date: string,
     *   checked_nights: int,
     *   missing_blocks: array,   // reservation exists, no availability row
     *   phantom_blocks: array,   // availability row blocked, no matching reservation
     *   summary: string
     * }
     */
    public function detect(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new \Exception("Start date must be strictly before end date.");
        }

        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lt($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        // Build expected blocked-date set from CONFIRMED reservations.
        $confirmedReservations = PropertyReservation::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('start_date', '<', $end->format('Y-m-d'))
            ->where('end_date', '>', $start->format('Y-m-d'))
            ->where('reservation_state', ReservationState::CONFIRMED->value)
            ->whereNull('cancelled_at')
            ->get();

        $expectedBlocked = []; // date => reservation_id
        foreach ($confirmedReservations as $res) {
            $rStart = Carbon::parse($res->start_date);
            $rEnd   = Carbon::parse($res->end_date);
            foreach ($dates as $dateStr) {
                $d = Carbon::parse($dateStr);
                if ($d->gte($rStart) && $d->lt($rEnd)) {
                    // First confirmed reservation wins for overlap reporting
                    if (!isset($expectedBlocked[$dateStr])) {
                        $expectedBlocked[$dateStr] = $res->id;
                    }
                }
            }
        }

        // Build actual blocked-date set from availability projection.
        // Only reservation-origin rows count for drift — owner/maintenance
        // blocks are intentional and not derived from reservations.
        // Also includes legacy rows (origin NULL, source=internal, block_reason=reservation)
        // written before E2 origin field was added.
        $actualBlockedRows = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->where('is_available', false)
            ->where(function ($q) {
                $q->where('origin', PropertyAvailabilityContract::ORIGIN_RESERVATION)
                  ->orWhere(function ($q2) {
                      $q2->whereNull('origin')
                         ->where('source_system', 'internal')
                         ->where('block_reason', 'reservation');
                  });
            })
            ->get(['date', 'reservation_id']);

        $actualBlocked = []; // date string => ['date', 'reservation_id']
        foreach ($actualBlockedRows as $row) {
            $dateStr = Carbon::parse($row->date)->format('Y-m-d');
            if (!isset($actualBlocked[$dateStr])) {
                $actualBlocked[$dateStr] = [
                    'date'           => $dateStr,
                    'reservation_id' => $row->reservation_id,
                ];
            }
        }

        // Compute missing blocks: date should be blocked (reservation exists)
        // but no availability row found.
        $missingBlocks = [];
        foreach ($expectedBlocked as $dateStr => $reservationId) {
            if (!isset($actualBlocked[$dateStr])) {
                $missingBlocks[] = [
                    'date'           => $dateStr,
                    'reservation_id' => $reservationId,
                    'drift_type'     => 'MISSING_BLOCK',
                ];
            }
        }

        // Compute phantom blocks: availability row is blocked (reservation origin)
        // but no matching CONFIRMED reservation covers this date.
        $phantomBlocks = [];
        foreach ($actualBlocked as $dateStr => $availRow) {
            if (!isset($expectedBlocked[$dateStr])) {
                $phantomBlocks[] = [
                    'date'           => $dateStr,
                    'reservation_id' => $availRow['reservation_id'] ?? null,
                    'drift_type'     => 'PHANTOM_BLOCK',
                ];
            }
        }

        $hasDrift    = !empty($missingBlocks) || !empty($phantomBlocks);
        $totalDrifts = count($missingBlocks) + count($phantomBlocks);

        $summary = $hasDrift
            ? sprintf(
                'DRIFT DETECTED: %d missing block(s), %d phantom block(s) across %d checked night(s).',
                count($missingBlocks),
                count($phantomBlocks),
                count($dates)
            )
            : sprintf('No drift detected across %d checked night(s).', count($dates));

        return [
            'has_drift'      => $hasDrift,
            'tenant_id'      => $tenantId,
            'property_id'    => $propertyId,
            'start_date'     => $start->format('Y-m-d'),
            'end_date'       => $end->format('Y-m-d'),
            'checked_nights' => count($dates),
            'missing_blocks' => $missingBlocks,
            'phantom_blocks' => $phantomBlocks,
            'total_drifts'   => $totalDrifts,
            'summary'        => $summary,
        ];
    }

    /**
     * Detect drift across all properties for a tenant.
     *
     * Returns only properties with drift — clean properties are excluded.
     *
     * @param int    $tenantId
     * @param string $startDate  YYYY-MM-DD
     * @param string $endDate    YYYY-MM-DD
     * @return array {
     *   tenant_id: int,
     *   start_date: string,
     *   end_date: string,
     *   properties_checked: int,
     *   properties_with_drift: int,
     *   drift_reports: array
     * }
     */
    public function detectForTenant(int $tenantId, string $startDate, string $endDate): array
    {
        $propertyIds = DB::table('ilanlar')
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->all();

        $driftReports    = [];
        $checkedCount    = 0;
        $driftCount      = 0;

        foreach ($propertyIds as $propertyId) {
            $report = $this->detect($tenantId, (int) $propertyId, $startDate, $endDate);
            $checkedCount++;

            if ($report['has_drift']) {
                $driftReports[] = $report;
                $driftCount++;
            }
        }

        return [
            'tenant_id'             => $tenantId,
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'properties_checked'    => $checkedCount,
            'properties_with_drift' => $driftCount,
            'drift_reports'         => $driftReports,
        ];
    }
}
