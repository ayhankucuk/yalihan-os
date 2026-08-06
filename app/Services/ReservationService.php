<?php

namespace App\Services;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Contracts\Reservation\ConflictDetectionServiceContract;
use App\Contracts\Reservation\ReservationConflictException;
use App\Enums\ReservationState;
use App\Events\Reservation\ReservationConflictDetectedEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\GuardsAgentWrites;

/**
 * ReservationService — Internal reservation creation and cancellation.
 *
 * RESERVATION_CORE Phase 1:
 * - createReservation() now creates PENDING reservations (availability NOT blocked yet)
 * - confirmReservation() transitions pending → confirmed and blocks availability
 * - cancelReservation() releases availability blocks (P0 leak fix)
 *
 * RESERVATION_CORE Phase 3:
 * - Conflict detection via ConflictDetectionService
 * - Dispatches ReservationConflictDetectedEvent on conflict
 *
 * Sprint 22 E01: Added tenant_id scope to all queries (G1 fix).
 */
class ReservationService
{
    use GuardsAgentWrites;

    public function __construct(
        private ConflictDetectionServiceContract $conflictService
    ) {}

    /**
     * Create a new PENDING reservation for a property.
     *
     * Phase 1 change: reservation starts as PENDING, availability is NOT blocked yet.
     * Call confirmReservation() to confirm and block availability.
     *
     * Phase 3: Uses ConflictDetectionService for unified conflict detection.
     *
     * @param int      $propertyId
     * @param string   $startDate
     * @param string   $endDate
     * @param array    $guestData
     * @param int|null $userId
     * @param int|null $tenantId   Optional — resolved from Ilan.tenant_id when omitted.
     * @return PropertyReservation
     * @throws Exception
     */
    public function createReservation(
        int $propertyId,
        string $startDate,
        string $endDate,
        array $guestData,
        ?int $userId = null,
        ?int $tenantId = null
    ): PropertyReservation {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be before end date.");
        }

        $nights = $start->diffInDays($end);

        $ilan = Ilan::findOrFail($propertyId);

        // Resolve tenantId from the property when not explicitly provided.
        $tenantId = $tenantId ?? (int) $ilan->tenant_id;

        if (!$ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        return DB::transaction(function () use ($tenantId, $propertyId, $start, $end, $nights, $guestData, $userId) {

            // Phase 3: Use unified ConflictDetectionService
            $report = $this->conflictService->detectConflicts(
                $tenantId,
                $propertyId,
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            );

            if ($report->hasConflict) {
                // Dispatch event for observability
                event(new ReservationConflictDetectedEvent(
                    $tenantId,
                    $propertyId,
                    0, // New reservation not created yet
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d'),
                    $report->conflictType ?? 'RESERVATION_OVERLAP',
                    array_map(fn($r) => $r->id, $report->conflictingReservations),
                    $report->conflictDates,
                    $report->highestPriority
                ));

                throw new ReservationConflictException(
                    "Conflict detected: The selected dates overlap with an existing reservation.",
                    $report
                );
            }

            // Create PENDING reservation — availability NOT blocked yet.
            // Call confirmReservation() to confirm and block availability.
            return PropertyReservation::create([
                'tenant_id'          => $tenantId ?: null,
                'property_id'        => $propertyId,
                'start_date'         => $start->format('Y-m-d'),
                'end_date'           => $end->format('Y-m-d'),
                'nights'             => $nights,
                'guest_name'         => $guestData['guest_name'],
                'guest_phone'        => $guestData['guest_phone'] ?? null,
                'guest_email'        => $guestData['guest_email'] ?? null,
                'guest_count'        => $guestData['guest_count'] ?? null,
                'notes'              => $guestData['notes'] ?? null,
                'reservation_state'  => ReservationState::PENDING->value,
                'created_by_user_id' => $userId,
            ]);
        });
    }

    /**
     * Confirm a pending reservation and block availability.
     *
     * RESERVATION_CORE Phase 1: explicit confirm step.
     * Transitions PENDING → CONFIRMED and writes PropertyAvailability blocks.
     *
     * @param int      $reservationId
     * @param int|null $tenantId
     * @return PropertyReservation
     * @throws Exception
     */
    public function confirmReservation(int $reservationId, ?int $tenantId = null): PropertyReservation
    {
        return DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            $resolvedTenantId = $tenantId ?? (int) $reservation->tenant_id;

            if ($reservation->tenant_id && (int) $reservation->tenant_id !== $resolvedTenantId) {
                throw new Exception("Reservation does not belong to the given tenant.");
            }

            // P2.2 Idempotency: already confirmed — return current state without side effects.
            if ($reservation->reservation_state === ReservationState::CONFIRMED) {
                return $reservation;
            }

            if (!$reservation->canTransitionTo(ReservationState::CONFIRMED)) {
                throw new Exception(
                    "Cannot confirm reservation in state '{$reservation->reservation_state->value}'."
                );
            }

            $tenantId = $resolvedTenantId;

            $start = Carbon::parse($reservation->start_date)->startOfDay();
            $end   = Carbon::parse($reservation->end_date)->startOfDay();
            $propertyId = $reservation->property_id;

            $dates = [];
            $currentDate = $start->copy();
            while ($currentDate->lt($end)) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Check availability before blocking.
            $blockedCount = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', false)
                ->lockForUpdate()
                ->count();

            if ($blockedCount > 0) {
                $firstBlocked = PropertyAvailability::where('property_id', $propertyId)
                    ->whereIn('date', $dates)
                    ->where('is_available', false)
                    ->orderBy('date')
                    ->value('date');
                throw new Exception("Dates are not available. Conflict on {$firstBlocked}.");
            }

            $now = now();

            // Insert placeholder availability rows for missing dates.
            $existingDates = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->all();

            $missingDates = array_diff($dates, $existingDates);
            if (!empty($missingDates)) {
                $insertData = [];
                foreach ($missingDates as $dateStr) {
                    $insertData[] = [
                        'tenant_id'     => $tenantId ?: null,
                        'property_id'   => $propertyId,
                        'date'          => $dateStr,
                        'is_available'  => true,
                        'source_system' => 'internal',
                        'origin'        => 'system',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                PropertyAvailability::insert($insertData);
            }

            // Lock and retrieve existing availability rows.
            $availQuery = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', true);

            if ($tenantId > 0) {
                $availQuery->where('tenant_id', $tenantId);
            }

            $existingAvailabilities = $availQuery
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            // Transition reservation to CONFIRMED.
            $reservation->reservation_state = ReservationState::CONFIRMED;
            $reservation->confirmed_at = $now;
            $reservation->save();

            // Block availability for each date.
            foreach ($dates as $dateStr) {
                if (isset($existingAvailabilities[$dateStr])) {
                    $existingAvailabilities[$dateStr]->update([
                        'is_available'            => false,
                        'block_reason'            => 'reservation',
                        'priority_tier'           => PropertyAvailabilityContract::TIER_RESERVATION,
                        'source_system'           => 'internal',
                        'origin'                  => PropertyAvailabilityContract::ORIGIN_RESERVATION,
                        'reservation_id'          => $reservation->id,
                        'projection_generated_at' => $now,
                        'projection_source'       => PropertyAvailabilityContract::PROJECTION_SOURCE_RESERVATION,
                    ]);
                }
            }

            return $reservation->fresh();
        });
    }

    /**
     * Cancel an existing reservation and free its availability blocks.
     *
     * P0 fix: This is the ONLY authoritative cancellation path.
     * UpdateReservationStateAction must delegate here, not call ->update() directly.
     *
     * @param int      $reservationId
     * @param int|null $tenantId  Optional — resolved from the reservation record when omitted.
     * @return void
     * @throws Exception
     */
    public function cancelReservation(int $reservationId, ?int $tenantId = null): void
    {
        DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            $tenantId = $tenantId ?? (int) $reservation->tenant_id;

            if ($reservation->tenant_id && $reservation->tenant_id !== $tenantId) {
                throw new Exception("Reservation does not belong to the given tenant.");
            }

            // Idempotent — already cancelled.
            if ($reservation->reservation_state === ReservationState::CANCELLED) {
                return;
            }

            if (!$reservation->canTransitionTo(ReservationState::CANCELLED)) {
                throw new Exception(
                    "Cannot cancel reservation in state '{$reservation->reservation_state->value}'."
                );
            }

            $reservation->reservation_state = ReservationState::CANCELLED;
            $reservation->cancelled_at = now();
            $reservation->save();

            // Release availability blocks (P0 fix — always runs on cancellation).
            $cleanupQuery = PropertyAvailability::where('reservation_id', $reservationId)
                ->where('source_system', 'internal');

            if ($tenantId > 0) {
                $cleanupQuery->where('tenant_id', $tenantId);
            }

            $cleanupQuery->update([
                'is_available'            => true,
                'block_reason'            => null,
                'priority_tier'           => PropertyAvailabilityContract::TIER_HOLD_PENDING,
                'reservation_id'          => null,
                'origin'                  => null,
                'projection_generated_at' => now(),
                'projection_source'       => PropertyAvailabilityContract::PROJECTION_SOURCE_RESERVATION,
            ]);
        });
    }

    /**
     * Complete a confirmed reservation (checkout).
     *
     * RESERVATION_CORE Phase 1: explicit completion.
     * Transitions CONFIRMED → COMPLETED.
     * Availability blocks remain (historical record).
     *
     * @param int      $reservationId
     * @param int|null $tenantId
     * @return PropertyReservation
     * @throws Exception
     */
    public function completeReservation(int $reservationId, ?int $tenantId = null): PropertyReservation
    {
        return DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            $tenantId = $tenantId ?? (int) $reservation->tenant_id;

            if ($reservation->tenant_id && $reservation->tenant_id !== $tenantId) {
                throw new Exception("Reservation does not belong to the given tenant.");
            }

            if (!$reservation->canTransitionTo(ReservationState::COMPLETED)) {
                throw new Exception(
                    "Cannot complete reservation in state '{$reservation->reservation_state->value}'."
                );
            }

            $reservation->reservation_state = ReservationState::COMPLETED;
            $reservation->save();

            return $reservation->fresh();
        });
    }

    /**
     * Mark a confirmed reservation as no-show.
     *
     * RESERVATION_CORE Phase 1.
     * Transitions CONFIRMED → NO_SHOW.
     * Availability blocks remain (historical record).
     *
     * @param int      $reservationId
     * @param int|null $tenantId
     * @return PropertyReservation
     * @throws Exception
     */
    public function markNoShow(int $reservationId, ?int $tenantId = null): PropertyReservation
    {
        return DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            $tenantId = $tenantId ?? (int) $reservation->tenant_id;

            if ($reservation->tenant_id && $reservation->tenant_id !== $tenantId) {
                throw new Exception("Reservation does not belong to the given tenant.");
            }

            if (!$reservation->canTransitionTo(ReservationState::NO_SHOW)) {
                throw new Exception(
                    "Cannot mark no-show for reservation in state '{$reservation->reservation_state->value}'."
                );
            }

            $reservation->reservation_state = ReservationState::NO_SHOW;
            $reservation->save();

            return $reservation->fresh();
        });
    }
}
