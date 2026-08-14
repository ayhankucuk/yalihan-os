<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationCancelledEvent — Canonical lifecycle event.
 *
 * Fired AFTER a reservation is cancelled.
 * Triggers:
 *   - Availability release (internal blocks only — external preserved)
 *   - Guest cancellation notification
 *   - Financial reversal (if applicable)
 *   - Channel sync update
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ReservationCancelledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int        $reservationId,
        public readonly int        $tenantId,
        public readonly int        $ilanId,
        public readonly string     $startDate,
        public readonly string     $endDate,
        public readonly int        $nights,
        public readonly string     $guestName,
        public readonly ?string    $guestEmail,
        public readonly ?string    $guestPhone,
        public readonly string     $cancelledAt,
        // Who/what triggered the cancellation
        public readonly ?string    $cancelledBy,         // 'guest' | 'admin' | 'channel'
        public readonly ?string    $externalReservationId,
        public readonly ?string    $externalChannel,
        public readonly ?string    $reason,
    ) {}

    public static function fromModel(
        PropertyReservation $reservation,
        ?string             $cancelledBy = null,
        ?string             $reason = null,
    ): self {
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
            nights:                 $reservation->nights,
            guestName:              $reservation->guest_name,
            guestEmail:              $reservation->guest_email,
            guestPhone:              $reservation->guest_phone,
            cancelledAt:             $reservation->cancelled_at instanceof \Carbon\Carbon
                ? $reservation->cancelled_at->toIso8601String()
                : (string) $reservation->cancelled_at,
            cancelledBy:             $cancelledBy,
            externalReservationId:  $reservation->external_reservation_id,
            externalChannel:         $reservation->external_channel,
            reason:                  $reason,
        );
    }

    /**
     * Dates to release from availability (internal blocks only).
     */
    public function getDatesToRelease(): array
    {
        $dates = [];
        $current = \Carbon\Carbon::parse($this->startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($this->endDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }
}
