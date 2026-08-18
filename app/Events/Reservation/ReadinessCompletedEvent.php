<?php

namespace App\Events\Reservation;

use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReadinessCompletedEvent — Fired when a reservation's readiness becomes complete.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Fired when:
 *   - All REQUIRED_DIMENSIONS of PropertyReadiness become true
 *   - is_ready transitions from false to true
 *
 * This event signals that the property is fully prepared for guest arrival.
 * Downstream handlers (future Wave 3):
 *   - Send readiness confirmation to staff
 *   - Update AI dashboard with readiness status
 *   - Trigger pre-arrival communication sequence
 */
class ReadinessCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int        $reservationId,
        public readonly int        $tenantId,
        public readonly int        $ilanId,
        public readonly bool       $isReady,
        /** @var string[] */
        public readonly array      $completedDimensions,
        /** @var string[] */
        public readonly array      $pendingDimensions,
        public readonly string     $checkinTime,
    ) {}

    public static function fromModel(PropertyReadiness $readiness, PropertyReservation $reservation): self
    {
        $checkInTime = $readiness->ilan?->check_in_time ?? '14:00';

        $allDimensions = PropertyReadiness::REQUIRED_DIMENSIONS;
        $completed = [];
        $pending = [];

        foreach ($allDimensions as $dimension) {
            if ($readiness->{$dimension} === true) {
                $completed[] = $dimension;
            } else {
                $pending[] = $dimension;
            }
        }

        return new self(
            reservationId:  $readiness->reservation_id,
            tenantId:       $readiness->tenant_id,
            ilanId:         $readiness->ilan_id,
            isReady:        $readiness->is_ready,
            completedDimensions: $completed,
            pendingDimensions: $pending,
            checkinTime:    $checkInTime,
        );
    }
}
