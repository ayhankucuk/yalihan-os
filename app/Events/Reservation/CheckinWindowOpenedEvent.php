<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CheckinWindowOpenedEvent — Fired when a reservation's check-in window opens.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Fired by:
 *   - OpenCheckinWindowJob (scheduled, daily)
 *   - GuestArrivalReadinessService::openCheckinWindow()
 *
 * Triggered after:
 *   - Reservation validity checks pass (CONFIRMED, not cancelled)
 *   - checkin_window_opened_at timestamp is set on the reservation
 *
 * This event is the signal that the guest can now check in.
 * Downstream handlers (future Wave 3):
 *   - Send guest welcome notification (Telegram/email)
 *   - Trigger Hermes AI agent for pre-arrival check
 *   - Update guest portal status
 */
class CheckinWindowOpenedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int        $reservationId,
        public readonly int        $tenantId,
        public readonly int        $ilanId,
        public readonly string     $startDate,
        public readonly string     $endDate,
        public readonly string     $guestName,
        public readonly ?string    $guestEmail,
        public readonly ?string    $guestPhone,
        public readonly string     $openedAt,
        public readonly string     $checkinTime,
        public readonly string     $checkinTimeFromProperty,
    ) {}

    public static function fromModel(PropertyReservation $reservation): self
    {
        $checkInTime = $reservation->ilan?->check_in_time ?? '14:00';

        return new self(
            reservationId:          $reservation->id,
            tenantId:               $reservation->tenant_id ?? 0,
            ilanId:                 $reservation->ilan_id ?? $reservation->property_id,
            startDate:              $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            endDate:                $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            guestName:              $reservation->guest_name ?? 'Guest',
            guestEmail:              $reservation->guest_email,
            guestPhone:              $reservation->guest_phone,
            openedAt:               $reservation->checkin_window_opened_at?->toIso8601String()
                ?? now()->toIso8601String(),
            checkinTime:            $checkInTime,
            checkinTimeFromProperty: $checkInTime,
        );
    }
}
