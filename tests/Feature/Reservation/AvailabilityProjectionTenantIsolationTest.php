<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationConfirmedEvent;
use App\Listeners\Reservation\ProjectConfirmedReservationListener;
use App\Listeners\Reservation\ReleaseCancelledReservationListener;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\Property\CanonicalAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2: E04 — Tenant Isolation Hardening
 *
 * Başarı sorusu (SAAB):
 * "Her reservation, event, listener, rebuild ve release işlemi yalnızca
 *  kendi tenant'ının PropertyAvailability kayıtlarını etkiliyor mu?"
 *
 * Zorunlu invariant:
 *   reservation.tenant_id = property.tenant_id = availability.tenant_id
 *
 * Test matrix:
 * E04.1 tenant_a_cannot_project_into_tenant_b_property
 * E04.2 tenant_a_cancel_cannot_release_tenant_b_availability
 * E04.3 listener_preserves_tenant_context
 * E04.4 rebuild_processes_only_requested_tenant
 * E04.5 reservation_property_tenant_mismatch_is_rejected
 * E04.6 cross_tenant_projection_attempt_leaves_no_side_effect
 * E04.7 cross_tenant_attempt_raises_detectable_signal
 */
class AvailabilityProjectionTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityProjectionContract $projection;
    protected CanonicalAvailabilityService $canonicalService;

    // Tenant A — the attacker / requesting tenant
    protected Tenant $tenantA;
    protected Ilan $propertyA;

    // Tenant B — the victim tenant
    protected Tenant $tenantB;
    protected Ilan $propertyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projection       = app(AvailabilityProjectionContract::class);
        $this->canonicalService = app(CanonicalAvailabilityService::class);

        $this->tenantA = Tenant::create([
            'name'      => 'E04 Tenant A',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name'      => 'E04 Tenant B',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->propertyA = Ilan::create([
            'baslik'          => 'E04 Property A',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id'       => $this->tenantA->id,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')
            ->where('id', $this->propertyA->id)
            ->update(['tenant_id' => $this->tenantA->id]);

        $this->propertyB = Ilan::create([
            'baslik'          => 'E04 Property B',
            'fiyat'           => 2000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id'       => $this->tenantB->id,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
        DB::table('ilanlar')
            ->where('id', $this->propertyB->id)
            ->update(['tenant_id' => $this->tenantB->id]);
    }

    // =========================================================================
    // E04.1 — tenant_a_cannot_project_into_tenant_b_property
    // =========================================================================

    /** @test */
    public function tenant_a_cannot_project_into_tenant_b_property(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Cross-tenant violation/');

        // TenantA attempts to write a projection onto TenantB's property
        $this->projection->projectConfirm(
            reservationId: 999,
            tenantId:      $this->tenantA->id,
            propertyId:    $this->propertyB->id,
            startDate:     '2031-01-10',
            endDate:       '2031-01-14'
        );
    }

    // =========================================================================
    // E04.2 — tenant_a_cancel_cannot_release_tenant_b_availability
    // =========================================================================

    /** @test */
    public function tenant_a_cancel_cannot_release_tenant_b_availability(): void
    {
        $propertyBId = $this->propertyB->id;
        $tenantBId   = $this->tenantB->id;
        $startDate   = '2031-02-01';
        $endDate     = '2031-02-05';

        // TenantB's confirmed reservation — properly projected
        $reservationB = PropertyReservation::create([
            'tenant_id'         => $tenantBId,
            'property_id'       => $propertyBId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 4,
            'guest_name'        => 'TenantB Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        $this->projection->projectConfirm(
            reservationId: $reservationB->id,
            tenantId:      $tenantBId,
            propertyId:    $propertyBId,
            startDate:     $startDate,
            endDate:       $endDate
        );

        $blockedBefore = PropertyAvailability::where('property_id', $propertyBId)
            ->where('is_available', false)
            ->whereBetween('date', [$startDate, '2031-02-04'])
            ->count();
        $this->assertEquals(4, $blockedBefore, 'Pre-condition: TenantB has 4 blocked rows');

        // TenantA attempts to cancel TenantB's reservation ID using TenantA's tenant_id
        // projectCancel scopes to tenant_id — must touch zero rows of TenantB
        $result = $this->projection->projectCancel(
            reservationId: $reservationB->id,
            tenantId:      $this->tenantA->id,  // WRONG tenant
            startDate:     $startDate,
            endDate:       $endDate
        );

        // TenantB's blocks must be completely untouched
        $blockedAfter = PropertyAvailability::where('property_id', $propertyBId)
            ->where('is_available', false)
            ->whereBetween('date', [$startDate, '2031-02-04'])
            ->count();

        $this->assertEquals(4, $blockedAfter,
            'TenantA cancel must not release TenantB availability — tenant_id scope must isolate');
        $this->assertEquals(0, $result['freed_days'],
            'freed_days must be 0 — no rows belong to TenantA in this date range');
    }

    // =========================================================================
    // E04.3 — listener_preserves_tenant_context
    // =========================================================================

    /** @test */
    public function listener_preserves_tenant_context(): void
    {
        $propertyAId = $this->propertyA->id;
        $tenantAId   = $this->tenantA->id;
        $startDate   = '2031-03-10';
        $endDate     = '2031-03-13';

        $reservationA = PropertyReservation::create([
            'tenant_id'         => $tenantAId,
            'property_id'       => $propertyAId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 3,
            'guest_name'        => 'Listener Guest A',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // Fire via listener — tenantId must come from the event, not be tampered
        $event = new ReservationConfirmedEvent(
            reservationId: $reservationA->id,
            tenantId:      $tenantAId,
            propertyId:    $propertyAId,
            startDate:     $startDate,
            endDate:       $endDate,
            nights:        3,
            guestName:     'Listener Guest A',
        );

        $listener = app(ProjectConfirmedReservationListener::class);
        $listener->handle($event);

        // Only TenantA's property must have blocks
        $tenantABlocks = PropertyAvailability::where('property_id', $propertyAId)
            ->where('tenant_id', $tenantAId)
            ->where('is_available', false)
            ->count();

        $tenantBBlocks = PropertyAvailability::where('property_id', $this->propertyB->id)
            ->where('is_available', false)
            ->count();

        $this->assertEquals(3, $tenantABlocks,
            'Listener must project exactly 3 blocks for TenantA');
        $this->assertEquals(0, $tenantBBlocks,
            'TenantB property must have zero blocks after TenantA listener fires');

        // Verify tenant_id on each availability row
        $rows = PropertyAvailability::where('property_id', $propertyAId)
            ->where('is_available', false)
            ->get();

        foreach ($rows as $row) {
            $this->assertEquals($tenantAId, (int) $row->tenant_id,
                "Every availability row must carry TenantA's tenant_id");
        }
    }

    // =========================================================================
    // E04.4 — rebuild_processes_only_requested_tenant
    // =========================================================================

    /** @test */
    public function rebuild_processes_only_requested_tenant(): void
    {
        $startDate = '2031-04-01';
        $endDate   = '2031-04-06';

        // Both tenants have confirmed reservations on their own properties
        PropertyReservation::create([
            'tenant_id'         => $this->tenantA->id,
            'property_id'       => $this->propertyA->id,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 5,
            'guest_name'        => 'TenantA Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        PropertyReservation::create([
            'tenant_id'         => $this->tenantB->id,
            'property_id'       => $this->propertyB->id,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 5,
            'guest_name'        => 'TenantB Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
        ]);

        // Rebuild ONLY for TenantA's property
        $this->canonicalService->rebuildAvailabilityProjection(
            $this->tenantA->id,
            $this->propertyA->id,
            $startDate,
            $endDate
        );

        // TenantA must have blocks
        $tenantABlocks = PropertyAvailability::where('property_id', $this->propertyA->id)
            ->where('tenant_id', $this->tenantA->id)
            ->where('is_available', false)
            ->count();

        // TenantB must have NO blocks (rebuild was not for TenantB)
        $tenantBBlocks = PropertyAvailability::where('property_id', $this->propertyB->id)
            ->where('is_available', false)
            ->count();

        $this->assertEquals(5, $tenantABlocks,
            'TenantA rebuild must produce 5 blocks for its own property');
        $this->assertEquals(0, $tenantBBlocks,
            'TenantB rebuild was not requested — must have zero blocks');
    }

    // =========================================================================
    // E04.5 — reservation_property_tenant_mismatch_is_rejected
    // =========================================================================

    /** @test */
    public function reservation_property_tenant_mismatch_is_rejected(): void
    {
        // reservation belongs to tenantA but we pass tenantB's property
        // validateTenantPropertyMatch must reject this

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Cross-tenant violation/');

        // TenantA reservation, TenantB property — mismatch
        $this->projection->projectConfirm(
            reservationId: 888,
            tenantId:      $this->tenantA->id,
            propertyId:    $this->propertyB->id, // belongs to TenantB
            startDate:     '2031-05-01',
            endDate:       '2031-05-04'
        );
    }

    // =========================================================================
    // E04.6 — cross_tenant_projection_attempt_leaves_no_side_effect
    // =========================================================================

    /** @test */
    public function cross_tenant_projection_attempt_leaves_no_side_effect(): void
    {
        $startDate = '2031-06-01';
        $endDate   = '2031-06-05';

        // Capture PropertyAvailability state before the attempt
        $countBefore = PropertyAvailability::where('property_id', $this->propertyB->id)->count();

        // TenantA attempts to project into TenantB's property — must be rejected
        try {
            $this->projection->projectConfirm(
                reservationId: 777,
                tenantId:      $this->tenantA->id,
                propertyId:    $this->propertyB->id,
                startDate:     $startDate,
                endDate:       $endDate
            );
            $this->fail('Expected Cross-tenant violation exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Cross-tenant violation', $e->getMessage());
        }

        // Absolutely no rows must have been written for TenantB's property
        $countAfter = PropertyAvailability::where('property_id', $this->propertyB->id)->count();

        $this->assertEquals($countBefore, $countAfter,
            'Cross-tenant attempt must leave zero side effects — no rows written or deleted');

        // No TenantA rows must exist on TenantB's property
        $tenantARowsOnB = PropertyAvailability::where('property_id', $this->propertyB->id)
            ->where('tenant_id', $this->tenantA->id)
            ->count();

        $this->assertEquals(0, $tenantARowsOnB,
            'TenantA must have zero availability rows on TenantB property');
    }

    // =========================================================================
    // E04.7 — cross_tenant_attempt_raises_detectable_signal
    // =========================================================================

    /** @test */
    public function cross_tenant_attempt_raises_detectable_signal(): void
    {
        // The system must raise a detectable signal (exception) on any cross-tenant attempt.
        // This makes auditing/monitoring possible: any uncaught exception from projectConfirm
        // with a "Cross-tenant violation" message is the observable signal.

        $signals = [];

        // Attempt 1: projectConfirm cross-tenant
        try {
            $this->projection->projectConfirm(
                reservationId: 666,
                tenantId:      $this->tenantA->id,
                propertyId:    $this->propertyB->id,
                startDate:     '2031-07-01',
                endDate:       '2031-07-03'
            );
        } catch (\Exception $e) {
            $signals[] = $e->getMessage();
        }

        // Attempt 2: validateTenantPropertyMatch direct call
        try {
            $this->projection->validateTenantPropertyMatch(
                $this->tenantA->id,
                $this->propertyB->id
            );
        } catch (\Exception $e) {
            $signals[] = $e->getMessage();
        }

        // Both attempts must have raised a signal
        $this->assertCount(2, $signals,
            'Both cross-tenant attempts must raise a detectable exception signal');

        foreach ($signals as $signal) {
            $this->assertStringContainsString('Cross-tenant violation', $signal,
                "Each signal must carry 'Cross-tenant violation' message for auditability");
        }

        // isCrossTenantAccess helper must correctly identify the mismatch
        $this->assertTrue(
            $this->projection->isCrossTenantAccess($this->tenantA->id, $this->tenantB->id),
            'isCrossTenantAccess must return true for different tenant IDs'
        );

        $this->assertFalse(
            $this->projection->isCrossTenantAccess($this->tenantA->id, $this->tenantA->id),
            'isCrossTenantAccess must return false for same tenant ID'
        );
    }
}
