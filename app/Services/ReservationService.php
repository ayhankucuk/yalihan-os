<?php

namespace App\Services;

use App\Contracts\Property\PropertyAvailabilityContract;
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
 * Sprint 22 E01: Added tenant_id scope to all queries (G1 fix).
 * Sprint 22 E01 TypeError fix: Moved tenantId to optional last parameter so all
 * pre-existing callers (propertyId, start, end, guestData, userId) continue to work.
 * tenantId is auto-resolved from Ilan.tenant_id when not explicitly provided.
 */
class ReservationService
{
    use GuardsAgentWrites;

    /**
     * Create a new confirmed reservation for a property.
     *
     * @param int      $propertyId
     * @param string   $startDate
     * @param string   $endDate
     * @param array    $guestData
     * @param int|null $userId
     * @param int|null $tenantId   Optional — resolved from Ilan.tenant_id when omitted.
     *                             Pre-existing callers that omit tenantId continue to work.
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

            // G1: Overlap check — scope to tenant_id when non-zero, always scope to property_id.
            $overlapQuery = PropertyReservation::where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled');

            if ($tenantId > 0) {
                $overlapQuery->where('tenant_id', $tenantId);
            }

            $overlapCount = $overlapQuery->lockForUpdate()->count();

            if ($overlapCount > 0) {
                throw new Exception("Conflict detected: The selected dates overlap with an existing reservation.");
            }

            $dates = [];
            $currentDate = $start->copy();
            while ($currentDate->lt($end)) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Check availability BEFORE inserting placeholder rows.
            // Any existing row with is_available=false blocks the booking regardless of source or tenant.
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

            // Insert placeholder rows only for dates that don't already exist.
            $now = now();
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

            // G1: Lock existing rows scoped to tenant_id AND property_id.
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

            // Create reservation
            $reservation = PropertyReservation::create([
                'tenant_id'            => $tenantId ?: null,
                'property_id'          => $propertyId,
                'start_date'           => $start->format('Y-m-d'),
                'end_date'             => $end->format('Y-m-d'),
                'nights'               => $nights,
                'guest_name'           => $guestData['guest_name'],
                'guest_phone'          => $guestData['guest_phone'] ?? null,
                'guest_email'          => $guestData['guest_email'] ?? null,
                'guest_count'          => $guestData['guest_count'] ?? null,
                'notes'                => $guestData['notes'] ?? null,
                'reservation_state'    => 'confirmed',
                'created_by_user_id'   => $userId,
                'confirmed_at'         => now(),
            ]);

            // Mark availability records as blocked
            foreach ($dates as $dateStr) {
                $avail = $existingAvailabilities[$dateStr];
                $avail->update([
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

            return $reservation;
        });
    }

    /**
     * Cancel an existing reservation and free its availability blocks.
     *
     * @param int      $reservationId
     * @param int|null $tenantId  Optional — resolved from the reservation record when omitted.
     *                            Pre-existing callers that pass only reservationId continue to work.
     * @return void
     * @throws Exception
     */
    public function cancelReservation(int $reservationId, ?int $tenantId = null): void
    {
        DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            // Resolve tenantId from the reservation when not provided by the caller.
            $tenantId = $tenantId ?? (int) $reservation->tenant_id;

            // Re-validate tenant ownership after resolution.
            if ($reservation->tenant_id && $reservation->tenant_id !== $tenantId) {
                throw new Exception("Reservation does not belong to the given tenant.");
            }

            if ($reservation->reservation_state === 'cancelled') {
                return; // Idempotent behaviour
            }

            $reservation->update([
                'reservation_state' => 'cancelled',
                'cancelled_at'      => now(),
            ]);

            // G1: Availability cleanup scoped to tenant_id when present.
            // priority_tier is NOT NULL — use TIER_HOLD_PENDING as the "free slot" sentinel.
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
}
