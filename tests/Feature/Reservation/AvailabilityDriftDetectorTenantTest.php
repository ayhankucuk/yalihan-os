<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\AvailabilityDriftDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2 E05 — Drift Detection (Tamamlayıcı)
 *
 * DriftDetectionE05Test'te eksik kalan:
 * E05.8: detect_for_tenant_aggregates_all_properties
 * E05.6: drift_detector_is_tenant_scoped (detectForTenant variant)
 *
 * + detectForTenant() API coverage:
 *   - tenant_with_no_drift_returns_empty_reports
 *   - tenant_with_multiple_properties_some_drifted
 *   - detect_for_tenant_does_not_cross_tenant_boundary
 */
class AvailabilityDriftDetectorTenantTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityDriftDetector $detector;
    protected AvailabilityProjectionContract $projection;

    protected Tenant $tenantA;
    protected Ilan $propertyA1;
    protected Ilan $propertyA2;

    protected Tenant $tenantB;
    protected Ilan $propertyB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector  = app(AvailabilityDriftDetector::class);
        $this->projection = app(AvailabilityProjectionContract::class);

        $this->tenantA = Tenant::create([
            'name'      => 'E05 TenantA',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name'      => 'E05 TenantB',
            'status'    => 'active',
            'is_active' => true,
        ]);

        // TenantA has two properties
        $this->propertyA1 = Ilan::create([
            'baslik'          => 'E05 PropertyA1',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')
            ->where('id', $this->propertyA1->id)
            ->update(['tenant_id' => $this->tenantA->id]);

        $this->propertyA2 = Ilan::create([
            'baslik'          => 'E05 PropertyA2',
            'fiyat'           => 2000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')
            ->where('id', $this->propertyA2->id)
            ->update(['tenant_id' => $this->tenantA->id]);

        // TenantB has one property
        $this->propertyB1 = Ilan::create([
            'baslik'          => 'E05 PropertyB1',
            'fiyat'           => 3000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')
            ->where('id', $this->propertyB1->id)
            ->update(['tenant_id' => $this->tenantB->id]);
    }

    // =========================================================================
    // E05.8 — detect_for_tenant_aggregates_all_properties
    // =========================================================================

    /** @test */
    public function detect_for_tenant_aggregates_all_properties(): void
    {
        $startDate = '2032-01-01';
        $endDate   = '2032-01-08';

        // PropertyA1: confirmed reservation WITHOUT projection → drift
        PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA1->id,
            'start_date'        => '2032-01-02',
            'end_date'          => '2032-01-05',
            'nights'            => 3,
            'guest_name'        => 'A1 Drifted Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
        // No projectConfirm → MISSING_BLOCK drift

        // PropertyA2: confirmed reservation WITH correct projection → no drift
        $resA2 = PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA2->id,
            'start_date'        => '2032-01-03',
            'end_date'          => '2032-01-06',
            'nights'            => 3,
            'guest_name'        => 'A2 Healthy Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
        $this->projection->projectConfirm(
            $resA2->id,
            $this->tenantA->id,
            $this->propertyA2->id,
            '2032-01-03',
            '2032-01-06'
        );

        $result = $this->detector->detectForTenant(
            $this->tenantA->id,
            $startDate,
            $endDate
        );

        $this->assertEquals($this->tenantA->id, $result['tenant_id']);
        $this->assertEquals(2, $result['properties_checked'],
            'detectForTenant must check all TenantA properties (2)');
        $this->assertEquals(1, $result['properties_with_drift'],
            'Only PropertyA1 should have drift');
        $this->assertCount(1, $result['drift_reports']);
        $this->assertEquals($this->propertyA1->id, $result['drift_reports'][0]['property_id'],
            'The drifted property must be PropertyA1');
        $this->assertTrue($result['drift_reports'][0]['has_drift']);
    }

    // =========================================================================
    // E05.6 — drift_detector_is_tenant_scoped (detectForTenant variant)
    // =========================================================================

    /** @test */
    public function detect_for_tenant_does_not_cross_tenant_boundary(): void
    {
        $startDate = '2032-02-01';
        $endDate   = '2032-02-08';

        // TenantB has a confirmed reservation with drift
        PropertyReservation::create([
            'tenant_id'         => $this->tenantB->id,
            'property_id'       => $this->propertyB1->id,
            'start_date'        => '2032-02-02',
            'end_date'          => '2032-02-05',
            'nights'            => 3,
            'guest_name'        => 'B1 Drifted Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
        // No projectConfirm → MISSING_BLOCK drift for TenantB

        // TenantA has NO reservations in this range
        $resultA = $this->detector->detectForTenant(
            $this->tenantA->id,
            $startDate,
            $endDate
        );

        // TenantA scan must not see TenantB's drift
        $this->assertEquals(0, $resultA['properties_with_drift'],
            'TenantA detectForTenant must not see TenantB drift');
        $this->assertEmpty($resultA['drift_reports'],
            'TenantA drift_reports must be empty — cross-tenant isolation');

        // TenantB scan must see its own drift
        $resultB = $this->detector->detectForTenant(
            $this->tenantB->id,
            $startDate,
            $endDate
        );

        $this->assertEquals(1, $resultB['properties_with_drift'],
            'TenantB must detect its own drift');
        $this->assertEquals(1, $resultB['properties_checked']);
    }

    // =========================================================================
    // tenant_with_no_drift_returns_clean_report
    // =========================================================================

    /** @test */
    public function tenant_with_no_drift_returns_clean_report(): void
    {
        $startDate = '2032-03-01';
        $endDate   = '2032-03-08';

        // TenantA: both properties have correct projections
        $resA1 = PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA1->id,
            'start_date'        => '2032-03-02',
            'end_date'          => '2032-03-05',
            'nights'            => 3,
            'guest_name'        => 'A1 Clean Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
        $this->projection->projectConfirm(
            $resA1->id,
            $this->tenantA->id,
            $this->propertyA1->id,
            '2032-03-02',
            '2032-03-05'
        );

        $resA2 = PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA2->id,
            'start_date'        => '2032-03-03',
            'end_date'          => '2032-03-06',
            'nights'            => 3,
            'guest_name'        => 'A2 Clean Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);
        $this->projection->projectConfirm(
            $resA2->id,
            $this->tenantA->id,
            $this->propertyA2->id,
            '2032-03-03',
            '2032-03-06'
        );

        $result = $this->detector->detectForTenant(
            $this->tenantA->id,
            $startDate,
            $endDate
        );

        $this->assertEquals(2, $result['properties_checked']);
        $this->assertEquals(0, $result['properties_with_drift'],
            'No drift expected when all projections are correct');
        $this->assertEmpty($result['drift_reports']);
    }

    // =========================================================================
    // detect_for_tenant_is_read_only
    // =========================================================================

    /** @test */
    public function detect_for_tenant_is_read_only(): void
    {
        $startDate = '2032-04-01';
        $endDate   = '2032-04-08';

        // Create a reservation without projection (drift exists)
        PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA1->id,
            'start_date'        => '2032-04-02',
            'end_date'          => '2032-04-05',
            'nights'            => 3,
            'guest_name'        => 'ReadOnly Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        $reservationCountBefore   = PropertyReservation::count();
        $availabilityCountBefore  = PropertyAvailability::count();

        // Call detectForTenant — must not write anything
        $this->detector->detectForTenant(
            $this->tenantA->id,
            $startDate,
            $endDate
        );

        $reservationCountAfter  = PropertyReservation::count();
        $availabilityCountAfter = PropertyAvailability::count();

        $this->assertEquals($reservationCountBefore, $reservationCountAfter,
            'detectForTenant must not modify reservation records');
        $this->assertEquals($availabilityCountBefore, $availabilityCountAfter,
            'detectForTenant must not write any availability rows');
    }
}
