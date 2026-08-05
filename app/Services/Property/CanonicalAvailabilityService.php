<?php

namespace App\Services\Property;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
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
 *
 * Sprint 22 E01 Remediation (post-SAAB review):
 * - BLOCKER-1: unblockDateRange now requires idempotency_key or explicit origin+source_system
 *   to prevent cross-source ownership bypass (e.g. maintenance blocks cannot be
 *   removed by an unrelated unblock caller).
 * - BLOCKER-2: rebuildAvailabilityProjection is now origin-scoped: only rows with
 *   origin IN (reservation, yazlik) are wiped and re-projected. Owner, maintenance,
 *   operational, external, and manual blocks are preserved across rebuilds.
 * - BLOCKER-3: yazlik_rezervasyonlar is now tenant-verified via ilanlar.tenant_id JOIN.
 *   The legacy table has no tenant_id column; isolation is enforced through the
 *   canonical property lookup (ilanlar.tenant_id = $tenantId).
 * - REQUIRED-1: conflict_reason is event-only (not persisted on availability rows).
 *   The field exists on the model/migration for future audit use; current contract
 *   is: conflict details are carried on PropertyAvailabilityConflictDetectedEvent only.
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

        // P2.4: tenant_id scope is mandatory — zero cross-tenant leakage.
        // P2.3: Exclude ALL terminal states AND PENDING.
        //       - Terminal states (CANCELLED, COMPLETED, NO_SHOW): dates are already
        //         resolved — they must not appear as active conflicts.
        //       - PENDING: not yet confirmed — does not hold availability.
        //         Only CONFIRMED reservations constitute live conflicts.
        $terminalValues = array_map(
            fn(ReservationState $s) => $s->value,
            array_filter(ReservationState::cases(), fn(ReservationState $s) => $s->isTerminal())
        );
        $conflictingReservations = PropertyReservation::where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('start_date', '<', $end->format('Y-m-d'))
            ->where('end_date', '>', $start->format('Y-m-d'))
            ->whereNotIn('reservation_state', $terminalValues)
            ->where('reservation_state', '!=', ReservationState::PENDING->value)
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
        ?string $idempotencyKey = null,
        ?string $origin = null,
        ?string $sourceSystem = null
    ): array {
        $this->blockAgentWrite(__FUNCTION__);

        // BLOCKER-1 remediation: unblockDateRange must target a specific block by
        // idempotency_key OR by origin+source_system. A caller without any targeting
        // criteria cannot mass-clear blocks of unrelated sources (e.g. maintenance).
        // At minimum, one targeting anchor is required.
        if ($idempotencyKey === null && $origin === null && $sourceSystem === null) {
            throw new Exception(
                "unblockDateRange requires at least one ownership anchor: " .
                "idempotencyKey, origin, or sourceSystem. " .
                "Mass-clearing all blocks in a date range is not permitted."
            );
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        return DB::transaction(function () use (
            $tenantId, $propertyId, $start, $end,
            $idempotencyKey, $origin, $sourceSystem
        ) {
            $dates = [];
            $cursor = $start->copy();
            while ($cursor->lt($end)) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            // Build a targeted query that only removes blocks owned by this caller.
            // Priority system integrity: a TIER_MAINTENANCE block placed by origin=maintenance
            // cannot be removed by a caller passing origin=manual or origin=ical.
            $query = PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->where('is_available', false);

            if ($idempotencyKey !== null) {
                // Idempotency key is the most precise ownership anchor.
                $query->where('idempotency_key', $idempotencyKey);
            } else {
                // Origin + source_system based ownership targeting.
                if ($origin !== null) {
                    $query->where('origin', $origin);
                }
                if ($sourceSystem !== null) {
                    $query->where('source_system', $sourceSystem);
                }
            }

            $affectedRows = $query->update([
                'is_available'            => true,
                'block_reason'            => null,
                // priority_tier and source_system are NOT NULL in DB schema.
                // TIER_HOLD_PENDING (5) is the conventional "free slot" sentinel for
                // available rows. source_system is preserved (not nulled out).
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
                $idempotencyKey, null, $origin
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

    /**
     * Origin-scoped availability projection rebuild.
     *
     * CONTRACT (BLOCKER-2 remediation):
     * This method performs a PARTIAL (origin-scoped) rebuild. Only rows with
     * origin IN ('reservation', 'yazlik') are deleted and re-projected from their
     * canonical sources. Rows with other origins (owner, maintenance, operational,
     * external, manual, system) are preserved untouched.
     *
     * This is intentional: owner blocks, maintenance blocks, and external channel
     * blocks have their own write paths (blockDateRange) and must not be silently
     * destroyed by a reservation-triggered rebuild.
     *
     * If a full wipe-and-rebuild of ALL sources is ever needed, that must be a
     * separate, explicitly named method (e.g. fullRebuildAvailabilityProjection)
     * with appropriate authorization checks.
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

            // BLOCKER-2 remediation: Origin-scoped delete.
            // Only wipe reservation and yazlik projection rows. Preserve all other
            // origins (owner, maintenance, operational, external, manual, system).
            PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->whereIn('origin', [self::ORIGIN_RESERVATION, self::ORIGIN_YAZLIK])
                ->delete();

            // Also wipe rows where origin IS NULL and source is internal reservation
            // (rows written before E2 origin field was added).
            PropertyAvailability::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->whereIn('date', $dates)
                ->whereNull('origin')
                ->where('source_system', 'internal')
                ->where('block_reason', 'reservation')
                ->delete();

            // Source 1: property_reservations (canonical internal reservations)
            // P2.3 Replay safety: include CONFIRMED reservations only — PENDING are not yet
            // committed to availability, CANCELLED/COMPLETED/NO_SHOW are terminal and their
            // availability rows are already correct (COMPLETED/NO_SHOW keep historic blocks,
            // CANCELLED rows were released at cancellation time via cancelReservation()).
            // Using whereNotIn on all terminal states keeps this in sync with Phase 1 enum.
            $activeReservations = PropertyReservation::where('tenant_id', $tenantId)
                ->where('property_id', $propertyId)
                ->where('start_date', '<', $end->format('Y-m-d'))
                ->where('end_date', '>', $start->format('Y-m-d'))
                ->whereNotIn('reservation_state', array_map(
                    fn(ReservationState $s) => $s->value,
                    array_filter(ReservationState::cases(), fn(ReservationState $s) => $s->isTerminal())
                ))
                ->whereNotIn('reservation_state', [ReservationState::PENDING->value])
                ->whereNull('cancelled_at')
                ->get();

            // G2 + BLOCKER-3 remediation: yazlik_rezervasyonlar tenant isolation.
            // The legacy table has no tenant_id column. Isolation is enforced via
            // JOIN on ilanlar.tenant_id — only records whose property belongs to
            // the requested tenant are included in the projection.
            $yazlikReservations = DB::table('yazlik_rezervasyonlar')
                ->join('ilanlar', 'yazlik_rezervasyonlar.ilan_id', '=', 'ilanlar.id')
                ->where('yazlik_rezervasyonlar.ilan_id', $propertyId)
                ->where('ilanlar.tenant_id', $tenantId)
                ->where('yazlik_rezervasyonlar.giris_tarihi', '<', $end->format('Y-m-d'))
                ->where('yazlik_rezervasyonlar.cikis_tarihi', '>', $start->format('Y-m-d'))
                ->whereIn('yazlik_rezervasyonlar.durum', ['beklemede', 'onaylandi'])
                ->select('yazlik_rezervasyonlar.*')
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
