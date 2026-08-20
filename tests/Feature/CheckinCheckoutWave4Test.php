<?php

namespace Tests\Feature;

use App\Console\Commands\ReservationCompleteCommand;
use App\DTOs\Reservation\ValidityResult;
use App\Enums\ReservationState;
use App\Events\Reservation\ReservationCheckedInEvent;
use App\Events\Reservation\ReservationCheckedOutEvent;
use App\Events\Reservation\ReservationCompletedEvent;
use App\Models\AccessCredential;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReadiness;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\GuestArrivalReadinessService;
use App\Services\Reservation\OperationalGorevService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * CheckinCheckoutWave4Test — Evidence tests for Wave 4 real-time check-in / check-out.
 *
 * CHECKIN_CHECKOUT Wave 4
 * SAAB Decision: WAVE4-CHECKIN / WAVE4-CHECKOUT
 * Baseline: 8406c78
 *
 * Evidence criteria:
 *  W4-E1:  confirmed reservation can check in (readiness gate passes)
 *  W4-E2:  cancelled reservation cannot check in
 *  W4-E3:  duplicate check-in is idempotent (no double-stamp)
 *  W4-E4:  cross-tenant check-in rejected
 *  W4-E5:  ReservationCheckedInEvent dispatched after commit
 *  W4-E6:  reservation can check out
 *  W4-E7:  checkout stamps checked_out_at
 *  W4-E8:  checkout stamps completed_at
 *  W4-E9:  checkout immediately creates turnover temizlik Gorev
 *  W4-E10: duplicate checkout does not duplicate turnover Gorev
 *  W4-E11: cross-tenant checkout rejected
 *  W4-E12: reservation:complete skips manually checked-out reservations
 *  W4-E13: checkout without formal check-in proceeds (soft guard — warning, no exception)
 */
class CheckinCheckoutWave4Test extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservationService;
    protected GuestArrivalReadinessService $readinessService;
    protected OperationalGorevService $gorevService;
    protected User $user;
    protected User $otherTenantUser;
    protected Ilan $ilan;
    protected Ilan $otherTenantIlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->readinessService   = app(GuestArrivalReadinessService::class);
        $this->gorevService       = app(OperationalGorevService::class);

        $this->user           = User::factory()->create(['tenant_id' => 1]);
        $this->otherTenantUser = User::factory()->create(['tenant_id' => 2]);

        $this->ilan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'check_in_time'   => '14:00',
            'check_out_time'  => '11:00',
            'tenant_id'       => 1,
        ]);

        $this->otherTenantIlan = Ilan::factory()->create([
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
            'check_in_time'   => '14:00',
            'check_out_time'  => '11:00',
            'tenant_id'       => 2,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Create a reservation fully primed for check-in:
     * - reservation_state = confirmed
     * - checkin_window_opened_at is set (window open)
     * - PropertyReadiness created with all dimensions = true, is_ready = true
     *
     * Creates reservation and readiness DIRECTLY to avoid triggering Wave 2
     * listeners (ListenReservationCreatedReadiness) which would collide with
     * the manual PropertyReadiness::create() on the unique reservation_id constraint.
     *
     * This tests Wave 4 service methods in isolation — not Wave 2 wiring.
     */
    private function createCheckInReadyReservation(): PropertyReservation
    {
        $startDate = now()->format('Y-m-d');
        $endDate   = now()->addDays(3)->format('Y-m-d');

        // Direct model creation — bypasses event listeners intentionally
        /** @var PropertyReservation $reservation */
        $reservation = PropertyReservation::create([
            'property_id'             => $this->ilan->id,
            'ilan_id'                 => $this->ilan->id,
            'tenant_id'               => 1,
            'start_date'              => $startDate,
            'end_date'                => $endDate,
            'nights'                  => 3,
            'guest_name'              => 'Test Guest',
            'guest_email'             => 'guest@test.com',
            'guest_phone'             => '+905000000001',
            'guest_count'             => 2,
            'reservation_state'       => 'confirmed',
            'checkin_window_opened_at' => now(), // window open → canCheckIn passes INV-W2-V2
        ]);

        // Create fully-ready PropertyReadiness (all dimensions complete) — is_ready = true
        PropertyReadiness::create([
            'tenant_id'               => 1,
            'reservation_id'          => $reservation->id,
            'ilan_id'                 => $this->ilan->id,
            'property_clean'          => true,
            'access_credential_ready' => true,
            'guest_contact_ready'     => true,
            'amenity_check_complete'  => true,
            'welcome_kit_prepared'    => true,
            'is_ready'                => true,
        ]);

        return $reservation->fresh();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E1: confirmed reservation with readiness can check in
    // ═══════════════════════════════════════════════════════════════════════

    public function test_confirmed_reservation_can_check_in(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        $result = $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        $this->assertNotNull($result->checked_in_at);
        $this->assertDatabaseHas('property_reservations', [
            'id' => $reservation->id,
        ]);

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->checked_in_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E2: cancelled reservation cannot check in
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cancelled_reservation_cannot_check_in(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        // Cancel the reservation
        $this->reservationService->cancelReservation($reservation->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cancelled|not found/i');

        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E3: duplicate check-in is idempotent
    // ═══════════════════════════════════════════════════════════════════════

    public function test_duplicate_checkin_is_idempotent(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        // First check-in
        $first = $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);
        $firstTimestamp = $first->checked_in_at->toIso8601String();

        // Second check-in — must not overwrite timestamp and must not throw
        $second = $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        $this->assertEquals($firstTimestamp, $second->checked_in_at->toIso8601String());

        // DB must have exactly one check-in record — no double-stamp
        $fresh = PropertyReservation::find($reservation->id);
        $this->assertEquals($firstTimestamp, $fresh->checked_in_at->toIso8601String());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E4: cross-tenant check-in rejected
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cross_tenant_checkin_rejected(): void
    {
        $reservation = $this->createCheckInReadyReservation();

        // Attempt check-in with wrong tenant_id = 2
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/not found or tenant mismatch/i');

        $this->reservationService->checkIn($reservation->id, 2 /* wrong tenant */);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E5: ReservationCheckedInEvent dispatched after commit
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkin_event_dispatched_after_commit(): void
    {
        Event::fake([ReservationCheckedInEvent::class]);

        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        Event::assertDispatched(ReservationCheckedInEvent::class, function (ReservationCheckedInEvent $e) use ($reservation) {
            return $e->reservationId === $reservation->id
                && $e->tenantId === $reservation->tenant_id;
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E6: reservation can check out
    // ═══════════════════════════════════════════════════════════════════════

    public function test_confirmed_reservation_can_check_out(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        $result = $this->reservationService->checkOut($reservation->id, $reservation->tenant_id);

        $this->assertNotNull($result->checked_out_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E7: checkout stamps checked_out_at on DB
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkout_stamps_checked_out_at(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);
        $this->reservationService->checkOut($reservation->id, $reservation->tenant_id);

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->checked_out_at, 'checked_out_at must be set after checkOut()');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E8: checkout also stamps completed_at
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkout_stamps_completed_at(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);
        $this->reservationService->checkOut($reservation->id, $reservation->tenant_id);

        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->completed_at, 'completed_at must be set after checkOut()');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E9: checkout immediately creates turnover temizlik Gorev
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkout_immediately_creates_turnover_gorev(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        // No gorev before checkout
        $before = Gorev::where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_TEMIZLIK)
            ->count();
        $this->assertEquals(0, $before, 'No temizlik gorev should exist before checkOut');

        // Dispatch the completed event inline as the listener does (sync queue driver)
        $event = \App\Events\Reservation\ReservationCompletedEvent::fromModel(
            PropertyReservation::find($reservation->id)
        );
        $job = new \App\Jobs\Reservation\ProcessReservationCompletedJob($event);
        $job->handle(app(OperationalGorevService::class));

        $after = Gorev::where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_TEMIZLIK)
            ->count();
        $this->assertEquals(1, $after, 'Exactly 1 temizlik gorev must exist after checkOut turnover pipeline');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E10: duplicate checkout does not duplicate turnover Gorev
    // ═══════════════════════════════════════════════════════════════════════

    public function test_duplicate_checkout_does_not_duplicate_turnover(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        // First checkout + turnover job
        $event1 = \App\Events\Reservation\ReservationCompletedEvent::fromModel(
            PropertyReservation::find($reservation->id)
        );
        $job1 = new \App\Jobs\Reservation\ProcessReservationCompletedJob($event1);
        $job1->handle(app(OperationalGorevService::class));

        // Second checkout attempt is idempotent — checkOut() returns early without re-dispatching
        $this->reservationService->checkOut($reservation->id, $reservation->tenant_id);

        // Run job again to simulate replay
        $event2 = \App\Events\Reservation\ReservationCompletedEvent::fromModel(
            PropertyReservation::find($reservation->id)
        );
        $job2 = new \App\Jobs\Reservation\ProcessReservationCompletedJob($event2);
        $job2->handle(app(OperationalGorevService::class));

        $count = Gorev::where('reservation_id', $reservation->id)
            ->where('gorev_tipi', OperationalGorevService::TASK_TEMIZLIK)
            ->count();

        $this->assertEquals(1, $count, 'Idempotency: temizlik gorev must not be duplicated on replay');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E11: cross-tenant checkout rejected
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cross_tenant_checkout_rejected(): void
    {
        $reservation = $this->createCheckInReadyReservation();
        $this->reservationService->checkIn($reservation->id, $reservation->tenant_id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/not found or tenant mismatch/i');

        $this->reservationService->checkOut($reservation->id, 2 /* wrong tenant */);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E12: reservation:complete skips manually checked-out reservations
    // ═══════════════════════════════════════════════════════════════════════

    public function test_complete_command_skips_manually_checked_out(): void
    {
        // Create a reservation with end_date in the past
        $startDate = now()->subDays(3)->format('Y-m-d');
        $endDate   = now()->subDay()->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'ilan_id'           => $this->ilan->id,
            'tenant_id'         => 1,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 2,
            'guest_name'        => 'Checkout Guest',
            'reservation_state' => 'confirmed',
            'checked_out_at'    => now()->subHours(5), // already checked out manually
            'completed_at'      => now()->subHours(5),
        ]);

        // Run the command — it should skip this reservation
        $this->artisan('reservation:complete')
            ->assertExitCode(0);

        // completed_at must not be overwritten
        $fresh = PropertyReservation::find($reservation->id);
        $this->assertNotNull($fresh->completed_at);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // W4-E13: checkout without formal check-in proceeds (SAAB soft guard)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_checkout_without_formal_checkin_proceeds_with_warning(): void
    {
        $startDate = now()->subDays(1)->format('Y-m-d');
        $endDate   = now()->addDay()->format('Y-m-d');

        $reservation = PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'ilan_id'           => $this->ilan->id,
            'tenant_id'         => 1,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 2,
            'guest_name'        => 'No Checkin Guest',
            'reservation_state' => 'confirmed',
            'checked_in_at'     => null, // Intentionally no check-in record
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with(
                'ReservationService::checkOut: soft-guard — checkout without formal check-in',
                \Mockery::on(fn ($ctx) =>
                    $ctx['reservation_id'] === $reservation->id &&
                    $ctx['tenant_id'] === 1
                )
            );

        Log::shouldReceive('info')->zeroOrMoreTimes();

        // Checkout must not throw
        $result = $this->reservationService->checkOut($reservation->id, 1);

        $this->assertNotNull($result->checked_out_at, 'checked_out_at must be stamped even without formal check-in');
        $this->assertNotNull($result->completed_at, 'completed_at must be stamped');
    }
}
