<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\AvailabilityDriftDetector;
use App\Services\Property\CanonicalAvailabilityService;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2 — Availability Projection Hardening
 *
 * Success criteria (SAAB):
 * "Reservation event'lerinden PropertyAvailability deterministik,
 *  idempotent, replay-safe ve tenant-safe şekilde üretilebiliyor mu?"
 *
 * Test matrix:
 * P2.1  confirm_idempotency_returns_same_state_on_second_call
 * P2.2  confirm_idempotency_does_not_create_duplicate_availability_rows
 * P2.3  rebuild_is_replay_safe_identical_result_on_second_call
 * P2.4  rebuild_only_projects_confirmed_reservations_not_pending
 * P2.5  rebuild_excludes_terminal_state_reservations
 * P2.6  check_availability_excludes_terminal_state_reservations
 * P2.7  tenant_isolation_confirmed_reservation_not_visible_to_other_tenant
 * P2.8  drift_detector_reports_missing_block
 * P2.9  drift_detector_reports_phantom_block
 * P2.10 drift_detector_reports_no_drift_when_clean
 * P2.11 drift_detector_tenant_scope_all_properties
 * P2.12 rebuild_preserves_owner_block_across_confirmed_reservation_rebuild
 */
class ReservationCorePhase2Test extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Ilan $property;
    protected ReservationService $reservationService;
    protected CanonicalAvailabilityService $availabilityService;
    protected AvailabilityDriftDetector $driftDetector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'      => 'Bodrum Luxury Phase2',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'            => 'Phase2 Test Villa',
            'para_birimi'       => 'TRY',
            'fiyat'             => 50000.00,
            'yayin_durumu'      => 'aktif',
            'aktiflik_durumu'   => true,
            'tenant_id'         => $this->tenant->id,
            'rental_enabled'    => true,
            'min_stay_nights'   => 1,
        ]);

        $this->reservationService  = app(ReservationService::class);
        $this->availabilityService = app(CanonicalAvailabilityService::class);
        $this->driftDetector       = app(AvailabilityDriftDetector::class);
    }

    // =========================================================================
    // P2.2 — IDEMPOTENCY
    // =========================================================================

    /** @test */
    public function confirm_idempotency_returns_same_state_on_second_call(): void
    {
        $reservation = $this->reservationService->createReservation(
            $this->property->id,
            '2027-09-01',
            '2027-09-05',
            ['guest_name' => 'Ali Bey'],
            null,
            $this->tenant->id
        );

        $first  = $this->reservationService->confirmReservation($reservation->id, $this->tenant->id);
        $second = $this->reservationService->confirmReservation($reservation->id, $this->tenant->id);

        $this->assertEquals(ReservationState::CONFIRMED, $first->reservation_state);
        $this->assertEquals(ReservationState::CONFIRMED, $second->reservation_state);
        $this->assertEquals($first->id, $second->id);
    }

    /** @test */
    public function confirm_idempotency_does_not_create_duplicate_availability_rows(): void
    {
        $reservation = $this->reservationService->createReservation(
            $this->property->id,
            '2027-09-10',
            '2027-09-13',
            ['guest_name' => 'Mehmet Bey'],
            null,
            $this->tenant->id
        );

        // Confirm twice — no exception, no duplicate rows
        $this->reservationService->confirmReservation($reservation->id, $this->tenant->id);
        $this->reservationService->confirmReservation($reservation->id, $this->tenant->id);

        // Exactly 3 blocked rows (sep 10, 11, 12) — not 6
        $count = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->whereBetween('date', ['2027-09-10', '2027-09-12'])
            ->where('is_available', false)
            ->count();

        $this->assertEquals(3, $count, 'Second confirm must not create duplicate availability rows');
    }

    // =========================================================================
    // P2.3 — REPLAY SAFETY
    // =========================================================================

    /** @test */
    public function rebuild_is_replay_safe_identical_result_on_second_call(): void
    {
        PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2027-10-01',
            'end_date'          => '2027-10-05',
            'nights'            => 4,
            'guest_name'        => 'Zeynep Hanım',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        $count1 = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id, $this->property->id, '2027-10-01', '2027-10-06'
        );

        $count2 = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id, $this->property->id, '2027-10-01', '2027-10-06'
        );

        $this->assertEquals($count1, $count2, 'Rebuild is idempotent — same row count on second call');

        // DB state must be consistent
        $blockedRows = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('is_available', false)
            ->whereBetween('date', ['2027-10-01', '2027-10-04'])
            ->count();

        $this->assertEquals(4, $blockedRows, 'Exactly 4 blocked rows after replay — no duplication');
    }

    /** @test */
    public function rebuild_only_projects_confirmed_reservations_not_pending(): void
    {
        // Create a PENDING reservation — should NOT block availability in rebuild
        $this->reservationService->createReservation(
            $this->property->id,
            '2027-11-01',
            '2027-11-04',
            ['guest_name' => 'Kemal Bey'],
            null,
            $this->tenant->id
        );

        $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id, $this->property->id, '2027-11-01', '2027-11-05'
        );

        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id, $this->property->id, '2027-11-01', '2027-11-04'
        );

        // PENDING does not block — dates must be available after rebuild
        $this->assertTrue(
            $check['is_available'],
            'PENDING reservation must NOT block availability in projection rebuild'
        );
    }

    /** @test */
    public function rebuild_excludes_terminal_state_reservations(): void
    {
        // COMPLETED reservation — dates historically blocked but should not
        // appear as "active conflict" in checkAvailability
        $completed = PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2027-12-01',
            'end_date'          => '2027-12-04',
            'nights'            => 3,
            'guest_name'        => 'Fatma Hanım',
            'reservation_state' => ReservationState::COMPLETED->value,
        ]);

        $count = $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id, $this->property->id, '2027-12-01', '2027-12-05'
        );

        $this->assertEquals(4, $count);

        // COMPLETED reservations do NOT block future availability projection
        $blockedRows = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('is_available', false)
            ->whereBetween('date', ['2027-12-01', '2027-12-03'])
            ->count();

        $this->assertEquals(0, $blockedRows, 'COMPLETED reservation must not create new blocks in rebuild');
    }

    // =========================================================================
    // P2.6 — checkAvailability terminal state filter
    // =========================================================================

    /** @test */
    public function check_availability_excludes_terminal_state_reservations(): void
    {
        // NO_SHOW reservation — should not appear as conflict in checkAvailability
        PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2028-01-10',
            'end_date'          => '2028-01-14',
            'nights'            => 4,
            'guest_name'        => 'İbrahim Bey',
            'reservation_state' => ReservationState::NO_SHOW->value,
            'cancelled_at'      => null,
        ]);

        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id, $this->property->id, '2028-01-10', '2028-01-14'
        );

        // NO_SHOW is terminal — should not conflict with new reservation
        $this->assertTrue($check['is_available'],
            'NO_SHOW (terminal) reservation must not block checkAvailability result');
        $this->assertEmpty($check['conflicts']);
    }

    // =========================================================================
    // P2.7 — TENANT ISOLATION
    // =========================================================================

    /** @test */
    public function tenant_isolation_confirmed_reservation_not_visible_to_other_tenant(): void
    {
        $tenant2   = Tenant::create(['name' => 'Rival Co', 'status' => 'active', 'is_active' => true]);
        $property2 = Ilan::create([
            'baslik'          => 'Rival Property',
            'para_birimi'     => 'TRY',
            'fiyat'           => 20000.00,
            'yayin_durumu'    => 'aktif',
            'aktiflik_durumu' => true,
            'tenant_id'       => $tenant2->id,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        // Confirmed reservation under tenant2's property
        PropertyReservation::create([
            'tenant_id'         => $tenant2->id,
            'property_id'       => $property2->id,
            'start_date'        => '2028-02-01',
            'end_date'          => '2028-02-05',
            'nights'            => 4,
            'guest_name'        => 'Tenant2 Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // checkAvailability for tenant1's property must not see tenant2's reservation
        $check = $this->availabilityService->checkAvailability(
            $this->tenant->id,
            $this->property->id,
            '2028-02-01',
            '2028-02-05'
        );

        $this->assertTrue($check['is_available'],
            'Tenant1 property must not be affected by Tenant2 confirmed reservation');
        $this->assertEmpty($check['conflicts']);
    }

    // =========================================================================
    // P2.8-P2.11 — DRIFT DETECTION
    // =========================================================================

    /** @test */
    public function drift_detector_reports_missing_block(): void
    {
        // CONFIRMED reservation exists but NO availability row
        $reservation = PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2028-03-01',
            'end_date'          => '2028-03-04',
            'nights'            => 3,
            'guest_name'        => 'Drift Test Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        $report = $this->driftDetector->detect(
            $this->tenant->id, $this->property->id, '2028-03-01', '2028-03-05'
        );

        $this->assertTrue($report['has_drift']);
        $this->assertNotEmpty($report['missing_blocks']);
        $this->assertEquals('MISSING_BLOCK', $report['missing_blocks'][0]['drift_type']);
        $this->assertEquals(3, count($report['missing_blocks']));
        $this->assertEmpty($report['phantom_blocks']);
    }

    /** @test */
    public function drift_detector_reports_phantom_block(): void
    {
        // Availability row blocked with ORIGIN_RESERVATION but NO matching CONFIRMED reservation
        $now = now();
        PropertyAvailability::insert([
            [
                'tenant_id'               => $this->tenant->id,
                'property_id'             => $this->property->id,
                'date'                    => '2028-04-05',
                'is_available'            => false,
                'block_reason'            => 'reservation',
                'priority_tier'           => PropertyAvailabilityContract::TIER_RESERVATION,
                'source_system'           => 'internal',
                'origin'                  => PropertyAvailabilityContract::ORIGIN_RESERVATION,
                'reservation_id'          => 99999, // Non-existent reservation
                'projection_generated_at' => $now,
                'projection_source'       => PropertyAvailabilityContract::PROJECTION_SOURCE_RESERVATION,
                'created_at'              => $now,
                'updated_at'              => $now,
            ],
        ]);

        $report = $this->driftDetector->detect(
            $this->tenant->id, $this->property->id, '2028-04-05', '2028-04-06'
        );

        $this->assertTrue($report['has_drift']);
        $this->assertNotEmpty($report['phantom_blocks']);
        $this->assertEquals('PHANTOM_BLOCK', $report['phantom_blocks'][0]['drift_type']);
        $this->assertEmpty($report['missing_blocks']);
    }

    /** @test */
    public function drift_detector_reports_no_drift_when_clean(): void
    {
        $reservation = $this->reservationService->createReservation(
            $this->property->id,
            '2028-05-01',
            '2028-05-04',
            ['guest_name' => 'Clean Guest'],
            null,
            $this->tenant->id
        );
        $this->reservationService->confirmReservation($reservation->id, $this->tenant->id);

        $report = $this->driftDetector->detect(
            $this->tenant->id, $this->property->id, '2028-05-01', '2028-05-04'
        );

        $this->assertFalse($report['has_drift']);
        $this->assertEmpty($report['missing_blocks']);
        $this->assertEmpty($report['phantom_blocks']);
        $this->assertStringContainsString('No drift detected', $report['summary']);
    }

    /** @test */
    public function drift_detector_tenant_scope_all_properties(): void
    {
        // Force-write tenant_id via raw DB to bypass any BelongsToTenant/TenantScope behaviour
        // that may suppress tenant_id in test environment.
        \Illuminate\Support\Facades\DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);

        // Drift on main property: confirmed reservation, no availability row
        PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2028-06-01',
            'end_date'          => '2028-06-03',
            'nights'            => 2,
            'guest_name'        => 'Tenant Drift Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // Verify detect() can find the drift on this property individually
        $singleReport = $this->driftDetector->detect(
            $this->tenant->id, $this->property->id, '2028-06-01', '2028-06-04'
        );
        $this->assertTrue($singleReport['has_drift'], 'detect() must find the drift on this property');

        // detectForTenant uses DB::table('ilanlar')->where('tenant_id') to enumerate properties.
        // Verify at least $this->property is picked up and the drift is surfaced.
        $tenantReport = $this->driftDetector->detectForTenant(
            $this->tenant->id, '2028-06-01', '2028-06-04'
        );

        $this->assertGreaterThanOrEqual(1, $tenantReport['properties_checked'],
            'detectForTenant must check at least 1 property');
        $this->assertGreaterThanOrEqual(1, $tenantReport['properties_with_drift'],
            'detectForTenant must report at least 1 property with drift');
        $this->assertNotEmpty($tenantReport['drift_reports']);
    }

    // =========================================================================
    // P2.12 — Owner block preserved across rebuild
    // =========================================================================

    /** @test */
    public function rebuild_preserves_owner_block_across_confirmed_reservation_rebuild(): void
    {
        // Place an owner block
        $this->availabilityService->blockDateRange(
            $this->tenant->id,
            $this->property->id,
            '2028-07-10',
            '2028-07-13',
            'Owner Weekend',
            PropertyAvailabilityContract::TIER_OWNER_BLOCK,
            'OWNER_P2_KEY_001',
            'internal',
            null,
            PropertyAvailabilityContract::ORIGIN_OWNER
        );

        // Rebuild for the same range
        $this->availabilityService->rebuildAvailabilityProjection(
            $this->tenant->id, $this->property->id, '2028-07-10', '2028-07-13'
        );

        // Owner block must survive — rebuild is origin-scoped
        $ownerBlock = PropertyAvailability::where('tenant_id', $this->tenant->id)
            ->where('property_id', $this->property->id)
            ->where('date', '2028-07-11')
            ->where('origin', PropertyAvailabilityContract::ORIGIN_OWNER)
            ->first();

        $this->assertNotNull($ownerBlock, 'Owner block must survive origin-scoped rebuild');
        $this->assertFalse($ownerBlock->is_available);
        $this->assertEquals(PropertyAvailabilityContract::TIER_OWNER_BLOCK, $ownerBlock->priority_tier);
    }
}
