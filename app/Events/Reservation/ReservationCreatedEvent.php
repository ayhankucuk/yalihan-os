<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationCreatedEvent — Canonical lifecycle event.
 *
 * Fired AFTER a reservation is successfully committed to the database.
 * This is the single authoritative event for all downstream systems:
 *   - Guest communication (confirmation notification)
 *   - Availability outbound sync
 *   - Financial recording
 *   - Stay operation task generation
 *
 * PROVIDER EVENTS ARE DOWNSTREAM — they describe HOW the reservation entered
 * the system. This event describes WHAT happened to the canonical reservation.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ReservationCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int                    $reservationId,
        public readonly int                    $tenantId,
        public readonly int                    $ilanId,
        public readonly string                 $startDate,
        public readonly string                 $endDate,
        public readonly int                    $nights,
        public readonly string                 $guestName,
        public readonly ?string                $guestPhone,
        public readonly ?string                $guestEmail,
        public readonly ?int                   $guestCount,
        public readonly ?string                $notes,
        public readonly string                 $reservationState,
        public readonly ?float                 $totalAmount,
        public readonly ?string               $currency,
        public readonly ?string               $externalReservationId,
        public readonly ?string               $externalChannel,
        public readonly ?int                  $createdByUserId,
        public readonly ?int                  $overrideOfId,
        public readonly ?int                  $overrideAuthorizedBy,
        public readonly ?string               $overrideOccurredAt,
    ) {}

    /**
     * Build from a PropertyReservation model.
     * Use this factory to ensure event always matches model state.
     */
    public static function fromModel(PropertyReservation $reservation): self
    {
        $state = $reservation->reservation_state instanceof \App\Enums\ReservationState
            ? $reservation->reservation_state->value
            : (string) $reservation->reservation_state;

        return new self(
            reservationId:         $reservation->id,
            tenantId:              $reservation->tenant_id ?? 0,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id,
            startDate:             $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            endDate:               $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            nights:                $reservation->nights,
            guestName:             $reservation->guest_name,
            guestPhone:            $reservation->guest_phone,
            guestEmail:            $reservation->guest_email,
            guestCount:            $reservation->guest_count,
            notes:                 $reservation->notes,
            reservationState:      $state,
            totalAmount:            $reservation->total_amount,
            currency:               $reservation->currency,
            externalReservationId:  $reservation->external_reservation_id,
            externalChannel:        $reservation->external_channel,
            createdByUserId:        $reservation->created_by_user_id,
            overrideOfId:           $reservation->override_of_id,
            overrideAuthorizedBy:   $reservation->override_authorized_by,
            overrideOccurredAt:     $reservation->override_occurred_at?->toIso8601String(),
        );
    }
}
