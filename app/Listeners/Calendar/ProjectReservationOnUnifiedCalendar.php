<?php

namespace App\Listeners\Calendar;

use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Reservation\Events\ReservationDatesChanged;
use App\Domain\Reservation\Events\ReservationStateTransitioned;
use App\Enums\ReservationState;
use App\Models\Calendar\UnifiedCalendarProjection;
use App\Models\Hermes\HermesEventLog;
use App\Models\PropertyReservation;
use Carbon\CarbonPeriod;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ProjectReservationOnUnifiedCalendar
{
    /**
     * Handle ReservationCreated event.
     */
    public function handleCreated(ReservationCreated $event): void
    {
        $reservation = $event->reservation->fresh() ?? $event->reservation;
        $this->validateTenantContext($reservation);

        $eventId = $event->eventId ?? (string) \Illuminate\Support\Str::uuid();

        $this->recordEventStoreAndTimeline(
            $reservation,
            'ReservationCreated',
            $eventId,
            [
                'reservation_id' => $reservation->id,
                'property_id' => $reservation->property_id,
                'state' => $reservation->reservation_state->value,
                'start_date' => (string) $reservation->start_date,
                'end_date' => (string) $reservation->end_date,
            ]
        );

        $this->projectReservationDays($reservation, $eventId);
    }

    /**
     * Handle ReservationStateTransitioned event.
     */
    public function handleTransition(ReservationStateTransitioned $event): void
    {
        $reservation = $event->reservation->fresh() ?? $event->reservation;
        $this->validateTenantContext($reservation);

        $eventId = $event->eventId;

        $this->recordEventStoreAndTimeline(
            $reservation,
            'ReservationStateTransitioned',
            $eventId,
            [
                'reservation_id' => $reservation->id,
                'property_id' => $reservation->property_id,
                'from_state' => $event->fromState->value,
                'to_state' => $event->toState->value,
            ]
        );

        if (in_array($event->toState, [ReservationState::CANCELLED, ReservationState::EXPIRED], true)) {
            $this->removeReservationDays($reservation);
        } else {
            $this->projectReservationDays($reservation, $eventId);
        }
    }

    /**
     * Handle ReservationDatesChanged event.
     */
    public function handleDatesChanged(ReservationDatesChanged $event): void
    {
        $reservation = $event->reservation->fresh() ?? $event->reservation;
        $this->validateTenantContext($reservation);

        $eventId = $event->eventId;

        $this->recordEventStoreAndTimeline(
            $reservation,
            'ReservationDatesChanged',
            $eventId,
            [
                'reservation_id' => $reservation->id,
                'old_start' => $event->oldDateRange->getStartsAtString(),
                'old_end' => $event->oldDateRange->getEndsAtString(),
                'new_start' => $event->newDateRange->getStartsAtString(),
                'new_end' => $event->newDateRange->getEndsAtString(),
            ]
        );

        // Remove old date projection buckets & re-project new date buckets
        $this->removeReservationDays($reservation);
        $this->projectReservationDays($reservation, $eventId);
    }

    /**
     * Projects daily calendar entries between start_date and end_date.
     */
    private function projectReservationDays(PropertyReservation $reservation, string $sourceEventId): void
    {
        $state = $reservation->reservation_state;
        if (is_string($state)) {
            $state = ReservationState::from($state);
        }

        if (in_array($state, [ReservationState::CANCELLED, ReservationState::EXPIRED], true)) {
            return;
        }

        $startDate = Carbon::parse($reservation->start_date);
        $endDate = Carbon::parse($reservation->end_date);
        $period = CarbonPeriod::create($startDate, $endDate);

        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        $status = $state === ReservationState::PENDING ? 'PENDING_APPROVAL' : 'BOOKED';

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $isCheckin = $dateStr === $startStr;
            $isCheckout = $dateStr === $endStr;

            try {
                UnifiedCalendarProjection::updateOrCreate(
                    [
                        'tenant_id' => $reservation->tenant_id,
                        'property_id' => $reservation->property_id,
                        'calendar_date' => $dateStr,
                        'source_type' => 'RESERVATION',
                        'reservation_id' => $reservation->id,
                    ],
                    [
                        'commercial_offering_id' => $reservation->commercial_offering_id,
                        'status' => $status,
                        'nightly_rate' => $reservation->islem_tutari && $reservation->nights > 0 
                            ? round((float) $reservation->islem_tutari / (int) $reservation->nights, 2) 
                            : null,
                        'currency' => $reservation->currency ?? 'TRY',
                        'is_checkin_day' => $isCheckin,
                        'is_checkout_day' => $isCheckout,
                        'guest_name' => $reservation->guest_name,
                        'external_source' => 'DIRECT_BOOKING',
                        'source_event_id' => $sourceEventId,
                        'last_projected_at' => now(),
                    ]
                );
            } catch (QueryException $e) {
                if (! $this->isDuplicateKeyViolation($e)) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Removes projection entries for cancelled/expired or modified reservations.
     */
    private function removeReservationDays(PropertyReservation $reservation): void
    {
        UnifiedCalendarProjection::where('tenant_id', $reservation->tenant_id)
            ->where('property_id', $reservation->property_id)
            ->where('reservation_id', $reservation->id)
            ->where('source_type', 'RESERVATION')
            ->delete();
    }

    /**
     * Appends event to HermesEventLog Event Store and Property Timeline.
     */
    private function recordEventStoreAndTimeline(PropertyReservation $reservation, string $eventName, string $eventId, array $payload): void
    {
        try {
            HermesEventLog::create([
                'tenant_id' => $reservation->tenant_id,
                'event_name' => $eventName,
                'event_class' => "App\\Domain\\Reservation\\Events\\{$eventName}",
                'projection_type' => 'UnifiedCalendarProjection',
                'source_event_id' => $eventId,
                'occurred_at' => now(),
                'payload' => $payload,
            ]);
        } catch (QueryException $e) {
            if (! $this->isDuplicateKeyViolation($e)) {
                throw $e;
            }
        }
    }

    private function validateTenantContext(PropertyReservation $reservation): void
    {
        if (empty($reservation->tenant_id)) {
            throw new InvalidArgumentException('Unified calendar projection requires valid tenant_id context.');
        }
    }

    private function isDuplicateKeyViolation(QueryException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'unic_cal_proj_tenant_prop_date_res_unique') ||
               str_contains($message, 'hermes_logs_tenant_projection_source_unique') ||
               str_contains($message, 'UNIQUE constraint failed') ||
               str_contains($message, '1062 Duplicate entry');
    }
}
