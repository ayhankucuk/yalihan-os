<?php

namespace App\Services\Reservation;

use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\WorkforceExecution;
use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\ReservationState;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DomainException;

class ReservationApplicationService
{
    public function __construct(private ConflictDetectionService $conflictDetector)
    {
    }

    /**
     * Transactionally creates a reservation, locks availability dates, logs execution audit, and dispatches domain event.
     */
    public function createReservation(Property $property, array $data): PropertyReservation
    {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        // 1. Conflict detection guard
        if ($this->conflictDetector->hasConflict($property, $startDate, $endDate)) {
            throw new DomainException("Date conflict detected: Property #{$property->id} is not available between {$startDate} and {$endDate}.");
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $nights = $start->diffInDays($end);

        $amount = $data['islem_tutari'] ?? $data['total_amount'] ?? 0;
        $currency = $data['currency'] ?? 'TRY';
        $money = new Money((float) $amount, $currency);

        return DB::transaction(function () use ($property, $data, $startDate, $endDate, $nights, $money) {
            // Create reservation
            $reservation = PropertyReservation::create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
                'commercial_offering_id' => $data['commercial_offering_id'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'nights' => $nights,
                'guest_name' => $data['guest_name'] ?? 'Direct Client',
                'guest_phone' => $data['guest_phone'] ?? null,
                'guest_email' => $data['guest_email'] ?? null,
                'guest_count' => $data['guest_count'] ?? 1,
                'reservation_state' => ReservationState::CONFIRMED,
                'islem_tutari' => $money->getAmount(),
                'total_amount' => $money->getAmount(),
                'total_price' => $money->getAmount(),
                'currency' => $money->getCurrency(),
                'confirmed_at' => now(),
            ]);

            // Lock availability dates (excluding checkout date)
            $period = CarbonPeriod::create($startDate, Carbon::parse($endDate)->subDay()->toDateString());
            foreach ($period as $date) {
                PropertyAvailability::create([
                    'property_id' => $property->id,
                    'date' => $date->toDateString(),
                    'is_available' => false,
                    'block_reason' => 'RESERVATION',
                    'source_system' => 'DIRECT_BOOKING',
                    'reservation_id' => $reservation->id,
                ]);
            }

            // Record WorkforceExecution audit
            WorkforceExecution::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $property->tenant_id,
                'workspace_id' => $property->workspace_id,
                'aggregate_type' => 'PropertyReservation',
                'aggregate_id' => $reservation->id,
                'capability' => 'create_reservation',
                'execution_status' => 'SUCCESS',
                'started_at' => now(),
                'finished_at' => now(),
                'input_snapshot' => $data,
                'result_snapshot' => [
                    'reservation_id' => $reservation->id,
                    'nights' => $nights,
                    'locked_dates_count' => count($period),
                ],
            ]);

            // Dispatch domain event
            ReservationCreated::dispatch($reservation);

            return $reservation;
        });
    }
}
