<?php

namespace App\Application\ChannelManager\DTOs;

use Carbon\Carbon;

/**
 * SynchronizeAvailabilityCommand — Command DTO for availability sync
 *
 * Sprint 13 E03: Per-Channel Execution Isolation
 *
 * Represents an intent to synchronize availability for a property
 * across registered channels.
 *
 * Idempotency key = tenant_id + property_id + reservation_id + date_range + operation [+ channel]
 * Channel-aware idempotency: same business op produces independent executions per channel.
 */
readonly class SynchronizeAvailabilityCommand
{
    /**
     * @param int $tenantId
     * @param int $propertyId
     * @param int|null $reservationId Null for maintenance/manual blocks
     * @param string $operation 'block' | 'unblock' | 'sync'
     * @param array $dateRange ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @param bool $available Target availability state (false = blocked)
     * @param string|null $blockReason 'reservation' | 'maintenance' | 'manual'
     * @param string|null $idempotencyKey Override auto-generated idempotency key
     * @param string|null $correlationId Links this sync to a parent execution
     * @param string|null $channel E03: Per-channel execution discriminator (airbnb|booking|...)
     */
    public function __construct(
        public int $tenantId,
        public int $propertyId,
        public ?int $reservationId,
        public string $operation,
        public array $dateRange,
        public bool $available,
        public ?string $blockReason = 'reservation',
        public ?string $idempotencyKey = null,
        public ?string $correlationId = null,
        public ?string $channel = null,
    ) {}

    /**
     * Generate idempotency key from command properties
     *
     * E03: Channel-aware idempotency.
     * When $channel is set, it is included in the key so that the same business
     * operation produces distinct execution records per channel.
     * E.g., Booking and Airbnb executions for the same reservation are NOT duplicates.
     */
    public function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }

        $start = $this->dateRange['start'] ?? '';
        $end = $this->dateRange['end'] ?? '';
        $channel = $this->channel ?? '';

        return "{$this->tenantId}:{$this->propertyId}:{$this->reservationId}:{$this->operation}:{$start}:{$end}" . ($channel ? ":{$channel}" : '');
    }

    /**
     * Get all dates in range (inclusive)
     *
     * @return array<string> Array of 'Y-m-d' strings
     */
    public function getDates(): array
    {
        $start = Carbon::parse($this->dateRange['start']);
        $end = Carbon::parse($this->dateRange['end']);
        $dates = [];

        while ($start->lte($end)) {
            $dates[] = $start->format('Y-m-d');
            $start->addDay();
        }

        return $dates;
    }

    /**
     * Check if this is a blocking operation
     */
    public function isBlocking(): bool
    {
        return $this->operation === 'block' && $this->available === false;
    }

    /**
     * Validate command
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->tenantId <= 0) {
            throw new \InvalidArgumentException('tenantId must be positive');
        }

        if ($this->propertyId <= 0) {
            throw new \InvalidArgumentException('propertyId must be positive');
        }

        if (!in_array($this->operation, ['block', 'unblock', 'sync'], true)) {
            throw new \InvalidArgumentException("operation must be one of: block, unblock, sync");
        }

        if (empty($this->dateRange['start']) || empty($this->dateRange['end'])) {
            throw new \InvalidArgumentException('dateRange must have start and end');
        }

        if ($this->blockReason !== null && !in_array($this->blockReason, ['reservation', 'maintenance', 'manual'], true)) {
            throw new \InvalidArgumentException("blockReason must be one of: reservation, maintenance, manual");
        }
    }
}
