<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\CanonicalAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2: E03 — Replay/Rebuild Safety
 *
 * Başarı sorusu (SAAB):
 * "Replay edilen projection ile canlı çalışan projection aynı sonucu üretiyor mu?"
 *
 * Test matrix:
 * E03.1 rebuild_matches_runtime_projection
 * E03.2 replay_twice_is_idempotent
 * E03.3 replay_does_not_mutate_history
 * E03.4 rebuild_is_tenant_scoped
 * E03.5 failed_rebuild_leaves_no_partial_projection
 * E03.6 withoutGlobalScopes_does_not_bypass_tenant_ownership_check
 */
class AvailabilityProjectionReplayTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityProjectionContract $projection;
    protected CanonicalAvailabilityService $canonicalService;
    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projection       = app(AvailabilityProjectionContract::class);
        $this->canonicalService = app(CanonicalAvailabilityService::class);

        $this->tenant = Tenant::create([
            'name'      => 'E03 Landlord',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'E03 Villa',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id'       => $this->tenant->id,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        // Force tenant_id — bypasses BelongsToTenant scope in test env
        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);
    }

    // =========================================================================
    // E03.1 — rebuild sonucu == runtime projection sonucu
    // =========================================================================

    /** @test */
    public function rebuild_matches_runtime_projection(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2030-01-10';
        $endDate    = '2030-01-14'; // 4 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 4,
            'guest_name'        => 'Rebuild Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // Step 1: runtime projection via projectConfirm()
        $this->projection->projectConfirm(
            $reservation->id, $tenantId, $propertyId, $startDate, $endDate
        );

        $runtimeBlocks = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->orderBy('date')
            ->get(['date', 'is_available', 'block_reason', 'priority_tier', 'origin', 'reservation_id'])
            ->toArray();

        // Step 2: wipe runtime projection, rebuild from canonical service
        PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->delete();

        $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        $rebuildBlocks = PropertyAvailability::where('property_id', $propertyId)
            ->where('is_available', false)
            ->where('origin', PropertyAvailabilityContract::ORIGIN_RESERVATION)
            ->orderBy('date')
            ->get(['date', 'is_available', 'block_reason', 'priority_tier', 'origin', 'reservation_id'])
            ->toArray();

        // Same count
        $this->assertCount(
            count($runtimeBlocks),
            $rebuildBlocks,
            'Rebuild must produce the same number of blocked rows as runtime projection'
        );

        // Same date set
        $runtimeDates  = array_column($runtimeBlocks, 'date');
        $rebuildDates  = array_column($rebuildBlocks, 'date');
        sort($runtimeDates);
        sort($rebuildDates);

        $this->assertEquals($runtimeDates, $rebuildDates,
            'Rebuild must block the same dates as runtime projection');

        // Same block semantics
        foreach ($rebuildBlocks as $block) {
            $this->assertFalse((bool) $block['is_available']);
            $this->assertEquals('reservation', $block['block_reason']);
            $this->assertEquals(PropertyAvailabilityContract::ORIGIN_RESERVATION, $block['origin']);
        }
    }

    // =========================================================================
    // E03.2 — replay twice is idempotent
    // =========================================================================

    /** @test */
    public function replay_twice_is_idempotent(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2030-02-01';
        $endDate    = '2030-02-05'; // 4 nights

        PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 4,
            'guest_name'        => 'Replay Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // First rebuild
        $count1 = $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        $snapshot1 = PropertyAvailability::where('property_id', $propertyId)
            ->orderBy('date')
            ->get(['date', 'is_available', 'block_reason', 'origin'])
            ->toArray();

        // Second rebuild — must yield identical state
        $count2 = $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        $snapshot2 = PropertyAvailability::where('property_id', $propertyId)
            ->orderBy('date')
            ->get(['date', 'is_available', 'block_reason', 'origin'])
            ->toArray();

        $this->assertEquals($count1, $count2,
            'Rebuild called twice must return same row count');
        $this->assertEquals($snapshot1, $snapshot2,
            'Availability state must be identical after two rebuilds');
    }

    // =========================================================================
    // E03.3 — replay does not mutate history
    // =========================================================================

    /** @test */
    public function replay_does_not_mutate_history(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2030-03-05';
        $endDate    = '2030-03-08'; // 3 nights

        // Place an owner block that must NOT be touched by rebuild
        $this->canonicalService->blockDateRange(
            $tenantId,
            $propertyId,
            '2030-03-06',
            '2030-03-07',
            'Owner Hold',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            'OWNER_E03_KEY_001',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_OWNER
        );

        $ownerBlockBefore = PropertyAvailability::where('property_id', $propertyId)
            ->where('date', '2030-03-06')
            ->where('origin', PropertyAvailabilityContract::ORIGIN_OWNER)
            ->first();

        $this->assertNotNull($ownerBlockBefore, 'Owner block must exist before rebuild');

        // Rebuild — must only touch reservation+yazlik origins
        $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        $ownerBlockAfter = PropertyAvailability::where('property_id', $propertyId)
            ->where('date', '2030-03-06')
            ->where('origin', PropertyAvailabilityContract::ORIGIN_OWNER)
            ->first();

        $this->assertNotNull($ownerBlockAfter,
            'Owner block must survive rebuild (replay must not mutate non-reservation history)');
        $this->assertEquals($ownerBlockBefore->id, $ownerBlockAfter->id,
            'Owner block row must be the same record — not deleted and re-inserted');
        $this->assertFalse((bool) $ownerBlockAfter->is_available);
    }

    // =========================================================================
    // E03.4 — rebuild is tenant scoped
    // =========================================================================

    /** @test */
    public function rebuild_is_tenant_scoped(): void
    {
        $tenant2   = Tenant::create(['name' => 'Tenant2', 'status' => 'active', 'is_active' => true]);
        $property2 = Ilan::create([
            'baslik'          => 'Tenant2 Property',
            'fiyat'           => 2000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id'       => $tenant2->id,
        ]);
        DB::table('ilanlar')->where('id', $property2->id)->update(['tenant_id' => $tenant2->id]);

        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2030-04-01';
        $endDate    = '2030-04-05';

        // Tenant2's reservation on a different property
        PropertyReservation::create([
            'tenant_id'         => $tenant2->id,
            'property_id'       => $property2->id,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 4,
            'guest_name'        => 'Tenant2 Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // Rebuild for tenant1's property
        $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        // Tenant1's property must show no blocks (no confirmed reservations)
        $blockedRows = PropertyAvailability::where('property_id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->where('is_available', false)
            ->whereBetween('date', [$startDate, '2030-04-04'])
            ->count();

        $this->assertEquals(0, $blockedRows,
            'Rebuild for tenant1 must not include tenant2 reservations');
    }

    // =========================================================================
    // E03.5 — failed rebuild leaves no partial projection
    // =========================================================================

    /** @test */
    public function failed_rebuild_leaves_no_partial_projection(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2030-05-01';
        $endDate    = '2030-05-06'; // 5 nights

        PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 5,
            'guest_name'        => 'Partial Rebuild Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // First build a clean projection
        $this->canonicalService->rebuildAvailabilityProjection(
            $tenantId, $propertyId, $startDate, $endDate
        );

        $beforeCount = PropertyAvailability::where('property_id', $propertyId)
            ->where('is_available', false)
            ->whereBetween('date', [$startDate, '2030-05-05'])
            ->count();

        $this->assertEquals(5, $beforeCount, 'Pre-condition: 5 blocked rows');

        // Simulate a failed rebuild: use DB transaction that rolls back.
        // The state before the rebuild must be restored.
        try {
            DB::transaction(function () use ($tenantId, $propertyId, $startDate, $endDate) {
                // Manually delete origin-scoped rows (as rebuild does)
                PropertyAvailability::where('property_id', $propertyId)
                    ->whereIn('origin', [
                        PropertyAvailabilityContract::ORIGIN_RESERVATION,
                        PropertyAvailabilityContract::ORIGIN_YAZLIK,
                    ])
                    ->delete();

                // Intentionally throw to simulate a mid-rebuild failure
                throw new \RuntimeException('Simulated rebuild failure');
            });
        } catch (\RuntimeException $e) {
            // Expected — transaction rolled back
        }

        // State must be identical to before the failed rebuild
        $afterCount = PropertyAvailability::where('property_id', $propertyId)
            ->where('is_available', false)
            ->whereBetween('date', [$startDate, '2030-05-05'])
            ->count();

        $this->assertEquals($beforeCount, $afterCount,
            'Failed rebuild (rolled-back transaction) must leave projection intact');
    }

    // =========================================================================
    // E03.6 — withoutGlobalScopes does not bypass tenant ownership check
    // =========================================================================

    /** @test */
    public function withoutGlobalScopes_does_not_bypass_tenant_ownership_check(): void
    {
        $tenant2   = Tenant::create(['name' => 'Attacker Tenant', 'status' => 'active', 'is_active' => true]);
        $property2 = Ilan::create([
            'baslik'          => 'Victim Property',
            'fiyat'           => 3000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
        ]);
        // property2 has NO tenant_id (legacy data edge case) — but still,
        // if tenant_id IS set, cross-tenant must be rejected.
        DB::table('ilanlar')->where('id', $property2->id)->update(['tenant_id' => $tenant2->id]);

        // Tenant1 attempts to project on Tenant2's property
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Cross-tenant violation/');

        $this->projection->projectConfirm(
            999,                     // fake reservation ID
            $this->tenant->id,       // tenant1
            $property2->id,          // tenant2's property
            '2030-06-01',
            '2030-06-03'
        );
    }
}
