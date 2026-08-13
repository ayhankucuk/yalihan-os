<?php

namespace App\Services\Ydl\Platform;

/**
 * AuthorityEvaluatorInterface — Platform-level authority interpretation.
 *
 * PILOT-001 + PILOT-002 common authority evaluation.
 *
 * This interface is implemented by AuthorityEvaluator and consumed
 * by domain orchestrators (YdlPublishOrchestrator, YdlReservationOrchestrator).
 *
 * Authority semantics (invariant — MUST NOT change):
 *   STOP   → always blocks, no exceptions
 *   LIMITED → scope intersection checked
 *   FULL   → proceed to domain evaluation
 *
 * This interface does NOT contain business logic.
 * Business logic stays in domain orchestrators.
 * Specifically: scope-to-blocker mapping is domain knowledge, not platform.
 *
 * Domain-agnostic: all parameters are primitive strings/arrays.
 * No App\DTOs\Ydl\... or App\Models\... imports.
 */
interface AuthorityEvaluatorInterface
{
    /**
     * Evaluate authority level and determine if operation can proceed.
     *
     * @param string $authority  STOP | LIMITED_BY_BLOCKER | LIMITED | FULL
     * @param string|null $taskScope  Caller-provided scope identifier
     * @param array|null $blockedScopes  Caller-provided blocked scope list
     * @return AuthorityDecision
     */
    public function evaluate(
        string $authority,
        ?string $taskScope = null,
        ?array $blockedScopes = null,
    ): AuthorityDecision;

    /**
     * Check if authority is STOP (always blocks).
     */
    public function isStopAuthority(string $authority): bool;

    /**
     * Check if authority is LIMITED (scope intersection may block).
     */
    public function isLimitedAuthority(string $authority): bool;

    /**
     * Check if task scope has blocking intersection with blocked scopes.
     *
     * Generic set intersection — domain orchestrator decides what to pass.
     *
     * @param string $taskScope  e.g. 'property_publish', 'reservation_create'
     * @param array|null $blockedScopes  List of blocked scope identifiers
     * @return bool
     */
    public function hasBlockingIntersection(string $taskScope, ?array $blockedScopes = null): bool;

    /**
     * Get the reason why authority is blocked (for evidence).
     */
    public function getBlockReason(string $authority, ?string $taskScope = null): string;
}
