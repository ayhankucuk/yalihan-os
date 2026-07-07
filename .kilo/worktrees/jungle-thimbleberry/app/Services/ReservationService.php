<?php

namespace App\Services;

use App\Application\Shared\Services\TenantContextResolver;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\GuardsAgentWrites;

/**
 * ReservationService
 *
 * SSOT for property_reservations table (CI/Airbnb-style calendar).
 *
 * SSOT consolidation (Sprint 5.2 — 2026-07-06):
 * - property_reservations → PropertyReservation is the sole authoritative model.
 * - IlanReservation is deprecated (see IlanReservation.php).
 * - ✅ Tenant isolation: all queries scoped by tenant_id via TenantContextResolver.
 *
 * @see PropertyReservation
 */
class ReservationService
{
    use GuardsAgentWrites;

    public function __construct(
        private readonly TenantContextResolver $tenantResolver,
    ) {}

    /**
     * Create a new reservation with conflict detection and availability blocking.
     *
     * ✅ Tenant isolation: reservation is scoped to current tenant.
     *
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
        $tenantId = $this->tenantResolver->getCurrentTenantId();

        // Tenant isolation: verify property belongs to current tenant
        $ilan = Ilan::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $ilan) {
            throw new Exception("Property not found or access denied.");
        }

        if (! $ilan->rental_enabled) {
            throw new Exception("This property is not enabled for rental.");
        }

        if ($nights < $ilan->min_stay_nights) {
            throw new Exception("Minimum stay is {$ilan->min_stay_nights} nights.");
        }

        return DB::transaction(function () use ($propertyId, $start, $end, $nights, $guestData, $userId, $tenantId) {

            // Overlap Constraint
            $overlapCount = PropertyReservation::where('property_id', $propertyId)
                ->where('tenant_id', $tenantId)
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

            // Bulk insert availability rows (ignore if already exist)
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
                ->keyBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

            foreach ($dates as $dateStr) {
                if (! isset($existingAvailabilities[$dateStr])) {
                    continue;
                }
                $avail = $existingAvailabilities[$dateStr];
                if (! $avail->is_available) {
                    throw new Exception("Dates are not available. Conflict on {$dateStr}.");
                }
            }

            // Create reservation
            $reservation = PropertyReservation::create([
                'tenant_id' => $tenantId,
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

            // Update availability to blocked
            foreach ($dates as $dateStr) {
                if (! isset($existingAvailabilities[$dateStr])) {
                    continue;
                }
                $existingAvailabilities[$dateStr]->update([
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
     * Cancel a reservation and restore availability.
     *
     * ✅ Tenant isolation: reservation is scoped to current tenant.
     *
     * @throws Exception
     */
    public function cancelReservation(int $reservationId): void
    {
        $tenantId = $this->tenantResolver->getCurrentTenantId();

        DB::transaction(function () use ($reservationId, $tenantId) {
            $reservation = PropertyReservation::where('id', $reservationId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (! $reservation) {
                throw new Exception("Reservation not found or access denied.");
            }

            if ($reservation->reservation_state === 'cancelled') {
                return; // Idempotent
            }

            $reservation->update([
                'reservation_state' => 'cancelled',
                'cancelled_at' => now(),
            ]);

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
