<?php

namespace App\DTOs\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;

/**
 * YdlCancellationEvidence — Immutable evidence record for cancellation operations.
 *
 * PILOT-002 Wave 2
 *
 * @readonly
 */
final class YdlCancellationEvidence
{
    public function __construct(
        public readonly string                $eventId,
        public readonly string                $pilot,
        public readonly int                   $reservationId,
        public readonly int                   $ilanId,
        public readonly int                   $tenantId,
        public readonly string               $outcome,
        /** 'SUCCESS' | 'BLOCKED' | 'IDEMPOTENT' | 'NOT_FOUND' */
        public readonly string               $ydlAuthority,
        public readonly string               $authorityContext,
        public readonly string               $canonicalResult,
        public readonly string               $executionOwner,
        public readonly string               $humanDecision,
        public readonly bool                 $success,
        public readonly ?string              $failureReason,
        public readonly ?int                $approvedBy,
        public readonly ?string              $idempotentReason,
        public readonly string               $occurredAt,
    ) {}

    public static function success(
        int    $reservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        int    $approvedBy,
        array  $recommendation,
    ): self {
        return new self(
            eventId:           $eventId,
            pilot:             'PILOT-002',
            reservationId:    $reservationId,
            ilanId:            $ilanId,
            tenantId:          $tenantId,
            outcome:            ReservationEvent::OUTCOME_SUCCESS,
            ydlAuthority:    $ydlAuthority,
            authorityContext:   $authorityContext,
            canonicalResult:    'ReservationService::cancelReservation() executed',
            executionOwner:     'YdlReservationOrchestrator::executeCancellation()',
            humanDecision:     "Cancellation approved by user #{$approvedBy}",
            success:           true,
            failureReason:      null,
            approvedBy:        $approvedBy,
            idempotentReason:  null,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public static function blocked(
        int    $reservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $reason,
    ): self {
        return new self(
            eventId:           $eventId,
            pilot:             'PILOT-002',
            reservationId:    $reservationId,
            ilanId:            $ilanId,
            tenantId:          $tenantId,
            outcome:            ReservationEvent::OUTCOME_BLOCKED,
            ydlAuthority:    $ydlAuthority,
            authorityContext:   $authorityContext,
            canonicalResult:    'BLOCKED — ' . $reason,
            executionOwner:     'YdlReservationOrchestrator',
            humanDecision:     'BLOCKED before execution',
            success:           false,
            failureReason:      $reason,
            approvedBy:        null,
            idempotentReason:  null,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public static function idempotentNoOp(
        int    $reservationId,
        int    $ilanId,
        int    $tenantId,
        string $eventId,
        string $ydlAuthority,
        string $authorityContext,
        string $reason,
    ): self {
        return new self(
            eventId:           $eventId,
            pilot:             'PILOT-002',
            reservationId:    $reservationId,
            ilanId:            $ilanId,
            tenantId:          $tenantId,
            outcome:            ReservationEvent::OUTCOME_IDEMPOTENT,
            ydlAuthority:    $ydlAuthority,
            authorityContext:   $authorityContext,
            canonicalResult:    'IDEMPOTENT_NO_OP',
            executionOwner:     'YdlReservationOrchestrator::executeCancellation()',
            humanDecision:     'No-op (already cancelled or duplicate event)',
            success:           true,
            failureReason:      null,
            approvedBy:        null,
            idempotentReason:  $reason,
            occurredAt:        now()->toIso8601String(),
        );
    }

    public function toReservationEvent(): ReservationEvent
    {
        return new ReservationEvent(
            eventId:            $this->eventId,
            pilot:              $this->pilot,
            type:               ReservationEvent::TYPE_CANCELLED,
            outcome:            $this->outcome,
            ilanId:            $this->ilanId,
            tenantId:          $this->tenantId,
            reservationId:    $this->reservationId,
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
            'event_id'           => $this->eventId,
            'pilot'             => $this->pilot,
            'reservation_id'   => $this->reservationId,
            'ilan_id'           => $this->ilanId,
            'tenant_id'         => $this->tenantId,
            'outcome'           => $this->outcome,
            'ydl_authority'     => $this->ydlAuthority,
            'authority_context'  => $this->authorityContext,
            'canonical_result'  => $this->canonicalResult,
            'execution_owner'   => $this->executionOwner,
            'human_decision'   => $this->humanDecision,
            'success'           => $this->success,
            'failure_reason'    => $this->failureReason,
            'approved_by'       => $this->approvedBy,
            'idempotent_reason' => $this->idempotentReason,
            'occurred_at'      => $this->occurredAt,
        ];
    }
}
