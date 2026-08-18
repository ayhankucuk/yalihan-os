<?php

namespace App\Application\ChannelManager\DTOs;

use Carbon\Carbon;

/**
 * SynchronizeRatesCommand — Command DTO for rate synchronization.
 *
 * Sprint 4.14 — Booking.com Provider Wave 5
 *
 * Represents an intent to push rates to external channels for a property.
 *
 * Idempotency key = tenant_id + property_id + from_date + to_date
 */
readonly class SynchronizeRatesCommand
{
    /**
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $fromDate  Y-m-d inclusive
     * @param string $toDate    Y-m-d exclusive
     * @param string|null $idempotencyKey
     * @param string|null $correlationId
     */
    public function __construct(
        public int    $tenantId,
        public int    $propertyId,
        public string $fromDate,
        public string $toDate,
        public ?string $idempotencyKey = null,
        public ?string $correlationId = null,
    ) {}

    public function getIdempotencyKey(): string
    {
        if ($this->idempotencyKey) {
            return $this->idempotencyKey;
        }
        return "rate_sync:{$this->tenantId}:{$this->propertyId}:{$this->fromDate}:{$this->toDate}";
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId
            ?? sprintf('rate-sync-%s-%s', now()->format('Ymd'), \Illuminate\Support\Str::random(8));
    }

    public function getDateRange(): array
    {
        return [
            'start' => $this->fromDate,
            'end'   => $this->toDate,
        ];
    }

    public function validate(): void
    {
        if ($this->tenantId <= 0) {
            throw new \InvalidArgumentException('tenantId must be positive');
        }
        if ($this->propertyId <= 0) {
            throw new \InvalidArgumentException('propertyId must be positive');
        }
        if (empty($this->fromDate) || empty($this->toDate)) {
            throw new \InvalidArgumentException('fromDate and toDate are required');
        }
        if (!Carbon::canBeCreatedFromFormat($this->fromDate, 'Y-m-d')) {
            throw new \InvalidArgumentException("fromDate must be Y-m-d: {$this->fromDate}");
        }
        if (!Carbon::canBeCreatedFromFormat($this->toDate, 'Y-m-d')) {
            throw new \InvalidArgumentException("toDate must be Y-m-d: {$this->toDate}");
        }
        if (Carbon::parse($this->fromDate)->gte(Carbon::parse($this->toDate))) {
            throw new \InvalidArgumentException('fromDate must be before toDate');
        }
    }
}
