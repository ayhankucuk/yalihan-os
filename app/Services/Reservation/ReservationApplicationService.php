<?php

namespace App\Services\Reservation;

use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
use App\Models\PropertyReservation;
use App\Models\WorkforceExecution;
use App\Domain\Reservation\Events\ReservationCreated;
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
        $amount = $data['islem_tutari'] ?? $data['total_amount'] ?? 0;
        $currency = $data['currency'] ?? 'TRY';
        $money = new Money((float) $amount, $currency);

        return DB::transaction(function () use ($property, $data, $dateRange, $money, $idempotencyKey, $offeringId) {
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
                'reservation_state' => ReservationState::CONFIRMED,
                'islem_tutari' => $money->getAmount(),
                'total_amount' => $money->getAmount(),
                'total_price' => $money->getAmount(),
                'currency' => $money->getCurrency(),
                'confirmed_at' => now(),
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
     * Transactionally cancels a reservation and releases associated availability blocks.
     */
    public function cancelReservation(PropertyReservation $reservation): PropertyReservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation->cancelled_at = now();
            $reservation->reservation_state = ReservationState::CANCELLED;
            $reservation->save();

            // Release availability blocks
            PropertyAvailabilityBlock::where('reservation_id', $reservation->id)
                ->update([
                    'status' => 'RELEASED',
                    'released_at' => now(),
                ]);

            // Record execution audit
            WorkforceExecution::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $reservation->tenant_id,
                'aggregate_type' => 'PropertyReservation',
                'aggregate_id' => $reservation->id,
                'capability' => 'cancel_reservation',
                'execution_status' => 'SUCCESS',
                'started_at' => now(),
                'finished_at' => now(),
                'result_snapshot' => [
                    'reservation_id' => $reservation->id,
                    'status' => 'CANCELLED',
                ],
                'metadata' => [
                    'execution_type' => 'APPLICATION',
                    'subsystem' => 'RESERVATION_CORE',
                ],
            ]);

            return $reservation;
        });
    }
}
