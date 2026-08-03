<?php

namespace App\Services\Property;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Events\Property\PropertyAvailabilityBlockedEvent;
use App\Events\Property\PropertyAvailabilityConflictDetectedEvent;
use App\Events\Property\PropertyAvailabilityUnblockedEvent;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\YazlikRezervasyon;
use App\Traits\GuardsAgentWrites;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * CanonicalAvailabilityService — Enterprise SSOT Availability Engine
 *
 * Sprint 22 E01 SAAB-Enhanced:
 * - G1: All queries tenant_id scoped
 * - G2: rebuildAvailabilityProjection includes YazlikRezervasyon
 * - E2: origin field on all domain events and daily records
 * - E3: projection_generated_at, projection_source, availability_version metadata
 * - E4: Detailed conflict reason codes
 */
class CanonicalAvailabilityService implements PropertyAvailabilityContract
{
    use GuardsAgentWrites;

    // -------------------------------------------------------------------------
    // checkAvailability
    // -------------------------------------------------------------------------

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

        $records = PropertyAvailability::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

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
                    'date'           => $dateStr,
                    'block_reason'   => $rec->block_reason,
                    'priority_tier'  => $rec->priority_tier,
                    'source_system'  => $rec->source_system,
                    'origin'         => $rec->origin,
                    'reservation_id' => $rec->reservation_id,
                ];
            }
        }

        foreach ($conflictingReservations as $res) {
            $resStart = Carbon::parse($res->start_date);
            $resEnd   = Carbon::parse($res->end_date);
            foreach ($dates as $dateStr) {
                $d = Carbon::parse($dateStr);
                if ($d->gte($resStart) && $d->lt($resEnd)) {
                    $alreadyAdded = array_filter($conflicts, fn($c) => $c['date'] === $dateStr);
                    if (empty($alreadyAdded)) {
                        $conflicts[] = [
                            'date'           => $dateStr,
                            'block_reason'   => 'reservation',
                            'priority_tier'  => self::TIER_RESERVATION,
                            'source_system'  => 'internal',
                            'origin'         => self::ORIGIN_RESERVATION,
                            'reservation_id' => $res->id,
                        ];
                    }
                }
            }
        }

        return [
            'is_available'     => empty($conflicts),
            'tenant_id'        => $tenantId,
            'property_id'      => $propertyId,
            'start_date'       => $start->format('Y-m-d'),
            'end_date'         => $end->format('Y-m-d'),
            'requested_nights' => count($dates),
            'available_nights' => count($dates) - count($conflicts),
            'conflicts'        => $conflicts,
        ];
    }

    // -------------------------------------------------------------------------
    // blockDateRange
    // -------------------------------------------------------------------------

    public function blockDateRange(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        string $reason,
        int $priorityTier = self::TIER_OWNER_BLOCK,
        ?string $idempotencyKey = null,
        ?string $sourceSystem = 'internal',
        ?string $externalRef = null,
        ?string $origin = null
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($start->gte($end)) {
            throw new Exception("Start date must be strictly before end date.");
        }

        $computedKey = $idempotencyKey ?? sprintf(
            '%d:%d:%s:%s:%s',
            $tenantId,
            $propertyId,
            $priorityTier,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        $resolvedOrigin = $origin ?? match ($sourceSystem) {
            'airbnb'  => self::ORIGIN_AIRBNB,
            'booking' => self::ORIGIN_BOOKING,
            'ical'    => self::ORIGIN_ICAL,
            default   => self::ORIGIN_MANUAL,
        };

        return DB::transaction(function () use (
            $tenantId, $propertyId, $start, $end, $reason,
            $priorityTier, $computedKey, $sourceSystem, $externalRef, $resolvedOrigin
        ) {
            $dates = [];
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            $existingRecords = PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($item) => Carbon::parse($item->date)->format('Y-m-d'));

            $collidingDates     = [];
            $conflictReasonCode = null;

            foreach ($dates as $dateStr) {
                if (!isset($existingRecords[$dateStr])) {
                    continue;
                }
                $rec = $existingRecords[$dateStr];
                if (!$rec->is_available && $rec->priority_tier <= $priorityTier) {
                    $conflictReasonCode = $this->resolveConflictReasonCode($rec->priority_tier, $rec->block_reason);
                    $collidingDates[] = [
                        'date'            => $dateStr,
                        'existing_tier'   => $rec->priority_tier,
                        'existing_reason' => $rec->block_reason,
                        'existing_origin' => $rec->origin,
                        'conflict_reason' => $conflictReasonCode,
                    ];
                }
            }

            if (!empty($collidingDates)) {
                event(new PropertyAvailabilityConflictDetectedEvent(
                    $tenantId, $propertyId,
                    $start->format('Y-m-d'), $end->format('Y-m-d'),
                    $reason, $priorityTier, $collidingDates,
                    $computedKey, $resolvedOrigin, $conflictReasonCode
                ));

                return [
                    'success'         => false,
                    'status'          => 'CONFLICT_REJECTED',
                    'conflict_reason' => $conflictReasonCode,
                    'reason'          => 'Existing higher or equal priority block prevents update.',
                    'collisions'      => $collidingDates,
                ];
            }

            $now = now();
            foreach ($dates as $dateStr) {
                $data = [
                    'is_available'            => false,
                    'block_reason'            => $reason,
                    'priority_tier'           => $priorityTier,
                    'idempotency_key'         => $computedKey,
                    'source_system'           => $sourceSystem,
                    'external_ref'            => $externalRef,
                    'origin'                  => $resolvedOrigin,
                    'projection_generated_at' => $now,
                    'projection_source'       => self::PROJECTION_SOURCE_BLOCK,
                ];
                if (isset($existingRecords[$dateStr])) {
                    $existingRecords[$dateStr]->update($data);
                } else {
                    PropertyAvailability::create(array_merge($data, [
                        'tenant_id'   => $tenantId,
                        'property_id' => $propertyId,
                        'date'        => $dateStr,
                    ]));
                }
            }

            event(new PropertyAvailabilityBlockedEvent(
                $tenantId, $propertyId,
                $start->format('Y-m-d'), $end->format('Y-m-d'),
                $reason, $priorityTier, $computedKey,
                $sourceSystem, $externalRef, $resolvedOrigin
            ));

            return [
                'success'         => true,
                'status'          => 'BLOCKED',
                'tenant_id'       => $tenantId,
                'property_id'     => $propertyId,
                'start_date'      => $start->format('Y-m-d'),
                'end_date'        => $end->format('Y-m-d'),
                'blocked_days'    => count($dates),
                'priority_tier'   => $priorityTier,
                'idempotency_key' => $computedKey,
                'origin'          => $resolvedOrigin,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // unblockDateRange
    // -------------------------------------------------------------------------

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
                    'is_available'            => true,
                    'block_reason'            => null,
                    'priority_tier'           => self::TIER_HOLD_PENDING,
                    'reservation_id'          => null,
                    'idempotency_key'         => null,
                    'origin'                  => null,
                    'projection_generated_at' => now(),
                    'projection_source'       => self::PROJECTION_SOURCE_BLOCK,
                ]);

            event(new PropertyAvailabilityUnblockedEvent(
                $tenantId, $propertyId,
                $start->format('Y-m-d'), $end->format('Y-m-d'),
                $idempotencyKey
            ));

            return [
                'success'         => true,
                'status'          => 'UNBLOCKED',
                'tenant_id'       => $tenantId,
                'property_id'     => $propertyId,
                'start_date'      => $start->format('Y-m-d'),
                'end_date'        => $end->format('Y-m-d'),
                'cleared_records' => $affectedRows,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // rebuildAvailabilityProjection — G2: includes YazlikRezervasyon
    // -------------------------------------------------------------------------

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

            // Source 1: property_reservations (canonical internal reservations)
            $activeReservations = PropertyReservation::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->where('reservation_state', '!=', 'cancelled')
                ->whereNull('cancelled_at')
                ->get();

            // G2 — Source 2: yazlik_rezervasyonlar (legacy, ilan_id based)
            // Real DB columns (baseline migration): giris_tarihi, cikis_tarihi, durum
            $yazlikReservations = DB::table('yazlik_rezervasyonlar')
                ->where('ilan_id', $propertyId)
                ->where('giris_tarihi', '<', $end->format('Y-m-d'))
                ->where('cikis_tarihi', '>', $start->format('Y-m-d'))
                ->whereIn('durum', ['beklemede', 'onaylandi'])
                ->get();

            // Build a blocked-date index: date => [reservation_id, source, origin]
            $blockedIndex = [];

            foreach ($activeReservations as $res) {
                $rStart = Carbon::parse($res->start_date);
                $rEnd   = Carbon::parse($res->end_date);
                foreach ($dates as $dateStr) {
                    $d = Carbon::parse($dateStr);
                    if ($d->gte($rStart) && $d->lt($rEnd) && !isset($blockedIndex[$dateStr])) {
                        $blockedIndex[$dateStr] = [
                            'reservation_id' => $res->id,
                            'source_system'  => 'internal',
                            'origin'         => self::ORIGIN_RESERVATION,
                        ];
                    }
                }
            }

            foreach ($yazlikReservations as $res) {
                $rStart = Carbon::parse($res->giris_tarihi);
                $rEnd   = Carbon::parse($res->cikis_tarihi);
                foreach ($dates as $dateStr) {
                    $d = Carbon::parse($dateStr);
                    if ($d->gte($rStart) && $d->lt($rEnd) && !isset($blockedIndex[$dateStr])) {
                        $blockedIndex[$dateStr] = [
                            'reservation_id' => null, // yazlik has no FK to property_reservations
                            'source_system'  => 'internal',
                            'origin'         => self::ORIGIN_YAZLIK,
                        ];
                    }
                }
            }

            $insertData = [];
            $now        = now();

            foreach ($dates as $dateStr) {
                $blocked = isset($blockedIndex[$dateStr]);
                $meta    = $blockedIndex[$dateStr] ?? [];

                $insertData[] = [
                    'tenant_id'               => $tenantId,
                    'property_id'             => $propertyId,
                    'date'                    => $dateStr,
                    'is_available'            => !$blocked,
                    'block_reason'            => $blocked ? 'reservation' : null,
                    'priority_tier'           => $blocked ? self::TIER_RESERVATION : self::TIER_HOLD_PENDING,
                    'source_system'           => $meta['source_system'] ?? 'internal',
                    'reservation_id'          => $meta['reservation_id'] ?? null,
                    'origin'                  => $meta['origin'] ?? null,
                    'projection_generated_at' => $now,
                    'projection_source'       => self::PROJECTION_SOURCE_REBUILD,
                    'availability_version'    => 1,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ];
            }

            PropertyAvailability::insert($insertData);

            return count($insertData);
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * E4: Resolve a detailed conflict reason code based on existing block state.
     */
    private function resolveConflictReasonCode(int $existingTier, ?string $existingReason): string
    {
        if ($existingTier === self::TIER_MAINTENANCE) {
            return self::CONFLICT_MAINTENANCE;
        }

        if ($existingTier === self::TIER_OWNER_BLOCK) {
            return self::CONFLICT_OWNER_BLOCK;
        }

        if ($existingTier === self::TIER_EXTERNAL_SYNC) {
            return self::CONFLICT_EXTERNAL_LOCK;
        }

        return self::CONFLICT_HIGHER_PRIORITY;
    }
}
