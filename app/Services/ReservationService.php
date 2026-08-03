<?php

namespace App\Services;

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
 * All reads and writes are scoped to both tenant_id AND property_id.
 */
class ReservationService
{
    use GuardsAgentWrites;

    /**
     * Create a new confirmed reservation for a property.
     *
     * @param int      $tenantId
     * @param int      $propertyId
     * @param string   $startDate
     * @param string   $endDate
     * @param array    $guestData
     * @param int|null $userId
     * @return PropertyReservation
     * @throws Exception
     */
    public function createReservation(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        array $guestData,
        ?int $userId = null
    ): PropertyReservation {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be before end date.");
        }

        $nights = $start->diffInDays($end);

        $ilan = Ilan::findOrFail($propertyId);

        if (!$ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        return DB::transaction(function () use ($tenantId, $propertyId, $start, $end, $nights, $guestData, $userId) {

            // G1: Overlap check scoped to BOTH tenant_id AND property_id
            $overlapCount = PropertyReservation::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled')
                ->lockForUpdate()
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

            // Ensure rows exist before locking
            $now = now();
            $insertData = [];
            foreach ($dates as $dateStr) {
                $insertData[] = [
                    'tenant_id'     => $tenantId,
                    'property_id'   => $propertyId,
                    'date'          => $dateStr,
                    'is_available'  => true,
                    'source_system' => 'internal',
                    'origin'        => 'system',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            PropertyAvailability::insertOrIgnore($insertData);

            // G1: Lock existing rows scoped to tenant_id AND property_id
            $existingAvailabilities = PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
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

            // Create reservation
            $reservation = PropertyReservation::create([
                'tenant_id'            => $tenantId,
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
                    'priority_tier'           => 2, // TIER_RESERVATION
                    'source_system'           => 'internal',
                    'origin'                  => 'reservation',
                    'reservation_id'          => $reservation->id,
                    'projection_generated_at' => $now,
                    'projection_source'       => 'reservation',
                ]);
            }

            return $reservation;
        });
    }

    /**
     * Cancel an existing reservation and free its availability blocks.
     *
     * @param int $reservationId
     * @param int $tenantId       Required for tenant-scoped availability cleanup (G1)
     * @return void
     * @throws Exception
     */
    public function cancelReservation(int $reservationId, int $tenantId): void
    {
        DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($reservationId);

            if ($reservation->reservation_state === 'cancelled') {
                return; // Idempotent behaviour
            }

            $reservation->update([
                'reservation_state' => 'cancelled',
                'cancelled_at'      => now(),
            ]);

            // G1: Availability cleanup scoped to tenant_id
            PropertyAvailability::where('tenant_id', $tenantId)
                ->where('reservation_id', $reservationId)
                ->where('source_system', 'internal')
                ->update([
                    'is_available'            => true,
                    'block_reason'            => null,
                    'priority_tier'           => 5, // TIER_HOLD_PENDING
                    'reservation_id'          => null,
                    'origin'                  => null,
                    'projection_generated_at' => now(),
                    'projection_source'       => 'reservation',
                ]);
        });
    }
}
