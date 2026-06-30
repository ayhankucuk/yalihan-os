<?php

namespace App\Services;

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

        $ilan = Ilan::findOrFail($propertyId);

        if (!$ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        return DB::transaction(function () use ($propertyId, $start, $end, $nights, $guestData, $userId) {

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
    }

    /**
     * @param int $reservationId
     * @return void
     * @throws Exception
     */
    public function cancelReservation(int $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            $reservation = PropertyReservation::lockForUpdate()->findOrFail($reservationId);

            if ($reservation->reservation_state === 'cancelled') {
                return; // Idempotent behaviour
            }

            $reservation->update([
                'reservation_state' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Sadece Internal blockları (reservation bazlı) kaldır
            PropertyAvailability::where('reservation_id', $reservationId)
                ->where('source_system', 'internal')
                ->update([
                    'is_available' => true,
                    'block_reason' => null,
                    'reservation_id' => null,
                ]);
        });
    }
}
