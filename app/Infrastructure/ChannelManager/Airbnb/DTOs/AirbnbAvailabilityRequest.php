<?php

namespace App\Infrastructure\ChannelManager\Airbnb\DTOs;

/**
 * AirbnbAvailabilityRequest — Outbound availability update payload
 *
 * Sprint 13 E03: Airbnb Adapter
 *
 * Maps canonical availability to Airbnb API-compatible format.
 * Canonical field: is_available (bool)
 * Airbnb field: available (bool, "t" or "f" in API)
 */
readonly class AirbnbAvailabilityRequest
{
    /**
     * @param string $listingId Airbnb external listing ID (not internal property_id)
     * @param string $startDate Y-m-d
     * @param string $endDate Y-m-d
     * @param bool $available
     * @param string|null $idempotencyKey
     * @param string|null $source Timestamp of when the availability was last changed
     */
    public function __construct(
        public string $listingId,
        public string $startDate,
        public string $endDate,
        public bool $available,
        public ?string $idempotencyKey = null,
        public ?string $source = null,
    ) {}

    /**
     * Convert to Airbnb API payload format
     *
     * Airbnb Calendar API expects:
     * {
     *   "listing_id": "123456",
     *   "start_date": "2026-08-01",
     *   "end_date": "2026-08-05",
     *   "available": "f",
     *   "idempotency_key": "tenant:prop:..."
     * }
     */
    public function toAirbnbPayload(): array
    {
        $payload = [
            'listing_id' => $this->listingId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'available' => $this->available ? 't' : 'f',
        ];

        if ($this->idempotencyKey !== null) {
            $payload['idempotency_key'] = $this->idempotencyKey;
        }

        if ($this->source !== null) {
            $payload['source'] = $this->source;
        }

        return $payload;
    }

    /**
     * Validate the request
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->listingId)) {
            throw new \InvalidArgumentException('listingId is required');
        }

        if (empty($this->startDate) || empty($this->endDate)) {
            throw new \InvalidArgumentException('startDate and endDate are required');
        }

        if ($this->startDate > $this->endDate) {
            throw new \InvalidArgumentException('startDate must be before or equal to endDate');
        }
    }
}
