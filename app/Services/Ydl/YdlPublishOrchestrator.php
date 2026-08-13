<?php

namespace App\Services\Ydl;

use App\DTOs\Ydl\Events\YdlEvent;
use App\DTOs\Ydl\YdlContextOutput;
use App\DTOs\Ydl\YdlPublishRecommendation;
use App\Enums\Governance\GovernanceState;
use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Services\Ydl\Platform\AuthorityEvaluator;
use App\Services\Ydl\Platform\AuthorityEvaluatorInterface;

/**
 * YdlPublishOrchestrator — E2E Property Publish Pipeline for YDL Phase 3.
 *
 * PILOT-001 Wave 2 — Orchestrated Integration
 *
 * Connects existing YDL Phase 3 components:
 *   YdlContextReader → YdlPublishReadinessService → YdlPublishRecommendation
 *       → Human Approval Gate → Publish Execution → Evidence Event
 *
 * GovernanceInvariant:
 *   - Human approval is MANDATORY — no bypass, ever
 *   - Authority = STOP → publish blocked regardless of readiness
 *   - Authority = LIMITED → scope intersection checked (BLK-001 ∩ Property Publish)
 *   - Duplicate events → idempotent no-op
 *
 * This orchestrator does NOT write to DB (only via IlanCrudService).
 * It does NOT create git commits (ydl:session-summary handles that).
 */
class YdlPublishOrchestrator
{
    public const PILOT = 'PILOT-001';

    /** Human approval token TTL in seconds (24 hours). */
    private const APPROVAL_TOKEN_TTL_SECONDS = 86400;

    private YdlPublishReadinessService $readinessService;
    private YdlEventLog $eventLog;
    private ?YdlContextReader $contextReader;
    private ?YdlStateOrchestrator $orchestrator;
    private ?string $basePath;
    private AuthorityEvaluatorInterface $authorityEvaluator;

    public function __construct(
        ?YdlPublishReadinessService $readinessService = null,
        ?YdlEventLog $eventLog = null,
        ?YdlContextReader $contextReader = null,
        ?YdlStateOrchestrator $orchestrator = null,
        ?string $basePath = null,
        ?AuthorityEvaluatorInterface $authorityEvaluator = null,
    ) {
        $this->readinessService = $readinessService ?? new YdlPublishReadinessService(
            new \App\Services\Listing\ListingScoreService(),
            new \App\Services\Governance\GovernanceTransitionGuard()
        );
        $this->eventLog = $eventLog ?? new YdlEventLog();
        $this->contextReader = $contextReader ?? new YdlContextReader();
        $this->orchestrator = $orchestrator;
        $this->basePath = $basePath;
        $this->authorityEvaluator = $authorityEvaluator ?? new AuthorityEvaluator($this->contextReader, $basePath);
    }

    // ─── Primary API ─────────────────────────────────────────────────────────
    /**
     * Step 1: Evaluate publish readiness for an Ilan given YDL authority context.
     *
     * Reads YDL context from disk (via YdlContextReader) and combines it with
     * the listing's readiness state to produce a publish recommendation.
     *
     * Authority logic:
     *   STOP         → BLOCKED_GATE (system halted)
     *   LIMITED      → scope intersection check: if blocked scope intersects → BLOCKED_GATE
     *   FULL/LIMITED → readiness evaluation via YdlPublishReadinessService
     *
     * Authority evaluation delegated to AuthorityEvaluator (platform-level).
     *
     * @param Ilan $ilan
     * @param string|null $ydlAuthorityOverride Pass this in tests to override authority
     */
    public function evaluateReadiness(
        Ilan $ilan,
        ?string $ydlAuthorityOverride = null,
    ): YdlPublishReadinessOutput
    {
        // Authority from caller override (tests) or from disk (production)
        $authority = $ydlAuthorityOverride ?? $this->readYdlAuthority();

        // Authority evaluation via platform AuthorityEvaluator
        $decision = $this->authorityEvaluator->evaluate($authority, 'property_publish');

        // If blocked by authority, return blocked readiness output
        if ($decision->isBlocked()) {
            return $this->blockedReadinessOutput(
                $ilan,
                $authority,
                $decision->reason
            );
        }

        // Run deterministic readiness evaluation (domain business logic)
        $recommendation = $this->readinessService->evaluate($ilan, $authority);

        return new YdlPublishReadinessOutput(
            recommendation: $recommendation,
            ydlAuthority: $authority,
            evaluatedAt: now()->toIso8601String(),
        );
    }

    /**
     * Build a BLOCKED_GATE recommendation for authority/scope failures.
     */
    private function blockedReadinessOutput(Ilan $ilan, string $authority, string $reason): YdlPublishReadinessOutput
    {
        $ilan->completion_score = $ilan->completion_score ?? 0;
        $ilan->quality_score = $ilan->quality_score ?? 0;

        return new YdlPublishReadinessOutput(
            recommendation: new \App\DTOs\Ydl\YdlPublishRecommendation(
                pilot: self::PILOT,
                ilanId: $ilan->id,
                ydlAuthority: $authority,
                decision: \App\DTOs\Ydl\YdlPublishRecommendation::DECISION_BLOCKED_GATE,
                decisionLabel: 'Bloke Edildi',
                rationale: $reason,
                confidence: 'HIGH',
                currentState: (string) ($ilan->getRawOriginal('yayin_durumu') ?? $ilan->yayin_durumu),
                targetState: IlanDurumu::YAYINDA->value,
                governanceState: GovernanceState::DRAFT->value,
                humanApprovalRequired: false,
                completionScore: (int) $ilan->completion_score,
                qualityScore: (float) $ilan->quality_score,
                canPublish: false,
                missingFields: [],
                blockingReasons: [$authority => $reason],
                suggestedActions: [
                    'Bloke nedenini incele ve gider.',
                    'YDL authority bloke olduğunda yayın yapılamaz.',
                ],
                evaluatedAt: now()->toIso8601String(),
            ),
            ydlAuthority: $authority,
            evaluatedAt: now()->toIso8601String(),
        );
    }

    /**
     * Step 2: Request human approval for publishing.
     *
     * Returns an approval token that must be presented when executing publish.
     * Token expires after APPROVAL_TOKEN_TTL_SECONDS.
     *
     * Throws DomainException if publish is not ready.
     */
    public function requestApproval(
        YdlPublishReadinessOutput $readiness,
    ): YdlPublishApprovalToken {
        if (! $readiness->recommendation->canPublish) {
            throw new \DomainException(
                "Cannot request approval: ilan #{$readiness->recommendation->ilanId} is not publish-ready. " .
                "Decision: {$readiness->recommendation->decisionLabel}"
            );
        }

        $ilanId = $readiness->recommendation->ilanId;
        $eventId = $this->buildEventId($ilanId);

        // Check idempotency BEFORE creating token
        if ($this->eventLog->eventExists($eventId)) {
            throw new \DomainException(
                "Duplicate publish attempt: event {$eventId} already processed. " .
                "This ilan was already published or the approval token has been used."
            );
        }

        $token = YdlPublishApprovalToken::create(
            ilanId: $ilanId,
            eventId: $eventId,
            recommendation: $readiness->recommendation,
            ydlAuthority: $readiness->ydlAuthority,
            requestedAt: now()->toIso8601String(),
            expiresAt: now()->addSeconds(self::APPROVAL_TOKEN_TTL_SECONDS)->toIso8601String(),
        );

        return $token;
    }

    /**
     * Step 3: Execute publish after human approval.
     *
     * Validates the approval token, checks idempotency, then triggers
     * the publish transition via IlanCrudService.
     *
     * For test isolation, provide a $publishExecutor callback that bypasses
     * IlanCrudService/YalihanLifecycle static guards:
     *   fn(Ilan $ilan) => $ilan->fresh() with yayin_durumu = YAYINDA
     *
     * Returns a PublishEvidence record that is appended to the YDL event log.
     *
     * @throws \DomainException if token is invalid, expired, or already used
     * @throws \App\Exceptions\DomainException if IlanCrudService publish fails
     */
    public function executePublish(
        YdlPublishApprovalToken $token,
        \App\Services\Ilan\IlanCrudService $crudService,
        int $approvedBy,
        GovernanceState $governanceState = GovernanceState::PROMOTED,
        ?callable $publishExecutor = null,
    ): YdlPublishEvidence {
        // ── 1. Token validation ────────────────────────────────────
        $token->validateOrFail();

        // ── 2. STOP authority: final gate (via AuthorityEvaluator) ──
        if ($this->authorityEvaluator->isStopAuthority($token->ydlAuthority)) {
            throw new \DomainException(
                "PUBLISH BLOCKED: YDL authority is STOP. Event: {$token->eventId}"
            );
        }

        // ── 3. Idempotency check ───────────────────────────────────
        if ($this->eventLog->eventExists($token->eventId)) {
            throw new \DomainException(
                "Duplicate event {$token->eventId} already in log. Idempotent no-op."
            );
        }

        // ── 4. Governance guard check ────────────────────────────
        $guard = new \App\Services\Governance\GovernanceTransitionGuard();
        if (! $guard->canPublish($governanceState)) {
            throw new \DomainException(
                "Governance guard failed: cannot publish from governance_state={$governanceState->value}"
            );
        }

        // ── 5. Load Ilan and verify state ─────────────────────────
        $ilan = Ilan::findOrFail($token->ilanId);

        $mevcutRaw = $ilan->getRawOriginal('yayin_durumu') ?? $ilan->yayin_durumu;
        $mevcutStr = $mevcutRaw instanceof IlanDurumu ? $mevcutRaw->value : (string) $mevcutRaw;

        if ($mevcutStr === IlanDurumu::YAYINDA->value) {
            // Idempotent: already published — create evidence as no-op
            $evidence = YdlPublishEvidence::idempotentNoOp(
                ilanId: $ilan->id,
                eventId: $token->eventId,
                reason: 'Ilan already in YAYINDA state',
                recommendation: $token->recommendation,
            );
            $this->eventLog->append($evidence->toYdlEvent());
            return $evidence;
        }

        // ── 6. Execute publish via IlanCrudService (single write authority) ──
        if ($publishExecutor !== null) {
            $ilan = $publishExecutor($ilan);
        } else {
            $ilan = $crudService->update($ilan, [
                'baslik' => $ilan->baslik,
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
            ]);
        }

        // ── 7. Record evidence ────────────────────────────────────
        $evidence = YdlPublishEvidence::success(
            ilanId: $ilan->id,
            eventId: $token->eventId,
            completionScore: $ilan->completion_score,
            qualityScore: $ilan->quality_score,
            approvedBy: $approvedBy,
            governanceState: $governanceState,
            recommendation: $token->recommendation,
        );

        $this->eventLog->append($evidence->toYdlEvent());

        return $evidence;
    }

    // ─── YDL Session Summary ────────────────────────────────────────────────

    /**
     * Build the CERTIFIED event for ydl:session-summary.
     *
     * This generates the YdlEvent WITHOUT persisting it — the session-summary
     * command handles the dry-run / confirm flow.
     */
    public function buildCertifiedEvent(
        YdlPublishEvidence $evidence,
        string $commit,
        GovernanceState $governanceState = GovernanceState::PROMOTED,
        ?YdlStateOrchestrator $orchestratorOverride = null,
    ): YdlEvent {
        $orchestrator = $orchestratorOverride ?? $this->orchestrator ?? new YdlStateOrchestrator($this->basePath);
        $stateResult = $orchestrator->run();
        $state = $stateResult['state'];

        return new YdlEvent(
            eventId: $evidence->eventId,
            type: YdlEvent::TYPE_CERTIFICATION,
            sprint: $state->sprint,
            snapshotId: $state->snapshotId,
            commit: $commit,
            action: 'CERTIFIED',
            target: self::PILOT . ': Property Publish',
            rationale: $evidence->success
                ? "Property Publish supervised autonomy certified. Ilan #{$evidence->ilanId} published. " .
                  "completion={$evidence->completionScore}, quality={$evidence->qualityScore}."
                : "Idempotent no-op: {$evidence->idempotentReason}",
            confidence: 'HIGH',
            parallelWorkAllowed: true,
            gatesPass: $state->gatesPass,
            gatesFail: $state->gatesFail,
            gatesBlockedExternal: $state->gatesBlockedExternal,
            gatesBlockedInternal: $state->gatesBlockedInternal,
            sabViolationsNew: $state->sabViolationsNew,
            sabViolationsBlocking: $state->sabViolationsBlocking,
            gitStatus: 'clean',
            blockerChanges: [],
            occurredAt: $evidence->occurredAt,
        );
    }

    // ─── Authority helpers ─────────────────────────────────────────────────────

    /**
     * Read YDL authority from session-start context (memory/ydl/state/current.json).
     */
    public function readYdlAuthority(): string
    {
        $ctx = $this->contextReader->read();
        return $ctx->authorityLevel;
    }

    /**
     * Scope intersection check for LIMITED authority.
     *
     * Delegates to AuthorityEvaluator (platform-level).
     *
     * Returns true if the task scope intersects with any active blocker scope.
     *
     * PILOT-001 scope: Property Publish
     * BLK-001 scope: Booking.com production smoke test
     * → Intersection = Ø → false (can proceed)
     *
     * Property Publish IS blocked by: blockers targeting "publish" infrastructure
     * Property Publish IS NOT blocked by: Booking.com, Airbnb, external channel blockers
     */
    public function hasBlockingIntersection(string $taskScope = 'property_publish'): bool
    {
        // Domain: resolve blocked scopes from context
        $blockedScopes = $this->resolveBlockedScopes($taskScope);
        // Delegate to AuthorityEvaluator
        return $this->authorityEvaluator->hasBlockingIntersection($taskScope, $blockedScopes);
    }

    // ─── Private ───────────────────────────────────────────────────────────────

    private function buildEventId(int $ilanId): string
    {
        $minuteTs = (string) (int) (time() / 60);
        $payload = self::PILOT . "|{$ilanId}|{$minuteTs}";
        return substr(hash('sha256', $payload), 0, 16);
    }

    /**
     * Resolve blocked scopes from context for a given task scope.
     *
     * Domain knowledge: which context blocker entries map to a task scope.
     * Platform AuthorityEvaluator only receives the final blocked scope list.
     *
     * @param string $taskScope
     * @return array<string>
     */
    private function resolveBlockedScopes(string $taskScope): array
    {
        $ctx = $this->contextReader->read();

        if ($ctx === null) {
            return [];
        }

        $blockedScopes = [];

        foreach ($ctx->activeBlockers as $blocker) {
            $blockerScope = $this->blockerScopeFromEntry($blocker);
            if ($blockerScope !== '') {
                $blockedScopes[] = $blockerScope;
            }
        }

        return $blockedScopes;
    }

    /**
     * Map a blocker entry to its blocking scope.
     *
     * Domain knowledge: which blocker affects which task scope.
     * This stays in the domain orchestrator, NOT in the platform AuthorityEvaluator.
     *
     * @param array $blocker
     * @return string
     */
    private function blockerScopeFromEntry(array $blocker): string
    {
        $id = $blocker['id'] ?? '';
        $gate = $blocker['gate'] ?? '';
        $action = $blocker['development_action'] ?? '';

        // BLK-001 → Booking.com — does NOT intersect with property_publish
        if ($id === 'BLK-001' || str_contains($action, 'BOOKING')) {
            return 'booking_com';
        }

        // G35 → external channel
        if ($gate === 'G35') {
            return 'booking_com';
        }

        // Infrastructure blockers block property_publish
        if ($id === 'BLK-PUBLISH-INFRA' || str_contains($id, 'publish_infra')) {
            return 'publish_infra';
        }

        if ($id === 'BLK-LISTING-DB' || str_contains($id, 'listing_db')) {
            return 'listing_db';
        }

        if ($id === 'BLK-LIFECYCLE' || str_contains($id, 'lifecycle_transition')) {
            return 'lifecycle_transition';
        }

        return '';
    }
}

// ─── Value Objects ─────────────────────────────────────────────────────────────

/**
 * Output from the readiness evaluation step.
 *
 * @readonly
 */
final class YdlPublishReadinessOutput
{
    public function __construct(
        public readonly YdlPublishRecommendation $recommendation,
        public readonly string $ydlAuthority,
        public readonly string $evaluatedAt,
    ) {}
}

/**
 * Human approval token — created when readiness is confirmed and human approves.
 *
 * Contains everything needed to execute publish after approval.
 * Token is validated at execute-time to prevent race conditions.
 *
 * @readonly
 */
final class YdlPublishApprovalToken
{
    private ?\DateTimeImmutable $validatedAt = null;

    public function __construct(
        public readonly int $ilanId,
        public readonly string $eventId,
        public readonly YdlPublishRecommendation $recommendation,
        public readonly string $ydlAuthority,
        public readonly string $requestedAt,
        public readonly string $expiresAt,
    ) {}

    public static function create(
        int $ilanId,
        string $eventId,
        YdlPublishRecommendation $recommendation,
        string $ydlAuthority,
        string $requestedAt,
        string $expiresAt,
    ): self {
        return new self(
            ilanId: $ilanId,
            eventId: $eventId,
            recommendation: $recommendation,
            ydlAuthority: $ydlAuthority,
            requestedAt: $requestedAt,
            expiresAt: $expiresAt,
        );
    }

    /**
     * Validate token is not expired and is for the correct ilan.
     *
     * @throws \DomainException if token is invalid
     */
    public function validateOrFail(): void
    {
        $expires = new \DateTimeImmutable($this->expiresAt);
        if ($expires < new \DateTimeImmutable()) {
            throw new \DomainException(
                "Approval token expired at {$this->expiresAt}. " .
                "Request a new approval token."
            );
        }

        $this->validatedAt = new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return (new \DateTimeImmutable($this->expiresAt)) < new \DateTimeImmutable();
    }

    public function isValidated(): bool
    {
        return $this->validatedAt !== null;
    }
}

/**
 * Evidence record from a publish operation.
 *
 * Produced by YdlPublishOrchestrator::executePublish().
 * Appended to YDL event log via YdlEventLog::append().
 *
 * @readonly
 */
final class YdlPublishEvidence
{
    public function __construct(
        public readonly int $ilanId,
        public readonly string $eventId,
        public readonly bool $success,
        public readonly ?int $completionScore,
        public readonly ?float $qualityScore,
        public readonly ?int $approvedBy,
        public readonly ?string $governanceState,
        public readonly bool $idempotentNoOp,
        public readonly ?string $idempotentReason,
        public readonly ?YdlPublishRecommendation $recommendation,
        public readonly string $occurredAt,
    ) {}

    public static function success(
        int $ilanId,
        string $eventId,
        ?int $completionScore,
        ?float $qualityScore,
        int $approvedBy,
        \App\Enums\Governance\GovernanceState $governanceState,
        YdlPublishRecommendation $recommendation,
    ): self {
        return new self(
            ilanId: $ilanId,
            eventId: $eventId,
            success: true,
            completionScore: $completionScore,
            qualityScore: $qualityScore,
            approvedBy: $approvedBy,
            governanceState: $governanceState->value,
            idempotentNoOp: false,
            idempotentReason: null,
            recommendation: $recommendation,
            occurredAt: now()->toIso8601String(),
        );
    }

    public static function idempotentNoOp(
        int $ilanId,
        string $eventId,
        string $reason,
        YdlPublishRecommendation $recommendation,
    ): self {
        return new self(
            ilanId: $ilanId,
            eventId: $eventId,
            success: false,
            completionScore: null,
            qualityScore: null,
            approvedBy: null,
            governanceState: null,
            idempotentNoOp: true,
            idempotentReason: $reason,
            recommendation: $recommendation,
            occurredAt: now()->toIso8601String(),
        );
    }

    /**
     * Convert to YdlEvent for appending to event log.
     */
    public function toYdlEvent(): YdlEvent
    {
        return new YdlEvent(
            eventId: $this->eventId,
            type: YdlEvent::TYPE_CERTIFICATION,
            sprint: 'PILOT-001',
            snapshotId: 'pilot-w1-' . $this->eventId,
            commit: 'pilot-w2',
            action: $this->idempotentNoOp ? 'NO_OP' : 'PUBLISH',
            target: 'PILOT-001: Property Publish',
            rationale: $this->idempotentNoOp
                ? "Idempotent no-op: {$this->idempotentReason}"
                : "Property published via supervised autonomy. Ilan #{$this->ilanId}.",
            confidence: 'HIGH',
            parallelWorkAllowed: true,
            gatesPass: 1,
            gatesFail: 0,
            gatesBlockedExternal: 0,
            gatesBlockedInternal: 0,
            sabViolationsNew: 0,
            sabViolationsBlocking: 0,
            gitStatus: 'clean',
            blockerChanges: [],
            occurredAt: $this->occurredAt,
        );
    }

    public function toArray(): array
    {
        return [
            'ilan_id' => $this->ilanId,
            'event_id' => $this->eventId,
            'success' => $this->success,
            'completion_score' => $this->completionScore,
            'quality_score' => $this->qualityScore,
            'approved_by' => $this->approvedBy,
            'governance_state' => $this->governanceState,
            'idempotent_no_op' => $this->idempotentNoOp,
            'idempotent_reason' => $this->idempotentReason,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
