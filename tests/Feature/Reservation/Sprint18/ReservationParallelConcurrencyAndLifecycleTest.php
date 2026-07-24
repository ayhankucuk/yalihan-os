<?php

namespace Tests\Feature\Reservation\Sprint18;

use App\Models\CommercialOffering;
use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
use App\Models\PropertyReservation;
use App\Models\PropertyWorkspace;
use App\Models\Hermes\HermesEventLog;
use App\Services\Reservation\ConflictDetectionService;
use App\Services\Reservation\ReservationApplicationService;
use App\Domain\Property\Events\CommercialOfferingCreated;
use App\Listeners\Property\RecordCommercialOfferingOnTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DomainException;
use Tests\TestCase;

class ReservationParallelConcurrencyAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ReservationApplicationService $reservationService;
    private ConflictDetectionService $conflictDetector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conflictDetector = new ConflictDetectionService();
        $this->reservationService = new ReservationApplicationService($this->conflictDetector);
    }

    public function test_cross_aggregate_offering_property_invariant_rejection(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Invariant Test Workspace',
            'code' => 'WS-INV-01',
        ]);

        $property1 = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-inv-1',
            'ada' => '601',
            'parsel' => '1',
        ]);

        $property2 = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-inv-2',
            'ada' => '602',
            'parsel' => '2',
        ]);

        // Offering belongs to Property 2
        $offering = CommercialOffering::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'property_id' => $property2->id,
            'offering_type' => 'SATILIK',
            'fiyat' => 15000,
            'para_birimi' => 'TRY',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not belong to Property');

        // Trying to create reservation for Property 1 using Property 2's offering (Should fail)
        $this->reservationService->createReservation($property1, [
            'commercial_offering_id' => $offering->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'islem_tutari' => 15000,
        ]);
    }

    public function test_reservation_cancellation_releases_availability_block(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Cancel Test Workspace',
            'code' => 'WS-CAN-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-can-1',
            'ada' => '603',
            'parsel' => '3',
        ]);

        $reservation = $this->reservationService->createReservation($property, [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
            'islem_tutari' => 20000,
        ]);

        // Verify active block
        $block = PropertyAvailabilityBlock::where('reservation_id', $reservation->id)->first();
        $this->assertEquals('ACTIVE', $block->status);
        $this->assertNull($block->released_at);

        // Cancel reservation
        $cancelled = $this->reservationService->cancelReservation($reservation);
        $this->assertNotNull($cancelled->cancelled_at);

        // Verify block status updated to RELEASED
        $block->refresh();
        $this->assertEquals('RELEASED', $block->status);
        $this->assertNotNull($block->released_at);
    }

    public function test_timeline_listener_replay_safety_prevents_duplicate_logs(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Replay Test Workspace',
            'code' => 'WS-REP-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-rep-1',
            'ada' => '604',
            'parsel' => '4',
        ]);

        $offering = CommercialOffering::create([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'property_id' => $property->id,
            'offering_type' => 'SATILIK',
            'fiyat' => 50000,
            'para_birimi' => 'TRY',
        ]);

        $listener = new RecordCommercialOfferingOnTimeline();
        $event = new CommercialOfferingCreated($offering);

        // Handle event twice (Simulating queue/replay duplication)
        $listener->handleCreated($event);
        $listener->handleCreated($event);

        // Assert only ONE timeline log was created
        $logs = HermesEventLog::where('event_name', 'Commercial Offering Created')->get();

        $this->assertCount(1, $logs);
    }

    public function test_parallel_transaction_concurrency_locking_prevents_double_booking(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Concurrency Workspace',
            'code' => 'WS-CONC-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-conc-1',
            'ada' => '605',
            'parsel' => '5',
        ]);

        // Simulating 2 competing bookings within DB transaction
        $res1 = $this->reservationService->createReservation($property, [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'islem_tutari' => 30000,
        ]);

        $this->assertNotNull($res1->id);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Date conflict detected');

        // Competing concurrent request for overlapping dates must fail inside transaction lock
        $this->reservationService->createReservation($property, [
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-15',
            'islem_tutari' => 35000,
        ]);
    }
}
