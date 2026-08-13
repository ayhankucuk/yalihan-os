<?php

namespace App\DTOs\Ydl\Reservation\Events;

use DateTimeImmutable;

/**
 * ReservationEvent — Immutable certification event for reservation operations.
 *
 * PILOT-002 Wave 1
 *
 * Represents a single reservation certification event processed by the YDL pipeline.
 * Used for idempotency: same event_id = same event, no duplicate processing.
 * Used for evidence: every CREATE / BLOCKED / OVERRIDE result is logged.
 *
 * @readonly
 */
final class ReservationEvent
{
    public const TYPE_CREATED     = 'RESERVATION_CREATED';
    public const TYPE_CANCELLED   = 'RESERVATION_CANCELLED';
    public const TYPE_BLOCKED     = 'RESERVATION_BLOCKED';
    public const TYPE_CONFLICT    = 'RESERVATION_CONFLICT';
    public const TYPE_OVERRIDE    = 'RESERVATION_OVERRIDE';
    public const TYPE_IDEMPOTENT  = 'RESERVATION_IDEMPOTENT';

    public const OUTCOME_SUCCESS  = 'SUCCESS';
    public const OUTCOME_BLOCKED  = 'BLOCKED';
    public const OUTCOME_CONFLICT = 'CONFLICT';
    public const OUTCOME_IDEMPOTENT = 'IDEMPOTENT';

    public function __construct(
        public readonly string $eventId,
        public readonly string $pilot,
        public readonly string $type,
        public readonly string $outcome,
        public readonly int    $ilanId,
        public readonly int    $tenantId,
        public readonly int    $reservationId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $ydlAuthority,
        public readonly string $authorityContext,
        public readonly string $canonicalResult,
        public readonly string $executionOwner,
        public readonly string $humanDecision,
        public readonly ?int   $userId,
        public readonly string $occurredAt,
    ) {}

    /**
     * Generate a deterministic event ID from reservation details.
     *
     * Same inputs → same event_id → idempotent.
     *
     * For cancellation, use reservationId as the identifier instead of dates.
     */
    public static function generateEventId(
        int    $ilanId,
        string $startDateOrReservationId,
        string $endDateOrAction,
        string $action = '',
    ): string {
        // Cancellation overload: reservationId replaces startDate, action replaces endDate
        $payload = "PILOT-002|{$ilanId}|{$startDateOrReservationId}|{$endDateOrAction}|{$action}";
        return substr(hash('sha256', $payload), 0, 16);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventId:            $data['event_id'],
            pilot:              $data['pilot'],
            type:               $data['type'],
            outcome:            $data['outcome'],
            ilanId:             (int) $data['ilan_id'],
            tenantId:           (int) $data['tenant_id'],
            reservationId:      (int) $data['reservation_id'],
            startDate:          $data['start_date'],
            endDate:            $data['end_date'],
            ydlAuthority:      $data['ydl_authority'],
            authorityContext:   $data['authority_context'],
            canonicalResult:    $data['canonical_result'],
            executionOwner:     $data['execution_owner'],
            humanDecision:     $data['human_decision'],
            userId:             isset($data['user_id']) ? (int) $data['user_id'] : null,
            occurredAt:         $data['occurred_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'event_id'           => $this->eventId,
            'pilot'             => $this->pilot,
            'type'              => $this->type,
            'outcome'           => $this->outcome,
            'ilan_id'           => $this->ilanId,
            'tenant_id'         => $this->tenantId,
            'reservation_id'    => $this->reservationId,
            'start_date'        => $this->startDate,
            'end_date'          => $this->endDate,
            'ydl_authority'     => $this->ydlAuthority,
            'authority_context' => $this->authorityContext,
            'canonical_result'  => $this->canonicalResult,
            'execution_owner'   => $this->executionOwner,
            'human_decision'   => $this->humanDecision,
            'user_id'           => $this->userId,
            'occurred_at'      => $this->occurredAt,
        ];
    }
}
