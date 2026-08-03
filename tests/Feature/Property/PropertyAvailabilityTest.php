<?php

namespace Tests\Feature\Property;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Events\Property\PropertyAvailabilityBlockedEvent;
use App\Events\Property\PropertyAvailabilityConflictDetectedEvent;
use App\Events\Property\PropertyAvailabilityUnblockedEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PropertyAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $property;
    protected PropertyAvailabilityContract $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Bodrum Luxury Stays',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'Yalıhan Luxury Villa Yalıkavak',
            'para_birimi'     => 'TRY',
            'fiyat'           => 50000.00,
            'yayin_durumu'    => 'aktif',
            'aktiflik_durumu' => true,
            'tenant_id'       => $this->tenant->id,  // Required for yazlik tenant JOIN
        ]);

        $this->availabilityService = app(PropertyAvailabilityContract::class);
    }

    /** @test */
    public function it_checks_availability_and_returns_true_for_unblocked_dates()
    {
        $result = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-05'
        );

        $this->assertTrue($result['is_available']);
        $this->assertEquals(4, $result['requested_nights']);
        $this->assertEquals(4, $result['available_nights']);
        $this->assertEmpty($result['conflicts']);
    }

    /** @test */
    public function it_blocks_date_range_and_dispatches_blocked_event()
    {
        Event::fake([PropertyAvailabilityBlockedEvent::class]);

        $blockResult = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-09-10',
            '2026-09-15',
            'Owner Personal Stay',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            'OWNER_BLOCK_IDEMPOTENCY_KEY_001'
        );

        $this->assertTrue($blockResult['success']);
        $this->assertEquals('BLOCKED', $blockResult['status']);
        $this->assertEquals(5, $blockResult['blocked_days']);

        Event::assertDispatched(PropertyAvailabilityBlockedEvent::class, function ($event) {
            return $event->tenantId === $this->tenant->id
                && $event->propertyId === $this->property->id
                && $event->startDate === '2026-09-10'
                && $event->endDate === '2026-09-15'
                && $event->priorityTier === PropertyAvailabilityContract::TIER_OWNER_BLOCK;
        });

        // Verify database state
        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2026-09-10',
            '2026-09-15'
        );

        $this->assertFalse($check['is_available']);
        $this->assertCount(5, $check['conflicts']);
    }

    /** @test */
    public function it_prevents_lower_priority_block_from_overriding_higher_priority_block()
    {
        Event::fake([PropertyAvailabilityConflictDetectedEvent::class]);

        // Step 1: Block with Tier 2 (Reservation)
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-09-20',
            '2026-09-25',
            'Confirmed Booking #1001',
            PropertyAvailabilityContract::TIER_RESERVATION
        );

        // Step 2: Attempt to block overlapping dates with Tier 4 (External Sync)
        $overrideResult = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-09-22',
            '2026-09-27',
            'Airbnb iCal Sync',
            PropertyAvailabilityContract::TIER_EXTERNAL_SYNC
        );

        $this->assertFalse($overrideResult['success']);
        $this->assertEquals('CONFLICT_REJECTED', $overrideResult['status']);

        Event::assertDispatched(PropertyAvailabilityConflictDetectedEvent::class);
    }

    /** @test */
    public function it_allows_higher_priority_maintenance_tier_to_override_lower_owner_block()
    {
        // Step 1: Block with Tier 3 (Owner Block)
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-10-01',
            '2026-10-05',
            'Owner Weekend',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK
        );

        // Step 2: Emergency Maintenance Tier 1 overrides Tier 3
        $maintenanceResult = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-10-02',
            '2026-10-04',
            'Pool Repair Hazard',
            PropertyAvailabilityContract::TIER_MAINTENANCE
        );

        $this->assertTrue($maintenanceResult['success']);
        $this->assertEquals('BLOCKED', $maintenanceResult['status']);

        // Verify database reflects Maintenance Tier 1 for October 2nd
        $rec = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2026-10-02')
            ->orderBy('id')
            ->first();

        $this->assertNotNull($rec);
        $this->assertEquals('Pool Repair Hazard', $rec->block_reason);
        $this->assertEquals(PropertyAvailabilityContract::TIER_MAINTENANCE, $rec->priority_tier);
    }

    /** @test */
    public function it_unblocks_date_range_and_dispatches_unblocked_event()
    {
        Event::fake([PropertyAvailabilityUnblockedEvent::class]);

        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-11-01',
            '2026-11-05',
            'Temporary Maintenance',
            PropertyAvailabilityContract::TIER_MAINTENANCE,
            'MAINT_UNBLOCK_KEY_001',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_MAINTENANCE
        );

        $unblockResult = $this->availabilityService->unblockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-11-01',
            '2026-11-05',
            'MAINT_UNBLOCK_KEY_001'  // idempotency_key — most precise anchor
        );

        $this->assertTrue($unblockResult['success']);
        $this->assertEquals(4, $unblockResult['cleared_records']);

        Event::assertDispatched(PropertyAvailabilityUnblockedEvent::class);

        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2026-11-01',
            '2026-11-05'
        );

        $this->assertTrue($check['is_available']);
    }

    /** @test */
    public function it_rebuilds_availability_projection_from_active_reservations()
    {
        // Create an active reservation
        PropertyReservation::create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
            'nights' => 4,
            'guest_name' => 'Ayhan Bey',
            'reservation_state' => 'confirmed',
        ]);

        // Trigger projection rebuild
        $rebuiltCount = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id,
            $this->property->id,
            '2026-12-01',
            '2026-12-06'
        );

        $this->assertEquals(5, $rebuiltCount);

        // Check 2026-12-01 to 2026-12-05 is blocked by reservation
        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2026-12-01',
            '2026-12-05'
        );

        $this->assertFalse($check['is_available']);
        $this->assertCount(4, $check['conflicts']);
    }

    /** @test */
    public function it_enforces_tenant_isolation_boundaries()
    {
        $tenant2 = Tenant::create(['name' => 'Alaçatı Stays', 'status' => 'active', 'is_active' => true]);

        // Block on Tenant 1
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-05',
            'Tenant 1 Block',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK
        );

        // Querying for Tenant 2 should return available (zero cross-tenant leakage)
        $checkTenant2 = $this->availabilityService->checkAvailability(
            $tenant2->id,
            $this->property->id,
            '2026-09-01',
            '2026-09-05'
        );

        $this->assertTrue($checkTenant2['is_available']);
        $this->assertEmpty($checkTenant2['conflicts']);
    }

    /** @test */
    public function it_rebuilds_projection_including_yazlik_rezervasyonlar()
    {
        // Ensure ilanlar.tenant_id is set for the test property so the
        // yazlik tenant-isolation JOIN (ilanlar.tenant_id = $tenantId) works.
        // BelongsToTenant auto-assign may not fire without TenantContextService,
        // so we enforce it via raw update here.
        \Illuminate\Support\Facades\DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);

        // Create a yazlik reservation using the real DB column names from baseline migration:
        // giris_tarihi, cikis_tarihi, toplam_tutar, durum
        \Illuminate\Support\Facades\DB::table('yazlik_rezervasyonlar')->insert([
            'ilan_id'       => $this->property->id,
            'giris_tarihi'  => '2027-01-10',
            'cikis_tarihi'  => '2027-01-14',
            'toplam_tutar'  => 10000,
            'durum'         => 'onaylandi',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $rebuiltCount = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id,
            $this->property->id,
            '2027-01-10',
            '2027-01-15'
        );

        $this->assertEquals(5, $rebuiltCount);

        // Days 10-13 should be blocked (yazlik reservation), day 14 should be free
        $blockedDay = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2027-01-11')
            ->first();

        $this->assertNotNull($blockedDay);
        $this->assertFalse($blockedDay->is_available);
        $this->assertEquals(\App\Contracts\Property\PropertyAvailabilityContract::ORIGIN_YAZLIK, $blockedDay->origin);

        $freeDay = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2027-01-14')
            ->first();

        $this->assertNotNull($freeDay);
        $this->assertTrue($freeDay->is_available);
    }

    /** @test */
    public function it_returns_detailed_conflict_reason_code_on_rejection()
    {
        Event::fake([PropertyAvailabilityConflictDetectedEvent::class]);

        // Create a Tier 2 (Reservation) block
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-02-01',
            '2027-02-05',
            'Confirmed Booking',
            PropertyAvailabilityContract::TIER_RESERVATION
        );

        // Attempt Tier 4 (External Sync) override — should be rejected with CONFLICT_HIGHER_PRIORITY
        $result = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-02-02',
            '2027-02-04',
            'Airbnb iCal',
            PropertyAvailabilityContract::TIER_EXTERNAL_SYNC
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('CONFLICT_REJECTED', $result['status']);
        $this->assertEquals(
            PropertyAvailabilityContract::CONFLICT_HIGHER_PRIORITY,
            $result['conflict_reason']
        );

        // Event should carry origin and conflict reason code
        Event::assertDispatched(PropertyAvailabilityConflictDetectedEvent::class, function ($event) {
            return $event->conflictReasonCode === PropertyAvailabilityContract::CONFLICT_HIGHER_PRIORITY;
        });
    }

    /** @test */
    public function it_stores_origin_field_on_blocked_records()
    {
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-03-01',
            '2027-03-03',
            'Airbnb Import',
            PropertyAvailabilityContract::TIER_EXTERNAL_SYNC,
            null,
            'airbnb',
            'AIRBNB_UID_12345',
            \App\Contracts\Property\PropertyAvailabilityContract::ORIGIN_AIRBNB
        );

        $rec = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2027-03-01')
            ->first();

        $this->assertNotNull($rec);
        $this->assertFalse($rec->is_available);
        $this->assertEquals(
            \App\Contracts\Property\PropertyAvailabilityContract::ORIGIN_AIRBNB,
            $rec->origin
        );
        $this->assertEquals('airbnb', $rec->source_system);
        $this->assertNotNull($rec->projection_generated_at);
        $this->assertEquals('block', $rec->projection_source);
    }
    // -------------------------------------------------------------------------
    // Sprint 22 E01 Remediation — Focused tests
    // -------------------------------------------------------------------------

    /** @test */
    public function it_prevents_unblock_without_ownership_anchor()
    {
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-04-01',
            '2027-04-05',
            'Maintenance Work',
            PropertyAvailabilityContract::TIER_MAINTENANCE,
            'MAINT_KEY_001',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_MAINTENANCE
        );

        // Calling unblockDateRange with NO anchor must throw
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/ownership anchor/');

        $this->availabilityService->unblockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-04-01',
            '2027-04-05'
            // no idempotencyKey, no origin, no sourceSystem → must throw
        );
    }

    /** @test */
    public function it_prevents_unrelated_source_from_removing_maintenance_block()
    {
        // Place a TIER_MAINTENANCE block owned by origin=maintenance
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-05-01',
            '2027-05-04',
            'Boiler Repair',
            PropertyAvailabilityContract::TIER_MAINTENANCE,
            'MAINT_KEY_002',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_MAINTENANCE
        );

        // An unrelated caller with origin=manual tries to unblock the same dates
        $result = $this->availabilityService->unblockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-05-01',
            '2027-05-04',
            null,
            PropertyAvailabilityContract::ORIGIN_MANUAL  // wrong owner
        );

        // Zero records should be cleared — the maintenance block must survive
        $this->assertEquals(0, $result['cleared_records']);

        // Verify the maintenance block is still in DB
        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2027-05-01',
            '2027-05-04'
        );
        $this->assertFalse($check['is_available']);
    }

    /** @test */
    public function it_preserves_non_reservation_origins_on_rebuild()
    {
        // Place an owner block for Oct 10-12
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-10-10',
            '2027-10-13',
            'Owner Weekend',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            'OWNER_KEY_003',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_OWNER
        );

        // Rebuild projection for the same range
        $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id,
            $this->property->id,
            '2027-10-10',
            '2027-10-13'
        );

        // Owner block must still be present — rebuild is origin-scoped
        $ownerBlock = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2027-10-11')
            ->first();

        $this->assertNotNull($ownerBlock);
        $this->assertFalse($ownerBlock->is_available);
        $this->assertEquals(PropertyAvailabilityContract::ORIGIN_OWNER, $ownerBlock->origin);
        $this->assertEquals(PropertyAvailabilityContract::TIER_OWNER_BLOCK, $ownerBlock->priority_tier);
    }

    /** @test */
    public function it_prevents_cross_tenant_yazlik_reservation_from_entering_projection()
    {
        // Create a second tenant with its own property
        $tenant2   = Tenant::create(['name' => 'Bodrum Rival Stays', 'status' => 'active', 'is_active' => true]);
        $property2 = Ilan::create([
            'baslik'          => 'Rival Villa',
            'para_birimi'     => 'TRY',
            'fiyat'           => 30000.00,
            'yayin_durumu'    => 'aktif',
            'aktiflik_durumu' => true,
        ]);

        // Insert a yazlik reservation that belongs to property2 (tenant2's property)
        \Illuminate\Support\Facades\DB::table('yazlik_rezervasyonlar')->insert([
            'ilan_id'      => $property2->id,
            'giris_tarihi' => '2027-06-01',
            'cikis_tarihi' => '2027-06-05',
            'toplam_tutar' => 5000,
            'durum'        => 'onaylandi',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Rebuild projection for tenant1's property (same property_id as property2 won't match,
        // but more critically: even if property IDs collide, the tenant join blocks cross-tenant leakage)
        $rebuilt = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id,      // tenant1
            $this->property->id,    // tenant1's property — NOT property2
            '2027-06-01',
            '2027-06-06'
        );

        // All days for tenant1's property should be available (no yazlik reservations for this tenant+property)
        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2027-06-01',
            '2027-06-05'
        );

        $this->assertTrue($check['is_available'],
            'Cross-tenant yazlik reservation must not pollute another tenant\'s projection');
        $this->assertEmpty($check['conflicts']);
    }

    /** @test */
    public function it_does_not_create_duplicate_block_for_repeated_idempotency_key()
    {
        $key = 'IDEM_KEY_REPEAT_001';

        // First block
        $first = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-07-01',
            '2027-07-04',
            'Owner Block',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            $key
        );
        $this->assertTrue($first['success']);

        // Second call with same key and overlapping range — must be rejected as conflict
        $second = $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2027-07-01',
            '2027-07-04',
            'Owner Block',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            $key
        );

        // Same-tier overlap is rejected by the priority matrix (tier <= existing tier)
        $this->assertFalse($second['success']);
        $this->assertEquals('CONFLICT_REJECTED', $second['status']);

        // Only one set of records should exist (no duplication)
        $count = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->whereBetween('date', ['2027-07-01', '2027-07-03'])
            ->where('idempotency_key', $key)
            ->count();

        $this->assertEquals(3, $count, 'Exactly 3 daily records for the 3-night block — no duplicates');
    }
}
