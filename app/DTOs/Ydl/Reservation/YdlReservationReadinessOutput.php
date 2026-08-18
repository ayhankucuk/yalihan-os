<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlReservationReadinessOutput — Immutable DTO for reservation readiness evaluation result.
 *
 * PILOT-002 Wave 1
 *
 * Mirrors YdlPublishReadinessOutput structure adapted for reservation domain.
 *
 * @readonly
 */
final class YdlReservationReadinessOutput
{
    public const RECOMMENDATION_PUBLISH_READY  = 'RESERVATION_READY';
    public const RECOMMENDATION_MISSING_FIELDS = 'MISSING_FIELDS';
    public const RECOMMENDATION_BLOCKED_GATE    = 'BLOCKED_GATE';
    public const RECOMMENDATION_CONFLICT        = 'CONFLICT_DETECTED';
    public const RECOMMENDATION_UNAVAILABLE     = 'UNAVAILABLE';

    public function __construct(
        public readonly string                $recommendation,
        public readonly string                $recommendationRationale,
        /** @var string[] */
        public readonly array                 $missingFields,
        public readonly string                $ydlAuthority,
        public readonly bool                  $rentalEnabled,
        public readonly int                   $minStayNights,
        public readonly int                   $requestedNights,
        public readonly ?string               $conflictDetails,
        public readonly string                $evaluatedAt,
        public readonly string                $snapshotId,
    ) {}

    public function isReady(): bool
    {
        return $this->recommendation === self::RECOMMENDATION_PUBLISH_READY;
    }

    public function isBlocked(): bool
    {
        return $this->recommendation === self::RECOMMENDATION_BLOCKED_GATE;
    }

    public function isConflict(): bool
    {
        return $this->recommendation === self::RECOMMENDATION_CONFLICT;
    }

    public function hasMissingFields(): bool
    {
        return $this->recommendation === self::RECOMMENDATION_MISSING_FIELDS;
    }

    public function toArray(): array
    {
        return [
            'recommendation'            => $this->recommendation,
            'recommendation_rationale'  => $this->recommendationRationale,
            'missing_fields'            => $this->missingFields,
            'ydl_authority'            => $this->ydlAuthority,
            'rental_enabled'           => $this->rentalEnabled,
            'min_stay_nights'          => $this->minStayNights,
            'requested_nights'         => $this->requestedNights,
            'conflict_details'         => $this->conflictDetails,
            'evaluated_at'             => $this->evaluatedAt,
            'snapshot_id'              => $this->snapshotId,
        ];
    }
}
