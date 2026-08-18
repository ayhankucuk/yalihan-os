<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationModifiedEvent — Canonical lifecycle event.
 *
 * Fired AFTER a reservation's dates or guest data are changed.
 * Captures both old and new values so listeners can:
 *   - Update availability: release old dates, block new dates
 *   - Notify guest of changes
 *   - Update financial records
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ReservationModifiedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int        $reservationId,
        public readonly int        $tenantId,
        public readonly int        $ilanId,
        // Previous values (for availability release)
        public readonly string     $previousStartDate,
        public readonly string     $previousEndDate,
        public readonly int        $previousNights,
        // New values
        public readonly string     $newStartDate,
        public readonly string     $newEndDate,
        public readonly int        $newNights,
        // Guest data (may be unchanged)
        public readonly string     $guestName,
        public readonly ?int      $guestCount,
        // Channel
        public readonly ?string    $externalReservationId,
        public readonly ?string    $externalChannel,
    ) {}

    public static function fromModel(
        PropertyReservation $reservation,
        string $previousStartDate,
        string $previousEndDate,
        int    $previousNights,
    ): self {
        return new self(
            reservationId:          $reservation->id,
            tenantId:               $reservation->tenant_id ?? 0,
            ilanId:                 $reservation->ilan_id ?? $reservation->property_id,
            previousStartDate:      $previousStartDate,
            previousEndDate:        $previousEndDate,
            previousNights:          $previousNights,
            newStartDate:           $reservation->start_date instanceof \Carbon\Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date,
            newEndDate:             $reservation->end_date instanceof \Carbon\Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date,
            newNights:              $reservation->nights,
            guestName:              $reservation->guest_name,
            guestCount:             $reservation->guest_count,
            externalReservationId:  $reservation->external_reservation_id,
            externalChannel:         $reservation->external_channel,
        );
    }

    /**
     * Dates that need to be released (no longer blocked for this reservation).
     */
    public function getDatesToRelease(): array
    {
        $dates = [];
        $current = \Carbon\Carbon::parse($this->previousStartDate)->startOfDay();
        $end = \Carbon\Carbon::parse($this->previousEndDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Dates that need to be blocked (new reservation period).
     */
    public function getDatesToBlock(): array
    {
        $dates = [];
        $current = \Carbon\Carbon::parse($this->newStartDate)->startOfDay();
        $end = \Carbon\Carbon::parse($this->newEndDate)->startOfDay();

        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }
}
