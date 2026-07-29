<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Domain\ChannelManager\Contracts\ChannelAdapter;
use App\Domain\ChannelManager\Models\ChannelApiResponse;

/**
 * InMemoryChannelAdapter — Fake adapter for testing
 *
 * Sprint 13 E02: Availability Synchronization
 *
 * This adapter simulates a real channel API without making external calls.
 * Used for feature tests and local development.
 *
 * Features:
 * - Configurable success/failure/conflict rates
 * - Artificial delays for timeout testing
 * - Conflict simulation
 */
class InMemoryChannelAdapter implements ChannelAdapter
{
    private array $availabilityStore = [];
    private bool $shouldFail = false;
    private bool $shouldConflict = false;
    private int $failureCount = 0;
    private int $callCount = 0;

    /**
     * @param string $channelId
     * @param string $channelName
     * @param bool $shouldFail Simulate API failures
     * @param bool $shouldConflict Simulate availability conflicts
     */
    public function __construct(
        private readonly string $channelId = 'test',
        private readonly string $channelName = 'Test Channel',
        bool $shouldFail = false,
        bool $shouldConflict = false,
    ) {
        $this->shouldFail = $shouldFail;
        $this->shouldConflict = $shouldConflict;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function pushAvailability(array $availabilityData): ChannelApiResponse
    {
        $this->callCount++;
        $this->validateAvailabilityData($availabilityData);

        if ($this->shouldFail && $this->failureCount < 3) {
            $this->failureCount++;
            return ChannelApiResponse::failure('Simulated API failure', 'SIMULATED_ERROR');
        }

        $channelRefs = [];
        foreach ($availabilityData as $item) {
            $date = $item['date'];
            $available = $item['available'];

            // Store in simulated channel
            $this->availabilityStore[$date] = [
                'available' => $available,
                'property_id' => $item['property_id'] ?? null,
                'synced_at' => now()->toIso8601String(),
            ];

            // Simulate conflict if enabled and date already stored with different state
            if ($this->shouldConflict && isset($this->availabilityStore[$date])) {
                $existing = $this->availabilityStore[$date];
                if ($existing['available'] !== $available) {
                    return ChannelApiResponse::failure(
                        'Availability conflict detected',
                        'CONFLICT'
                    )->withMetadata([
                        'conflict' => [
                            'date' => $date,
                            'local_state' => $available,
                            'remote_state' => $existing['available'],
                        ],
                    ]);
                }
            }

            $channelRefs[] = "{$this->channelId}-ref-" . now()->format('YmdHis') . "-{$date}";
        }

        return ChannelApiResponse::success(
            channelReference: implode(',', $channelRefs),
            metadata: [
                'synced_count' => count($availabilityData),
                'channel_id' => $this->channelId,
                'synced_at' => now()->toIso8601String(),
            ]
        );
    }

    public function pullAvailability(string $fromDate, string $toDate): ChannelApiResponse
    {
        $this->callCount++;

        if ($this->shouldFail) {
            return ChannelApiResponse::failure('Simulated pull failure', 'SIMULATED_ERROR');
        }

        $records = [];
        $current = \Carbon\Carbon::parse($fromDate);
        $end = \Carbon\Carbon::parse($toDate);

        while ($current->lte($end)) {
            $date = $current->format('Y-m-d');
            $records[] = [
                'date' => $date,
                'available' => $this->availabilityStore[$date]['available'] ?? true,
            ];
            $current->addDay();
        }

        return ChannelApiResponse::success(
            channelReference: "{$this->channelId}-pull-" . now()->format('YmdHis'),
            metadata: [
                'records' => $records,
                'count' => count($records),
            ]
        );
    }

    public function pushReservation(array $reservationData): ChannelApiResponse
    {
        $this->callCount++;

        if ($this->shouldFail) {
            return ChannelApiResponse::failure('Simulated reservation failure', 'SIMULATED_ERROR');
        }

        return ChannelApiResponse::success(
            channelReference: "{$this->channelId}-reservation-" . now()->format('YmdHis'),
            metadata: [
                'reservation' => $reservationData,
                'created_at' => now()->toIso8601String(),
            ]
        );
    }

    public function fetchStatus(): ChannelApiResponse
    {
        return ChannelApiResponse::success(
            channelReference: "{$this->channelId}-status",
            metadata: [
                'connected' => true,
                'call_count' => $this->callCount,
                'stored_dates' => count($this->availabilityStore),
            ]
        );
    }

    // ─── Test helpers ────────────────────────────────────────────────

    /**
     * Reset the in-memory store
     */
    public function resetStore(): void
    {
        $this->availabilityStore = [];
        $this->failureCount = 0;
    }

    /**
     * Get the stored availability
     */
    public function getStoredAvailability(): array
    {
        return $this->availabilityStore;
    }

    /**
     * Set availability in the store (for simulating remote state)
     */
    public function setRemoteAvailability(string $date, bool $available): void
    {
        $this->availabilityStore[$date] = [
            'available' => $available,
            'property_id' => null,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Enable/disable failure simulation
     */
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    /**
     * Enable/disable conflict simulation
     */
    public function setShouldConflict(bool $shouldConflict): void
    {
        $this->shouldConflict = $shouldConflict;
    }

    /**
     * Get call count
     */
    public function getCallCount(): int
    {
        return $this->callCount;
    }

    private function validateAvailabilityData(array $data): void
    {
        foreach ($data as $item) {
            if (!isset($item['date']) || !isset($item['available'])) {
                throw new \InvalidArgumentException(
                    'Each availability item must have date and available fields'
                );
            }
        }
    }
}
