<?php

namespace App\Infrastructure\ChannelManager\Channex;

use App\Infrastructure\ChannelManager\Channex\Exceptions\ChannexMalformedDataException;

/**
 * ChannexAvailabilityMapper — Maps canonical availability to Channex API format.
 *
 * CHANNEL_MANAGER_CHANNEX_WAVE1 — Implementation
 *
 * TRANSPORT-ONLY: No domain logic. Pure data transformation.
 *
 * Input format (canonical):
 * [
 *   ['date' => '2026-08-15', 'available' => true],
 *   ['date' => '2026-08-16', 'available' => false],
 *   ...
 * ]
 *
 * Output format (Channex API):
 * {
 *   "data": [
 *     {"date": "2026-08-15", "available": 1},
 *     {"date": "2026-08-16", "available": 0},
 *   ]
 * }
 *
 * @see https://docs.channex.io/api-v.1-documentation/availability/
 */
class ChannexAvailabilityMapper
{
    private const MAX_DATES_PER_BATCH = 100;

    /**
     * Convert canonical availability data to Channex API payload.
     *
     * @param array $availabilityData Canonical availability: [['date' => 'Y-m-d', 'available' => bool], ...]
     * @return array Channex-formatted payload: ['data' => [...]]
     *
     * @throws ChannexMalformedDataException if data is invalid
     */
    public function toChannexAvailabilityPayload(array $availabilityData): array
    {
        if (empty($availabilityData)) {
            return ['data' => []];
        }

        $data = [];
        $errors = [];

        foreach ($availabilityData as $index => $item) {
            // Validate structure
            if (!isset($item['date'])) {
                $errors[] = "Item {$index}: missing 'date' field";
                continue;
            }

            if (!isset($item['available'])) {
                $errors[] = "Item {$index}: missing 'available' field";
                continue;
            }

            // Validate date format
            if (!$this->isValidDate($item['date'])) {
                $errors[] = "Item {$index}: invalid date format '{$item['date']}' (expected Y-m-d)";
                continue;
            }

            // Validate available type
            if (!is_bool($item['available']) && !is_int($item['available'])) {
                $errors[] = "Item {$index}: 'available' must be bool or int, got " . gettype($item['available']);
                continue;
            }

            // Build Channex format: available is 1/0 integer
            $data[] = [
                'date'      => $item['date'],
                'available' => $item['available'] ? 1 : 0,
            ];
        }

        // If all items failed validation, reject entire payload
        if (empty($data) && !empty($errors)) {
            throw new ChannexMalformedDataException(
                'All availability items failed validation: ' . implode('; ', array_slice($errors, 0, 5))
            );
        }

        // Log warnings for partial validation failures
        if (count($errors) > 0) {
            $validCount = count($data);
            $invalidCount = count($errors);
            // Could log here if needed for debugging
        }

        // Batch if exceeds max
        if (count($data) > self::MAX_DATES_PER_BATCH) {
            $data = array_slice($data, 0, self::MAX_DATES_PER_BATCH);
        }

        return ['data' => $data];
    }

    /**
     * Convert Channex availability response to canonical format.
     *
     * @param array $channexResponse Raw Channex API response
     * @return array Canonical format: [['date' => 'Y-m-d', 'available' => bool], ...]
     */
    public function fromChannexAvailabilityResponse(array $channexResponse): array
    {
        if (!isset($channexResponse['data']) || !is_array($channexResponse['data'])) {
            return [];
        }

        $canonical = [];
        foreach ($channexResponse['data'] as $item) {
            if (!isset($item['date'])) {
                continue;
            }

            // Channex: available is 1/0, canonical: bool
            $available = isset($item['available']) ? (int) $item['available'] === 1 : true;

            $canonical[] = [
                'date'      => $item['date'],
                'available' => $available,
                'booked'    => (int) ($item['booked'] ?? 0),
            ];
        }

        return $canonical;
    }

    /**
     * Validate Y-m-d date format.
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Build batched payload for large date ranges.
     *
     * Returns array of payloads, each within MAX_DATES_PER_BATCH limit.
     *
     * @param array $availabilityData Canonical availability data
     * @return array Array of Channex payloads
     */
    public function toBatchedChannexPayload(array $availabilityData): array
    {
        if (empty($availabilityData)) {
            return [['data' => []]];
        }

        $payloads = [];
        $chunks = array_chunk($availabilityData, self::MAX_DATES_PER_BATCH);

        foreach ($chunks as $chunk) {
            $payloads[] = $this->toChannexAvailabilityPayload($chunk);
        }

        return $payloads;
    }
}
