<?php

namespace App\Services;

use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Events\Reservation\ReservationModifiedEvent;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\GuardsAgentWrites;

class ReservationService
{
    use GuardsAgentWrites;
    /**
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @param array $guestData
     * @param int|null $userId
     * @return PropertyReservation
     * @throws Exception
     */
    public function createReservation(
        int $propertyId,
        string $startDate,
        string $endDate,
        array $guestData,
        ?int $userId = null
    ): PropertyReservation {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be before end date.");
        }

        $nights = $start->diffInDays($end);

        $ilan = Ilan::withoutGlobalScopes()->findOrFail($propertyId);

        if (!$ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        $reservation = DB::transaction(function () use ($ilan, $propertyId, $start, $end, $nights, $guestData, $userId) {

            // Overlap Constraint (Strict User Requirement)
            $overlapCount = PropertyReservation::where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled')
                ->lockForUpdate() // Prevent concurrent reading of overlapping rows before insertion
                ->count();

            if ($overlapCount > 0) {
                throw new Exception("Conflict detected: The selected dates overlap with an existing reservation.");
            }

            $dates = [];
            $currentDate = $start->copy();

            while ($currentDate->lt($end)) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Ensure rows exist before locking using bulk insertOrIgnore
            $now = now();
            $insertData = [];
            foreach ($dates as $dateStr) {
                $insertData[] = [
                    'property_id' => $propertyId,
                    'date' => $dateStr,
                    'is_available' => true,
                    'source_system' => 'internal',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            PropertyAvailability::insertOrIgnore($insertData);

            // Lock existing rows for update
            $existingAvailabilities = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->lockForUpdate()
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->date)->format('Y-m-d');
                });

            foreach ($dates as $dateStr) {
                if (isset($existingAvailabilities[$dateStr])) {
                    $avail = $existingAvailabilities[$dateStr];
                    if (!$avail->is_available) {
                        throw new Exception("Dates are not available. Conflict on {$dateStr}.");
                    }
                }
            }

            // 2. Create reservation
            $reservation = PropertyReservation::create([
                'tenant_id' => $ilan->tenant_id,
                'property_id' => $propertyId,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'nights' => $nights,
                'guest_name' => $guestData['guest_name'],
                'guest_phone' => $guestData['guest_phone'] ?? null,
                'guest_email' => $guestData['guest_email'] ?? null,
                'guest_count' => $guestData['guest_count'] ?? null,
                'notes' => $guestData['notes'] ?? null,
                'reservation_state' => 'confirmed',
                'created_by_user_id' => $userId,
                'confirmed_at' => now(),
            ]);

            // 3. Update availability objects directly using locked models
            foreach ($dates as $dateStr) {
                $avail = $existingAvailabilities[$dateStr];
                $avail->update([
                    'is_available' => false,
                    'block_reason' => 'reservation',
                    'source_system' => 'internal',
                    'reservation_id' => $reservation->id,
                ]);
            }

            return $reservation;
        });

        // ── Dispatch canonical lifecycle event ──────────────────────────
        // Event fires ONLY after transaction commits (outside closure).
        event(new ReservationCreatedEvent(
            reservationId:         $reservation->id,
            tenantId:              $reservation->tenant_id ?? 0,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id,
            startDate:             $this->formatDate($reservation->start_date),
            endDate:               $this->formatDate($reservation->end_date),
            nights:                $reservation->nights,
            guestName:             $reservation->guest_name,
            guestPhone:            $reservation->guest_phone,
            guestEmail:            $reservation->guest_email,
            guestCount:            $reservation->guest_count,
            notes:                 $reservation->notes,
            reservationState:      $reservation->reservation_state instanceof ReservationState
                ? $reservation->reservation_state->value
                : (string) $reservation->reservation_state,
            totalAmount:           $reservation->total_amount ?? $reservation->islem_tutari,
            currency:              $reservation->currency,
            externalReservationId: $reservation->external_reservation_id,
            externalChannel:       $reservation->external_channel,
            createdByUserId:       $reservation->created_by_user_id,
            overrideOfId:          $reservation->override_of_id,
            overrideAuthorizedBy:  $reservation->override_authorized_by,
            overrideOccurredAt:    $reservation->override_occurred_at?->toIso8601String(),
        ));

        return $reservation;
    }

    /**
     * Override execution: atomically cancel conflicting reservation and create new one.
     *
     * PILOT-002 Wave 3 — Canonical override execution path.
     *
     * This method is the SOLE canonical execution path for overrides.
     * Orchestrator authorizes; this service executes — never bypassed.
     *
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @param array $guestData
     * @param int|null $userId
     * @param int $conflictReservationId Conflicting reservation to atomically cancel
     * @param int $overrideAuthorizedBy User ID who authorized the override (for audit)
     * @return PropertyReservation
     * @throws Exception
     */
    public function createReservationWithOverride(
        int    $propertyId,
        string $startDate,
        string $endDate,
        array  $guestData,
        ?int   $userId,
        int    $conflictReservationId,
        int    $overrideAuthorizedBy,
    ): PropertyReservation {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be before end date.");
        }

        $nights = $start->diffInDays($end);

        $ilan = Ilan::withoutGlobalScopes()->findOrFail($propertyId);

        if (!$ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        return DB::transaction(function () use (
            $propertyId, $start, $end, $nights, $guestData, $userId,
            $conflictReservationId, $overrideAuthorizedBy,
        ) {
            // ── 1. Lock the conflicting reservation ──────────────────────────────
            $conflict = PropertyReservation::lockForUpdate()->find($conflictReservationId);

            if ($conflict === null) {
                throw new Exception("Conflict reservation #{$conflictReservationId} not found.");
            }

            if ($conflict->property_id !== $propertyId) {
                throw new Exception("Conflict reservation #{$conflictReservationId} does not belong to property #{$propertyId}.");
            }

            // Refetch current state under lock — if already cancelled, no override needed
            $conflict->refresh();
            $conflictState = $conflict->reservation_state instanceof ReservationState
                ? $conflict->reservation_state->value
                : (string) $conflict->reservation_state;

            if ($conflictState === ReservationState::CANCELLED->value) {
                // Conflict resolved itself — proceed as normal create (no override needed)
                return $this->createReservationInternal(
                    $propertyId, $start, $end, $nights, $guestData, $userId
                );
            }

            // ── 2. Cancel the conflicting reservation ─────────────────────────
            // Release its availability blocks synchronously within this transaction.
            // The event dispatched after commit (see below) triggers channel sync only —
            // it does NOT re-release the blocks (idempotency key prevents double-release).
            $conflict->update([
                'reservation_state' => ReservationState::CANCELLED->value,
                'cancelled_at'     => now(),
            ]);

            // Release conflicting reservation's internal availability blocks synchronously.
            // This is REQUIRED because the cancellation event's listener would release them
            // asynchronously (via queue), but the new reservation needs them available NOW
            // within the same transaction.
            PropertyAvailability::where('reservation_id', $conflictReservationId)
                ->where('source_system', 'internal')
                ->where('is_available', false)
                ->update([
                    'is_available'   => true,
                    'block_reason'   => null,
                    'reservation_id' => null,
                ]);

            // ── 3. Create new reservation (same logic as createReservation) ─────
            $dates = [];
            $current = $start->copy();
            while ($current->lt($end)) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }

            $now = now();
            $insertData = [];
            foreach ($dates as $dateStr) {
                $insertData[] = [
                    'property_id'  => $propertyId,
                    'date'        => $dateStr,
                    'is_available' => true,
                    'source_system' => 'internal',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
            PropertyAvailability::insertOrIgnore($insertData);

            $existingAvailabilities = PropertyAvailability::where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            foreach ($dates as $dateStr) {
                if (isset($existingAvailabilities[$dateStr])) {
                    $avail = $existingAvailabilities[$dateStr];
                    if (!$avail->is_available) {
                        throw new Exception("Dates are not available after override. Conflict on {$dateStr}.");
                    }
                }
            }

            $reservation = PropertyReservation::create([
                'property_id'         => $propertyId,
                'start_date'          => $start->format('Y-m-d'),
                'end_date'            => $end->format('Y-m-d'),
                'nights'              => $nights,
                'guest_name'          => $guestData['guest_name'],
                'guest_phone'         => $guestData['guest_phone'] ?? null,
                'guest_email'         => $guestData['guest_email'] ?? null,
                'guest_count'         => $guestData['guest_count'] ?? null,
                'notes'               => $guestData['notes'] ?? null,
                'reservation_state'   => ReservationState::CONFIRMED->value,
                'created_by_user_id'  => $userId,
                'confirmed_at'        => now(),
                // Wave 3 override audit fields
                'override_of_id'             => $conflictReservationId,
                'override_authorized_by'     => $overrideAuthorizedBy,
                'override_occurred_at'       => now(),
            ]);

            foreach ($dates as $dateStr) {
                $avail = $existingAvailabilities[$dateStr];
                $avail->update([
                    'is_available'   => false,
                    'block_reason'   => 'reservation',
                    'source_system'  => 'internal',
                    'reservation_id' => $reservation->id,
                ]);
            }

            return $reservation;
        });

        // ── Dispatch canonical lifecycle events (after commit) ───────────
        // 1. New reservation created
        event(new ReservationCreatedEvent(
            reservationId:         $reservation->id,
            tenantId:              $reservation->tenant_id ?? 0,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id,
            startDate:             $this->formatDate($reservation->start_date),
            endDate:               $this->formatDate($reservation->end_date),
            nights:                $reservation->nights,
            guestName:             $reservation->guest_name,
            guestPhone:            $reservation->guest_phone,
            guestEmail:            $reservation->guest_email,
            guestCount:            $reservation->guest_count,
            notes:                 $reservation->notes,
            reservationState:      $reservation->reservation_state instanceof ReservationState
                ? $reservation->reservation_state->value
                : (string) $reservation->reservation_state,
            totalAmount:           $reservation->total_amount ?? $reservation->islem_tutari,
            currency:              $reservation->currency,
            externalReservationId: $reservation->external_reservation_id,
            externalChannel:       $reservation->external_channel,
            createdByUserId:       $reservation->created_by_user_id,
            overrideOfId:          $reservation->override_of_id,
            overrideAuthorizedBy:  $reservation->override_authorized_by,
            overrideOccurredAt:    $reservation->override_occurred_at?->toIso8601String(),
        ));

        // 2. Conflicting reservation cancelled — triggers ProcessReservationCancelled →
        //    AvailabilitySynchronizationService.release() for external channel sync
        $cancelledConflict = PropertyReservation::find($conflictReservationId);
        if ($cancelledConflict !== null) {
            $cancelledAt = $cancelledConflict->cancelled_at;
            event(new ReservationCancelledEvent(
                reservationId:         $cancelledConflict->id,
                tenantId:              $cancelledConflict->tenant_id ?? 0,
                ilanId:                $cancelledConflict->ilan_id ?? $cancelledConflict->property_id,
                startDate:             $this->formatDate($cancelledConflict->start_date),
                endDate:               $this->formatDate($cancelledConflict->end_date),
                nights:                $cancelledConflict->nights,
                guestName:             $cancelledConflict->guest_name,
                guestEmail:            $cancelledConflict->guest_email,
                guestPhone:            $cancelledConflict->guest_phone,
                cancelledAt:           $cancelledAt instanceof \Carbon\Carbon
                    ? $cancelledAt->toIso8601String()
                    : (string) $cancelledAt,
                cancelledBy:           'override',
                externalReservationId: $cancelledConflict->external_reservation_id,
                externalChannel:       $cancelledConflict->external_channel,
                reason:                "Override by user #{$overrideAuthorizedBy} → new reservation #{$reservation->id}",
            ));
        }

        return $reservation;
    }

    /**
     * Internal shared create logic (used by both createReservation and createReservationWithOverride).
     *
     * @throws Exception
     */
    private function createReservationInternal(
        int    $propertyId,
        Carbon $start,
        Carbon $end,
        int    $nights,
        array  $guestData,
        ?int   $userId,
    ): PropertyReservation {
        $dates = [];
        $current = $start->copy();
        while ($current->lt($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        $now = now();
        $insertData = [];
        foreach ($dates as $dateStr) {
            $insertData[] = [
                'property_id'  => $propertyId,
                'date'        => $dateStr,
                'is_available' => true,
                'source_system' => 'internal',
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        PropertyAvailability::insertOrIgnore($insertData);

        $existingAvailabilities = PropertyAvailability::where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->lockForUpdate()
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        foreach ($dates as $dateStr) {
            if (isset($existingAvailabilities[$dateStr])) {
                $avail = $existingAvailabilities[$dateStr];
                if (!$avail->is_available) {
                    throw new Exception("Dates are not available. Conflict on {$dateStr}.");
                }
            }
        }

            $reservation = PropertyReservation::create([
                'tenant_id'           => $ilan->tenant_id,
                'property_id'         => $propertyId,
                'start_date'          => $start->format('Y-m-d'),
                'end_date'            => $end->format('Y-m-d'),
                'nights'              => $nights,
                'guest_name'          => $guestData['guest_name'],
                'guest_phone'         => $guestData['guest_phone'] ?? null,
                'guest_email'         => $guestData['guest_email'] ?? null,
                'guest_count'         => $guestData['guest_count'] ?? null,
                'notes'               => $guestData['notes'] ?? null,
                'reservation_state'   => ReservationState::CONFIRMED->value,
                'created_by_user_id'  => $userId,
                'confirmed_at'        => now(),
                // Wave 3 override audit fields
                'override_of_id'             => $conflictReservationId,
                'override_authorized_by'     => $overrideAuthorizedBy,
                'override_occurred_at'       => now()->toDateString(),
            ]);

        foreach ($dates as $dateStr) {
            $avail = $existingAvailabilities[$dateStr];
            $avail->update([
                'is_available'   => false,
                'block_reason'   => 'reservation',
                'source_system'  => 'internal',
                'reservation_id' => $reservation->id,
            ]);
        }

        return $reservation;
    }

    /**
     * Cancel a reservation — public entry point.
     *
     * Dispatches ReservationCancelledEvent after the transaction commits.
     * The event triggers ProcessReservationCancelled → AvailabilitySynchronizationService.release()
     * to propagate the availability release to external channels.
     *
     * @throws Exception
     */
    public function cancelReservation(int $reservationId): void
    {
        $result = $this->cancelReservationInternal($reservationId);

        if ($result === null) {
            return; // Idempotent — was already cancelled
        }

        // Dispatch event AFTER transaction commits
        $reservation = $result;
        $cancelledAt = $reservation->cancelled_at;
        event(new ReservationCancelledEvent(
            reservationId:         $reservation->id,
            tenantId:              $reservation->tenant_id ?? 0,
            ilanId:                $reservation->ilan_id ?? $reservation->property_id,
            startDate:             $this->formatDate($reservation->start_date),
            endDate:               $this->formatDate($reservation->end_date),
            nights:                $reservation->nights,
            guestName:             $reservation->guest_name,
            guestEmail:            $reservation->guest_email,
            guestPhone:            $reservation->guest_phone,
            cancelledAt:           $cancelledAt instanceof \Carbon\Carbon
                ? $cancelledAt->toIso8601String()
                : (string) $cancelledAt,
            cancelledBy:           'system',
            externalReservationId: $reservation->external_reservation_id,
            externalChannel:       $reservation->external_channel,
            reason:                null,
        ));
    }

    /**
     * Internal cancellation — DB transaction only, no event dispatch.
     *
     * Returns null if idempotent (already cancelled).
     * Returns the cancelled PropertyReservation if this was a real cancellation.
     *
     * Releases internal availability blocks within the same transaction to ensure
     * synchronous availability state for callers that cannot use the event chain
     * (e.g., tests with Queue::fake()). The event dispatched by cancelReservation()
     * triggers channel sync via ProcessReservationCancelled.
     *
     * @throws Exception
     */
    private function cancelReservationInternal(int $reservationId): ?PropertyReservation
    {
        $result = null;

        DB::transaction(function () use ($reservationId, &$result) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            $state = $reservation->reservation_state instanceof ReservationState
                ? $reservation->reservation_state->value
                : (string) $reservation->reservation_state;

            if ($state === 'cancelled') {
                $result = null; // Idempotent
                return;
            }

            $reservation->update([
                'reservation_state' => ReservationState::CANCELLED->value,
                'cancelled_at' => now(),
            ]);

            // Release internal availability blocks within the same transaction.
            // External channel blocks (source_system = 'airbnb_ical', etc.) are preserved.
            // Only rows with is_available = false are updated (already-blocked rows are skipped).
            PropertyAvailability::where('reservation_id', $reservationId)
                ->where('source_system', 'internal')
                ->where('is_available', false)
                ->update([
                    'is_available' => true,
                    'block_reason' => null,
                    'reservation_id' => null,
                ]);

            $result = $reservation->fresh();
        });

        return $result;
    }

    /**
     * Modify a reservation's dates and/or guest data.
     *
     * CHANNEL_MANAGER_PROVIDER Wave 3 — ADR-008
     * Canonical method — conflict detection runs inside.
     * Out-of-order: terminal state reservation → silently ignored (returns existing).
     *
     * Availability mutation is handled by the event chain:
     *   modifyReservation → ReservationModifiedEvent
     *     → ProcessReservationModified
     *       → AvailabilitySynchronizationService (release old + block new)
     *
     * @throws Exception on conflict or invalid dates
     */
    public function modifyReservation(
        int    $reservationId,
        string $newStartDate,
        string $newEndDate,
        array  $guestData = [],
    ): PropertyReservation {
        $resultData = null;

        DB::transaction(function () use ($reservationId, $newStartDate, $newEndDate, $guestData, &$resultData) {
            $reservation = PropertyReservation::withoutGlobalScopes()->lockForUpdate()->findOrFail($reservationId);

            // ADR-008: terminal state → silently ignore modification
            $state = $reservation->reservation_state instanceof ReservationState
                ? $reservation->reservation_state->value
                : (string) $reservation->reservation_state;

            if ($state === 'cancelled') {
                $resultData = ['cancelled' => true, 'modified' => false, 'reservation' => $reservation];
                return;
            }

            // Capture previous values for event
            $previousStartDate = $reservation->start_date instanceof Carbon
                ? $reservation->start_date->format('Y-m-d')
                : (string) $reservation->start_date;
            $previousEndDate   = $reservation->end_date instanceof Carbon
                ? $reservation->end_date->format('Y-m-d')
                : (string) $reservation->end_date;
            $previousNights   = $reservation->nights;

            $start = Carbon::parse($newStartDate)->startOfDay();
            $end   = Carbon::parse($newEndDate)->startOfDay();

            if ($start->gte($end)) {
                throw new Exception("Start date must be before end date.");
            }

            $nights = $start->diffInDays($end);

            // Conflict check (exclude self) — OVERLAP constraint from business requirement.
            // Availability conflict detection is handled by the event chain via
            // AvailabilitySynchronizationService.synchronize().
            $overlapCount = PropertyReservation::where('property_id', $reservation->property_id)
                ->where('id', '!=', $reservationId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled')
                ->lockForUpdate()
                ->count();

            if ($overlapCount > 0) {
                throw new Exception("Modification conflict: new dates overlap with an existing reservation.");
            }

            // Update reservation dates — availability mutation via event chain
            $updateData = [
                'start_date' => $start->format('Y-m-d'),
                'end_date'   => $end->format('Y-m-d'),
                'nights'     => $nights,
            ];
            if (!empty($guestData['guest_name'])) {
                $updateData['guest_name'] = $guestData['guest_name'];
            }
            if (array_key_exists('guest_count', $guestData)) {
                $updateData['guest_count'] = $guestData['guest_count'];
            }

            $reservation->update($updateData);

            $resultData = [
                'cancelled'          => false,
                'modified'          => true,
                'reservation'        => $reservation->fresh(),
                'previousStartDate'  => $previousStartDate,
                'previousEndDate'    => $previousEndDate,
                'previousNights'    => $previousNights,
            ];
        });

        // ── Dispatch canonical lifecycle event (after commit) ───────────
        if ($resultData && ($resultData['modified'] ?? false) === true) {
            $reservation = $resultData['reservation'];
            event(new ReservationModifiedEvent(
                reservationId:          $reservation->id,
                tenantId:               $reservation->tenant_id ?? 0,
                ilanId:                 $reservation->ilan_id ?? $reservation->property_id,
                previousStartDate:      $resultData['previousStartDate'],
                previousEndDate:        $resultData['previousEndDate'],
                previousNights:        $resultData['previousNights'],
                newStartDate:           $this->formatDate($reservation->start_date),
                newEndDate:             $this->formatDate($reservation->end_date),
                newNights:             $reservation->nights,
                guestName:             $reservation->guest_name,
                guestCount:            $reservation->guest_count,
                externalReservationId: $reservation->external_reservation_id,
                externalChannel:       $reservation->external_channel,
            ));
        }

        // Cancelled state returns early with $resultData['reservation'] = $reservation (not fresh)
        return $resultData['reservation'] ?? $resultData['reservation'];
    }

    /**
     * Format a date field safely (Carbon or string).
     */
    private function formatDate(\DateTimeInterface|string|null $date): string
    {
        if ($date === null) {
            return '';
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        return (string) $date;
    }
}
