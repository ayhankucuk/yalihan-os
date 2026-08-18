<?php

namespace App\Services\Concierge;

/**
 * RoutingDecision — Immutable result of GuestConciergeRouter.resolve()
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * This is a Value Object returned by the router.
 * It contains the routing decision and all context needed for processing.
 *
 * GC-D1: Canonical routing authority — NOT LLM
 */
final readonly class RoutingDecision
{
    private function __construct(
        public string $decision,       // GUEST_ACTIVE, GUEST_FUTURE, GUEST_PAST, LEAD, UNKNOWN
        public string $phone,
        public ?int $tenantId = null,
        public ?int $reservationId = null,
        public ?int $ilanId = null,
        public ?int $leadId = null,
        public ?int $kisiId = null,
        public ?string $guestName = null,
        public ?string $reason = null,
        public ?string $checkinDate = null,
        public ?string $checkoutDate = null,
    ) {}

    // ── Factory Methods ────────────────────────────────────────────────

    public static function guestActive(
        string $phone,
        int $tenantId,
        int $reservationId,
        int $ilanId,
        ?string $guestName = null,
    ): self {
        return new self(
            decision: 'GUEST_ACTIVE',
            phone: $phone,
            tenantId: $tenantId,
            reservationId: $reservationId,
            ilanId: $ilanId,
            guestName: $guestName,
        );
    }

    public static function guestFuture(
        string $phone,
        int $tenantId,
        int $reservationId,
        int $ilanId,
        ?string $guestName = null,
        ?string $checkinDate = null,
    ): self {
        return new self(
            decision: 'GUEST_FUTURE',
            phone: $phone,
            tenantId: $tenantId,
            reservationId: $reservationId,
            ilanId: $ilanId,
            guestName: $guestName,
            checkinDate: $checkinDate,
        );
    }

    public static function guestPast(
        string $phone,
        int $tenantId,
        int $reservationId,
        int $ilanId,
        ?string $guestName = null,
        ?string $checkoutDate = null,
    ): self {
        return new self(
            decision: 'GUEST_PAST',
            phone: $phone,
            tenantId: $tenantId,
            reservationId: $reservationId,
            ilanId: $ilanId,
            guestName: $guestName,
            checkoutDate: $checkoutDate,
        );
    }

    public static function lead(
        string $phone,
        int $tenantId,
        int $leadId,
    ): self {
        return new self(
            decision: 'LEAD',
            phone: $phone,
            tenantId: $tenantId,
            leadId: $leadId,
        );
    }

    public static function unknown(
        string $phone,
        ?string $reason = null,
        ?int $kisiId = null,
        ?int $tenantId = null,
    ): self {
        return new self(
            decision: 'UNKNOWN',
            phone: $phone,
            reason: $reason,
            kisiId: $kisiId,
            tenantId: $tenantId,
        );
    }

    // ── Query Helpers ─────────────────────────────────────────────────

    public function isGuest(): bool
    {
        return in_array($this->decision, ['GUEST_ACTIVE', 'GUEST_FUTURE', 'GUEST_PAST'], true);
    }

    public function isLead(): bool
    {
        return $this->decision === 'LEAD';
    }

    public function isUnknown(): bool
    {
        return $this->decision === 'UNKNOWN';
    }

    public function isActiveGuest(): bool
    {
        return $this->decision === 'GUEST_ACTIVE';
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function hasReservation(): bool
    {
        return $this->reservationId !== null;
    }

    public function hasIlan(): bool
    {
        return $this->ilanId !== null;
    }
}
