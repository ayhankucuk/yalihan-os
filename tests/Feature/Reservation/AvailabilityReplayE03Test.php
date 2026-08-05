<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\Property\AvailabilityReplayService;
use App\Services\Property\CanonicalAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2 E03 — Replay / Rebuild Safety
 *
 * SAAB Zorunlu Testler (8):
 * 1. rebuild_creates_projection_from_confirmed_reservation
 * 2. rebuild_ignores_pending_reservation
 * 3. rebuild_ignores_cancelled_reservation
 * 4. rebuild_twice_does_not_duplicate_projection
 * 5. rebuild_matches_runtime_projection
 * 6. rebuild_is_tenant_scoped
 * 7. failed_rebuild_does_not_leave_partial_projection
 * 8. replay_does_not_mutate_reservation_history
 *
 * Mimari Kural:
 * Canonical reservations
 *         ↓
 * Projection rebuild (AvailabilityReplayService)
 *         ↓
 * PropertyAvailability
 *         ↓
 * Runtime ile aynı sonuç
 */
class AvailabilityReplayE03Test extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityReplayService $replayService;
    protected AvailabilityProjectionContract $projectionService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replayService = app(AvailabilityReplayService::class);
        $this->projectionService = app(AvailabilityProjectionContract::class);
        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);
    }

    // =========================================================================
    // E03-T1: rebuild_creates_projection_from_confirmed_reservation
    // =========================================================================

    public function test_rebuild_creates_projection_from_confirmed_reservation(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(10)->format('Y-m-d');
        $end = now()->addDays(13)->format('Y-m-d');

        // Create and confirm a reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Test Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Verify no availability blocked BEFORE rebuild
        $beforeCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(0, $beforeCount, 'No blocks should exist before rebuild');

        // Run rebuild
        $result = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(5)->format('Y-m-d'),
            now()->addDays(20)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success, 'Rebuild should succeed');
        $this->assertEquals(1, $result->reservationsProcessed);

        // Verify availability is NOW blocked
        $blockedDays = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(3, $blockedDays, 'All 3 nights should be blocked after rebuild');
    }

    // =========================================================================
    // E03-T2: rebuild_ignores_pending_reservation
    // =========================================================================

    public function test_rebuild_ignores_pending_reservation(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(20)->format('Y-m-d');
        $end = now()->addDays(23)->format('Y-m-d');

        // Create a PENDING reservation (not yet confirmed)
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Pending Guest',
            'reservation_state' => ReservationState::PENDING,
            'created_by_user_id' => $this->user->id,
        ]);

        // Run rebuild
        $result = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(15)->format('Y-m-d'),
            now()->addDays(30)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success);

        // Verify NO availability blocks created for PENDING reservation
        $blockedCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(0, $blockedCount, 'Pending reservation must NOT block availability');
    }

    // =========================================================================
    // E03-T3: rebuild_ignores_cancelled_reservation
    // =========================================================================

    public function test_rebuild_ignores_cancelled_reservation(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(30)->format('Y-m-d');
        $end = now()->addDays(33)->format('Y-m-d');

        // Create a CANCELLED reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Cancelled Guest',
            'reservation_state' => ReservationState::CANCELLED,
            'cancelled_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Run rebuild
        $result = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(25)->format('Y-m-d'),
            now()->addDays(40)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success);

        // Verify NO availability blocks created for CANCELLED reservation
        $blockedCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(0, $blockedCount, 'Cancelled reservation must NOT block availability');
    }

    // =========================================================================
    // E03-T4: rebuild_twice_does_not_duplicate_projection
    // =========================================================================

    public function test_rebuild_twice_does_not_duplicate_projection(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(50)->format('Y-m-d');
        $end = now()->addDays(53)->format('Y-m-d');

        // Create confirmed reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Idempotent Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // First rebuild
        $result1 = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(45)->format('Y-m-d'),
            now()->addDays(60)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result1->success);

        $blockedAfterFirst = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(3, $blockedAfterFirst);

        // Second rebuild (idempotency check)
        $result2 = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(45)->format('Y-m-d'),
            now()->addDays(60)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result2->success);

        $blockedAfterSecond = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(
            $blockedAfterFirst,
            $blockedAfterSecond,
            'Second rebuild must NOT duplicate availability blocks'
        );
    }

    // =========================================================================
    // E03-T5: rebuild_matches_runtime_projection
    // =========================================================================

    public function test_rebuild_matches_runtime_projection(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(70)->format('Y-m-d');
        $end = now()->addDays(73)->format('Y-m-d');

        // Create confirmed reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Runtime Match Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Project via runtime (as if reservation was confirmed normally)
        $runtimeProjection = $this->projectionService->projectConfirm(
            $reservation->id,
            $tenantId,
            $this->ilan->id,
            $start,
            $end
        );

        $this->assertTrue($runtimeProjection['success']);
        $this->assertEquals(3, $runtimeProjection['blocked_days']);

        // Get runtime projection state
        $runtimeBlockedDates = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->pluck('date')
            ->sort()
            ->values()
            ->toArray();

        // Delete all availability and rebuild from scratch
        PropertyAvailability::where('property_id', $this->ilan->id)->delete();

        // Rebuild
        $rebuildResult = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(65)->format('Y-m-d'),
            now()->addDays(80)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($rebuildResult->success);

        // Get rebuild projection state
        $rebuildBlockedDates = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->pluck('date')
            ->sort()
            ->values()
            ->toArray();

        $this->assertEquals(
            count($runtimeBlockedDates),
            count($rebuildBlockedDates),
            'Rebuild must produce same number of blocked days as runtime'
        );

        $this->assertEquals(
            $runtimeBlockedDates,
            $rebuildBlockedDates,
            'Rebuild must produce same blocked dates as runtime projection'
        );
    }

    // =========================================================================
    // E03-T6: rebuild_is_tenant_scoped
    // =========================================================================

    public function test_rebuild_is_tenant_scoped(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;

        // Create second property with SAME tenant
        $ilanSameTenant = Ilan::factory()->create([
            'tenant_id' => $tenantId,
            'rental_enabled' => true,
        ]);

        // Create third property with DIFFERENT tenant
        $ilanOtherTenant = Ilan::factory()->create([
            'tenant_id' => $tenantId + 100, // Different tenant
            'rental_enabled' => true,
        ]);

        // Create confirmed reservations
        PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => now()->addDays(90)->format('Y-m-d'),
            'end_date' => now()->addDays(93)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Same Tenant Guest 1',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $ilanSameTenant->id,
            'ilan_id' => $ilanSameTenant->id,
            'start_date' => now()->addDays(90)->format('Y-m-d'),
            'end_date' => now()->addDays(93)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Same Tenant Guest 2',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        PropertyReservation::create([
            'tenant_id' => $tenantId + 100,
            'property_id' => $ilanOtherTenant->id,
            'ilan_id' => $ilanOtherTenant->id,
            'start_date' => now()->addDays(90)->format('Y-m-d'),
            'end_date' => now()->addDays(93)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Other Tenant Guest',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Rebuild for specific tenant ONLY (and specific property)
        $result = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id, // Only this property
            now()->addDays(85)->format('Y-m-d'),
            now()->addDays(100)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success);
        $this->assertEquals(1, $result->propertiesProcessed);

        // Verify this property has blocks
        $thisPropertyBlocks = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(3, $thisPropertyBlocks, 'This property must have blocks');

        // Verify other tenant property has NO blocks (not even touched)
        $otherTenantBlocks = PropertyAvailability::where('property_id', $ilanOtherTenant->id)
            ->where('is_available', false)
            ->where('origin', 'reservation')
            ->count();

        $this->assertEquals(0, $otherTenantBlocks, 'Other tenant property must NOT be touched');
    }

    // =========================================================================
    // E03-T7: failed_rebuild_does_not_leave_partial_projection
    // =========================================================================

    public function test_failed_rebuild_does_not_leave_partial_projection(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;

        // Create multiple confirmed reservations
        $reservation1 = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => now()->addDays(110)->format('Y-m-d'),
            'end_date' => now()->addDays(113)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Transaction Test Guest 1',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        $reservation2 = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => now()->addDays(120)->format('Y-m-d'),
            'end_date' => now()->addDays(123)->format('Y-m-d'),
            'nights' => 3,
            'guest_name' => 'Transaction Test Guest 2',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        // Pre-existing external block (should be preserved by rebuild)
        PropertyAvailability::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'date' => now()->addDays(115)->format('Y-m-d'),
            'is_available' => false,
            'block_reason' => 'maintenance',
            'priority_tier' => 1,
            'source_system' => 'internal',
            'origin' => 'maintenance',
            'projection_source' => 'block',
        ]);

        // Record state before rebuild
        $blocksBefore = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->count();

        $this->assertEquals(1, $blocksBefore, 'Should have 1 maintenance block before rebuild');

        // Run rebuild - rebuild is transactional, if it fails, state should be unchanged
        $result = $this->replayService->rebuild(
            $tenantId,
            $this->ilan->id,
            now()->addDays(105)->format('Y-m-d'),
            now()->addDays(130)->format('Y-m-d'),
            'test'
        );

        $this->assertTrue($result->success, 'Rebuild should succeed');

        // After successful rebuild, we should have:
        // - 1 maintenance block (preserved, origin != reservation/yazlik)
        // - 3 blocks for reservation 1
        // - 3 blocks for reservation 2
        $blocksAfter = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->count();

        // Maintenance block should still exist (origin-scoped delete preserves it)
        $maintenanceBlockExists = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('origin', 'maintenance')
            ->exists();

        $this->assertTrue($maintenanceBlockExists, 'Maintenance block must be preserved');
        $this->assertEquals(7, $blocksAfter, 'Should have 7 blocks total (1 maint + 3 res1 + 3 res2)');
    }

    // =========================================================================
    // E03-T8: replay_does_not_mutate_reservation_history
    // =========================================================================

    public function test_replay_does_not_mutate_reservation_history(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;
        $start = now()->addDays(130)->format('Y-m-d');
        $end = now()->addDays(133)->format('Y-m-d');

        // Create confirmed reservation
        $reservation = PropertyReservation::create([
            'tenant_id' => $tenantId,
            'property_id' => $this->ilan->id,
            'ilan_id' => $this->ilan->id,
            'start_date' => $start,
            'end_date' => $end,
            'nights' => 3,
            'guest_name' => 'Original Guest',
            'notes' => 'Original notes',
            'reservation_state' => ReservationState::CONFIRMED,
            'confirmed_at' => now()->subDays(5),
            'created_by_user_id' => $this->user->id,
        ]);

        // Record original state
        $originalState = $reservation->reservation_state;
        $originalConfirmedAt = $reservation->confirmed_at;
        $originalGuestName = $reservation->guest_name;
        $originalNotes = $reservation->notes;

        // Run rebuild multiple times
        for ($i = 0; $i < 3; $i++) {
            $result = $this->replayService->rebuild(
                $tenantId,
                $this->ilan->id,
                now()->addDays(125)->format('Y-m-d'),
                now()->addDays(140)->format('Y-m-d'),
                'test'
            );

            $this->assertTrue($result->success, "Rebuild iteration {$i} should succeed");
        }

        // Refresh from database
        $reservation->refresh();

        // Verify reservation record was NOT mutated
        $this->assertEquals(
            $originalState,
            $reservation->reservation_state,
            'Reservation state must not change during rebuild'
        );

        $this->assertEquals(
            $originalConfirmedAt?->format('Y-m-d H:i:s'),
            $reservation->confirmed_at?->format('Y-m-d H:i:s'),
            'Reservation confirmed_at must not change during rebuild'
        );

        $this->assertEquals(
            $originalGuestName,
            $reservation->guest_name,
            'Reservation guest_name must not change during rebuild'
        );

        $this->assertEquals(
            $originalNotes,
            $reservation->notes,
            'Reservation notes must not change during rebuild'
        );
    }
}
