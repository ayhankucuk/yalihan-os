<?php

namespace App\Services\Concierge;

/**
 * GuestConciergePilotGate — Runtime pilot allowlist enforcement.
 *
 * MICRO PILOT READINESS SPRINT — PILOT-GATE-01
 * SAAB Orchestrator Decision: c7bb116
 *
 * PILOT-GATE-01 INVARIANT:
 *   Empty allowlist = fail-closed (tam blokaj)
 *   enabled=true + empty allowlist = tam blokaj, FULL ROLLOUT DEĞIL
 *
 * Decision flow:
 *   RoutingDecision.isGuest() = false → BLOCKED
 *       ↓ (true)
 *   Reservation allowlist not empty?
 *     ├─ YES → reservation_id allowlisted? → YES=ALLOWED, NO=BLOCKED
 *     └─ NO  → tenant_id allowlisted?
 *                → YES=ALLOWED
 *                → NO (tenant allowlist empty OR id not in list) = BLOCKED (FAIL-CLOSED)
 *
 * All checks are deterministic PHP — never LLM.
 */
class GuestConciergePilotGate
{
    /** @var array<int> */
    private array $allowedTenantIds;

    /** @var array<int> */
    private array $allowedReservationIds;

    public function __construct()
    {
        $config = config('concierge.pilot', []);

        $this->allowedTenantIds = $this->normalizeIds(
            $config['tenant_ids'] ?? []
        );
        $this->allowedReservationIds = $this->normalizeIds(
            $config['reservation_ids'] ?? []
        );
    }

    /**
     * PILOT-GATE-01: Check if routing decision is authorized for Concierge.
     *
     * FAIL-CLOSED INVARIANT:
     *   - Empty tenant allowlist → BLOCKED
     *   - Empty reservation allowlist → falls back to tenant check
     *   - Empty tenant allowlist → FAIL-CLOSED (no one passes)
     *   - Only GUEST_ACTIVE/FUTURE/PAST can pass
     *   - LEAD and UNKNOWN are always BLOCKED
     *
     * @param RoutingDecision $decision
     * @return bool
     */
    public function isAllowed(RoutingDecision $decision): bool
    {
        // Only guest-type decisions can enter Concierge pipeline
        if (!$decision->isGuest()) {
            return false;
        }

        // Reservation-level allowlist takes priority
        if (!empty($this->allowedReservationIds)) {
            return $this->isReservationAllowed($decision->reservationId);
        }

        // Tenant-level allowlist fallback
        return $this->isTenantAllowed($decision->tenantId);
    }

    /**
     * Check if a tenant is in the pilot allowlist.
     *
     * @param int|null $tenantId
     * @return bool
     */
    public function isTenantAllowed(?int $tenantId): bool
    {
        if ($tenantId === null) {
            return false;
        }

        // FAIL-CLOSED INVARIANT: empty allowlist = no one is allowed
        if (empty($this->allowedTenantIds)) {
            return false;
        }

        return in_array($tenantId, $this->allowedTenantIds, true);
    }

    /**
     * Check if a reservation is explicitly allowlisted.
     * Overrides tenant allowlist.
     *
     * @param int|null $reservationId
     * @return bool
     */
    public function isReservationAllowed(?int $reservationId): bool
    {
        if ($reservationId === null) {
            return false;
        }

        if (empty($this->allowedReservationIds)) {
            return false;
        }

        return in_array($reservationId, $this->allowedReservationIds, true);
    }

    /**
     * PILOT-GATE-01 INVARIANT: Is the current configuration safe?
     *
     * Returns false if allowlists are misconfigured:
     *   - enabled=true + empty allowlist → unsafe (accidental full rollout)
     *
     * @return bool
     */
    public function isSafeConfiguration(): bool
    {
        // Reservation allowlist = sufficient
        if (!empty($this->allowedReservationIds)) {
            return true;
        }

        // Tenant allowlist = sufficient
        if (!empty($this->allowedTenantIds)) {
            return true;
        }

        // Empty = fail-closed
        return false;
    }

    /**
     * Get gate status for logging/debugging.
     *
     * @return array{tenant_count: int, reservation_count: int, is_safe: bool}
     */
    public function getStatus(): array
    {
        return [
            'tenant_count' => count($this->allowedTenantIds),
            'reservation_count' => count($this->allowedReservationIds),
            'is_safe' => $this->isSafeConfiguration(),
        ];
    }

    /**
     * Normalize ID list to array of positive integers.
     *
     * @param mixed $ids
     * @return array<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', $ids),
            fn(int $id): bool => $id > 0,
        ));
    }
}
