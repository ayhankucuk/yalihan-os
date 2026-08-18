<?php

namespace App\Infrastructure\ChannelManager\Airbnb;

use App\Infrastructure\ChannelManager\Airbnb\DTOs\AirbnbAvailabilityRequest;

/**
 * AirbnbAvailabilityMapper — Maps canonical to Airbnb format
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Responsibilities:
 * - Convert canonical date ranges to Airbnb payload format
 * - Map canonical availability (bool) to Airbnb format ("t"/"f")
 * - Include idempotency keys
 *
 * Does NOT:
 * - Make any domain decisions
 * - Access external systems
 * - Store credentials
 */
class AirbnbAvailabilityMapper
{
    /**
     * Map canonical availability to Airbnb request
     *
     * @param string $airbnbListingId External Airbnb listing ID
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @param bool $available Canonical availability
     * @param string $idempotencyKey Idempotency key for this sync operation
     * @param string|null $sourceTimestamp ISO timestamp of canonical change
     * @return AirbnbAvailabilityRequest
     */
    public function mapAvailability(
        string $airbnbListingId,
        string $startDate,
        string $endDate,
        bool $available,
        string $idempotencyKey,
        ?string $sourceTimestamp = null,
    ): AirbnbAvailabilityRequest {
        return new AirbnbAvailabilityRequest(
            listingId: $airbnbListingId,
            startDate: $startDate,
            endDate: $endDate,
            available: $available,
            idempotencyKey: $idempotencyKey,
            source: $sourceTimestamp ?? now()->toIso8601String(),
        );
    }

    /**
     * Map a batch of canonical date availabilities to Airbnb requests
     *
     * @param string $airbnbListingId
     * @param array<string, bool> $dateAvailabilities ['Y-m-d' => bool]
     * @param string $idempotencyKeyPrefix
     * @return array<AirbnbAvailabilityRequest>
     */
    public function mapBatch(
        string $airbnbListingId,
        array $dateAvailabilities,
        string $idempotencyKeyPrefix,
    ): array {
        $requests = [];

        // Group consecutive dates with same availability into ranges
        $ranges = $this->groupConsecutiveDates($dateAvailabilities);

        foreach ($ranges as $range) {
            $dateKey = $range['start'];
            $idempotencyKey = "{$idempotencyKeyPrefix}:{$range['start']}:{$range['end']}";

            $requests[] = $this->mapAvailability(
                airbnbListingId: $airbnbListingId,
                startDate: $range['start'],
                endDate: $range['end'],
                available: $range['available'],
                idempotencyKey: $idempotencyKey,
            );
        }

        return $requests;
    }

    /**
     * Group consecutive dates with the same availability into ranges
     *
     * @param array<string, bool> $dateAvailabilities
     * @return array<array{start: string, end: string, available: bool}>
     */
    private function groupConsecutiveDates(array $dateAvailabilities): array
    {
        if (empty($dateAvailabilities)) {
            return [];
        }

        ksort($dateAvailabilities);
        $ranges = [];
        $currentRange = null;

        $previousDate = null;
        $previousAvailable = null;

        foreach ($dateAvailabilities as $date => $available) {
            if ($previousDate === null) {
                // First date
                $currentRange = [
                    'start' => $date,
                    'end' => $date,
                    'available' => $available,
                ];
            } elseif ($date === date('Y-m-d', strtotime($previousDate . ' +1 day'))
                && $available === $previousAvailable
            ) {
                // Consecutive date with same availability
                $currentRange['end'] = $date;
            } else {
                // Non-consecutive or different availability — close current range
                $ranges[] = $currentRange;
                $currentRange = [
                    'start' => $date,
                    'end' => $date,
                    'available' => $available,
                ];
            }

            $previousDate = $date;
            $previousAvailable = $available;
        }

        if ($currentRange !== null) {
            $ranges[] = $currentRange;
        }

        return $ranges;
    }
}
