<?php

namespace App\DTOs\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;

/**
 * YdlOverrideEvidence — Immutable evidence record for conflict override operations.
 *
 * PILOT-002 Wave 3
 *
 * @readonly
 */
final class YdlOverrideEvidence
{
    public function __construct(
        public readonly string  $eventId,
        public readonly string  $pilot,
        public readonly int     $conflictReservationId,
        public readonly int     $ilanId,
        public readonly int     $tenantId,
        public readonly string  $outcome,
        public readonly string  $ydlAuthority,
        public readonly string  $authorityContext,
        public readonly string  $canonicalResult,
        public readonly string  $executionOwner,
        public readonly string  $humanDecision,
        public readonly bool    $success,
        public readonly ?string $failureReason,
        public readonly ?int    $approvedBy,
        public readonly ?string $overrideReason,
        /** New reservation ID created by the override */
        public readonly ?int    $reservationId,
        public readonly string  $occurredAt,
    ) {}

    public static function success(
        int    $conflictReservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        int    $approvedBy,
        string $overrideReason,
        ?int   $reservationId = null,
    ): self {
        return new self(
            eventId:            $eventId,
            pilot:              'PILOT-002',
            conflictReservationId: $conflictReservationId,
            ilanId:             $ilanId,
            tenantId:           $tenantId,
            outcome:             ReservationEvent::OUTCOME_SUCCESS,
            ydlAuthority:      $ydlAuthority,
            authorityContext:    $authorityContext,
            canonicalResult:     'Override authorized — reservation continues despite conflict',
            executionOwner:     'YdlReservationOrchestrator::executeOverride()',
            humanDecision:     "Override approved by user #{$approvedBy}",
            success:           true,
            failureReason:      null,
            approvedBy:        $approvedBy,
            overrideReason:   $overrideReason,
            reservationId:     $reservationId,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public static function blocked(
        int    $conflictReservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $reason,
    ): self {
        return new self(
            eventId:            $eventId,
            pilot:              'PILOT-002',
            conflictReservationId: $conflictReservationId,
            ilanId:             $ilanId,
            tenantId:           $tenantId,
            outcome:             ReservationEvent::OUTCOME_BLOCKED,
            ydlAuthority:      $ydlAuthority,
            authorityContext:    $authorityContext,
            canonicalResult:     'Override BLOCKED — ' . $reason,
            executionOwner:     'YdlReservationOrchestrator',
            humanDecision:     'BLOCKED before execution',
            success:           false,
            failureReason:     $reason,
            approvedBy:        null,
            overrideReason:   null,
            reservationId:     null,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public static function unauthorized(
        int    $conflictReservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $reason,
    ): self {
        return new self(
            eventId:            $eventId,
            pilot:              'PILOT-002',
            conflictReservationId: $conflictReservationId,
            ilanId:             $ilanId,
            tenantId:           $tenantId,
            outcome:             ReservationEvent::OUTCOME_BLOCKED,
            ydlAuthority:      $ydlAuthority,
            authorityContext:    $authorityContext,
            canonicalResult:     'Override not authorized — ' . $reason,
            executionOwner:     'ConflictOverrideService',
            humanDecision:     'Authorization rejected',
            success:           false,
            failureReason:     $reason,
            approvedBy:        null,
            overrideReason:   null,
            reservationId:     null,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public function toReservationEvent(): ReservationEvent
    {
        return new ReservationEvent(
            eventId:            $this->eventId,
            pilot:              $this->pilot,
            type:               ReservationEvent::TYPE_OVERRIDE,
            outcome:            $this->outcome,
            ilanId:             $this->ilanId,
            tenantId:           $this->tenantId,
            reservationId:     $this->reservationId ?? 0,
            startDate:        '',
            endDate:          '',
            ydlAuthority:    $this->ydlAuthority,
            authorityContext:   $this->authorityContext,
            canonicalResult:  $this->canonicalResult,
            executionOwner:   $this->executionOwner,
            humanDecision:     $this->humanDecision,
            userId:           $this->approvedBy,
            occurredAt:        $this->occurredAt,
        );
    }

    public function toArray(): array
    {
        return [
            'event_id'            => $this->eventId,
            'pilot'              => $this->pilot,
            'conflict_reservation_id' => $this->conflictReservationId,
            'ilan_id'             => $this->ilanId,
            'tenant_id'           => $this->tenantId,
            'outcome'            => $this->outcome,
            'ydl_authority'     => $this->ydlAuthority,
            'authority_context'  => $this->authorityContext,
            'canonical_result'   => $this->canonicalResult,
            'execution_owner'    => $this->executionOwner,
            'human_decision'    => $this->humanDecision,
            'success'            => $this->success,
            'failure_reason'     => $this->failureReason,
            'approved_by'        => $this->approvedBy,
            'override_reason'    => $this->overrideReason,
            'reservation_id'     => $this->reservationId,
            'occurred_at'       => $this->occurredAt,
        ];
    }
}
