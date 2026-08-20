<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationCheckedInEvent — Fired when a guest is confirmed as checked in.
 *
 * CHECKIN_CHECKOUT Wave 4
 *
 * Fired by:
 *   - ReservationService::checkIn() — after transaction commits
 *
 * Represents the immutable operational fact:
 *   "Guest physically arrived and was registered at the property."
 *
 * Downstream (future Wave 5+):
 *   - Hermes pre-arrival satisfaction ping
 *   - Staff portal status update
 *   - Revenue accounting period start
 *
 * SAAB Decision: WAVE4-CHECKIN
 * Baseline: 8406c78
 */
class ReservationCheckedInEvent
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
        public readonly string  $checkedInAt,
    ) {}

    public static function fromModel(PropertyReservation $reservation): self
    {
        return new self(
            reservationId: $reservation->id,
            tenantId:      $reservation->tenant_id ?? 0,
            ilanId:        $reservation->ilan_id ?? $reservation->property_id,
            startDate:     $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            endDate:       $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            nights:        $reservation->nights,
            guestName:     $reservation->guest_name ?? 'Guest',
            guestEmail:    $reservation->guest_email,
            guestPhone:    $reservation->guest_phone,
            guestCount:    $reservation->guest_count,
            checkedInAt:   $reservation->checked_in_at instanceof \Carbon\Carbon
                ? $reservation->checked_in_at->toIso8601String()
                : (string) $reservation->checked_in_at,
        );
    }
}
