<?php

namespace App\Services\Concierge;

use App\Models\Kisi;
use App\Models\Lead;
use App\Models\PropertyReservation;
use App\Services\Notification\GuestCommunicationPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * GuestConciergeRouter — Canonical routing authority for inbound guest messages.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Routing Decision:
 *   The AI/LLM does NOT make routing decisions.
 *   This deterministic service resolves guest context and returns a RoutingDecision.
 *
 * Pipeline:
 *   WhatsApp message
 *     → ResolveWhatsAppInboundJob (tenant-agnostic)
 *       → GuestConciergeRouter.resolve()
 *         → ProcessGuestMessageJob (tenant-aware)
 *           → GuestConciergeAuthorityPolicy
 *             → Hermes (LLM intent only)
 *               → Answer/Action/Escalate
 *
 * GC-D1: Canonical routing authority — NOT LLM
 */
class GuestConciergeRouter
{
    public function __construct(
        private readonly GuestCommunicationPolicy $policy
    ) {}

    /**
     * Resolve inbound message routing decision.
     *
     * This is tenant-agnostic — it resolves who the sender is and
     * returns context for the tenant-aware job to process.
     *
     * @return RoutingDecision
     */
    public function resolve(string $phone, ?string $name = null): RoutingDecision
    {
        // 1. Normalize phone number
        $normalizedPhone = $this->policy->normalizePhone($phone);
        if ($normalizedPhone === null) {
            Log::warning('[GuestConciergeRouter] Unparseable phone number', ['phone' => $phone]);
            return RoutingDecision::unknown(phone: $phone, reason: 'unparseable_phone');
        }

        // 2. Try to find ACTIVE reservation (current date range)
        $reservation = $this->findActiveReservation($normalizedPhone);
        if ($reservation !== null) {
            Log::info('[GuestConciergeRouter] Guest with active reservation', [
                'phone' => $normalizedPhone,
                'reservation_id' => $reservation->id,
                'tenant_id' => $reservation->tenant_id,
            ]);
            return RoutingDecision::guestActive(
                phone: $normalizedPhone,
                tenantId: $reservation->tenant_id,
                reservationId: $reservation->id,
                ilanId: $reservation->ilan_id ?? $reservation->property_id,
                guestName: $reservation->guest_name,
            );
        }

        // 3. Try to find FUTURE reservation
        $futureReservation = $this->findFutureReservation($normalizedPhone);
        if ($futureReservation !== null) {
            Log::info('[GuestConciergeRouter] Guest with future reservation', [
                'phone' => $normalizedPhone,
                'reservation_id' => $futureReservation->id,
                'tenant_id' => $futureReservation->tenant_id,
            ]);
            return RoutingDecision::guestFuture(
                phone: $normalizedPhone,
                tenantId: $futureReservation->tenant_id,
                reservationId: $futureReservation->id,
                ilanId: $futureReservation->ilan_id ?? $futureReservation->property_id,
                guestName: $futureReservation->guest_name,
                checkinDate: $futureReservation->start_date,
            );
        }

        // 4. Try to find PAST reservation
        $pastReservation = $this->findPastReservation($normalizedPhone);
        if ($pastReservation !== null) {
            Log::info('[GuestConciergeRouter] Guest with past reservation', [
                'phone' => $normalizedPhone,
                'reservation_id' => $pastReservation->id,
                'tenant_id' => $pastReservation->tenant_id,
            ]);
            return RoutingDecision::guestPast(
                phone: $normalizedPhone,
                tenantId: $pastReservation->tenant_id,
                reservationId: $pastReservation->id,
                ilanId: $pastReservation->ilan_id ?? $pastReservation->property_id,
                guestName: $pastReservation->guest_name,
                checkoutDate: $pastReservation->end_date,
            );
        }

        // 5. Try to find Kisi by phone (for consent lookup)
        $kisi = $this->findKisi($normalizedPhone);

        // 6. Try to find Lead by phone
        $lead = $this->findLead($normalizedPhone);
        if ($lead !== null) {
            Log::info('[GuestConciergeRouter] Lead identified', [
                'phone' => $normalizedPhone,
                'lead_id' => $lead->id,
                'tenant_id' => $lead->tenant_id,
            ]);
            return RoutingDecision::lead(
                phone: $normalizedPhone,
                tenantId: $lead->tenant_id,
                leadId: $lead->id,
            );
        }

        // 7. If we found Kisi but no reservation/lead — escalate to human
        if ($kisi !== null) {
            Log::info('[GuestConciergeRouter] Kisi found but no reservation/lead — escalating', [
                'phone' => $normalizedPhone,
                'kisi_id' => $kisi->id,
                'tenant_id' => $kisi->tenant_id,
            ]);
            return RoutingDecision::unknown(
                phone: $normalizedPhone,
                reason: 'no_active_reservation',
                kisiId: $kisi->id,
                tenantId: $kisi->tenant_id,
            );
        }

        // 8. Completely unknown — escalate
        Log::warning('[GuestConciergeRouter] Unknown sender — escalating', [
            'phone' => $normalizedPhone,
        ]);
        return RoutingDecision::unknown(
            phone: $normalizedPhone,
            reason: 'no_match',
        );
    }

    /**
     * Find active reservation (check-in date <= today <= check-out date).
     */
    protected function findActiveReservation(string $phone): ?PropertyReservation
    {
        $today = Carbon::today()->toDateString();

        return PropertyReservation::query()
            ->where('guest_phone', $phone)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereIn('reservation_state', ['confirmed', 'CHECKED_IN'])
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Find next upcoming reservation.
     */
    protected function findFutureReservation(string $phone): ?PropertyReservation
    {
        $today = Carbon::today()->toDateString();

        return PropertyReservation::query()
            ->where('guest_phone', $phone)
            ->where('start_date', '>', $today)
            ->whereIn('reservation_state', ['confirmed'])
            ->whereNull('deleted_at')
            ->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Find most recent past reservation.
     */
    protected function findPastReservation(string $phone): ?PropertyReservation
    {
        $today = Carbon::today()->toDateString();

        return PropertyReservation::query()
            ->where('guest_phone', $phone)
            ->where('end_date', '<', $today)
            ->whereIn('reservation_state', ['confirmed', 'COMPLETED', 'CHECKED_IN', 'CHECKED_OUT'])
            ->whereNull('deleted_at')
            ->orderBy('end_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Find Kisi by phone (for tenant identification).
     */
    protected function findKisi(string $phone): ?Kisi
    {
        return Kisi::query()
            ->where(function ($q) use ($phone) {
                $q->where('telefon', $phone)
                  ->orWhere('telefon', '+' . $phone);
            })
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Find Lead by phone.
     */
    protected function findLead(string $phone): ?Lead
    {
        return Lead::query()
            ->where(function ($q) use ($phone) {
                $q->where('telefon', $phone)
                  ->orWhere('telefon', '+' . $phone);
            })
            ->orderBy('id', 'desc')
            ->first();
    }
}
