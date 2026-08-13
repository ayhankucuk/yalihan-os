<?php

namespace App\Services\Ydl\Platform;

/**
 * AuthorityEvaluator — Platform-level authority interpretation.
 *
 * PILOT-001 + PILOT-002 common authority evaluation.
 *
 * Extracts common authority interpretation from domain orchestrators:
 *   - YdlPublishOrchestrator (PILOT-001)
 *   - YdlReservationOrchestrator (PILOT-002)
 *
 * Authority semantics (invariant — MUST NOT change):
 *   STOP   → always blocks, no exceptions
 *   LIMITED → scope intersection checked
 *   FULL   → proceed to domain evaluation
 *
 * This class does NOT contain business logic.
 * Business logic stays in domain orchestrators.
 * Specifically: scope-to-blocker mapping is domain knowledge, not platform.
 *
 * Composition pattern: injected into domain orchestrators,
 * not inherited. Domain orchestrators call AuthorityEvaluator
 * for authority decisions, then apply their own business logic.
 *
 * Domain-agnostic: no imports from App\DTOs\Ydl\... or App\Models\...
 * All authority levels are primitive strings.
 */
class AuthorityEvaluator implements AuthorityEvaluatorInterface
{
    // Primitive authority level constants — platform-wide shared vocabulary
    public const STOP                    = 'STOP';
    public const LIMITED_BY_BLOCKER      = 'LIMITED_BY_BLOCKER';
    public const LIMITED                 = 'LIMITED';
    public const FULL                    = 'FULL';

    public function __construct()
    {
    }

    /**
     * Evaluate authority level and determine if operation can proceed.
     *
     * @param string $authority  STOP | LIMITED_BY_BLOCKER | LIMITED | FULL
     * @param string|null $taskScope  Caller-provided scope identifier
     * @param array|null $blockedScopes  Caller-provided blocked scope list (null = none)
     * @return AuthorityDecision
     */
    public function evaluate(
        string $authority,
        ?string $taskScope = null,
        ?array $blockedScopes = null,
    ): AuthorityDecision {
        // STOP always blocks — no exception
        if ($this->isStopAuthority($authority)) {
            return AuthorityDecision::blocked(
                $authority,
                'YDL authority STOP — sistem durduruldu',
                false,
                $taskScope,
            );
        }

        // LIMITED requires scope intersection check
        if ($this->isLimitedAuthority($authority)) {
            $blocked = $this->hasBlockingIntersection($taskScope ?? '', $blockedScopes);
            if ($blocked) {
                return AuthorityDecision::blocked(
                    $authority,
                    "Active blocker scope intersects with {$taskScope} workflow",
                    true,
                    $taskScope,
                );
            }
        }

        // FULL or LIMITED without intersection → proceed
        return AuthorityDecision::proceed($authority, $taskScope);
    }

    /**
     * Check if authority is STOP (always blocks).
     */
    public function isStopAuthority(string $authority): bool
    {
        return $authority === self::STOP;
    }

    /**
     * Check if authority is LIMITED (scope intersection may block).
     */
    public function isLimitedAuthority(string $authority): bool
    {
        return $authority === self::LIMITED_BY_BLOCKER || $authority === self::LIMITED;
    }

    /**
     * Check if task scope has blocking intersection with active blockers.
     *
     * Generic set intersection — no domain knowledge.
     * Caller (domain orchestrator) provides blockedScopes with domain-specific mapping.
     *
     * @param string $taskScope        e.g. 'property_publish', 'reservation_create'
     * @param array|null $blockedScopes List of blocked scope identifiers
     * @return bool
     */
    public function hasBlockingIntersection(string $taskScope, ?array $blockedScopes = null): bool
    {
        if ($taskScope === '' || $blockedScopes === null || $blockedScopes === []) {
            return false;
        }

        // Simple set intersection — domain orchestrator decides what to pass
        return in_array($taskScope, $blockedScopes, true);
    }

    /**
     * Get the reason why authority is blocked (for evidence).
     */
    public function getBlockReason(string $authority, ?string $taskScope = null): string
    {
        if ($this->isStopAuthority($authority)) {
            return 'YDL authority STOP — sistem durduruldu';
        }

        if ($this->isLimitedAuthority($authority)) {
            if ($taskScope !== null) {
                return "Active blocker scope intersects with {$taskScope} workflow";
            }
            return 'Active blocker scope present — scope intersection check required';
        }

        return 'Unknown authority level';
    }
}
