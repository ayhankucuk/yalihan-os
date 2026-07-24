<?php

namespace Tests\Feature\Calendar\Sprint19;

use App\Domain\Reservation\Events\ReservationCreated;
use App\Domain\Reservation\Events\ReservationDatesChanged;
use App\Domain\Reservation\Events\ReservationStateTransitioned;
use App\Domain\Shared\ValueObjects\DateRange;
use App\Enums\ReservationState;
use App\Listeners\Calendar\ProjectReservationOnUnifiedCalendar;
use App\Models\Calendar\UnifiedCalendarProjection;
use App\Models\Hermes\HermesEventLog;
use App\Models\Property;
use App\Models\PropertyWorkspace;
use App\Services\Calendar\UnifiedCalendarProjectionService;
use App\Services\Reservation\ConflictDetectionService;
use App\Services\Reservation\ReservationApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnifiedCalendarProjectionTest extends TestCase
{
    use RefreshDatabase;

    private ReservationApplicationService $reservationService;
    private ProjectReservationOnUnifiedCalendar $listener;
    private UnifiedCalendarProjectionService $projectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = new ReservationApplicationService(new ConflictDetectionService());
        $this->listener = new ProjectReservationOnUnifiedCalendar();
        $this->projectionService = new UnifiedCalendarProjectionService();
    }

    /**
     * Test creating reservation projects daily calendar entries and logs Event Store.
     */
    public function test_creating_reservation_projects_calendar_days_and_event_store(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Calendar Test Workspace',
            'code' => 'WS-CAL-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cal-1',
        ]);

        $reservation = $this->reservationService->createReservation($property, [
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-14', // 5 days: 10th, 11th, 12th, 13th, 14th
            'guest_name' => 'John Doe',
            'islem_tutari' => 10000.00,
        ]);

        $event = new ReservationCreated($reservation);
        $this->listener->handleCreated($event);

        // 1. Verify 5 daily projection rows created
        $count = UnifiedCalendarProjection::where('tenant_id', 1)
            ->where('property_id', $property->id)
            ->where('reservation_id', $reservation->id)
            ->count();

        $this->assertEquals(5, $count);

        // 2. Verify checkin and checkout flags
        $checkinRow = UnifiedCalendarProjection::where('calendar_date', '2026-09-10')->first();
        $this->assertTrue($checkinRow->is_checkin_day);
        $this->assertFalse($checkinRow->is_checkout_day);

        $checkoutRow = UnifiedCalendarProjection::where('calendar_date', '2026-09-14')->first();
        $this->assertFalse($checkoutRow->is_checkin_day);
        $this->assertTrue($checkoutRow->is_checkout_day);

        // 3. Verify Event Store Log in hermes_event_logs
        $log = HermesEventLog::where('tenant_id', 1)
            ->where('projection_type', 'UnifiedCalendarProjection')
            ->where('source_event_id', $event->eventId)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('ReservationCreated', $log->event_name);
    }

    /**
     * Test state transition updates projection status or removes entries on cancellation.
     */
    public function test_state_transition_updates_or_removes_calendar_projections(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Transition Workspace',
            'code' => 'WS-CAL-02',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cal-2',
        ]);

        $reservation = $this->reservationService->createReservation($property, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
            'pending' => true,
        ]);

        $createEvent = new ReservationCreated($reservation);
        $this->listener->handleCreated($createEvent);

        // Pending state -> status PENDING_APPROVAL
        $pendingRow = UnifiedCalendarProjection::where('reservation_id', $reservation->id)->first();
        $this->assertEquals('PENDING_APPROVAL', $pendingRow->status);

        // Transition to CONFIRMED
        $confirmedRes = $this->reservationService->confirmReservation($reservation);
        $transEvent = new ReservationStateTransitioned($confirmedRes, ReservationState::PENDING, ReservationState::CONFIRMED);
        $this->listener->handleTransition($transEvent);

        $confirmedRow = UnifiedCalendarProjection::where('reservation_id', $reservation->id)->first();
        $this->assertEquals('BOOKED', $confirmedRow->status);

        // Transition to CANCELLED -> removes calendar rows
        $cancelledRes = $this->reservationService->cancelReservation($confirmedRes);
        $cancelEvent = new ReservationStateTransitioned($cancelledRes, ReservationState::CONFIRMED, ReservationState::CANCELLED);
        $this->listener->handleTransition($cancelEvent);

        $remainingCount = UnifiedCalendarProjection::where('reservation_id', $reservation->id)->count();
        $this->assertEquals(0, $remainingCount);
    }

    /**
     * Test modifying dates cleans old projected days and inserts new days.
     */
    public function test_modifying_dates_updates_calendar_projections(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Modify Workspace',
            'code' => 'WS-CAL-03',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cal-3',
        ]);

        $reservation = $this->reservationService->createReservation($property, [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]);

        $createEvent = new ReservationCreated($reservation);
        $this->listener->handleCreated($createEvent);

        // Modify dates to 2026-11-05 to 2026-11-07
        $oldRange = new DateRange('2026-11-01', '2026-11-03');
        $newRange = new DateRange('2026-11-05', '2026-11-07');

        $updatedRes = $this->reservationService->modifyReservationDates($reservation, $newRange);
        $dateEvent = new ReservationDatesChanged($updatedRes, $oldRange, $newRange);
        $this->listener->handleDatesChanged($dateEvent);

        // Verify old dates removed
        $oldDateCount = UnifiedCalendarProjection::where('reservation_id', $reservation->id)
            ->where('calendar_date', '2026-11-01')
            ->count();
        $this->assertEquals(0, $oldDateCount);

        // Verify new dates created
        $newDateCount = UnifiedCalendarProjection::where('reservation_id', $reservation->id)
            ->where('calendar_date', '2026-11-05')
            ->count();
        $this->assertEquals(1, $newDateCount);
    }

    /**
     * Test replay service deterministically rebuilds unified calendar projection.
     */
    public function test_replay_service_rebuilds_calendar_projections(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Replay Workspace',
            'code' => 'WS-CAL-04',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cal-4',
        ]);

        $res1 = $this->reservationService->createReservation($property, [
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
        ]);

        $res2 = $this->reservationService->createReservation($property, [
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-12',
        ]);

        // Rebuild calendar projections via UnifiedCalendarProjectionService
        $projectedRows = $this->projectionService->rebuildForProperty($property);
        $this->assertEquals(6, $projectedRows); // 3 days + 3 days

        $days = $this->projectionService->getCalendarDaysForProperty($property, '2026-12-01', '2026-12-31');
        $this->assertCount(6, $days);
    }
}
