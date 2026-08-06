<?php

namespace App\DTOs\Property;

/**
 * CalendarView
 *
 * OPERATIONAL_CALENDAR — Implementation Sprint
 *
 * Immutable aggregated view of a property's operational calendar for a date range.
 *
 * Contains all calendar entries (one per day), summary statistics,
 * and source breakdown. Derived from PropertyAvailability projection.
 *
 * No business rule ownership — presents the canonical projection as-is.
 */
final class CalendarView
{
    /**
     * @param CalendarEntry[] $entries  One entry per day in [startDate, endDate)
     * @param array           $summary  ['reservation'=>N, 'owner'=>N, 'maintenance'=>N, ...]
     */
    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $propertyId,
        public readonly string $startDate,    // inclusive
        public readonly string $endDate,      // exclusive
        public readonly int    $totalNights,
        public readonly int    $availableNights,
        public readonly int    $blockedNights,
        public readonly array  $entries,
        public readonly array  $summary,
    ) {}

    /**
     * Factory: build CalendarView from an ordered array of CalendarEntry objects.
     *
     * @param CalendarEntry[] $entries
     */
    public static function fromEntries(
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate,
        array  $entries
    ): self {
        $totalNights     = count($entries);
        $availableNights = 0;
        $blockedNights   = 0;
        $summary         = [];

        foreach ($entries as $entry) {
            if ($entry->isAvailable) {
                $availableNights++;
            } else {
                $blockedNights++;
                $type = $entry->entryType;
                $summary[$type] = ($summary[$type] ?? 0) + 1;
            }
        }

        return new self(
            tenantId:        $tenantId,
            propertyId:      $propertyId,
            startDate:       $startDate,
            endDate:         $endDate,
            totalNights:     $totalNights,
            availableNights: $availableNights,
            blockedNights:   $blockedNights,
            entries:         $entries,
            summary:         $summary,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'       => $this->tenantId,
            'property_id'     => $this->propertyId,
            'start_date'      => $this->startDate,
            'end_date'        => $this->endDate,
            'total_nights'    => $this->totalNights,
            'available_nights' => $this->availableNights,
            'blocked_nights'  => $this->blockedNights,
            'summary'         => $this->summary,
            'entries'         => array_map(fn(CalendarEntry $e) => $e->toArray(), $this->entries),
        ];
    }
}
