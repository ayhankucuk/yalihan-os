<?php

namespace App\Services\Property;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Events\Property\PropertyAvailabilityBlockedEvent;
use App\Events\Property\PropertyAvailabilityConflictDetectedEvent;
use App\Events\Property\PropertyAvailabilityUnblockedEvent;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * CanonicalAvailabilityService — Enterprise SSOT Availability Engine
 *
 * Enforces:
 * - Single Source of Truth availability resolution
 * - 5-tier Priority Conflict Matrix
 * - Tenant Isolation Boundaries
 * - Idempotency Key Guards
 * - Deterministic Replay & Projection Rebuild
 */
class CanonicalAvailabilityService implements PropertyAvailabilityContract
{
    use GuardsAgentWrites;

    /**
     * Check if a property is available for a requested date range [startDate, endDate).
     */
    public function checkAvailability(int $tenantId, int $propertyId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be strictly before end date.");
        }

        $dates = [];
        $cursor = $start->copy();
        while ($cursor->lt($end)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        // Query daily availability records for this tenant & property
        $records = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

        // Query active reservations for this tenant & property that overlap [startDate, endDate)
        $conflictingReservations = PropertyReservation::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('start_date', '<', $end->format('Y-m-d'))
            ->where('end_date', '>', $start->format('Y-m-d'))
            ->where('reservation_state', '!=', 'cancelled')
            ->whereNull('cancelled_at')
            ->get();

        $conflicts = [];
        foreach ($dates as $dateStr) {
            $rec = $records[$dateStr] ?? null;
            if ($rec && !$rec->is_available) {
                $conflicts[] = [
                    'date' => $dateStr,
                    'block_reason' => $rec->block_reason,
                    'priority_tier' => $rec->priority_tier,
                    'source_system' => $rec->source_system,
                    'reservation_id' => $rec->reservation_id,
                ];
            }
        }

        // Also check direct reservation collisions if not captured in daily records
        foreach ($conflictingReservations as $res) {
            $resStart = Carbon::parse($res->start_date);
            $resEnd   = Carbon::parse($res->end_date);
            foreach ($dates as $dateStr) {
                $d = Carbon::parse($dateStr);
                if ($d->gte($resStart) && $d->lt($resEnd)) {
                    $alreadyAdded = array_filter($conflicts, fn($c) => $c['date'] === $dateStr);
                    if (empty($alreadyAdded)) {
                        $conflicts[] = [
                            'date' => $dateStr,
                            'block_reason' => 'reservation',
                            'priority_tier' => self::TIER_RESERVATION,
                            'source_system' => 'internal',
                            'reservation_id' => $res->id,
                        ];
                    }
                }
            }
        }

        $isAvailable = empty($conflicts);

        return [
            'is_available' => $isAvailable,
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'requested_nights' => count($dates),
            'available_nights' => count($dates) - count($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Block a date range for a property using 5-tier priority matrix and idempotency guard.
     */
    public function blockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        string $reason,
        int $priorityTier = self::TIER_OWNER_BLOCK,
        ?string $idempotencyKey = null,
        ?string $sourceSystem = 'internal',
        ?string $externalRef = null
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be strictly before end date.");
        }

        // Compute default idempotency key if not provided
        $computedIdempotencyKey = $idempotencyKey ?? sprintf(
            '%d:%d:%s:%s:%s',
            $tenantId,
            $propertyId,
            $priorityTier,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        return DB::transaction(function () use (
            $tenantId,
            $propertyId,
            $start,
            $end,
            $reason,
            $priorityTier,
            $computedIdempotencyKey,
            $sourceSystem,
            $externalRef
        ) {
            $dates = [];
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            // Lock existing daily records for update
            $existingRecords = PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            $collidingDates = [];
            foreach ($dates as $dateStr) {
                if (isset($existingRecords[$dateStr])) {
                    $rec = $existingRecords[$dateStr];
                    // Conflict if date is blocked by equal or higher priority tier (lower numeric tier = higher priority)
                    if (!$rec->is_available && $rec->priority_tier <= $priorityTier) {
                        $collidingDates[] = [
                            'date' => $dateStr,
                            'existing_tier' => $rec->priority_tier,
                            'existing_reason' => $rec->block_reason,
                        ];
                    }
                }
            }

            // If there are un-overridable higher/equal priority collisions, reject block and fire event
            if (!empty($collidingDates)) {
                event(new PropertyAvailabilityConflictDetectedEvent(
                    $tenantId,
                    $propertyId,
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d'),
                    $reason,
                    $priorityTier,
                    $collidingDates,
                    $computedIdempotencyKey
                ));

                return [
                    'success' => false,
                    'status' => 'CONFLICT_REJECTED',
                    'reason' => 'Existing higher or equal priority block prevents update.',
                    'collisions' => $collidingDates,
                ];
            }

            // Apply block to daily records (create or update)
            $now = now();
            foreach ($dates as $dateStr) {
                if (isset($existingRecords[$dateStr])) {
                    $rec = $existingRecords[$dateStr];
                    $rec->update([
                        'is_available' => false,
                        'block_reason' => $reason,
                        'priority_tier' => $priorityTier,
                        'idempotency_key' => $computedIdempotencyKey,
                        'source_system' => $sourceSystem,
                        'external_ref' => $externalRef,
                    ]);
                } else {
                    PropertyAvailability::create([
                        'tenant_id' => $tenantId,
                        'property_id' => $propertyId,
                        'date' => $dateStr,
                        'is_available' => false,
                        'block_reason' => $reason,
                        'priority_tier' => $priorityTier,
                        'idempotency_key' => $computedIdempotencyKey,
                        'source_system' => $sourceSystem,
                        'external_ref' => $externalRef,
                    ]);
                }
            }

            // Dispatch domain event
            event(new PropertyAvailabilityBlockedEvent(
                $tenantId,
                $propertyId,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $reason,
                $priorityTier,
                $computedIdempotencyKey,
                $sourceSystem,
                $externalRef
            ));

            return [
                'success' => true,
                'status' => 'BLOCKED',
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'blocked_days' => count($dates),
                'priority_tier' => $priorityTier,
                'idempotency_key' => $computedIdempotencyKey,
            ];
        });
    }

    /**
     * Unblock a date range for a property.
     */
    public function unblockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?string $idempotencyKey = null
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        return DB::transaction(function () use ($tenantId, $propertyId, $start, $end, $idempotencyKey) {
            $dates = [];
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            $affectedRows = PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', false)
                ->update([
                    'is_available' => true,
                    'block_reason' => null,
                    'priority_tier' => self::TIER_HOLD_PENDING,
                    'reservation_id' => null,
                    'idempotency_key' => null,
                ]);

            event(new PropertyAvailabilityUnblockedEvent(
                $tenantId,
                $propertyId,
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $idempotencyKey
            ));

            return [
                'success' => true,
                'status' => 'UNBLOCKED',
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'cleared_records' => $affectedRows,
            ];
        });
    }

    /**
     * Deterministically rebuild availability projection from active reservations and blocks.
     */
    public function rebuildAvailabilityProjection(int $tenantId, int $propertyId, string $startDate, string $endDate): int
    {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        return DB::transaction(function () use ($tenantId, $propertyId, $start, $end) {
            $dates = [];
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            // Wipe existing projections for the range
            PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->delete();

            // Fetch active reservations
            $activeReservations = PropertyReservation::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled')
                ->whereNull('cancelled_at')
                ->get();

            $insertData = [];
            $now = now();

            foreach ($dates as $dateStr) {
                $d = Carbon::parse($dateStr);
                $isBlockedByReservation = false;
                $matchingResId = null;

                foreach ($activeReservations as $res) {
                    $rStart = Carbon::parse($res->start_date);
                    $rEnd   = Carbon::parse($res->end_date);
                    if ($d->gte($rStart) && $d->lt($rEnd)) {
                        $isBlockedByReservation = true;
                        $matchingResId = $res->id;
                        break;
                    }
                }

                $insertData[] = [
                    'tenant_id' => $tenantId,
                    'property_id' => $propertyId,
                    'date' => $dateStr,
                    'is_available' => !$isBlockedByReservation,
                    'block_reason' => $isBlockedByReservation ? 'reservation' : null,
                    'priority_tier' => $isBlockedByReservation ? self::TIER_RESERVATION : self::TIER_HOLD_PENDING,
                    'source_system' => 'internal',
                    'reservation_id' => $matchingResId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            PropertyAvailability::insert($insertData);

            return count($insertData);
        });
    }
}
