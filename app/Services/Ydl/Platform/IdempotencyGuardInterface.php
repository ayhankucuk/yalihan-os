<?php

namespace App\Services\Ydl\Platform;

/**
 * IdempotencyGuardInterface — Platform-level event deduplication (read-only).
 *
 * PILOT-001 + PILOT-002 common idempotency primitive.
 *
 * Extracted from domain orchestrators:
 *   - YdlPublishOrchestrator (PILOT-001)
 *   - YdlReservationOrchestrator (PILOT-002)
 *
 * READ-ONLY — does NOT write to event log.
 *
 * Responsibilities:
 *   - eventId deduplication (has this eventId been recorded?)
 *
 * Does NOT include:
 *   - Event recording (domain orchestrator writes after successful execution)
 *   - Evidence production (domain orchestrator decides what to record)
 *   - EventId generation (P5 EventIdentityPolicy owns this)
 *
 * CRITICAL CONSTRAINT:
 *   IdempotencyGuard has ZERO domain knowledge.
 *   It does NOT know about: publish, reservation, cancel, override,
 *   Ilan, PropertyReservation, ReservationService, IlanCrudService,
 *   or any domain-specific "already done" semantics.
 *
 * Pipeline ownership:
 *   P5 EventIdentityPolicy → eventId üretir
 *           ↓
 *   P3 IdempotencyGuard   → daha önce işlendi mi? (READ ONLY)
 *           ↓
 *   Domain Orchestrator     → ne yapılacağına karar verir
 *           ↓
 *   Domain EventLog      → evidence yazar
 *
 * Domain-agnostic: all parameters are primitive strings.
 * No App\Models\... or App\Services\... imports.
 */
interface IdempotencyGuardInterface
{
    /**
     * Check whether an eventId has already been processed.
     *
     * READ ONLY — does not modify any state.
     *
     * @param string $eventId Unique event identifier
     * @return IdempotencyGuardResult
     */
    public function check(string $eventId): IdempotencyGuardResult;

    /**
     * Check if an eventId exists in the log.
     *
     * READ ONLY.
     *
     * @param string $eventId
     * @return bool
     */
    public function exists(string $eventId): bool;
}
