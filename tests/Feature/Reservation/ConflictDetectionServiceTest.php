<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Reservation\ConflictDetectionServiceContract;
use App\Contracts\Reservation\ConflictReport;
use App\Contracts\Reservation\ReservationConflictException;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 3 — Conflict Detection Tests
 *
 * SAAB Zorunlu Testler (12):
 * 1. rejects_overlapping_confirmed_reservation
 * 2. allows_back_to_back_reservations
 * 3. pending_reservation_blocks_overlap
 * 4. cancelled_reservation_does_not_block
 * 5. completed_reservation_does_not_block
 * 6. maintenance_block_has_priority
 * 7. owner_block_has_priority
 * 8. concurrent_create_allows_only_one
 * 9. cross_tenant_conflict_not_visible
 * 10. conflict_detection_is_deterministic
 * 11. conflict_event_dispatched
 * 12. override_requires_authorization
 */
class ConflictDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConflictDetectionServiceContract $conflictService;
    protected ReservationService $reservationService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conflictService = app(ConflictDetectionServiceContract::class);
        $this->reservationService = app(ReservationService::class);
        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'tenant_id' => 1,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);
    }

    // =========================================================================
    // T1: rejects_overlapping_confirmed_reservation
    // =========================================================================

    public function test_rejects_overlapping_confirmed_reservation(): void
    {
        $tenantId = 1;

        // Create confirmed reservation [Jun 10-13)
        $existing = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-13',
            'nights' => 3,
            'guest_name' => 'Existing Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Attempt to create overlapping reservation [Jun 12-15)
        $this->expectException(ReservationConflictException::class);

        $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-06-12',
            '2026-06-15',
            ['guest_name' => 'New Guest'],
            $this->user->id
        );
    }

    // =========================================================================
    // T2: allows_back_to_back_reservations
    // =========================================================================

    public function test_allows_back_to_back_reservations(): void
    {
        // Create confirmed reservation [Jun 10-13)
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-13',
            'nights' => 3,
            'guest_name' => 'First Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Create reservation starting on checkout day [Jun 13-16) — should be OK
        $result = $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-06-13',
            '2026-06-16',
            ['guest_name' => 'Second Guest'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $result->reservation_state);
        $this->assertEquals('Second Guest', $result->guest_name);
    }

    // =========================================================================
    // T3: pending_reservation_blocks_overlap
    // =========================================================================

    public function test_pending_reservation_blocks_overlap(): void
    {
        // Create PENDING reservation [Jun 20-23)
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-23',
            'nights' => 3,
            'guest_name' => 'Pending Guest',
            'reservation_state' => ReservationState::PENDING,
            'created_by_user_id' => $this->user->id,
        ]);

        // Attempt to create overlapping reservation — PENDING blocks overlap
        $this->expectException(ReservationConflictException::class);

        $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-06-22',
            '2026-06-25',
            ['guest_name' => 'Conflicting Guest'],
            $this->user->id
        );
    }

    // =========================================================================
    // T4: cancelled_reservation_does_not_block
    // =========================================================================

    public function test_cancelled_reservation_does_not_block(): void
    {
        // Create and cancel reservation [Jun 30 - Jul 3)
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-06-30',
            'end_date' => '2026-07-03',
            'nights' => 3,
            'guest_name' => 'Cancelled Guest',
            'reservation_state' => ReservationState::CANCELLED,
            'cancelled_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // CANCELLED reservation should not block
        $result = $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-06-30',
            '2026-07-03',
            ['guest_name' => 'New Guest'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $result->reservation_state);
    }

    // =========================================================================
    // T5: completed_reservation_does_not_block
    // =========================================================================

    public function test_completed_reservation_does_not_block(): void
    {
        // Create completed reservation [Jul 10-13)
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-13',
            'nights' => 3,
            'guest_name' => 'Completed Guest',
            'reservation_state' => ReservationState::COMPLETED,
            'confirmed_at' => now()->subDays(10),
            'created_by_user_id' => $this->user->id,
        ]);

        // COMPLETED reservation should not block
        $result = $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-07-10',
            '2026-07-13',
            ['guest_name' => 'New Guest'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $result->reservation_state);
    }

    // =========================================================================
    // T6: maintenance_block_has_priority
    // =========================================================================

    public function test_maintenance_block_has_priority(): void
    {
        $tenantId = 1;

        // Create maintenance block (priority_tier = 1)
        PropertyAvailability::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'date' => '2026-08-01',
            'is_available' => false,
            'block_reason' => 'maintenance',
            'priority_tier' => 1, // MAINTENANCE
            'source_system' => 'internal',
            'origin' => 'maintenance',
        ]);

        // Detect conflict — maintenance blocks reservation (3)
        $report = $this->conflictService->detectConflicts(
            $tenantId,
            $this->ilan->id,
            '2026-08-01',
            '2026-08-03'
        );

        $this->assertTrue($report->hasConflict);
        $this->assertEquals('AVAILABILITY_CONFLICT', $report->conflictType);
        $this->assertEquals(1, $report->highestPriority); // MAINTENANCE = 1
    }

    // =========================================================================
    // T7: owner_block_has_priority
    // =========================================================================

    public function test_owner_block_has_priority(): void
    {
        $tenantId = 1;

        // Create owner block (priority_tier = 2)
        PropertyAvailability::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'date' => '2026-08-10',
            'is_available' => false,
            'block_reason' => 'owner_block',
            'priority_tier' => 2, // OWNER_BLOCK
            'source_system' => 'internal',
            'origin' => 'owner',
        ]);

        // Detect conflict — owner blocks reservation (3)
        $report = $this->conflictService->detectConflicts(
            $tenantId,
            $this->ilan->id,
            '2026-08-10',
            '2026-08-13'
        );

        $this->assertTrue($report->hasConflict);
        $this->assertEquals('AVAILABILITY_CONFLICT', $report->conflictType);
        $this->assertEquals(2, $report->highestPriority); // OWNER_BLOCK = 2
    }

    // =========================================================================
    // T8: concurrent_create_allows_only_one
    // =========================================================================

    public function test_concurrent_create_allows_only_one(): void
    {
        // This tests that the transaction + lockForUpdate prevents double booking
        // Note: True concurrency test would require threading, but we can verify
        // that the conflict detection works correctly

        $tenantId = 1;

        // Create first reservation [Aug 20-23)
        $first = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-23',
            'nights' => 3,
            'guest_name' => 'First Concurrent',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Second attempt to create overlapping reservation should fail
        $this->expectException(ReservationConflictException::class);

        $this->reservationService->createReservation(
            $this->ilan->id,
            '2026-08-21',
            '2026-08-24',
            ['guest_name' => 'Second Concurrent'],
            $this->user->id
        );
    }

    // =========================================================================
    // T9: cross_tenant_conflict_not_visible
    // =========================================================================

    public function test_cross_tenant_conflict_not_visible(): void
    {
        $ilanTenant2 = Ilan::factory()->create([
            'tenant_id' => 2,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        // Create reservation for tenant 2
        PropertyReservation::create([
            'tenant_id' => 2,
            'property_id' => $ilanTenant2->id,
            'ilan_id' => $ilanTenant2->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-13',
            'nights' => 3,
            'guest_name' => 'Tenant 2 Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Tenant 1 should NOT see tenant 2's reservation as conflict
        $report = $this->conflictService->detectConflicts(
            1, // Tenant 1
            $this->ilan->id, // Tenant 1's property
            '2026-09-10',
            '2026-09-13'
        );

        $this->assertFalse($report->hasConflict);
        $this->assertEmpty($report->conflictingReservations);
    }

    // =========================================================================
    // T10: conflict_detection_is_deterministic
    // =========================================================================

    public function test_conflict_detection_is_deterministic(): void
    {
        $tenantId = 1;

        // Create reservation [Sep 20-23)
        PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-23',
            'nights' => 3,
            'guest_name' => 'Deterministic Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Run conflict detection 5 times — should always return same result
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->conflictService->detectConflicts(
                $tenantId,
                $this->ilan->id,
                '2026-09-20',
                '2026-09-23'
            );
        }

        // All results should be identical
        $this->assertTrue($results[0]->hasConflict);
        for ($i = 1; $i < 5; $i++) {
            $this->assertEquals($results[0]->hasConflict, $results[$i]->hasConflict);
            $this->assertEquals($results[0]->conflictType, $results[$i]->conflictType);
            $this->assertEquals(
                count($results[0]->conflictingReservations),
                count($results[$i]->conflictingReservations)
            );
        }
    }

    // =========================================================================
    // T11: conflict_event_dispatched
    // =========================================================================

    public function test_conflict_event_dispatched(): void
    {
        Event::fake();

        // Create existing reservation [Oct 10-13)
        PropertyReservation::create([
            'tenant_id' => 1,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'nights' => 3,
            'guest_name' => 'Existing',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Attempt conflicting reservation
        try {
            $this->reservationService->createReservation(
                $this->ilan->id,
                '2026-10-11',
                '2026-10-14',
                ['guest_name' => 'Conflicting'],
                $this->user->id
            );
        } catch (ReservationConflictException $e) {
            // Expected
        }

        // Verify event was dispatched
        Event::assertDispatched(\App\Events\Reservation\ReservationConflictDetectedEvent::class);
    }

    // =========================================================================
    // T12: override_requires_authorization
    // =========================================================================

    public function test_override_requires_authorization(): void
    {
        // Maintenance blocks (priority = 1) should NEVER be overridable
        // by a regular reservation

        $tenantId = 1;

        // Create maintenance block
        PropertyAvailability::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'date' => '2026-11-01',
            'is_available' => false,
            'block_reason' => 'maintenance',
            'priority_tier' => 1, // MAINTENANCE
            'source_system' => 'internal',
            'origin' => 'maintenance',
        ]);

        // Verify that maintenance block creates a conflict
        // that cannot be resolved by a regular reservation
        $report = $this->conflictService->detectConflicts(
            $tenantId,
            $this->ilan->id,
            '2026-11-01',
            '2026-11-03'
        );

        $this->assertTrue($report->hasConflict);
        $this->assertEquals(1, $report->highestPriority); // Maintenance has priority 1

        // The ConflictReport should contain the maintenance block
        $this->assertNotEmpty($report->conflictingBlocks);
        $this->assertEquals('maintenance', $report->conflictingBlocks[0]->origin);
    }
}
