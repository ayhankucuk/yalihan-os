<?php

namespace App\Application\ChannelManager\DTOs;

use Carbon\Carbon;

/**
 * SynchronizeAvailabilityCommand — Command DTO for availability sync
 *
 * Sprint 13 E02: Availability Synchronization
 *
 * Represents an intent to synchronize availability for a property
 * across registered channels.
 *
 * Idempotency key = tenant_id + property_id + reservation_id + date_range + operation
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
    ) {}

    /**
     * Generate idempotency key from command properties
     */
    public function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }

        $start = $this->dateRange['start'] ?? '';
        $end = $this->dateRange['end'] ?? '';

        return "{$this->tenantId}:{$this->propertyId}:{$this->reservationId}:{$this->operation}:{$start}:{$end}";
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
