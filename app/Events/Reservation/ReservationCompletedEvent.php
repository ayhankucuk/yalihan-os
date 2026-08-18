<?php

namespace App\Events\Reservation;

use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReservationCompletedEvent — Canonical lifecycle event.
 *
 * Fired when a reservation's stay is officially complete (checkout date passed).
 * This is the trigger for:
 *   - Financial closure (owner payout calculation, commission)
 *   - Post-stay guest survey / review request
 *   - Cleaning completion verification
 *   - Housekeeping task closure
 *
 * NOT fired by ReservationService directly — triggered by a scheduled job
 * (ReservationCompletionJob) that runs daily and checks for past checkout dates.
 *
 * Sprint 4-WAVE-EB — Canonical Event Backbone
 */
class ReservationCompletedEvent
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
        public readonly ?int       $guestCount,
        // Financial summary at completion
        public readonly ?float     $totalAmount,
        public readonly ?string    $currency,
        public readonly ?float    $lockedNightlyRate,
        // Completion metadata
        public readonly string     $completedAt,
        public readonly bool       $checkedOutCleanly,   // false = no-show, early departure, damage
        public readonly ?string    $externalReservationId,
        public readonly ?string    $externalChannel,
    ) {}

    public static function fromModel(PropertyReservation $reservation, bool $checkedOutCleanly = true): self
    {
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
            guestCount:             $reservation->guest_count ?? 0,
            totalAmount:             $reservation->total_amount ?? $reservation->islem_tutari,
            currency:               $reservation->currency,
            lockedNightlyRate:      $reservation->locked_nightly_rate,
            completedAt:            now()->toIso8601String(),
            checkedOutCleanly:      $checkedOutCleanly,
            externalReservationId:  $reservation->external_reservation_id,
            externalChannel:         $reservation->external_channel,
        );
    }
}
