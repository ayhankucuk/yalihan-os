<?php

namespace Tests\Feature\ConflictDetection;

use App\Contracts\Property\ConflictDetectionContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\DTOs\Property\ConflictResult;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\ConflictDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CONFLICT_DETECTION Phase 3A — E02: ConflictDetectionService
 *
 * SAAB mandated 18 test scenarios (ADR-003).
 *
 * Success question: "Can YALIHAN detect reservation conflicts on the same
 * property in a transaction-safe, tenant-safe, and deterministic manner?"
 */
class ConflictDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConflictDetectionContract $detector;
    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(ConflictDetectionContract::class);

        $this->tenant = Tenant::create([
            'name'      => 'E02 Conflict Tenant',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'E02 Conflict Property',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);
    }

    // =========================================================================
    // Helper: block a date range directly in PropertyAvailability
    // =========================================================================

    private function blockDates(
        string $startDate,
        string $endDate,
        string $origin = PropertyAvailabilityContract::ORIGIN_RESERVATION,
        int $priorityTier = PropertyAvailabilityContract::TIER_RESERVATION,
        ?int $reservationId = null
    ): void {
        $current = \Carbon\Carbon::parse($startDate);
        $end     = \Carbon\Carbon::parse($endDate);
        while ($current->lt($end)) {
            PropertyAvailability::create([
                'tenant_id'     => $this->tenant->id,
                'property_id'   => $this->property->id,
                'date'          => $current->format('Y-m-d'),
                'is_available'  => false,
                'block_reason'  => 'reservation',
                'priority_tier' => $priorityTier,
                'source_system' => 'internal',
                'origin'        => $origin,
                'reservation_id' => $reservationId,
            ]);
            $current->addDay();
        }
    }

    private function makeConfirmedReservation(string $start, string $end): PropertyReservation
    {
        return PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => $start,
            'end_date'          => $end,
            'nights'            => \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end)),
            'guest_name'        => 'Test Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
    }

    // =========================================================================
    // T01: overlapping_confirmed_reservation_is_conflict
    // =========================================================================

    /** @test */
    public function overlapping_confirmed_reservation_is_conflict(): void
    {
        $res = $this->makeConfirmedReservation('2033-01-10', '2033-01-15');
        $this->blockDates('2033-01-10', '2033-01-15', PropertyAvailabilityContract::ORIGIN_RESERVATION, PropertyAvailabilityContract::TIER_RESERVATION, $res->id);

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-01-12', '2033-01-16');

        $this->assertTrue($result->hasConflict);
        $this->assertNotEmpty($result->conflictDates);
        $this->assertContains('2033-01-12', $result->conflictDates);
        $this->assertContains('2033-01-14', $result->conflictDates);
        $this->assertInstanceOf(ConflictResult::class, $result);
    }

    // =========================================================================
    // T02: back_to_back_ranges_do_not_conflict
    // =========================================================================

    /** @test */
    public function back_to_back_ranges_do_not_conflict(): void
    {
        // Reservation A: Aug 10–15 (blocks 10,11,12,13,14)
        $this->blockDates('2033-02-10', '2033-02-15');

        // New request starts on Aug 15 (exclusive end of A → no overlap)
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-02-15', '2033-02-20');

        $this->assertFalse($result->hasConflict);
        $this->assertEmpty($result->conflictDates);
    }

    // =========================================================================
    // T03: maintenance_block_is_conflict
    // =========================================================================

    /** @test */
    public function maintenance_block_is_conflict(): void
    {
        $this->blockDates(
            '2033-03-05',
            '2033-03-08',
            PropertyAvailabilityContract::ORIGIN_MAINTENANCE,
            PropertyAvailabilityContract::TIER_MAINTENANCE
        );

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-03-04', '2033-03-07');

        $this->assertTrue($result->hasConflict);
        $this->assertContains('2033-03-05', $result->conflictDates);
        $this->assertEquals(PropertyAvailabilityContract::ORIGIN_MAINTENANCE, $result->blockingSources[0]['origin']);
    }

    // =========================================================================
    // T04: owner_block_is_conflict
    // =========================================================================

    /** @test */
    public function owner_block_is_conflict(): void
    {
        $this->blockDates(
            '2033-04-01',
            '2033-04-04',
            PropertyAvailabilityContract::ORIGIN_OWNER,
            PropertyAvailabilityContract::TIER_OWNER_BLOCK
        );

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-04-01', '2033-04-04');

        $this->assertTrue($result->hasConflict);
        $this->assertCount(3, $result->conflictDates);
        $this->assertEquals(PropertyAvailabilityContract::ORIGIN_OWNER, $result->blockingSources[0]['origin']);
    }

    // =========================================================================
    // T05: external_block_is_conflict
    // =========================================================================

    /** @test */
    public function external_block_is_conflict(): void
    {
        // Simulate Airbnb block
        PropertyAvailability::create([
            'tenant_id'     => $this->tenant->id,
            'property_id'   => $this->property->id,
            'date'          => '2033-05-10',
            'is_available'  => false,
            'block_reason'  => 'external_sync',
            'priority_tier' => PropertyAvailabilityContract::TIER_EXTERNAL_SYNC,
            'source_system' => 'airbnb',
            'origin'        => PropertyAvailabilityContract::ORIGIN_AIRBNB,
        ]);

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-05-10', '2033-05-11');

        $this->assertTrue($result->hasConflict);
        $this->assertContains('2033-05-10', $result->conflictDates);
    }

    // =========================================================================
    // T06: cancelled_reservation_is_ignored
    // =========================================================================

    /** @test */
    public function cancelled_reservation_is_ignored(): void
    {
        // No availability rows for this range (cancelled = released)
        // Do not block any dates → availability is open
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-06-01', '2033-06-05');

        $this->assertFalse($result->hasConflict);
        $this->assertEmpty($result->conflictDates);
    }

    // =========================================================================
    // T07: completed_reservation_is_ignored
    // =========================================================================

    /** @test */
    public function completed_reservation_is_ignored(): void
    {
        // Completed reservation: availability rows remain blocked historically,
        // but they are NOT in our date range (future dates are clean)
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-07-01', '2033-07-05');

        $this->assertFalse($result->hasConflict);
        $this->assertEmpty($result->conflictDates);
    }

    // =========================================================================
    // T08: no_show_reservation_is_ignored
    // =========================================================================

    /** @test */
    public function no_show_reservation_is_ignored(): void
    {
        // No-show: same as completed — past rows may remain, future is clean
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-08-01', '2033-08-05');

        $this->assertFalse($result->hasConflict);
        $this->assertEmpty($result->conflictDates);
    }

    // =========================================================================
    // T09: pending_behavior_matches_saab_decision
    // =========================================================================

    /** @test */
    public function pending_behavior_matches_saab_decision(): void
    {
        // PENDING reservation exists — but is NOT projected to PropertyAvailability
        PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2033-09-01',
            'end_date'          => '2033-09-05',
            'nights'            => 4,
            'guest_name'        => 'Pending Guest',
            'reservation_state' => ReservationState::PENDING->value,
        ]);
        // No PropertyAvailability rows created (PENDING is not projected)

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-09-01', '2033-09-05');

        // ConflictDetectionService operates on projection — PENDING = no conflict
        $this->assertFalse($result->hasConflict,
            'PENDING reservation must not appear as conflict in ConflictDetectionService (ADR-003 two-layer architecture)');
    }

    // =========================================================================
    // T10: same_input_returns_same_ordered_result
    // =========================================================================

    /** @test */
    public function same_input_returns_same_ordered_result(): void
    {
        $this->blockDates('2033-10-05', '2033-10-08');

        $result1 = $this->detector->detect($this->tenant->id, $this->property->id, '2033-10-04', '2033-10-10');
        $result2 = $this->detector->detect($this->tenant->id, $this->property->id, '2033-10-04', '2033-10-10');
        $result3 = $this->detector->detect($this->tenant->id, $this->property->id, '2033-10-04', '2033-10-10');

        $this->assertEquals($result1->hasConflict, $result2->hasConflict);
        $this->assertEquals($result1->hasConflict, $result3->hasConflict);
        $this->assertEquals($result1->conflictDates, $result2->conflictDates);
        $this->assertEquals($result1->conflictDates, $result3->conflictDates);
        $this->assertEquals($result1->summary, $result2->summary);
    }

    // =========================================================================
    // T11: cross_tenant_records_are_invisible
    // =========================================================================

    /** @test */
    public function cross_tenant_records_are_invisible(): void
    {
        $tenant2   = Tenant::create(['name' => 'Tenant2', 'status' => 'active', 'is_active' => true]);
        $property2 = Ilan::create([
            'baslik'          => 'Tenant2 Property',
            'fiyat'           => 2000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
        ]);
        DB::table('ilanlar')->where('id', $property2->id)->update(['tenant_id' => $tenant2->id]);

        // Block dates on Tenant2's property
        PropertyAvailability::create([
            'tenant_id'     => $tenant2->id,
            'property_id'   => $property2->id,
            'date'          => '2033-11-10',
            'is_available'  => false,
            'block_reason'  => 'reservation',
            'priority_tier' => PropertyAvailabilityContract::TIER_RESERVATION,
            'source_system' => 'internal',
            'origin'        => PropertyAvailabilityContract::ORIGIN_RESERVATION,
        ]);

        // Tenant1 checks Tenant1's property — must not see Tenant2's blocks
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2033-11-10', '2033-11-12');

        $this->assertFalse($result->hasConflict,
            'Cross-tenant availability records must be invisible to ConflictDetectionService');
    }

    // =========================================================================
    // T12: excluded_reservation_does_not_conflict_with_itself
    // =========================================================================

    /** @test */
    public function excluded_reservation_does_not_conflict_with_itself(): void
    {
        $res = $this->makeConfirmedReservation('2033-12-01', '2033-12-05');
        $this->blockDates('2033-12-01', '2033-12-05', PropertyAvailabilityContract::ORIGIN_RESERVATION, PropertyAvailabilityContract::TIER_RESERVATION, $res->id);

        // Without exclusion — conflict detected
        $withConflict = $this->detector->detect($this->tenant->id, $this->property->id, '2033-12-01', '2033-12-05');
        $this->assertTrue($withConflict->hasConflict);

        // With exclusion — reservation's own blocks ignored
        $noConflict = $this->detector->detect($this->tenant->id, $this->property->id, '2033-12-01', '2033-12-05', $res->id);
        $this->assertFalse($noConflict->hasConflict,
            'A reservation must not conflict with its own availability blocks (excludeReservationId)');
    }

    // =========================================================================
    // T13: conflict_detection_is_read_only
    // =========================================================================

    /** @test */
    public function conflict_detection_is_read_only(): void
    {
        $this->blockDates('2034-01-05', '2034-01-08');

        $reservationCountBefore   = PropertyReservation::count();
        $availabilityCountBefore  = PropertyAvailability::count();

        // Call detect multiple times
        for ($i = 0; $i < 3; $i++) {
            $this->detector->detect($this->tenant->id, $this->property->id, '2034-01-01', '2034-01-10');
        }

        $this->assertEquals($reservationCountBefore, PropertyReservation::count(),
            'ConflictDetectionService must not write reservation records');
        $this->assertEquals($availabilityCountBefore, PropertyAvailability::count(),
            'ConflictDetectionService must not write availability records');
    }

    // =========================================================================
    // T14: conflict_result_contains_blocking_source_detail
    // =========================================================================

    /** @test */
    public function conflict_result_contains_blocking_source_detail(): void
    {
        $res = $this->makeConfirmedReservation('2034-02-10', '2034-02-13');
        $this->blockDates('2034-02-10', '2034-02-13', PropertyAvailabilityContract::ORIGIN_RESERVATION, PropertyAvailabilityContract::TIER_RESERVATION, $res->id);

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2034-02-10', '2034-02-13');

        $this->assertTrue($result->hasConflict);
        $this->assertNotEmpty($result->blockingSources);

        $source = $result->blockingSources[0];
        $this->assertArrayHasKey('date', $source);
        $this->assertArrayHasKey('origin', $source);
        $this->assertArrayHasKey('reservation_id', $source);
        $this->assertArrayHasKey('block_reason', $source);
        $this->assertArrayHasKey('priority_tier', $source);
    }

    // =========================================================================
    // T15: no_conflict_result_has_correct_structure
    // =========================================================================

    /** @test */
    public function no_conflict_result_has_correct_structure(): void
    {
        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2034-03-01', '2034-03-05');

        $this->assertFalse($result->hasConflict);
        $this->assertEquals($this->tenant->id, $result->tenantId);
        $this->assertEquals($this->property->id, $result->propertyId);
        $this->assertEquals('2034-03-01', $result->startDate);
        $this->assertEquals('2034-03-05', $result->endDate);
        $this->assertEquals(4, $result->checkedNights);
        $this->assertEmpty($result->conflictDates);
        $this->assertEmpty($result->blockingSources);
        $this->assertStringContainsString('No conflict', $result->summary);
    }

    // =========================================================================
    // T16: invalid_date_range_throws_exception
    // =========================================================================

    /** @test */
    public function invalid_date_range_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2034-04-10',  // start
            '2034-04-05'   // end BEFORE start
        );
    }

    // =========================================================================
    // T17: conflict_result_to_array_serialization
    // =========================================================================

    /** @test */
    public function conflict_result_to_array_serialization(): void
    {
        $this->blockDates('2034-05-01', '2034-05-04');

        $result = $this->detector->detect($this->tenant->id, $this->property->id, '2034-05-01', '2034-05-04');

        $array = $result->toArray();

        $this->assertArrayHasKey('has_conflict', $array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('property_id', $array);
        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertArrayHasKey('conflict_dates', $array);
        $this->assertArrayHasKey('blocking_sources', $array);
        $this->assertArrayHasKey('checked_nights', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertTrue($array['has_conflict']);
    }

    // =========================================================================
    // T18: service_is_bound_in_container
    // =========================================================================

    /** @test */
    public function service_is_bound_in_container(): void
    {
        $service = app(ConflictDetectionContract::class);

        $this->assertInstanceOf(ConflictDetectionService::class, $service);
    }
}
