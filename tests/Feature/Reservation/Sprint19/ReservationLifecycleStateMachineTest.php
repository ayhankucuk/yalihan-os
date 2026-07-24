<?php

namespace Tests\Feature\Reservation\Sprint19;

use App\Domain\Reservation\Events\ReservationDatesChanged;
use App\Domain\Reservation\Events\ReservationStateTransitioned;
use App\Domain\Shared\ValueObjects\DateRange;
use App\Enums\ReservationState;
use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
use App\Models\PropertyReservation;
use App\Models\PropertyWorkspace;
use App\Services\Reservation\ConflictDetectionService;
use App\Services\Reservation\ReservationApplicationService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservationLifecycleStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private ReservationApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationApplicationService(new ConflictDetectionService());
    }

    /**
     * Test canonical sequential lifecycle transitions: PENDING -> CONFIRMED -> CHECKED_IN -> CHECKED_OUT -> CLOSED
     */
    public function test_canonical_sequential_lifecycle_transitions(): void
    {
        Event::fake([ReservationStateTransitioned::class]);

        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'State Machine Workspace',
            'code' => 'WS-SM-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-sm-1',
        ]);

        // 1. Create Pending Reservation
        $reservation = $this->service->createReservation($property, [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'guest_name' => 'Lifecycle Guest',
            'pending' => true,
        ]);

        $this->assertEquals(ReservationState::PENDING, $reservation->reservation_state);

        // 2. Confirm
        $reservation = $this->service->confirmReservation($reservation);
        $this->assertEquals(ReservationState::CONFIRMED, $reservation->reservation_state);
        $this->assertNotNull($reservation->confirmed_at);

        // 3. Check-In
        $reservation = $this->service->checkIn($reservation);
        $this->assertEquals(ReservationState::CHECKED_IN, $reservation->reservation_state);

        // 4. Check-Out
        $reservation = $this->service->checkOut($reservation);
        $this->assertEquals(ReservationState::CHECKED_OUT, $reservation->reservation_state);

        // 5. Close
        $reservation = $this->service->closeReservation($reservation);
        $this->assertEquals(ReservationState::CLOSED, $reservation->reservation_state);

        Event::assertDispatched(ReservationStateTransitioned::class, 4);
    }

    /**
     * Test forbidden transitions are rejected by DomainException
     */
    public function test_forbidden_transitions_are_rejected(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Forbidden Transition Workspace',
            'code' => 'WS-SM-02',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-sm-2',
        ]);

        $reservation = $this->service->createReservation($property, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
        ]); // CONFIRMED

        // Attempt invalid transition: CONFIRMED -> CLOSED
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Forbidden reservation state transition from confirmed to closed.');

        $this->service->transitionState($reservation, ReservationState::CLOSED);
    }

    /**
     * Test side transitions: PENDING -> EXPIRED and CONFIRMED -> CANCELLED release blocks
     */
    public function test_cancellation_and_expiration_release_availability_blocks(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Release Test Workspace',
            'code' => 'WS-SM-03',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-sm-3',
        ]);

        $pendingRes = $this->service->createReservation($property, [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'pending' => true,
        ]);

        // Expire pending
        $this->service->expireReservation($pendingRes);
        $this->assertEquals(ReservationState::EXPIRED, $pendingRes->fresh()->reservation_state);

        $block = PropertyAvailabilityBlock::where('reservation_id', $pendingRes->id)->first();
        $this->assertEquals('RELEASED', $block->status);
        $this->assertNotNull($block->released_at);
    }

    /**
     * Test date modification updates availability blocks and dispatches event
     */
    public function test_modify_reservation_dates_updates_availability_block(): void
    {
        Event::fake([ReservationDatesChanged::class]);

        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Date Modify Workspace',
            'code' => 'WS-SM-04',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-sm-4',
        ]);

        $reservation = $this->service->createReservation($property, [
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);

        $newRange = new DateRange('2026-12-03', '2026-12-08');
        $updatedRes = $this->service->modifyReservationDates($reservation, $newRange);

        $this->assertEquals('2026-12-03', $updatedRes->start_date);
        $this->assertEquals('2026-12-08', $updatedRes->end_date);
        $this->assertEquals(5, $updatedRes->nights);

        $block = PropertyAvailabilityBlock::where('reservation_id', $reservation->id)->first();
        $this->assertEquals('2026-12-03 00:00:00', $block->starts_at);
        $this->assertEquals('2026-12-08 00:00:00', $block->ends_at);

        Event::assertDispatched(ReservationDatesChanged::class);
    }
}
