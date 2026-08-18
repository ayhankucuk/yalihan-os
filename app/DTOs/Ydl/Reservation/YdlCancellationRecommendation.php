<?php

namespace App\DTOs\Ydl\Reservation;

/**
 * YdlCancellationRecommendation — Immutable DTO for cancellation readiness.
 *
 * PILOT-002 Wave 2
 *
 * @readonly
 */
final class YdlCancellationRecommendation
{
    public const DECISION_CANCEL_READY     = 'CANCEL_READY';
    public const DECISION_ALREADY_CANCELLED = 'ALREADY_CANCELLED';
    public const DECISION_BLOCKED_GATE     = 'BLOCKED_GATE';
    public const DECISION_NOT_FOUND        = 'NOT_FOUND';

    public const PILOT = 'PILOT-002';

    public function __construct(
        public readonly int                   $reservationId,
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string                $ydlAuthority,
        /** self::DECISION_* constant */
        public readonly string                $decision,
        public readonly string                $decisionLabel,
        public readonly string                $rationale,
        public readonly string                $confidence,
        public readonly bool                  $humanApprovalRequired,
        public readonly bool                  $canCancel,
        public readonly string                $reservationState,
        public readonly ?string              $startDate,
        public readonly ?string              $endDate,
        public readonly ?int                 $existingReservationId,
        /** ISO8601 timestamp */
        public readonly string               $evaluatedAt,
        public readonly string               $snapshotId,
    ) {}

    public function isReady(): bool
    {
        return $this->decision === self::DECISION_CANCEL_READY;
    }

    public function isBlocked(): bool
    {
        return $this->decision === self::DECISION_BLOCKED_GATE;
    }

    public function isAlreadyCancelled(): bool
    {
        return $this->decision === self::DECISION_ALREADY_CANCELLED;
    }

    public function toArray(): array
    {
        return [
            'pilot'                    => self::PILOT,
            'reservation_id'           => $this->reservationId,
            'ilan_id'                  => $this->ilanId,
            'tenant_id'                => $this->tenantId,
            'ydl_authority'           => $this->ydlAuthority,
            'decision'                => $this->decision,
            'decision_label'          => $this->decisionLabel,
            'rationale'               => $this->rationale,
            'confidence'              => $this->confidence,
            'human_approval_required'  => $this->humanApprovalRequired,
            'can_cancel'             => $this->canCancel,
            'reservation_state'        => $this->reservationState,
            'start_date'             => $this->startDate,
            'end_date'               => $this->endDate,
            'existing_reservation_id'  => $this->existingReservationId,
            'evaluated_at'           => $this->evaluatedAt,
            'snapshot_id'            => $this->snapshotId,
        ];
    }
}
