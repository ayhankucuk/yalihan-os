<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\Property\AvailabilityReplayService;
use App\Services\Property\TenantIsolationEnforcer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2 E04 — Tenant Isolation
 *
 * SAAB Zorunlu Testler (6):
 * 1. tenant_a_cannot_project_into_tenant_b_property
 * 2. tenant_a_cancel_cannot_release_tenant_b_availability
 * 3. rebuild_only_processes_current_tenant
 * 4. projection_rejects_reservation_property_tenant_mismatch
 * 5. listener_preserves_tenant_context
 * 6. cross_tenant_attempt_creates_audit_evidence
 *
 * Temel Invariant:
 * reservation.tenant_id = property.tenant_id = availability.tenant_id
 *
 * Uyumsuzluk durumunda:
 * reject + log + audit evidence
 */
class TenantIsolationE04Test extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityProjectionContract $projectionService;
    protected AvailabilityReplayService $replayService;
    protected TenantIsolationEnforcer $enforcer;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectionService = app(AvailabilityProjectionContract::class);
        $this->replayService = app(AvailabilityReplayService::class);
        $this->enforcer = app(TenantIsolationEnforcer::class);
        $this->user = User::factory()->create();
    }

    // =========================================================================
    // E04-T1: tenant_a_cannot_project_into_tenant_b_property
    // =========================================================================

    public function test_tenant_a_cannot_project_into_tenant_b_property(): void
    {
        // Create two properties with different tenants
        $ilanA = Ilan::factory()->create(['tenant_id' => 1, 'rental_enabled' => true]);
        $ilanB = Ilan::factory()->create(['tenant_id' => 2, 'rental_enabled' => true]);

        // Create reservation in Tenant A
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $ilanA->id,
            'ilan_id' => $ilanA->id,
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(13)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Tenant A Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Attempt to project Tenant A's reservation into Tenant B's property
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cross-tenant');

        $this->projectionService->projectConfirm(
            $reservation->id,
            1, // Tenant A requesting
            $ilanB->id, // Tenant B's property
            $reservation->start_date,
            $reservation->end_date
        );
    }

    // =========================================================================
    // E04-T2: tenant_a_cancel_cannot_release_tenant_b_availability
    // =========================================================================

    public function test_tenant_a_cancel_cannot_release_tenant_b_availability(): void
    {
        $ilanB = Ilan::factory()->create(['tenant_id' => 2, 'rental_enabled' => true]);

        // Create availability block owned by Tenant B
        PropertyAvailability::create([
            'tenant_id' => 2,
            'property_id' => $ilanB->id,
            'date' => now()->addDays(20)->format('Y-m-d'),
            'is_available' => false,
            'block_reason' => 'reservation',
            'priority_tier' => 2,
            'reservation_id' => 999, // Non-existent reservation
            'source_system' => 'internal',
            'origin' => 'reservation',
        ]);

        // Attempt to cancel as Tenant A (should not release Tenant B's block)
        $result = $this->projectionService->projectCancel(
            999, // Non-existent reservation ID
            1, // Tenant A requesting
            now()->addDays(20)->format('Y-m-d'),
            now()->addDays(21)->format('Y-m-d')
        );

        // Verify Tenant B's block is still present
        $tenantBBlock = PropertyAvailability::where('property_id', $ilanB->id)
            ->where('tenant_id', 2)
            ->where('is_available', false)
            ->first();

        $this->assertNotNull($tenantBBlock, 'Tenant B block must be preserved');
        $this->assertEquals(2, $tenantBBlock->tenant_id, 'Block tenant_id must be preserved');
    }

    // =========================================================================
    // E04-T3: rebuild_only_processes_current_tenant
    // =========================================================================

    public function test_rebuild_only_processes_current_tenant(): void
    {
        $ilanA = Ilan::factory()->create(['tenant_id' => 1, 'rental_enabled' => true]);
        $ilanB = Ilan::factory()->create(['tenant_id' => 2, 'rental_enabled' => true]);

        // Create reservations for both tenants
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $ilanA->id,
            'ilan_id' => $ilanA->id,
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(33)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Tenant A Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        PropertyReservation::create([
            'tenant_id' => 2,
            'property_id' => $ilanB->id,
            'ilan_id' => $ilanB->id,
            'start_date' => now()->addDays(30)->format('Y-m-d'),
            'end_date' => now()->addDays(33)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Tenant B Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Rebuild for Tenant A only
        $result = $this->replayService->rebuild(
            1, // Tenant A
            null, // All properties
            now()->addDays(25)->format('Y-m-d'),
            now()->addDays(40)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->propertiesProcessed);

        // Verify Tenant A has blocks
        $tenantABlocks = PropertyAvailability::where('property_id', $ilanA->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(3, $tenantABlocks);

        // Verify Tenant B has NO blocks (not touched by Tenant A rebuild)
        $tenantBBlocks = PropertyAvailability::where('property_id', $ilanB->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(0, $tenantBBlocks, 'Tenant B must not be affected');
    }

    // =========================================================================
    // E04-T4: projection_rejects_reservation_property_tenant_mismatch
    // =========================================================================

    public function test_projection_rejects_reservation_property_tenant_mismatch(): void
    {
        $ilanA = Ilan::factory()->create(['tenant_id' => 1, 'rental_enabled' => true]);
        $ilanB = Ilan::factory()->create(['tenant_id' => 2, 'rental_enabled' => true]);

        // Create reservation for Tenant A on Tenant A's property
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $ilanA->id,
            'ilan_id' => $ilanA->id,
            'start_date' => now()->addDays(40)->format('Y-m-d'),
            'end_date' => now()->addDays(43)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Mismatch Test Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Verify the enforcer detects the mismatch when projecting to wrong property
        $result = $this->enforcer->verifyPropertyOwnership(2, $ilanA->id);

        $this->assertFalse($result->isValid);
        $this->assertEquals('CROSS_TENANT_PROPERTY_ACCESS', $result->errorCode);

        // Attempt to project should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cross-tenant');

        $this->projectionService->projectConfirm(
            $reservation->id,
            2, // Tenant B requesting
            $ilanA->id, // Tenant A's property
            $reservation->start_date,
            $reservation->end_date
        );
    }

    // =========================================================================
    // E04-T5: listener_preserves_tenant_context
    // =========================================================================

    public function test_listener_preserves_tenant_context(): void
    {
        $ilan = Ilan::factory()->create(['tenant_id' => 1, 'rental_enabled' => true]);

        // Create reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $ilan->id,
            'ilan_id' => $ilan->id,
            'start_date' => now()->addDays(50)->format('Y-m-d'),
            'end_date' => now()->addDays(53)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Context Test Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Verify reservation has correct tenant_id
        $this->assertEquals(1, $reservation->tenant_id);

        // Project using the projection service
        $result = $this->projectionService->projectConfirm(
            $reservation->id,
            $reservation->tenant_id, // Tenant 1
            $ilan->id,
            $reservation->start_date,
            $reservation->end_date
        );

        $this->assertTrue($result['success']);

        // Verify availability row has same tenant_id
        $availability = PropertyAvailability::where('reservation_id', $reservation->id)->first();

        $this->assertNotNull($availability);
        $this->assertEquals(1, $availability->tenant_id, 'Availability tenant_id must match reservation');
        $this->assertEquals($ilan->id, $availability->property_id);
    }

    // =========================================================================
    // E04-T6: cross_tenant_attempt_creates_audit_evidence
    // =========================================================================

    public function test_cross_tenant_attempt_creates_audit_evidence(): void
    {
        $ilanA = Ilan::factory()->create(['tenant_id' => 1, 'rental_enabled' => true]);
        $ilanB = Ilan::factory()->create(['tenant_id' => 2, 'rental_enabled' => true]);

        // Ensure audit table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('cross_tenant_violation_audit')) {
            \Illuminate\Support\Facades\Schema::create('cross_tenant_violation_audit', function ($table) {
                $table->id();
                $table->string('event_type', 100);
                $table->unsignedInteger('requesting_tenant_id')->index();
                $table->unsignedInteger('property_id')->nullable()->index();
                $table->unsignedInteger('reservation_id')->nullable()->index();
                $table->text('message');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        // Clear existing audit records
        DB::table('cross_tenant_violation_audit')->truncate();

        // Directly test the enforcer's audit logging capability
        // This simulates a cross-tenant attempt being detected and logged
        try {
            $this->enforcer->enforceProjectionAccess(
                1, // Tenant A
                2, // Tenant B (mismatch)
                'project',
                $ilanB->id,
                123
            );
        } catch (\App\Services\Property\CrossTenantAccessException $e) {
            // Expected - this is the enforcer throwing the exception
        }

        // Verify audit evidence was created
        $auditRecords = DB::table('cross_tenant_violation_audit')
            ->where('requesting_tenant_id', 1)
            ->where('property_id', $ilanB->id)
            ->get();

        $this->assertGreaterThan(0, count($auditRecords), 'Audit evidence must be created');

        $latestAudit = $auditRecords->sortByDesc('id')->first();
        $this->assertEquals('cross_tenant_project_attempt', $latestAudit->event_type);
        $this->assertStringContainsString('tenant 2', $latestAudit->message);
    }
}
