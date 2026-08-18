<?php

namespace App\Services\Ydl\Platform;

/**
 * EventIdentityPolicy — Platform-level deterministic event ID generation.
 *
 * PILOT-001 + PILOT-002 common event identity primitive.
 *
 * Extracted from domain orchestrators:
 *   - YdlPublishOrchestrator (PILOT-001): buildEventId()
 *   - YdlReservationOrchestrator (PILOT-002): ReservationEvent::generateEventId()
 *
 * Responsibilities:
 *   - Deterministic event ID from pilot + identifiers + minute timestamp
 *   - Same inputs → same event_id (idempotent)
 *
 * Does NOT include:
 *   - Any domain semantics (publish, reservation, cancel, override, Ilan, PropertyReservation)
 *   - Business logic of any kind
 *   - Any knowledge of what the identifiers represent
 *
 * CRITICAL CONSTRAINT:
 *   EventIdentityPolicy has ZERO domain knowledge.
 *   It does NOT know about: publish, reservation, cancel, override,
 *   Ilan, PropertyReservation, ReservationService, IlanCrudService,
 *   or any domain-specific semantics.
 *
 * Pipeline ownership:
 *   P5 EventIdentityPolicy → eventId üretir (BU DOSYA)
 *           ↓
 *   P3 IdempotencyGuard   → daha önce işlendi mi? (READ ONLY)
 *           ↓
 *   Domain Orchestrator     → ne yapılacağına karar verir
 *           ↓
 *   Domain EventLog         → evidence yazar
 *
 * Domain-agnostic: all parameters are primitive strings.
 * No App\Models\... or App\Services\... imports.
 */
class EventIdentityPolicy implements EventIdentityPolicyInterface
{
    /**
     * @inheritDoc
     */
    public function generate(string $pilot, string ...$identifiers): string
    {
        $minuteTs = (string) (int) (time() / 60);
        $payload = $pilot . '|' . implode('|', $identifiers) . '|' . $minuteTs;
        return substr(hash('sha256', $payload), 0, 16);
    }
}
