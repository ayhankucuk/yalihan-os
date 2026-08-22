<?php

namespace App\Services;

use App\DTOs\Reservation\ValidityResult;
use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationCheckedInEvent;
use App\Events\Reservation\ReservationCheckedOutEvent;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Events\Reservation\ReservationModifiedEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Services\Reservation\GuestArrivalReadinessService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
            // B1: financial fields — conditionally persisted based on column existence.
            // Both production (islem_tutari) and test (total_amount) schemas supported.
            $financialAmount = $guestData['total_amount'] ?? $guestData['total_price'] ?? null;
            $createData = [
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
            ];
            // Only set financial fields when amount is explicitly provided (Channel Manager ingestion).
            // If amount is null, model uses DB defaults — no FxService call in downstream pipeline.
            if ($financialAmount !== null) {
                $currency = $guestData['currency'] ?? $ilan->para_birimi ?? 'TRY';
                if (Schema::hasColumn('property_reservations', 'islem_tutari')) {
                    $createData['islem_tutari'] = $financialAmount;
                } elseif (Schema::hasColumn('property_reservations', 'total_amount')) {
                    $createData['total_amount'] = $financialAmount;
                }
                $createData['currency'] = $currency;
            }
            $reservation = PropertyReservation::create($createData);

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

        // Pass-by-reference so $cancelledConflict is available after transaction
        $cancelledConflict = null;

        $reservation = DB::transaction(function () use (
            $propertyId, $start, $end, $nights, $guestData, $userId,
            $conflictReservationId, $overrideAuthorizedBy, $ilan,
            &$cancelledConflict,  // ← receive cancelled model after commit
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

            // ── 2. Cancel the conflicting reservation via canonical path ────────
            // Uses cancelReservationInternal() so the cancellation DB logic is defined
            // in ONE place. Event dispatch happens below (ReservationCancelledEvent).
            //
            // idempotency: if conflict is already cancelled, cancelReservationInternal()
            // returns null and no availability is released (already free). The new
            // reservation then proceeds to create and lock — correct behavior.
            $cancelledConflict = $this->cancelReservationInternal($conflictReservationId);

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

            // B1: financial fields — conditionally persisted based on column existence.
            $createData = [
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
                'override_occurred_at'       => now(),
            ];
            // B1: only set financial fields when amount is explicitly provided
            $financialAmount = $guestData['total_amount'] ?? $guestData['total_price'] ?? null;
            if ($financialAmount !== null) {
                $currency = $guestData['currency'] ?? $ilan->para_birimi ?? 'TRY';
                if (Schema::hasColumn('property_reservations', 'islem_tutari')) {
                    $createData['islem_tutari'] = $financialAmount;
                } elseif (Schema::hasColumn('property_reservations', 'total_amount')) {
                    $createData['total_amount'] = $financialAmount;
                }
                $createData['currency'] = $currency;
            }
            $reservation = PropertyReservation::create($createData);

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

        // 2. Conflicting reservation cancelled — ONLY if cancelReservationInternal()
        //    actually cancelled it (not idempotent-already-cancelled). This uses the
        //    canonical cancellation path so DB logic lives in ONE place.
        //    Triggers ProcessReservationCancelled → availability sync + financial reversal.
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
                ->whereIn('source_system', ['internal', 'canonical'])
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
            // B1: financial fields — Channel Manager ingestion may carry price updates
            if (array_key_exists('total_amount', $guestData) || array_key_exists('total_price', $guestData)) {
                $amount = $guestData['total_amount'] ?? $guestData['total_price'] ?? null;
                if (Schema::hasColumn('property_reservations', 'islem_tutari')) {
                    $updateData['islem_tutari'] = $amount;
                } elseif (Schema::hasColumn('property_reservations', 'total_amount')) {
                    $updateData['total_amount'] = $amount;
                }
            }
            if (array_key_exists('currency', $guestData)) {
                $updateData['currency'] = $guestData['currency'];
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

    // ─────────────────────────────────────────────────────────────────────
    // Wave 4: Real-Time Check-in / Check-out
    // SAAB Decision WAVE4-CHECKIN / WAVE4-CHECKOUT
    // Baseline: 8406c78
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Record guest physical check-in.
     *
     * Invariants enforced (in order):
     *  1. Tenant isolation — reservation must belong to $tenantId
     *  2. Reservation must be CONFIRMED
     *  3. Reservation must not be cancelled
     *  4. Idempotency — checked_in_at must be null (lockForUpdate)
     *  5. Readiness gate — GuestArrivalReadinessService::canCheckIn() must PASS
     *
     * Fires ReservationCheckedInEvent after transaction commits.
     *
     * @throws Exception on invariant violation
     */
    public function checkIn(int $reservationId, int $tenantId): PropertyReservation
    {
        $this->blockAgentWrite(__FUNCTION__);

        $reservation = null;

        DB::transaction(function () use ($reservationId, $tenantId, &$reservation) {
            /** @var PropertyReservation|null $locked */
            $locked = PropertyReservation::lockForUpdate()
                ->where('id', $reservationId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($locked === null) {
                throw new Exception(
                    "Check-in failed: reservation #{$reservationId} not found or tenant mismatch."
                );
            }

            // INV-W4-CI-1: must be CONFIRMED
            $state = $locked->reservation_state instanceof ReservationState
                ? $locked->reservation_state->value
                : (string) $locked->reservation_state;

            if ($state !== 'confirmed') {
                throw new Exception(
                    "Check-in failed: reservation #{$reservationId} is not CONFIRMED (current: {$state})."
                );
            }

            // INV-W4-CI-2: must not be cancelled
            if ($locked->cancelled_at !== null) {
                throw new Exception(
                    "Check-in failed: reservation #{$reservationId} is cancelled."
                );
            }

            // INV-W4-CI-3: idempotency — cannot check in twice
            if ($locked->checked_in_at !== null) {
                Log::info('ReservationService::checkIn: idempotent — already checked in', [
                    'reservation_id' => $reservationId,
                    'tenant_id'      => $tenantId,
                    'checked_in_at'  => $locked->checked_in_at->toIso8601String(),
                ]);
                $reservation = $locked;
                return; // Idempotent: return existing state, no double-stamp
            }

            // INV-W4-CI-4: readiness gate — reuse existing canonical policy
            // SAAB Decision Q2: canCheckIn() must be enforced here.
            // GuestArrivalReadinessService::canCheckIn() is the canonical readiness policy.
            $readinessService = app(GuestArrivalReadinessService::class);
            $validityResult = $readinessService->canCheckIn($locked);

            if (! $validityResult->canCheckIn) {
                throw new Exception(
                    "Check-in blocked [{$validityResult->blockedCode}]: {$validityResult->blockedReason}"
                );
            }

            // All invariants passed — stamp check-in
            $locked->update(['checked_in_at' => now()]);

            Log::info('ReservationService::checkIn: checked_in_at stamped', [
                'reservation_id' => $reservationId,
                'tenant_id'      => $tenantId,
                'checked_in_at'  => $locked->checked_in_at?->toIso8601String() ?? now()->toIso8601String(),
            ]);

            $reservation = $locked->fresh();
        });

        // Dispatch canonical event after commit
        event(ReservationCheckedInEvent::fromModel($reservation));

        return $reservation;
    }

    /**
     * Record guest physical check-out and immediately trigger turnover.
     *
     * Invariants enforced (in order):
     *  1. Tenant isolation — reservation must belong to $tenantId
     *  2. Idempotency — checked_out_at must be null (lockForUpdate)
     *  3. Soft guard — if checked_in_at IS NULL, emits structured warning and continues
     *     (SAAB Decision Q1: checkout must not be blocked by missing check-in record)
     *
     * Stamps checked_out_at and completed_at.
     * Fires ReservationCheckedOutEvent (domain fact) and
     * ReservationCompletedEvent (reuses canonical turnover pipeline).
     *
     * @throws Exception on invariant violation
     */
    public function checkOut(int $reservationId, int $tenantId): PropertyReservation
    {
        $this->blockAgentWrite(__FUNCTION__);

        $reservation = null;
        $hadFormalCheckin = true;

        DB::transaction(function () use ($reservationId, $tenantId, &$reservation, &$hadFormalCheckin) {
            /** @var PropertyReservation|null $locked */
            $locked = PropertyReservation::lockForUpdate()
                ->where('id', $reservationId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($locked === null) {
                throw new Exception(
                    "Check-out failed: reservation #{$reservationId} not found or tenant mismatch."
                );
            }

            // INV-W4-CO-1: idempotency — cannot check out twice
            if ($locked->checked_out_at !== null) {
                Log::info('ReservationService::checkOut: idempotent — already checked out', [
                    'reservation_id'  => $reservationId,
                    'tenant_id'       => $tenantId,
                    'checked_out_at'  => $locked->checked_out_at->toIso8601String(),
                ]);
                $reservation = $locked;
                return; // Idempotent
            }

            // INV-W4-CO-2: soft guard — warn if check-in was never formally recorded
            if ($locked->checked_in_at === null) {
                $hadFormalCheckin = false;
                Log::warning('ReservationService::checkOut: soft-guard — checkout without formal check-in', [
                    'reservation_id' => $reservationId,
                    'tenant_id'      => $tenantId,
                    'executed_at'    => now()->toIso8601String(),
                    'audit_note'     => 'SAAB-Q1: checkout proceeds, checked_in_at was null. Manual reconciliation may be required.',
                ]);
            }

            $now = now();
            $updates = [
                'checked_out_at' => $now,
            ];

            // Stamp completed_at only if not already set (idempotent canonical completion)
            if ($locked->completed_at === null) {
                $updates['completed_at'] = $now;
            }

            $locked->update($updates);

            Log::info('ReservationService::checkOut: checked_out_at stamped', [
                'reservation_id'   => $reservationId,
                'tenant_id'        => $tenantId,
                'checked_out_at'   => $now->toIso8601String(),
                'completed_at'     => $locked->fresh()->completed_at?->toIso8601String(),
                'had_formal_checkin' => $hadFormalCheckin,
            ]);

            $reservation = $locked->fresh();
        });

        // ── Dispatch domain fact event (checkout record) ────────────────
        event(ReservationCheckedOutEvent::fromModel($reservation));

        // ── Reuse canonical turnover pipeline ───────────────────────────
        // ReservationCompletedEvent → ListenReservationCompleted
        //   → ProcessReservationCompletedJob → OperationalGorevService::createTurnoverTask()
        // Idempotent: OperationalGorevService checks for existing temizlik gorev before creating.
        event(ReservationCompletedEvent::fromModel($reservation));

        return $reservation;
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
