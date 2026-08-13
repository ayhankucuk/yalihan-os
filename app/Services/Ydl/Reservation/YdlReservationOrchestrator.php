<?php

namespace App\Services\Ydl\Reservation;

use App\DTOs\Ydl\Reservation\Events\ReservationEvent;
use App\DTOs\Ydl\Reservation\YdlCancellationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlCancellationEvidence;
use App\DTOs\Ydl\Reservation\YdlCancellationRecommendation;
use App\DTOs\Ydl\Reservation\YdlOverrideApprovalToken;
use App\DTOs\Ydl\Reservation\YdlOverrideEvidence;
use App\DTOs\Ydl\Reservation\YdlOverrideRecommendation;
use App\DTOs\Ydl\Reservation\YdlReservationApprovalToken;
use App\DTOs\Ydl\Reservation\YdlReservationContextOutput;
use App\DTOs\Ydl\Reservation\YdlReservationEvidence;
use App\DTOs\Ydl\Reservation\YdlReservationRecommendation;
use App\DTOs\Ydl\YdlContextOutput;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use App\Services\Ydl\Platform\AuthorityEvaluator;
use App\Services\Ydl\Platform\AuthorityEvaluatorInterface;

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
    private ?ConflictOverrideService $conflictOverrideService;
    private AuthorityEvaluatorInterface $authorityEvaluator;

    public function __construct(
        ?ReservationReadinessService $readinessService = null,
        ?ReservationEventLog $eventLog = null,
        ?ConflictOverrideService $conflictOverrideService = null,
        ?AuthorityEvaluatorInterface $authorityEvaluator = null,
    ) {
        $this->readinessService = $readinessService ?? new ReservationReadinessService();
        $this->eventLog = $eventLog ?? new ReservationEventLog();
        $this->conflictOverrideService = $conflictOverrideService;
        $this->authorityEvaluator = $authorityEvaluator ?? new AuthorityEvaluator();
    }

    private function getConflictOverrideService(): ConflictOverrideService
    {
        return $this->conflictOverrideService ?? new ConflictOverrideService();
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

        // ── 2. STOP authority: final gate (via AuthorityEvaluator) ──
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
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

        // ── Authority gate (via AuthorityEvaluator) ───────────────────
        if ($this->authorityEvaluator->isStopAuthority($ydlAuthority)) {
            return $this->cancellationBlocked(
                $reservationId, 0, $tenantId, $ydlAuthority,
                'YDL authority: STOP — cancellation pipeline durduruldu'
            );
        }

        if ($this->authorityEvaluator->hasBlockingIntersection('reservation_cancel', $blockedScopes)) {
            return $this->cancellationBlocked(
                $reservationId, 0, $tenantId, $ydlAuthority,
                'Active blocker scope intersects with reservation_cancel workflow'
            );
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

        // ── 2. STOP authority: final gate (via AuthorityEvaluator) ──
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
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

    // ─── OVERRIDE ─────────────────────────────────────────────────────

    /**
     * Step 1 (Wave 3): Evaluate override readiness.
     *
     * Override = proceed with reservation despite conflict. Requires explicit authorization.
     *
     * Checks: STOP authority blocked, conflict exists, user authorized.
     */
    public function evaluateOverrideReadiness(
        int $ilanId,
        int $tenantId,
        string $startDate,
        string $endDate,
        int $userId,
        ?string $ydlAuthorityOverride = null,
    ): YdlOverrideRecommendation {
        $context = $this->readinessService->readContext($tenantId);
        $ydlAuthority = $ydlAuthorityOverride ?? $context->authorityLevel;

        // STOP authority: no override possible (via AuthorityEvaluator)
        if ($this->authorityEvaluator->isStopAuthority($ydlAuthority)) {
            return $this->overrideBlocked(
                ilanId:        $ilanId,
                tenantId:      $tenantId,
                ydlAuthority: $ydlAuthority,
                reason:      'STOP authority — override not permitted',
            );
        }

        // Check for conflicting reservation
        $conflict = PropertyReservation::withoutGlobalScopes()
            ->where('property_id', $ilanId)
            ->where('tenant_id', $tenantId)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->where('reservation_state', '!=', ReservationState::CANCELLED->value)
            ->first();

        if ($conflict === null) {
            return new YdlOverrideRecommendation(
                conflictReservationId:      0,
                ilanId:                  $ilanId,
                tenantId:                $tenantId,
                ydlAuthority:           $ydlAuthority,
                decision:                YdlOverrideRecommendation::DECISION_OVERRIDE_BLOCKED,
                decisionLabel:            'Çakışma Yok',
                rationale:               'No conflicting reservation found — standard create available',
                confidence:             'HIGH',
                humanApprovalRequired:   false,
                canOverride:           false,
                authorizedUserId:       null,
                overrideReasons:         [],
                startDate:            $startDate,
                endDate:              $endDate,
                evaluatedAt:           now()->toIso8601String(),
                snapshotId:            $this->readinessService->currentSnapshotId(),
            );
        }

        // User authorization check
        $overrideService = $this->getConflictOverrideService();
        $canOverride = $overrideService->canOverride(
            userId:       $userId,
            propertyId:    $ilanId,
            ydlAuthority: $ydlAuthority,
            conflictReservationId: $conflict->id,
        );

        if (! $canOverride) {
            return new YdlOverrideRecommendation(
                conflictReservationId:     $conflict->id,
                ilanId:                 $ilanId,
                tenantId:               $tenantId,
                ydlAuthority:          $ydlAuthority,
                decision:               YdlOverrideRecommendation::DECISION_OVERRIDE_UNAUTHORIZED,
                decisionLabel:           'Yetkisiz',
                rationale:              "User #{$userId} not authorized for override on property #{$ilanId}",
                confidence:             'HIGH',
                humanApprovalRequired: false,
                canOverride:            false,
                authorizedUserId:        null,
                overrideReasons:          ['Unauthorized for override'],
                startDate:             $startDate,
                endDate:               $endDate,
                evaluatedAt:            now()->toIso8601String(),
                snapshotId:             $this->readinessService->currentSnapshotId(),
            );
        }

        // Ready to override
        return new YdlOverrideRecommendation(
            conflictReservationId:     $conflict->id,
            ilanId:                 $ilanId,
            tenantId:               $tenantId,
            ydlAuthority:          $ydlAuthority,
            decision:               YdlOverrideRecommendation::DECISION_OVERRIDE_READY,
            decisionLabel:           'Override Hazır',
            rationale:              "Conflict #{$conflict->id} confirmed. Override authorized for user #{$userId}.",
            confidence:             'HIGH',
            humanApprovalRequired: true, // Explicit human decision always
            canOverride:           true,
            authorizedUserId:        $userId,
            overrideReasons:         [],
            startDate:             $startDate,
            endDate:               $endDate,
            evaluatedAt:            now()->toIso8601String(),
            snapshotId:             $this->readinessService->currentSnapshotId(),
        );
    }

    /**
     * Step 2 (Wave 3): Request human approval for override.
     */
    public function requestOverrideApproval(
        YdlOverrideRecommendation $readiness,
        ?int $requestedBy = null,
    ): YdlOverrideApprovalToken {
        if (! $readiness->isReady()) {
            throw new \DomainException(
                "Cannot request override: reason: {$readiness->decisionLabel}. {$readiness->rationale}"
            );
        }

        $eventId = ReservationEvent::generateEventId(
            $readiness->ilanId,
            'OVERRIDE_' . $readiness->conflictReservationId,
            'OVERRIDE',
            'OVERRIDE',
        );

        if ($this->eventLog->eventExists($eventId)) {
            throw new \DomainException("Override event {$eventId} already processed.");
        }

        $now = now()->toIso8601String();
        $expiresAt = now()->addSeconds(YdlReservationOrchestrator::APPROVAL_TOKEN_TTL_SECONDS)->toIso8601String();

        return YdlOverrideApprovalToken::create(
            conflictReservationId:  $readiness->conflictReservationId,
            ilanId:              $readiness->ilanId,
            tenantId:            $readiness->tenantId,
            eventId:           $eventId,
            ydlAuthority:       $readiness->ydlAuthority,
            authorityContext:    $readiness->decisionLabel,
            startDate:         $readiness->startDate,
            endDate:           $readiness->endDate,
            recommendation:     $readiness->toArray(),
            requestedAt:        $now,
            expiresAt:          $expiresAt,
            requestedBy:        $requestedBy,
        );
    }

    /**
     * Step 3 (Wave 3): Execute override.
     *
     * PILOT-002 Authority Design — Canonical execution path:
     *
     *   1. Token validation
     *   2. STOP authority → BLOCKED (cannot override STOP ever)
     *   3. Idempotency check
     *   4. ConflictOverrideService::canOverride() — re-validation at execution time
     *      (readiness may be stale; execution-time check is the authoritative gate)
     *   5. Tenant isolation
     *   6. ReservationService::createReservationWithOverride() — atomic cancel + create
     *   7. Evidence → ReservationEventLog
     *
     * Red Line: Orchestrator NEVER calls createReservation() directly for override.
     * Red Line: canOverride() is called at execution time, not only at readiness.
     *
     * @throws \DomainException if token invalid/expired
     * @throws \Exception if canonical execution fails
     */
    public function executeOverride(
        YdlOverrideApprovalToken $token,
        int $approvedBy,
        array $guestData,
        ?int $userId = null,
        ?ReservationService $reservationService = null,
    ): YdlOverrideEvidence {
        // ── 1. Token validation ───────────────────────────────────────────
        $token->validateOrFail();

        // ── 2. STOP authority: final gate (via AuthorityEvaluator) ──────
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'STOP authority',
                reason:           'STOP authority — override not permitted',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        // ── 3. Idempotency check ────────────────────────────────────────
        if ($this->eventLog->eventExists($token->eventId)) {
            return YdlOverrideEvidence::unauthorized(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'Idempotent',
                reason:           "Event {$token->eventId} already in log",
            );
        }

        // ── 4. ConflictOverrideService: re-validate authorization at execution time
        // Readiness may be stale (e.g., user permissions revoked after approval).
        // Execution-time check is the authoritative gate per PILOT-002 invariant.
        // User who was authorized for override is stored in recommendation['authorizedUserId'].
        $authUserId = $token->recommendation['authorizedUserId'] ?? $token->requestedBy ?? $approvedBy;
        $overrideService = $this->getConflictOverrideService();
        $canOverride = $overrideService->canOverride(
            userId:               $authUserId,
            propertyId:            $token->ilanId,
            ydlAuthority:         $token->ydlAuthority,
            conflictReservationId: $token->conflictReservationId,
        );

        if (! $canOverride) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'ConflictOverrideService::canOverride() failed at execution',
                reason:           'User not authorized for override at execution time',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        // ── 5. Tenant isolation ─────────────────────────────────────────
        // Check: conflict reservation's tenant must match token's tenant.
        // If the conflicting reservation belongs to a different tenant,
        // the override authorization cannot be granted by this tenant.
        $conflictReservation = PropertyReservation::withoutGlobalScopes()
            ->find($token->conflictReservationId);

        if ($conflictReservation === null) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'Tenant isolation',
                reason:           'Conflict reservation not found',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        if ($conflictReservation->tenant_id !== $token->tenantId) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'Tenant isolation',
                reason:           'Cross-tenant override rejected: conflict belongs to different tenant',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        $ilan = Ilan::withoutGlobalScopes()->find($token->ilanId);
        if ($ilan === null || $ilan->tenant_id !== $token->tenantId) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  'Tenant isolation',
                reason:           'Cross-tenant override rejected: ilan belongs to different tenant',
            );
            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;
        }

        // ── 6. Canonical override execution via ReservationService ─────────
        $service = $reservationService ?? new ReservationService();

        try {
            $reservation = $service->createReservationWithOverride(
                propertyId:             $token->ilanId,
                startDate:              $token->startDate,
                endDate:                $token->endDate,
                guestData:              $guestData,
                userId:                 $userId,
                conflictReservationId:  $token->conflictReservationId,
                overrideAuthorizedBy:   $approvedBy,
            );

            // ── 7. Evidence ──────────────────────────────────────────────
            $evidence = YdlOverrideEvidence::success(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  $token->authorityContext,
                approvedBy:        $approvedBy,
                overrideReason:   'Override authorized by ConflictOverrideService::canOverride() at execution',
                reservationId:    $reservation->id,
            );

            $this->eventLog->append($evidence->toReservationEvent());
            return $evidence;

        } catch (\Exception $e) {
            $evidence = YdlOverrideEvidence::blocked(
                conflictReservationId: $token->conflictReservationId,
                ilanId:            $token->ilanId,
                tenantId:          $token->tenantId,
                eventId:           $token->eventId,
                ydlAuthority:     $token->ydlAuthority,
                authorityContext:  $token->authorityContext,
                reason:           $e->getMessage(),
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

    private function overrideBlocked(
        int    $ilanId,
        int    $tenantId,
        string $ydlAuthority,
        string $reason,
    ): YdlOverrideRecommendation {
        return new YdlOverrideRecommendation(
            conflictReservationId:     0,
            ilanId:                 $ilanId,
            tenantId:               $tenantId,
            ydlAuthority:          $ydlAuthority,
            decision:               YdlOverrideRecommendation::DECISION_OVERRIDE_BLOCKED,
            decisionLabel:           'Override Bloke Edildi',
            rationale:              $reason,
            confidence:             'HIGH',
            humanApprovalRequired:   false,
            canOverride:           false,
            authorizedUserId:        null,
            overrideReasons:         [],
            startDate:            '',
            endDate:              '',
            evaluatedAt:           now()->toIso8601String(),
            snapshotId:            $this->readinessService->currentSnapshotId(),
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
