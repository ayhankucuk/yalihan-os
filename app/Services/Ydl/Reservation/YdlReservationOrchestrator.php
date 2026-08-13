<?php

namespace App\Services\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;
use App\DTOs\Ydl\Reservation\YdlCancellationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlCancellationEvidence;
use App\DTOs\Ydl\Reservation\YdlCancellationRecommendation;
use App\DTOs\Ydl\Reservation\YdlReservationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
use App\DTOs\Ydl\Reservation\YdlReservationEvidence;
use App\DTOs\Ydl\Reservation\YdlReservationRecommendation;
use App\DTOs\Ydl\YdlContextOutput;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Services\ReservationService;

/**
 * YdlReservationOrchestrator — E2E Reservation Pipeline for PILOT-002 Wave 1.
 *
 * PILOT-002 Wave 1 — CREATE + Double-Booking Prevention
 *
 * Pipeline:
 *   evaluateReadiness() → requestApproval() → executeReservation()
 *
 * GovernanceInvariant (PILOT-002 Authority Design):
 *   - STOP authority → BLOCKED regardless of readiness
 *   - LIMITED authority → scope intersection checked
 *   - Human approval MANDATORY (pilot süresince — auto-approval sonraya)
 *   - Duplicate events → idempotent no-op
 *   - Canonical lockForUpdate in ReservationService is NEVER bypassed
 *
 * Canonical Ownership:
 *   - Orchestrator: karar koordinasyonu
 *   - ReservationService: overlap/locking (TEK source of truth)
 *   - ConflictOverrideService: override (gelecek wave)
 *
 * This orchestrator does NOT write to DB directly.
 * All DB writes go through ReservationService::createReservation().
 *
 * @readonly token validation state
 */
class YdlReservationOrchestrator
{
    public const PILOT = 'PILOT-002';

    /** Human approval token TTL: 24 hours (matches PILOT-001). */
    private const APPROVAL_TOKEN_TTL_SECONDS = 86400;

    private ReservationReadinessService $readinessService;
    private ReservationEventLog $eventLog;

    public function __construct(
        ?ReservationReadinessService $readinessService = null,
        ?ReservationEventLog $eventLog = null,
    ) {
        $this->readinessService = $readinessService ?? new ReservationReadinessService();
        $this->eventLog = $eventLog ?? new ReservationEventLog();
    }

    // ─── Step 1: Evaluate Readiness ───────────────────────────────────────────

    /**
     * Step 1: Evaluate reservation readiness for an Ilan + date range.
     *
     * Reads YDL authority context and combines it with the property's
     * readiness state to produce a reservation recommendation.
     *
     * Authority logic:
     *   STOP  → BLOCKED_GATE (system halted)
     *   LIMITED → scope intersection check: if reservation_create blocked → BLOCKED_GATE
     *   FULL  → readiness evaluation
     *
     * Preliminary conflict check: WITHOUT lockForUpdate.
     * For agent feedback only — NOT the canonical conflict check.
     * Canonical check lives in executeReservation() via ReservationService.
     *
     * @param Ilan $ilan
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @param string|null $ydlAuthorityOverride Override authority (tests only)
     * @return YdlReservationRecommendation
     */
    public function evaluateReadiness(
        Ilan $ilan,
        string $startDate,
        string $endDate,
        ?string $ydlAuthorityOverride = null,
    ): YdlReservationRecommendation {
        return $this->readinessService->evaluate(
            $ilan,
            $startDate,
            $endDate,
            $ydlAuthorityOverride,
        );
    }

    // ─── Step 2: Request Human Approval ─────────────────────────────────────

    /**
     * Step 2: Request human approval for a ready reservation.
     *
     * Returns an approval token that must be presented when executing reservation.
     * Token expires after APPROVAL_TOKEN_TTL_SECONDS.
     *
     * @throws \DomainException if not ready or already processed
     */
    public function requestApproval(
        YdlReservationRecommendation $readiness,
        ?int $requestedBy = null,
    ): YdlReservationApprovalToken {
        if (! $readiness->isReady()) {
            throw new \DomainException(
                "Cannot request approval: ilan #{$readiness->ilanId} is not ready. " .
                "Decision: {$readiness->decisionLabel} — {$readiness->rationale}"
            );
        }

        $eventId = ReservationEvent::generateEventId(
            $readiness->ilanId,
            $readiness->startDate,
            $readiness->endDate,
            'CREATE',
        );

        // Idempotency check BEFORE creating token
        if ($this->eventLog->eventExists($eventId)) {
            throw new \DomainException(
                "Duplicate reservation attempt: event {$eventId} already processed. " .
                "This reservation was already created or the approval token has been used."
            );
        }

        $now = now()->toIso8601String();
        $expiresAt = now()->addSeconds(self::APPROVAL_TOKEN_TTL_SECONDS)->toIso8601String();

        return YdlReservationApprovalToken::create(
            ilanId:            $readiness->ilanId,
            tenantId:          $readiness->tenantId,
            eventId:           $eventId,
            ydlAuthority:     $readiness->ydlAuthority,
            authorityContext:   $readiness->decisionLabel,
            startDate:        $readiness->startDate,
            endDate:          $readiness->endDate,
            recommendation:    $readiness->toArray(),
            requestedAt:       $now,
            expiresAt:         $expiresAt,
            requestedBy:       $requestedBy,
        );
    }

    // ─── Step 3: Execute Reservation ───────────────────────────────────────

    /**
     * Step 3: Execute reservation after human approval.
     *
     * Pipeline guarantees (Invariant #2 — Race):
     *   Authority/readiness passed ≠ reservation correctness.
     *   Final canonical lockForUpdate + overlap check in ReservationService
     *   is MANDATORY and cannot be bypassed.
     *
     * Execution flow:
     *   1. Token validation
     *   2. STOP authority → BLOCKED
     *   3. Idempotency check
     *   4. ReservationService::createReservation() → lockForUpdate + canonical overlap
     *   5. Evidence → ReservationEventLog
     *
     * @throws \DomainException if token invalid/expired or already used
     * @throws \Exception if ReservationService create fails
     */
    public function executeReservation(
        YdlReservationApprovalToken $token,
        int $approvedBy,
        array $guestData,
        ?int $userId = null,
        ?ReservationService $reservationService = null,
    ): YdlReservationEvidence {
        // ── 1. Token validation ────────────────────────────────────
        $token->validateOrFail();

        // ── 1b. Tenant isolation: verify token tenant matches ilan tenant ──
        $ilan = Ilan::withoutGlobalScopes()->findOrFail($token->ilanId);
        if ($ilan->tenant_id !== $token->tenantId) {
            throw new \DomainException(
                "Cross-tenant reservation attempt: token tenant={$token->tenantId}, ilan tenant={$ilan->tenant_id}"
            );
        }

        // ── 2. STOP authority: final gate ────────────────────────
        if ($token->ydlAuthority === YdlReservationContextOutput::AUTHORITY_STOP) {
            $evidence = YdlReservationEvidence::blocked(
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                startDate:        $token->startDate,
                endDate:          $token->endDate,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:   'STOP authority',
                reason:            'YDL authority STOP — reservation pipeline durduruldu',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        // ── 3. Idempotency check ─────────────────────────────────
        if ($this->eventLog->eventExists($token->eventId)) {
            // Already processed — return idempotent evidence (no exception, no re-execution)
            return YdlReservationEvidence::idempotentNoOp(
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                startDate:        $token->startDate,
                endDate:          $token->endDate,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:   $token->authorityContext,
                reason:            "Event {$token->eventId} already in log",
            );
        }

        // ── 4. Canonical execution via ReservationService ─────────
        // RACE INVARIANT: lockForUpdate + overlap check is MANDATORY.
        // Orchestrator does NOT write its own conflict algorithm.
        // ReservationService::createReservation() is the single source of truth.
        $service = $reservationService ?? new ReservationService();

        try {
            $reservation = $service->createReservation(
                propertyId:  $token->ilanId,
                startDate:   $token->startDate,
                endDate:     $token->endDate,
                guestData:   $guestData,
                userId:      $userId,
            );

            // ── 5. Record evidence ───────────────────────────────
            $evidence = YdlReservationEvidence::success(
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                reservationId:     $reservation->id,
                eventId:           $token->eventId,
                startDate:        $token->startDate,
                endDate:          $token->endDate,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:   $token->authorityContext,
                approvedBy:        $approvedBy,
                recommendation:    $token->recommendation,
            );

            $this->eventLog->append($evidence->toReservationEvent());

            return $evidence;

        } catch (\Exception $e) {
            // Canonical lockForUpdate detected conflict during execution.
            // This is the RACE INVARIANT proof: readiness passed but
            // canonical check caught the conflict.
            $msg = $e->getMessage();

            if (str_contains($msg, 'Conflict detected') || str_contains($msg, 'overlap')) {
                // Extract existing reservation ID if available
                $conflictId = $this->extractConflictId($msg);

                $evidence = YdlReservationEvidence::conflict(
                    ilanId:            $token->ilanId,
                    tenantId:          $token->tenantId,
                    eventId:           $token->eventId,
                    startDate:        $token->startDate,
                    endDate:          $token->endDate,
                    ydlAuthority:     $token->ydlAuthority,
                    authorityContext:   $token->authorityContext,
                    conflictReservationId: $conflictId,
                );
            } else {
                // Other error — blocked
                $evidence = YdlReservationEvidence::blocked(
                    ilanId:            $token->ilanId,
                    tenantId:          $token->tenantId,
                    eventId:           $token->eventId,
                    startDate:        $token->startDate,
                    endDate:          $token->endDate,
                    ydlAuthority:     $token->ydlAuthority,
                    authorityContext:   $token->authorityContext,
                    reason:            $msg,
                );
            }

            $this->eventLog->append($evidence->toReservationEvent());
            throw $e;
        }
    }

    // ─── CANCELLATION ───────────────────────────────────────────────────────

    /**
     * Step 1 (Wave 2): Evaluate cancellation readiness for a reservation.
     *
     * Checks: authority + reservation existence + state + tenant.
     * Canonical cancellation check lives in executeCancellation().
     *
     * @param int $reservationId
     * @param int $tenantId Expected tenant — cross-tenant rejection
     * @param string|null $ydlAuthorityOverride For tests only
     * @param array|null $blockedScopesOverride For tests only
     * @return YdlCancellationRecommendation
     */
    public function evaluateCancellationReadiness(
        int    $reservationId,
        int    $tenantId,
        ?string $ydlAuthorityOverride = null,
        ?array $blockedScopesOverride = null,
    ): YdlCancellationRecommendation {
        $context = $this->readinessService->readContext($tenantId);
        $ydlAuthority = $ydlAuthorityOverride ?? $context->authorityLevel;
        $blockedScopes = $blockedScopesOverride ?? $context->blockedScopes;

        // ── Authority gate ────────────────────────────────────────────
        if ($ydlAuthority === YdlReservationContextOutput::AUTHORITY_STOP) {
            return $this->cancellationBlocked(
                $reservationId, 0, $tenantId, $ydlAuthority,
                'YDL authority: STOP — cancellation pipeline durduruldu'
            );
        }

        if ($ydlAuthority === YdlReservationContextOutput::AUTHORITY_LIMITED) {
            if (in_array('reservation_cancel', $blockedScopes, true)) {
                return $this->cancellationBlocked(
                    $reservationId, 0, $tenantId, $ydlAuthority,
                    'Active blocker scope intersects with reservation_cancel workflow'
                );
            }
        }

        // ── Reservation lookup ─────────────────────────────────────────
        $reservation = PropertyReservation::withoutGlobalScopes()->find($reservationId);

        if ($reservation === null) {
            return new YdlCancellationRecommendation(
                reservationId:            $reservationId,
                ilanId:                0,
                tenantId:              $tenantId,
                ydlAuthority:         $ydlAuthority,
                decision:              YdlCancellationRecommendation::DECISION_NOT_FOUND,
                decisionLabel:          'Bulunamadı',
                rationale:             "Reservation #{$reservationId} not found in this tenant",
                confidence:            'HIGH',
                humanApprovalRequired:  false,
                canCancel:           false,
                reservationState:       '',
                startDate:           null,
                endDate:             null,
                existingReservationId:  null,
                evaluatedAt:         now()->toIso8601String(),
                snapshotId:          $this->readinessService->currentSnapshotId(),
            );
        }

        // ── Tenant isolation ──────────────────────────────────────────
        if ($reservation->tenant_id !== $tenantId) {
            return $this->cancellationBlocked(
                $reservationId, $reservation->ilan_id ?? 0, $tenantId, $ydlAuthority,
                "Cross-tenant cancellation rejected: reservation tenant={$reservation->tenant_id}"
            );
        }

        // ── Terminal state check ─────────────────────────────────────
        $stateRaw = $reservation->reservation_state instanceof ReservationState
            ? $reservation->reservation_state->value
            : (string) $reservation->reservation_state;

        if ($stateRaw === ReservationState::CANCELLED->value) {
        return new YdlCancellationRecommendation(
            reservationId:            $reservationId,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id ?? 0,
            tenantId:              $tenantId,
            ydlAuthority:         $ydlAuthority,
            decision:              YdlCancellationRecommendation::DECISION_ALREADY_CANCELLED,
            decisionLabel:          'Zaten İptal Edilmiş',
            rationale:             "Reservation #{$reservationId} is already in CANCELLED state",
            confidence:            'HIGH',
            humanApprovalRequired:  false,
            canCancel:           false,
            reservationState:       $stateRaw,
            startDate:           $reservation->start_date,
            endDate:             $reservation->end_date,
            existingReservationId:  $reservationId,
            evaluatedAt:         now()->toIso8601String(),
            snapshotId:          $this->readinessService->currentSnapshotId(),
        );
        }

        // ── Ready to cancel ─────────────────────────────────────────
        return new YdlCancellationRecommendation(
            reservationId:            $reservationId,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id ?? 0,
            tenantId:              $tenantId,
            ydlAuthority:         $ydlAuthority,
            decision:              YdlCancellationRecommendation::DECISION_CANCEL_READY,
            decisionLabel:          'İptal Hazır',
            rationale:             "Reservation #{$reservationId} in state '{$stateRaw}' — cancellation allowed",
            confidence:            'HIGH',
            humanApprovalRequired:  true, // Pilot süresince zorunlu
            canCancel:           true,
            reservationState:       $stateRaw,
            startDate:           $reservation->start_date,
            endDate:             $reservation->end_date,
            existingReservationId:  $reservationId,
            evaluatedAt:         now()->toIso8601String(),
            snapshotId:          $this->readinessService->currentSnapshotId(),
        );
    }

    /**
     * Step 2 (Wave 2): Request human approval for cancellation.
     */
    public function requestCancellationApproval(
        YdlCancellationRecommendation $readiness,
        ?int $requestedBy = null,
    ): YdlCancellationApprovalToken {
        if (! $readiness->isReady()) {
            throw new \DomainException(
                "Cannot request cancellation approval: reservation #{$readiness->reservationId} is not ready. " .
                "Decision: {$readiness->decisionLabel}"
            );
        }

        $eventId = ReservationEvent::generateEventId(
            $readiness->ilanId,
            'CANCEL_' . $readiness->reservationId,
            'CANCEL',
            'CANCEL',
        );

        // Idempotency check
        if ($this->eventLog->eventExists($eventId)) {
            throw new \DomainException(
                "Duplicate cancellation: event {$eventId} already processed."
            );
        }

        $now = now()->toIso8601String();
        $expiresAt = now()->addSeconds(self::APPROVAL_TOKEN_TTL_SECONDS)->toIso8601String();

        return YdlCancellationApprovalToken::create(
            reservationId:  $readiness->reservationId,
            ilanId:       $readiness->ilanId,
            tenantId:     $readiness->tenantId,
            eventId:      $eventId,
            ydlAuthority: $readiness->ydlAuthority,
            authorityContext: $readiness->decisionLabel,
            reservationState: $readiness->reservationState,
            recommendation: $readiness->toArray(),
            requestedAt:  $now,
            expiresAt:    $expiresAt,
            requestedBy:  $requestedBy,
        );
    }

    /**
     * Step 3 (Wave 2): Execute cancellation after human approval.
     *
     * Canonical owner: ReservationService::cancelReservation()
     *
     * @throws \DomainException if token invalid/expired
     * @throws \Exception if canonical cancellation fails
     */
    public function executeCancellation(
        YdlCancellationApprovalToken $token,
        int $approvedBy,
        ?ReservationService $reservationService = null,
    ): YdlCancellationEvidence {
        // ── 1. Token validation ────────────────────────────────────
        $token->validateOrFail();

        // ── 2. STOP authority: final gate ────────────────────────
        if ($token->ydlAuthority === YdlReservationContextOutput::AUTHORITY_STOP) {
            $evidence = YdlCancellationEvidence::blocked(
                reservationId:  $token->reservationId,
                ilanId:        $token->ilanId,
                tenantId:      $token->tenantId,
                eventId:       $token->eventId,
                ydlAuthority: $token->ydlAuthority,
                authorityContext: 'STOP authority',
                reason:        'YDL authority STOP — cancellation pipeline durduruldu',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        // ── 3. Idempotency check ─────────────────────────────────
        if ($this->eventLog->eventExists($token->eventId)) {
            return YdlCancellationEvidence::idempotentNoOp(
                reservationId:  $token->reservationId,
                ilanId:        $token->ilanId,
                tenantId:      $token->tenantId,
                eventId:       $token->eventId,
                ydlAuthority: $token->ydlAuthority,
                authorityContext: $token->authorityContext,
                reason:        "Event {$token->eventId} already in log",
            );
        }

        // ── 4. Canonical cancellation via ReservationService ─────────
        $service = $reservationService ?? new ReservationService();

        try {
            $service->cancelReservation($token->reservationId);

            $evidence = YdlCancellationEvidence::success(
                reservationId:  $token->reservationId,
                ilanId:        $token->ilanId,
                tenantId:      $token->tenantId,
                eventId:       $token->eventId,
                ydlAuthority: $token->ydlAuthority,
                authorityContext: $token->authorityContext,
                approvedBy:   $approvedBy,
                recommendation: $token->recommendation,
            );

            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;

        } catch (\Throwable $e) {
            $evidence = YdlCancellationEvidence::blocked(
                reservationId:  $token->reservationId,
                ilanId:        $token->ilanId,
                tenantId:      $token->tenantId,
                eventId:       $token->eventId,
                ydlAuthority: $token->ydlAuthority,
                authorityContext: $token->authorityContext,
                reason:        $e->getMessage(),
            );
            $this->eventLog->append($evidence->toReservationEvent());
            throw $e;
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function cancellationBlocked(
        int $reservationId,
        int $ilanId,
        int $tenantId,
        string $ydlAuthority,
        string $reason,
    ): YdlCancellationRecommendation {
        return new YdlCancellationRecommendation(
            reservationId:            $reservationId,
            ilanId:                $ilanId,
            tenantId:              $tenantId,
            ydlAuthority:         $ydlAuthority,
            decision:              YdlCancellationRecommendation::DECISION_BLOCKED_GATE,
            decisionLabel:          'Bloke Edildi',
            rationale:             $reason,
            confidence:            'HIGH',
            humanApprovalRequired:  false,
            canCancel:           false,
            reservationState:       '',
            startDate:           null,
            endDate:             null,
            existingReservationId:  null,
            evaluatedAt:         now()->toIso8601String(),
            snapshotId:          now()->format('Ymd'),
        );
    }

    /**
     * Extract existing reservation ID from conflict exception message.
     */
    private function extractConflictId(string $message): int
    {
        // Message format: "Conflict detected: The selected dates overlap with an existing reservation."
        // The reservation ID is NOT in the message — extract from message if present
        if (preg_match('/#(\d+)/', $message, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
}
