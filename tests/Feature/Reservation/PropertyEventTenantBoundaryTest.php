<?php

namespace Tests\Feature\Reservation;

use App\Events\Reservation\ReservationCreatedEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Models\User;
use App\Services\ReservationService;
use App\Services\SaaS\TenantContextService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * REZERVASYON_03 — Tenant Boundary Security Regression Test
 *
 * Verifies that the canonical reservation workflow (both HTTP endpoint POST /api/admin/events
 * and direct ReservationService::createReservation write-authority) strictly rejects cross-tenant
 * reservation attempts, preserves fail-closed isolation, and prevents availability mutations
 * on foreign tenant listings.
 */
class PropertyEventTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected TenantContextService $tenantContextService;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected Ilan $ilanA;
    protected Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->tenantContextService = app(TenantContextService::class);

        $this->tenantA = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant Alpha',
            'domain' => 'tenant-alpha.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Tenant Beta',
            'domain' => 'tenant-beta.test',
            'status' => 'active',
        ]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
        ]);

        // Establish tenant context for model creation
        $this->tenantContextService->setTenant($this->tenantA);
        $this->ilanA = Ilan::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 5000,
        ]);

        $this->tenantContextService->setTenant($this->tenantB);
        $this->ilanB = Ilan::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'fiyat' => 7500,
        ]);

        $this->tenantContextService->clearTenant();
    }

    /**
     * R0 — SAME TENANT HTTP
     * Authenticated Tenant A user creates reservation for Tenant A's Ilan.
     * Must succeed with 201, persist PropertyReservation, and mutate availability for Ilan A.
     */
    public function test_r0_same_tenant_http_succeeds_and_persists_reservation_and_availability(): void
    {
        Event::fake([ReservationCreatedEvent::class]);

        $checkIn = Carbon::tomorrow()->addDays(5)->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(8)->format('Y-m-d');

        $response = $this->actingAs($this->userA, 'web')->postJson('/api/admin/events', [
            'ilan_id' => $this->ilanA->id,
            'event_type' => 'booking',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guest_name' => 'Legitimate Guest A',
            'guest_phone' => '05551112233',
            'guest_email' => 'guest_a@test.com',
            'guest_count' => 2,
            'notes' => 'Same tenant test note',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        // Assert PropertyReservation persisted with correct ownership
        $this->assertDatabaseHas('property_reservations', [
            'property_id' => $this->ilanA->id,
            'tenant_id' => $this->tenantA->id,
            'guest_name' => 'Legitimate Guest A',
            'reservation_state' => 'confirmed',
        ]);

        // Assert PropertyAvailability dates blocked
        $blockedCount = PropertyAvailability::where('property_id', $this->ilanA->id)
            ->where('is_available', false)
            ->where('block_reason', 'reservation')
            ->count();
        $this->assertEquals(3, $blockedCount);

        Event::assertDispatched(ReservationCreatedEvent::class);
    }

    /**
     * R1 — FOREIGN TENANT HTTP
     * Authenticated Tenant A user attempts to create reservation for Tenant B's Ilan.
     * Must fail closed (404/403), with NO PropertyReservation and NO availability mutation.
     */
    public function test_r1_foreign_tenant_http_fails_closed_without_persisting_or_mutating_availability(): void
    {
        Event::fake([ReservationCreatedEvent::class]);

        $checkIn = Carbon::tomorrow()->addDays(10)->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(15)->format('Y-m-d');

        $initialReservationCount = PropertyReservation::count();
        $initialAvailabilityCount = PropertyAvailability::where('property_id', $this->ilanB->id)->count();

        $response = $this->actingAs($this->userA, 'web')->postJson('/api/admin/events', [
            'ilan_id' => $this->ilanB->id,
            'event_type' => 'booking',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guest_name' => 'Malicious Cross-Tenant Actor',
            'guest_phone' => '05559998877',
            'guest_email' => 'attacker@test.com',
            'guest_count' => 4,
            'notes' => 'Attempting to hijack Tenant B calendar',
        ]);

        // Must be rejected with client error (404 concealment or 403 forbidden)
        $this->assertTrue(
            in_array($response->status(), [403, 404]),
            "Expected 403 or 404 rejection, got HTTP {$response->status()}"
        );

        // Critical persistence absence verification:
        $this->assertEquals(
            $initialReservationCount,
            PropertyReservation::count(),
            'CRITICAL SECURITY VIOLATION: PropertyReservation was persisted during cross-tenant attempt!'
        );

        $this->assertDatabaseMissing('property_reservations', [
            'property_id' => $this->ilanB->id,
            'guest_name' => 'Malicious Cross-Tenant Actor',
        ]);

        // Critical availability absence verification:
        $mutatedAvailability = PropertyAvailability::where('property_id', $this->ilanB->id)
            ->where('is_available', false)
            ->count();
        $this->assertEquals(
            0,
            $mutatedAvailability,
            'CRITICAL SECURITY VIOLATION: PropertyAvailability was mutated on foreign tenant property!'
        );

        // No downstream lifecycle event
        Event::assertNotDispatched(ReservationCreatedEvent::class);
    }

    /**
     * R2 — DIRECT SERVICE FOREIGN TENANT
     * Direct invocation of ReservationService::createReservation under Tenant A context with Ilan B.
     * Must throw AuthorizationException, NO PropertyReservation created, NO PropertyAvailability mutated.
     */
    public function test_r2_direct_service_foreign_tenant_fails_closed_with_authorization_exception(): void
    {
        Event::fake([ReservationCreatedEvent::class]);

        $this->tenantContextService->setTenant($this->tenantA);

        $checkIn = Carbon::tomorrow()->addDays(20)->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(23)->format('Y-m-d');

        $initialReservationCount = PropertyReservation::count();

        $this->expectException(AuthorizationException::class);

        try {
            $this->reservationService->createReservation(
                $this->ilanB->id,
                $checkIn,
                $checkOut,
                ['guest_name' => 'Direct Service Attacker'],
                $this->userA->id
            );
        } finally {
            // Even if exception was thrown, verify that nothing was persisted before the exception
            $this->assertEquals($initialReservationCount, PropertyReservation::count());
            $this->assertDatabaseMissing('property_reservations', [
                'property_id' => $this->ilanB->id,
                'guest_name' => 'Direct Service Attacker',
            ]);
            $this->assertEquals(
                0,
                PropertyAvailability::where('property_id', $this->ilanB->id)->where('is_available', false)->count()
            );
            Event::assertNotDispatched(ReservationCreatedEvent::class);
        }
    }

    /**
     * R3 — DIRECT SERVICE SAME TENANT
     * Direct invocation of ReservationService::createReservation under Tenant A context with Ilan A.
     * Must succeed and persist reservation.
     */
    public function test_r3_direct_service_same_tenant_succeeds(): void
    {
        $this->tenantContextService->setTenant($this->tenantA);

        $checkIn = Carbon::tomorrow()->addDays(30)->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(33)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilanA->id,
            $checkIn,
            $checkOut,
            ['guest_name' => 'Direct Service Tenant A Guest'],
            $this->userA->id
        );

        $this->assertNotNull($reservation);
        $this->assertEquals($this->tenantA->id, $reservation->tenant_id);
        $this->assertEquals($this->ilanA->id, $reservation->property_id);
        $this->assertEquals('Direct Service Tenant A Guest', $reservation->guest_name);

        $this->assertDatabaseHas('property_reservations', [
            'id' => $reservation->id,
            'tenant_id' => $this->tenantA->id,
            'property_id' => $this->ilanA->id,
        ]);
    }

    /**
     * R4 — FORMER BOOTSTRAP TEST TENANT BYPASS PROBE
     * Verifies that the previous security bypass cannot recur:
     * - testing environment (app()->environment('testing') is true)
     * - unauthenticated direct service call (!auth()->check() is true)
     * - effective tenant domain is 'test.yalihan.local'
     * - Ilan B belongs to a different tenant (Tenant B)
     * MUST fail closed with AuthorizationException, persist zero PropertyReservation,
     * mutate zero PropertyAvailability, and dispatch zero ReservationCreatedEvent.
     */
    public function test_r4_former_bootstrap_bypass_fails_closed_under_unauthenticated_test_domain_context(): void
    {
        Event::fake([ReservationCreatedEvent::class]);

        $bootstrapTenant = Tenant::firstOrCreate(
            ['domain' => 'test.yalihan.local'],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'name' => 'Bootstrap Test Tenant',
                'status' => 'active',
            ]
        );

        $this->tenantContextService->setTenant($bootstrapTenant);
        $this->assertFalse(auth()->check());
        $this->assertTrue(app()->environment('testing'));
        $this->assertEquals('test.yalihan.local', $this->tenantContextService->getTenant()->domain);
        $this->assertNotEquals($this->ilanB->tenant_id, $bootstrapTenant->id);

        $checkIn = Carbon::tomorrow()->addDays(40)->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(43)->format('Y-m-d');

        $initialReservationCount = PropertyReservation::count();

        $this->expectException(AuthorizationException::class);

        try {
            $this->reservationService->createReservation(
                $this->ilanB->id,
                $checkIn,
                $checkOut,
                ['guest_name' => 'Former Bypass Exploit Guest'],
                null
            );
        } finally {
            $this->assertEquals($initialReservationCount, PropertyReservation::count());
            $this->assertDatabaseMissing('property_reservations', [
                'property_id' => $this->ilanB->id,
                'guest_name' => 'Former Bypass Exploit Guest',
            ]);
            $this->assertEquals(
                0,
                PropertyAvailability::where('property_id', $this->ilanB->id)->where('is_available', false)->count()
            );
            Event::assertNotDispatched(ReservationCreatedEvent::class);
        }
    }
}

