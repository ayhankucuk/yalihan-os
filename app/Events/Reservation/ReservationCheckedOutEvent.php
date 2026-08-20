<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationCheckedOutEvent — Fired when a guest departs the property.
 *
 * CHECKIN_CHECKOUT Wave 4
 *
 * Fired by:
 *   - ReservationService::checkOut() — after transaction commits
 *
 * Represents the immutable operational fact:
 *   "Guest physically departed and departure was registered."
 *
 * This event records the domain checkout fact.
 * The canonical turnover pipeline is triggered via ReservationCompletedEvent,
 * which is also dispatched by ReservationService::checkOut() to reuse
 * the existing ListenReservationCompleted → ProcessReservationCompletedJob chain.
 *
 * SAAB Decision: WAVE4-CHECKOUT
 * Baseline: 8406c78
 */
class ReservationCheckedOutEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int     $reservationId,
        public readonly int     $tenantId,
        public readonly int     $ilanId,
        public readonly string  $startDate,
        public readonly string  $endDate,
        public readonly int     $nights,
        public readonly string  $guestName,
        public readonly ?string $guestEmail,
        public readonly ?string $guestPhone,
        public readonly ?int    $guestCount,
        public readonly string  $checkedOutAt,
        public readonly string  $completedAt,
        /** Whether the guest had a formal check-in record. False = soft-guard triggered. */
        public readonly bool    $hadFormalCheckin,
    ) {}

    public static function fromModel(PropertyReservation $reservation): self
    {
        return new self(
            reservationId:    $reservation->id,
            tenantId:         $reservation->tenant_id ?? 0,
            ilanId:           $reservation->ilan_id ?? $reservation->property_id,
            startDate:        $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            endDate:          $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            nights:           $reservation->nights,
            guestName:        $reservation->guest_name ?? 'Guest',
            guestEmail:       $reservation->guest_email,
            guestPhone:       $reservation->guest_phone,
            guestCount:       $reservation->guest_count,
            checkedOutAt:     $reservation->checked_out_at instanceof \Carbon\Carbon
                ? $reservation->checked_out_at->toIso8601String()
                : (string) $reservation->checked_out_at,
            completedAt:      $reservation->completed_at instanceof \Carbon\Carbon
                ? $reservation->completed_at->toIso8601String()
                : (string) $reservation->completed_at,
            hadFormalCheckin: $reservation->checked_in_at !== null,
        );
    }
}
