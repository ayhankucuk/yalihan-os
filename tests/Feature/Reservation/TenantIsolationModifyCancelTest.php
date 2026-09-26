<?php

namespace Tests\Feature\Reservation;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyReservation;
use App\Models\SaaS\Tenant;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TASK_10 — Tenant Isolation Regression Tests
 *
 * Verifies that PropertyReservation modifyReservation and cancelReservation
 * enforce tenant boundaries as required by TASK_10 security contract.
 *
 * Required contracts (TASK_10 §5):
 *   A. same tenant modify  → succeeds
 *   B. foreign tenant modify → fails closed (ModelNotFoundException)
 *   C. same tenant cancel   → succeeds
 *   D. foreign tenant cancel → fails closed (ModelNotFoundException)
 *   E. NULL tenant reservation → fails closed for any positive tenant_id
 *
 * Evidence Level: TEST_VERIFIED
 */
class TenantIsolationModifyCancelTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Ilan $ilanA;
    protected Ilan $ilanB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReservationService();

        // Create two distinct tenants
        $this->tenantA = Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant A',
            'domain' => 'tenant-a.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'uuid'   => (string) \Illuminate\Support\Str::uuid(),
            'name'   => 'Tenant B',
            'domain' => 'tenant-b.test',
            'status' => 'active',
        ]);

        // Set context to Tenant A for model creation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);
        $this->ilanA = Ilan::factory()->create();
        $this->assertEquals($this->tenantA->id, $this->ilanA->tenant_id);

        // Set context to Tenant B for model creation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantB);
        $this->ilanB = Ilan::factory()->create();
        $this->assertEquals($this->tenantB->id, $this->ilanB->tenant_id);

        // Reset to Tenant A for test assertions
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);
    }

    // ─── A: Same-tenant modify succeeds ────────────────────────────────────────

    public function test_same_tenant_modify_reservation_succeeds(): void
    {
        $reservation = PropertyReservation::factory()
            ->forIlan($this->ilanA)
            ->create([
                'start_date'          => now()->addDays(10)->format('Y-m-d'),
                'end_date'            => now()->addDays(13)->format('Y-m-d'),
                'reservation_state'   => ReservationState::CONFIRMED,
            ]);

        $updated = $this->service->modifyReservation(
            $this->tenantA->id,
            $reservation->id,
            now()->addDays(12)->format('Y-m-d'),
            now()->addDays(15)->format('Y-m-d'),
            [],
        );

        $this->assertEquals($reservation->id, $updated->id);
        // Dates should be updated (factory dates were in the past/future relative to now)
        $this->assertNotEquals($reservation->start_date, $updated->start_date);
    }

    // ─── B: Foreign-tenant modify fails closed ───────────────────────────────────

    public function test_foreign_tenant_modify_reservation_fails_closed(): void
    {
        // Tenant B creates a reservation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantB);
        $reservation = PropertyReservation::factory()
            ->forIlan($this->ilanB)
            ->create([
                'start_date'          => now()->addDays(10)->format('Y-m-d'),
                'end_date'            => now()->addDays(13)->format('Y-m-d'),
                'reservation_state'   => ReservationState::CONFIRMED,
            ]);
        $foreignReservationId = $reservation->id;

        // Tenant A attempts to modify Tenant B's reservation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->modifyReservation(
            $this->tenantA->id,
            $foreignReservationId,
            now()->addDays(12)->format('Y-m-d'),
            now()->addDays(15)->format('Y-m-d'),
            [],
        );
    }

    // ─── C: Same-tenant cancel succeeds ─────────────────────────────────────────

    public function test_same_tenant_cancel_reservation_succeeds(): void
    {
        $reservation = PropertyReservation::factory()
            ->forIlan($this->ilanA)
            ->create([
                'start_date'          => now()->addDays(10)->format('Y-m-d'),
                'end_date'            => now()->addDays(13)->format('Y-m-d'),
                'reservation_state'   => ReservationState::CONFIRMED,
            ]);

        $this->service->cancelReservation($reservation->id, $this->tenantA->id);

        $reservation->refresh();
        $this->assertEquals(ReservationState::CANCELLED, $reservation->reservation_state);
    }

    // ─── D: Foreign-tenant cancel fails closed ───────────────────────────────────

    public function test_foreign_tenant_cancel_reservation_fails_closed(): void
    {
        // Tenant B creates a reservation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantB);
        $reservation = PropertyReservation::factory()
            ->forIlan($this->ilanB)
            ->create([
                'start_date'          => now()->addDays(10)->format('Y-m-d'),
                'end_date'            => now()->addDays(13)->format('Y-m-d'),
                'reservation_state'   => ReservationState::CONFIRMED,
            ]);
        $foreignReservationId = $reservation->id;

        // Tenant A attempts to cancel Tenant B's reservation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->cancelReservation($foreignReservationId, $this->tenantA->id);
    }

    // ─── E: NULL-tenant reservation cannot be modified ───────────────────────────

    public function test_null_tenant_reservation_cannot_be_modified_by_any_tenant(): void
    {
        // Create reservation with NULL tenant_id via direct DB insert
        $reservationId = DB::table('property_reservations')->insertGetId([
            'property_id'       => $this->ilanA->id,
            'tenant_id'         => null,
            'start_date'        => now()->addDays(10)->format('Y-m-d'),
            'end_date'          => now()->addDays(13)->format('Y-m-d'),
            'nights'            => 3,
            'guest_name'        => 'NULL Tenant Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Any positive tenant_id should fail (NULL ≠ any positive int)
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->modifyReservation(
            $this->tenantA->id,
            $reservationId,
            now()->addDays(12)->format('Y-m-d'),
            now()->addDays(15)->format('Y-m-d'),
            [],
        );
    }

    // ─── E2: NULL-tenant reservation cannot be cancelled ────────────────────────

    public function test_null_tenant_reservation_cannot_be_cancelled_by_any_tenant(): void
    {
        // Create reservation with NULL tenant_id via direct DB insert
        $reservationId = DB::table('property_reservations')->insertGetId([
            'property_id'       => $this->ilanA->id,
            'tenant_id'         => null,
            'start_date'        => now()->addDays(10)->format('Y-m-d'),
            'end_date'          => now()->addDays(13)->format('Y-m-d'),
            'nights'            => 3,
            'guest_name'        => 'NULL Tenant Guest',
            'reservation_state' => ReservationState::CONFIRMED->value,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // Any positive tenant_id should fail (NULL ≠ any positive int)
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->cancelReservation($reservationId, $this->tenantA->id);
    }

    // ─── Cross-check: modify and cancel require the SAME tenant_id ──────────────

    public function test_modify_requires_reservation_tenant_match(): void
    {
        // Tenant B's reservation
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantB);
        $reservation = PropertyReservation::factory()
            ->forIlan($this->ilanB)
            ->create([
                'start_date'          => now()->addDays(10)->format('Y-m-d'),
                'end_date'            => now()->addDays(13)->format('Y-m-d'),
                'reservation_state'   => ReservationState::CONFIRMED,
            ]);

        // Tenant A's ID used to call modify → fails
        app(\App\Services\SaaS\TenantContextService::class)->setTenant($this->tenantA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->modifyReservation(
            $this->tenantA->id,
            $reservation->id,
            now()->addDays(12)->format('Y-m-d'),
            now()->addDays(15)->format('Y-m-d'),
            [],
        );
    }
}
