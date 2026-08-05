<?php

namespace Tests\Feature\Reservation;

use App\Actions\Admin\Reservation\UpdateReservationStateAction;
use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\IlanReservation;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use Exception;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 1 — Test Suite
 *
 * SAAB required tests (14):
 * 1.  creates_pending_reservation
 * 2.  confirms_pending_reservation
 * 3.  cancels_pending_reservation
 * 4.  cancels_confirmed_reservation
 * 5.  completes_confirmed_reservation
 * 6.  marks_confirmed_as_no_show
 * 7.  rejects_invalid_transition
 * 8.  assigns_tenant_id
 * 9.  rejects_cross_tenant_property
 * 10. confirmed_reservation_blocks_availability
 * 11. cancelled_reservation_releases_availability
 * 12. replay_does_not_duplicate_availability
 * 13. ilan_reservation_is_deprecated
 * 14. reservation_service_uses_property_reservation
 */
class ReservationCorePhase1Test extends TestCase
{
    protected ReservationService $service;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReservationService::class);
        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 2,
        ]);
    }

    // =========================================================================
    // 1. creates_pending_reservation
    // =========================================================================

    public function test_creates_pending_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(10)->format('Y-m-d'),
            now()->addDays(13)->format('Y-m-d'),
            ['guest_name' => 'Ali Veli'],
            $this->user->id
        );

        $this->assertInstanceOf(PropertyReservation::class, $reservation);
        $this->assertEquals(ReservationState::PENDING, $reservation->reservation_state);
        $this->assertNull($reservation->confirmed_at);
    }

    // =========================================================================
    // 2. confirms_pending_reservation
    // =========================================================================

    public function test_confirms_pending_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(20)->format('Y-m-d'),
            now()->addDays(23)->format('Y-m-d'),
            ['guest_name' => 'Ayşe Fatma'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $reservation->reservation_state);

        $confirmed = $this->service->confirmReservation($reservation->id);

        $this->assertEquals(ReservationState::CONFIRMED, $confirmed->reservation_state);
        $this->assertNotNull($confirmed->confirmed_at);
    }

    // =========================================================================
    // 3. cancels_pending_reservation
    // =========================================================================

    public function test_cancels_pending_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(30)->format('Y-m-d'),
            now()->addDays(33)->format('Y-m-d'),
            ['guest_name' => 'Mehmet Kaya'],
            $this->user->id
        );

        $this->service->cancelReservation($reservation->id);

        $fresh = $reservation->fresh();
        $this->assertEquals(ReservationState::CANCELLED, $fresh->reservation_state);
        $this->assertNotNull($fresh->cancelled_at);
    }

    // =========================================================================
    // 4. cancels_confirmed_reservation
    // =========================================================================

    public function test_cancels_confirmed_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(40)->format('Y-m-d'),
            now()->addDays(43)->format('Y-m-d'),
            ['guest_name' => 'Zeynep Arslan'],
            $this->user->id
        );

        $this->service->confirmReservation($reservation->id);
        $this->service->cancelReservation($reservation->id);

        $fresh = $reservation->fresh();
        $this->assertEquals(ReservationState::CANCELLED, $fresh->reservation_state);
    }

    // =========================================================================
    // 5. completes_confirmed_reservation
    // =========================================================================

    public function test_completes_confirmed_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(50)->format('Y-m-d'),
            now()->addDays(53)->format('Y-m-d'),
            ['guest_name' => 'Hasan Demir'],
            $this->user->id
        );

        $this->service->confirmReservation($reservation->id);
        $completed = $this->service->completeReservation($reservation->id);

        $this->assertEquals(ReservationState::COMPLETED, $completed->reservation_state);
    }

    // =========================================================================
    // 6. marks_confirmed_as_no_show
    // =========================================================================

    public function test_marks_confirmed_as_no_show(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(60)->format('Y-m-d'),
            now()->addDays(63)->format('Y-m-d'),
            ['guest_name' => 'Fatih Yıldız'],
            $this->user->id
        );

        $this->service->confirmReservation($reservation->id);
        $noShow = $this->service->markNoShow($reservation->id);

        $this->assertEquals(ReservationState::NO_SHOW, $noShow->reservation_state);
    }

    // =========================================================================
    // 7. rejects_invalid_transition
    // =========================================================================

    public function test_rejects_invalid_transition(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(70)->format('Y-m-d'),
            now()->addDays(73)->format('Y-m-d'),
            ['guest_name' => 'Emre Can'],
            $this->user->id
        );

        // PENDING → COMPLETED is invalid (must go through CONFIRMED first)
        $this->expectException(Exception::class);
        $this->service->completeReservation($reservation->id);
    }

    // =========================================================================
    // 8. assigns_tenant_id
    // =========================================================================

    public function test_assigns_tenant_id(): void
    {
        $tenantId = (int) $this->ilan->tenant_id;

        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(80)->format('Y-m-d'),
            now()->addDays(83)->format('Y-m-d'),
            ['guest_name' => 'Tenant Test'],
            $this->user->id
        );

        if ($tenantId > 0) {
            $this->assertEquals($tenantId, $reservation->tenant_id);
        } else {
            // Ilan has no tenant_id — reservation may be null tenant
            $this->assertDatabaseHas('property_reservations', [
                'id'         => $reservation->id,
                'property_id' => $this->ilan->id,
            ]);
        }
    }

    // =========================================================================
    // 9. rejects_cross_tenant_property
    // =========================================================================

    public function test_rejects_cross_tenant_property(): void
    {
        // Create a reservation under tenant 1
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(90)->format('Y-m-d'),
            now()->addDays(93)->format('Y-m-d'),
            ['guest_name' => 'Cross Tenant'],
            $this->user->id,
            tenantId: 1
        );

        // Try to cancel under a different tenant
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("does not belong to the given tenant");

        $this->service->cancelReservation($reservation->id, tenantId: 999);
    }

    // =========================================================================
    // 10. confirmed_reservation_blocks_availability
    // =========================================================================

    public function test_confirmed_reservation_blocks_availability(): void
    {
        $start = now()->addDays(100)->format('Y-m-d');
        $end   = now()->addDays(103)->format('Y-m-d');

        $reservation = $this->service->createReservation(
            $this->ilan->id,
            $start,
            $end,
            ['guest_name' => 'Availability Block Test'],
            $this->user->id
        );

        // PENDING — availability should NOT be blocked yet
        $blockedCount = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(0, $blockedCount, 'Pending reservation must not block availability');

        // CONFIRM — availability should now be blocked
        $this->service->confirmReservation($reservation->id);

        $blockedAfterConfirm = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(3, $blockedAfterConfirm, 'Confirmed reservation must block all 3 nights');
    }

    // =========================================================================
    // 11. cancelled_reservation_releases_availability
    // =========================================================================

    public function test_cancelled_reservation_releases_availability(): void
    {
        $start = now()->addDays(110)->format('Y-m-d');
        $end   = now()->addDays(113)->format('Y-m-d');

        $reservation = $this->service->createReservation(
            $this->ilan->id,
            $start,
            $end,
            ['guest_name' => 'Release Test'],
            $this->user->id
        );

        $this->service->confirmReservation($reservation->id);

        // Confirm blocks exist
        $this->assertEquals(
            3,
            PropertyAvailability::where('property_id', $this->ilan->id)
                ->where('is_available', false)
                ->where('reservation_id', $reservation->id)
                ->count()
        );

        // Cancel — blocks should be released
        $this->service->cancelReservation($reservation->id);

        $stillBlocked = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(0, $stillBlocked, 'Cancelled reservation must release all availability blocks');
    }

    // =========================================================================
    // 12. replay_does_not_duplicate_availability
    // =========================================================================

    public function test_replay_does_not_duplicate_availability(): void
    {
        $start = now()->addDays(120)->format('Y-m-d');
        $end   = now()->addDays(123)->format('Y-m-d');

        $reservation = $this->service->createReservation(
            $this->ilan->id,
            $start,
            $end,
            ['guest_name' => 'Idempotent Test'],
            $this->user->id
        );

        $this->service->confirmReservation($reservation->id);

        $countBefore = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('reservation_id', $reservation->id)
            ->count();

        // Attempt double-confirm — should throw (terminal state guard)
        try {
            $this->service->confirmReservation($reservation->id);
        } catch (Exception $e) {
            // Expected — already confirmed
        }

        $countAfter = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals($countBefore, $countAfter, 'Double confirm must not duplicate availability rows');
    }

    // =========================================================================
    // 13. ilan_reservation_is_deprecated
    // =========================================================================

    public function test_ilan_reservation_is_deprecated(): void
    {
        $reflection = new \ReflectionClass(IlanReservation::class);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment, 'IlanReservation must have a PHPDoc comment');
        $this->assertStringContainsString(
            '@deprecated',
            $docComment,
            'IlanReservation must be marked @deprecated'
        );
        $this->assertStringContainsString(
            'PropertyReservation',
            $docComment,
            'IlanReservation @deprecated must reference PropertyReservation as replacement'
        );
    }

    // =========================================================================
    // 14. reservation_service_uses_property_reservation
    // =========================================================================

    public function test_reservation_service_uses_property_reservation(): void
    {
        $reservation = $this->service->createReservation(
            $this->ilan->id,
            now()->addDays(130)->format('Y-m-d'),
            now()->addDays(133)->format('Y-m-d'),
            ['guest_name' => 'Model Check'],
            $this->user->id
        );

        $this->assertInstanceOf(
            PropertyReservation::class,
            $reservation,
            'ReservationService::createReservation must return PropertyReservation, not IlanReservation'
        );

        $this->assertDatabaseHas('property_reservations', [
            'id'         => $reservation->id,
            'property_id' => $this->ilan->id,
        ]);
    }
}
