<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Availability Projection Foundation Test
 *
 * RESERVATION_CORE Phase 2: E01 — Availability Projection Foundation
 *
 * Mimari Kural:
 * Reservation → Event → Projection Service → PropertyAvailability
 * ASLA: Reservation → PropertyAvailability::save()
 *
 * Test Coverage:
 * 1. creates_availability_block_once
 * 2. confirm_twice_is_idempotent
 * 3. tenant_cannot_touch_other_projection
 * 4. projection_identity_is_deterministic
 */
class AvailabilityProjectionFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityProjectionContract $projection;
    protected ReservationService $reservationService;
    protected Tenant $tenant;
    protected Tenant $otherTenant;
    protected Ilan $property;
    protected Ilan $otherProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projection = app(AvailabilityProjectionContract::class);
        $this->reservationService = app(ReservationService::class);

        // Create tenant and capture ID immediately
        $this->tenant = Tenant::create([
            'name' => 'Landlord A',
            'status' => 'active',
        ]);
        $tenantIdA = $this->tenant->id;

        $this->otherTenant = Tenant::create([
            'name' => 'Landlord B',
            'status' => 'active',
        ]);
        $tenantIdB = $this->otherTenant->id;

        $this->property = Ilan::create([
            'baslik' => 'Villa Alpha',
            'fiyat' => 1000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $tenantIdA,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        $this->otherProperty = Ilan::create([
            'baslik' => 'Villa Beta',
            'fiyat' => 2000,
            'para_birimi' => 'TRY',
            'yayin_durumu' => 'yayinda',
            'tenant_id' => $tenantIdB,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);
    }

    /** @test */
    public function creates_availability_block_once(): void
    {
        // Resolve tenant_id from the property (canonical source of truth)
        $ilan = Ilan::find($this->property->id);
        $tenantId = (int) $ilan->tenant_id;
        $propertyId = $ilan->id;

        $startDate = now()->addDays(5)->format('Y-m-d');
        $endDate = now()->addDays(8)->format('Y-m-d'); // 3 nights

        // Create reservation via service (uses tenant from property)
        $reservation = $this->reservationService->createReservation(
            $propertyId,
            $startDate,
            $endDate,
            ['guest_name' => 'Test Guest'],
            null,
            null // Let service resolve tenant_id from property
        );

        // Confirm via projection service (canonical path)
        // Tenant ID from property's tenant_id
        $result = $this->projection->projectConfirm(
            $reservation->id,
            $tenantId,
            $propertyId,
            $startDate,
            $endDate
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['blocked_days']);

        // Verify exactly 3 availability records created
        $blocks = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->get();

        $this->assertCount(3, $blocks);

        // All should be unavailable
        foreach ($blocks as $block) {
            $this->assertFalse((bool) $block->is_available);
            $this->assertEquals('reservation', $block->block_reason);
        }
    }

    /** @test */
    public function confirm_twice_is_idempotent(): void
    {
        $ilan = Ilan::find($this->property->id);
        $tenantId = (int) $ilan->tenant_id;
        $propertyId = $ilan->id;

        $startDate = now()->addDays(10)->format('Y-m-d');
        $endDate = now()->addDays(12)->format('Y-m-d'); // 2 nights

        // Create and confirm once
        $reservation = $this->reservationService->createReservation(
            $propertyId,
            $startDate,
            $endDate,
            ['guest_name' => 'Test Guest'],
            null,
            null // Let service resolve tenant_id
        );

        $firstResult = $this->projection->projectConfirm(
            $reservation->id,
            $tenantId,
            $propertyId,
            $startDate,
            $endDate
        );

        $this->assertTrue($firstResult['success']);

        // Confirm AGAIN — should be idempotent
        $secondResult = $this->projection->projectConfirm(
            $reservation->id,
            $tenantId,
            $propertyId,
            $startDate,
            $endDate
        );

        $this->assertTrue($secondResult['success']);
        $this->assertTrue($secondResult['idempotent']);

        // Still exactly 2 records (no duplicates)
        $blocks = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->count();

        $this->assertEquals(2, $blocks);
    }

    /** @test */
    public function tenant_cannot_touch_other_projection(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cross-tenant violation');

        // Tenant A attempts to project on Tenant B's property
        $this->projection->projectConfirm(
            999, // Fake reservation ID
            $this->tenant->id,
            $this->otherProperty->id,
            now()->addDays(5)->format('Y-m-d'),
            now()->addDays(7)->format('Y-m-d')
        );
    }

    /** @test */
    public function projection_identity_is_deterministic(): void
    {
        $reservationId = 42;
        $date = '2026-09-15';

        // Same inputs = same key
        $key1 = $this->projection->getProjectionKey($reservationId, $date);
        $key2 = $this->projection->getProjectionKey($reservationId, $date);

        $this->assertEquals($key1, $key2);
        $this->assertEquals("reservation:42:2026-09-15", $key1);

        // Different inputs = different key
        $key3 = $this->projection->getProjectionKey($reservationId, '2026-09-16');
        $this->assertNotEquals($key1, $key3);

        $key4 = $this->projection->getProjectionKey(43, $date);
        $this->assertNotEquals($key1, $key4);
    }

    /** @test */
    public function projection_service_is_sole_write_path(): void
    {
        // Resolve from property's tenant
        $ilan = Ilan::find($this->property->id);
        $tenantId = (int) $ilan->tenant_id;
        $propertyId = $ilan->id;

        $startDate = now()->addDays(15)->format('Y-m-d');
        $endDate = now()->addDays(17)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $propertyId,
            $startDate,
            $endDate,
            ['guest_name' => 'Service Path Test'],
            null,
            null // Let service resolve tenant_id
        );

        // Projection should be the ONLY authorized path
        $result = $this->projection->projectConfirm(
            $reservation->id,
            $tenantId,
            $propertyId,
            $startDate,
            $endDate
        );

        $this->assertTrue($result['success']);

        // Verify all blocks have proper identity
        $blocks = PropertyAvailability::where('reservation_id', $reservation->id)->get();
        foreach ($blocks as $block) {
            $this->assertNotNull($block->idempotency_key);
            $this->assertEquals('internal', $block->source_system);
            $this->assertEquals('reservation', $block->origin);
        }
    }
}
