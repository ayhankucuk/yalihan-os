<?php

namespace App\Services\Property;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AvailabilityQueryService — RESERVATION_CORE Phase 4
 *
 * Canonical availability query API.
 *
 * Mimari Kural:
 * - Query Service yalnızca PROJEKSİYON OKUR
 * - ConflictDetectionService karar üretmeye devam eder
 * - Reservation karar verme katmanı ayrıdır
 *
 * Bu ayrım CQRS benzeri read/write ayrımını korur.
 */
class AvailabilityQueryService
{
    public function __construct(
        private AvailabilityProjectionContract $projectionService,
        private AvailabilityTimelineService $timelineService
    ) {}

    /**
     * Get canonical availability for a property and date.
     *
     * Combines:
     * - Confirmed reservations
     * - Owner blocks
     * - Maintenance blocks
     * - External channel blocks
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $date YYYY-MM-DD
     * @return array
     */
    public function getCanonicalAvailability(int $tenantId, int $propertyId, string $date): array
    {
        // Get all blocks for this date
        $blocks = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('date', $date)
            ->get();

        if ($blocks->isEmpty()) {
            return $this->buildCleanState($tenantId, $propertyId, $date);
        }

        // Find highest priority (lowest number) blocking block
        $blockingBlock = $blocks
            ->filter(fn($b) => !$b->is_available)
            ->sortBy('priority_tier')
            ->first();

        if (!$blockingBlock) {
            return $this->buildCleanState($tenantId, $propertyId, $date);
        }

        return $this->buildBlockedState($tenantId, $propertyId, $date, $blockingBlock);
    }

    /**
     * Get canonical availability for a date range.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array Date => Canonical state
     */
    public function getCanonicalAvailabilityRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $dates = $this->generateDateRange($startDate, $endDate);
        $blocks = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->get()
            ->groupBy(fn($b) => Carbon::parse($b->date)->format('Y-m-d'));

        $result = [];
        foreach ($dates as $date) {
            $dateBlocks = $blocks[$date] ?? collect();

            $blockingBlock = $dateBlocks
                ->filter(fn($b) => !$b->is_available)
                ->sortBy('priority_tier')
                ->first();

            if ($blockingBlock) {
                $result[$date] = $this->buildBlockedState($tenantId, $propertyId, $date, $blockingBlock);
            } else {
                $result[$date] = $this->buildCleanState($tenantId, $propertyId, $date);
            }
        }

        return $result;
    }

    /**
     * Get aggregated availability summary for a property.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getAvailabilitySummary(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $range = $this->getCanonicalAvailabilityRange($tenantId, $propertyId, $startDate, $endDate);

        $available = 0;
        $blocked = 0;
        $bySource = [];

        foreach ($range as $date => $state) {
            if ($state['is_available']) {
                $available++;
            } else {
                $blocked++;
                $source = $state['blocking_source'] ?? 'unknown';
                $bySource[$source] = ($bySource[$source] ?? 0) + 1;
            }
        }

        return [
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => count($range),
            'available_days' => $available,
            'blocked_days' => $blocked,
            'availability_rate' => count($range) > 0
                ? round(($available / count($range)) * 100, 2)
                : 100,
            'blocks_by_source' => $bySource,
        ];
    }

    /**
     * Check if property is fully available for date range.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function isFullyAvailable(int $tenantId, int $propertyId, string $startDate, string $endDate): bool
    {
        $range = $this->getCanonicalAvailabilityRange($tenantId, $propertyId, $startDate, $endDate);

        foreach ($range as $state) {
            if (!$state['is_available']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get timeline for availability changes.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getTimeline(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        return $this->timelineService->getTimeline($tenantId, $propertyId, $startDate, $endDate);
    }

    /**
     * Get events for a reservation.
     *
     * @param int $tenantId
     * @param int $reservationId
     * @return array
     */
    public function getReservationEvents(int $tenantId, int $reservationId): array
    {
        return $this->timelineService->getEventsForReservation($tenantId, $reservationId);
    }

    /*=======================================================================
     * Private Helpers
     *=======================================================================*/

    private function buildCleanState(int $tenantId, int $propertyId, string $date): array
    {
        return [
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'date' => $date,
            'is_available' => true,
            'blocking_source' => null,
            'blocking_reason' => null,
            'priority_tier' => null,
            'reservation_id' => null,
            'origin' => null,
        ];
    }

    private function buildBlockedState(int $tenantId, int $propertyId, string $date, $blockingBlock): array
    {
        return [
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'date' => $date,
            'is_available' => false,
            'blocking_source' => $blockingBlock->origin,
            'blocking_reason' => $blockingBlock->block_reason,
            'priority_tier' => $blockingBlock->priority_tier,
            'reservation_id' => $blockingBlock->reservation_id,
            'origin' => $blockingBlock->origin,
        ];
    }

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
