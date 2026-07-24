<?php

namespace App\Services\Reservation;

use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
use App\Models\PropertyReservation;
use App\Models\WorkforceExecution;
use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Reservation\Events\ReservationStateTransitioned;
use App\Domain\Reservation\Events\ReservationDatesChanged;
use App\Domain\Shared\ValueObjects\DateRange;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\ReservationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DomainException;

class ReservationApplicationService
{
    public function __construct(private ConflictDetectionService $conflictDetector)
    {
    }

    /**
     * Transactionally creates a reservation with pessimistic row locking, idempotency check, range availability block, and commit-safe event dispatching.
     */
    public function createReservation(Property $property, array $data): PropertyReservation
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        // 1. Idempotency Replay Check
        if (! empty($idempotencyKey)) {
            $existing = PropertyReservation::where('tenant_id', $property->tenant_id)
                ->where('idempotency_key', $idempotencyKey)
                ->orderBy('id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // 2. Cross-Aggregate Offering–Property–Tenant Invariant Guard
        $offeringId = $data['commercial_offering_id'] ?? null;
        if ($offeringId !== null) {
            $offering = CommercialOffering::where('tenant_id', $property->tenant_id)
                ->where('property_id', $property->id)
                ->whereKey($offeringId)
                ->first();

            if (! $offering) {
                throw new DomainException("Commercial Offering #{$offeringId} does not belong to Property #{$property->id} or Tenant #{$property->tenant_id}.");
            }
        }

        $dateRange = new DateRange($data['start_date'], $data['end_date']);
        $amount = $data['islem_tutari'] ?? $data['total_amount'] ?? $data['total_price'] ?? 1000.00;
        $currency = $data['currency'] ?? 'TRY';
        $money = new Money((float) $amount, $currency);
        $initialState = isset($data['pending']) && $data['pending'] ? ReservationState::PENDING : ReservationState::CONFIRMED;

        return DB::transaction(function () use ($property, $data, $dateRange, $money, $idempotencyKey, $offeringId, $initialState) {
            // 3. Pessimistic Row Lock to prevent race conditions
            Property::where('tenant_id', $property->tenant_id)
                ->whereKey($property->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 4. Transactional Conflict Detection
            if ($this->conflictDetector->hasConflict($property, $dateRange)) {
                throw new DomainException("Date conflict detected: Property #{$property->id} is not available between {$dateRange->getStartsAtString()} and {$dateRange->getEndsAtString()}.");
            }

            // 5. Create PropertyReservation
            $reservation = PropertyReservation::create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
                'commercial_offering_id' => $offeringId,
                'idempotency_key' => $idempotencyKey,
                'start_date' => $dateRange->getStartsAt()->toDateString(),
                'end_date' => $dateRange->getEndsAt()->toDateString(),
                'nights' => $dateRange->getNights(),
                'guest_name' => $data['guest_name'] ?? 'Direct Client',
                'guest_phone' => $data['guest_phone'] ?? null,
                'guest_email' => $data['guest_email'] ?? null,
                'guest_count' => $data['guest_count'] ?? 1,
                'reservation_state' => $initialState,
                'islem_tutari' => $money->getAmount(),
                'total_amount' => $money->getAmount(),
                'total_price' => $money->getAmount(),
                'currency' => $money->getCurrency(),
                'confirmed_at' => $initialState === ReservationState::CONFIRMED ? now() : null,
            ]);

            // 6. Create Range Availability Block
            PropertyAvailabilityBlock::create([
                'tenant_id' => $property->tenant_id,
                'property_id' => $property->id,
                'reservation_id' => $reservation->id,
                'block_type' => 'RESERVATION',
                'starts_at' => $dateRange->getStartsAtString(),
                'ends_at' => $dateRange->getEndsAtString(),
                'status' => 'ACTIVE',
                'source' => 'DIRECT_BOOKING',
                'idempotency_key' => $idempotencyKey,
            ]);

            // 7. Record WorkforceExecution Audit Trail with Classification
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
                    'nights' => $dateRange->getNights(),
                ],
                'metadata' => [
                    'execution_type' => 'APPLICATION',
                    'subsystem' => 'RESERVATION_CORE',
                ],
            ]);

            // 8. Commit-Safe Event Dispatching
            DB::afterCommit(function () use ($reservation) {
                ReservationCreated::dispatch($reservation);
            });

            return $reservation;
        });
    }

    /**
     * Transactionally transitions reservation state enforcing state machine rules.
     */
    public function transitionState(PropertyReservation $reservation, ReservationState $targetState): PropertyReservation
    {
        $currentState = $reservation->reservation_state;

        if (! $currentState->canTransitionTo($targetState)) {
            throw new DomainException("Forbidden reservation state transition from {$currentState->value} to {$targetState->value}.");
        }

        return DB::transaction(function () use ($reservation, $currentState, $targetState) {
            $lockedReservation = PropertyReservation::where('tenant_id', $reservation->tenant_id)
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReservation->reservation_state = $targetState;

            if ($targetState === ReservationState::CONFIRMED && empty($lockedReservation->confirmed_at)) {
                $lockedReservation->confirmed_at = now();
            }

            if ($targetState === ReservationState::CANCELLED || $targetState === ReservationState::EXPIRED) {
                $lockedReservation->cancelled_at = now();

                // Release availability blocks
                PropertyAvailabilityBlock::where('reservation_id', $lockedReservation->id)
                    ->update([
                        'status' => 'RELEASED',
                        'released_at' => now(),
                    ]);
            }

            $lockedReservation->save();

            // Record execution audit
            WorkforceExecution::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $lockedReservation->tenant_id,
                'aggregate_type' => 'PropertyReservation',
                'aggregate_id' => $lockedReservation->id,
                'capability' => 'transition_reservation_state',
                'execution_status' => 'SUCCESS',
                'started_at' => now(),
                'finished_at' => now(),
                'result_snapshot' => [
                    'reservation_id' => $lockedReservation->id,
                    'from_state' => $currentState->value,
                    'to_state' => $targetState->value,
                ],
                'metadata' => [
                    'execution_type' => 'APPLICATION',
                    'subsystem' => 'RESERVATION_CORE',
                ],
            ]);

            DB::afterCommit(function () use ($lockedReservation, $currentState, $targetState) {
                ReservationStateTransitioned::dispatch($lockedReservation, $currentState, $targetState);
            });

            return $lockedReservation;
        });
    }

    public function confirmReservation(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::CONFIRMED);
    }

    public function checkIn(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::CHECKED_IN);
    }

    public function checkOut(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::CHECKED_OUT);
    }

    public function closeReservation(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::CLOSED);
    }

    public function expireReservation(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::EXPIRED);
    }

    public function cancelReservation(PropertyReservation $reservation): PropertyReservation
    {
        return $this->transitionState($reservation, ReservationState::CANCELLED);
    }

    /**
     * Transactionally modifies reservation dates and updates availability blocks.
     */
    public function modifyReservationDates(PropertyReservation $reservation, DateRange $newDateRange): PropertyReservation
    {
        return DB::transaction(function () use ($reservation, $newDateRange) {
            $property = Property::where('tenant_id', $reservation->tenant_id)
                ->whereKey($reservation->property_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldDateRange = new DateRange($reservation->start_date, $reservation->end_date);

            // Check conflicts ignoring current reservation's block
            $hasConflict = PropertyAvailabilityBlock::where('tenant_id', $reservation->tenant_id)
                ->where('property_id', $reservation->property_id)
                ->where('status', 'ACTIVE')
                ->where('reservation_id', '!=', $reservation->id)
                ->where('starts_at', '<', $newDateRange->getEndsAtString())
                ->where('ends_at', '>', $newDateRange->getStartsAtString())
                ->exists();

            if ($hasConflict) {
                throw new DomainException("Date modification conflict: Property #{$property->id} is occupied during {$newDateRange->getStartsAtString()} to {$newDateRange->getEndsAtString()}.");
            }

            $reservation->start_date = $newDateRange->getStartsAt()->toDateString();
            $reservation->end_date = $newDateRange->getEndsAt()->toDateString();
            $reservation->nights = $newDateRange->getNights();
            $reservation->save();

            // Update active availability block
            PropertyAvailabilityBlock::where('reservation_id', $reservation->id)
                ->where('status', 'ACTIVE')
                ->update([
                    'starts_at' => $newDateRange->getStartsAtString(),
                    'ends_at' => $newDateRange->getEndsAtString(),
                ]);

            WorkforceExecution::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $reservation->tenant_id,
                'aggregate_type' => 'PropertyReservation',
                'aggregate_id' => $reservation->id,
                'capability' => 'modify_reservation_dates',
                'execution_status' => 'SUCCESS',
                'started_at' => now(),
                'finished_at' => now(),
                'result_snapshot' => [
                    'reservation_id' => $reservation->id,
                    'old_start' => $oldDateRange->getStartsAtString(),
                    'old_end' => $oldDateRange->getEndsAtString(),
                    'new_start' => $newDateRange->getStartsAtString(),
                    'new_end' => $newDateRange->getEndsAtString(),
                ],
            ]);

            DB::afterCommit(function () use ($reservation, $oldDateRange, $newDateRange) {
                ReservationDatesChanged::dispatch($reservation, $oldDateRange, $newDateRange);
            });

            return $reservation;
        });
    }
}
