<?php

namespace Tests\Feature\Reservation\Sprint18;

use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\PropertyWorkspace;
use App\Models\WorkforceExecution;
use App\Services\Reservation\ConflictDetectionService;
use App\Services\Reservation\ReservationApplicationService;
use App\Domain\Reservation\Events\ReservationCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservationAvailabilityCoreTest extends TestCase
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

    public function test_reservation_creation_locks_availability_dates_and_dispatches_event(): void
    {
        Event::fake();

        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Reservation Core Workspace',
            'code' => 'WS-RES-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-res-1',
            'ada' => '401',
            'parsel' => '10',
        ]);

        $reservation = $this->reservationService->createReservation($property, [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'islem_tutari' => 20000.00,
            'currency' => 'TRY',
            'guest_name' => 'Ahmet Yılmaz',
        ]);

        $this->assertNotNull($reservation->id);
        $this->assertEquals(4, $reservation->nights);
        $this->assertEquals(20000.00, (float) $reservation->islem_tutari);

        // Verify Availability blocks created for 4 nights (Aug 1, 2, 3, 4)
        $blocks = PropertyAvailability::where('property_id', $property->id)->get();
        $this->assertCount(4, $blocks);
        foreach ($blocks as $block) {
            $this->assertFalse($block->is_available);
            $this->assertEquals('RESERVATION', $block->block_reason);
        }

        // Verify WorkforceExecution entry
        $execution = WorkforceExecution::where('aggregate_type', 'PropertyReservation')
            ->where('aggregate_id', $reservation->id)
            ->first();

        $this->assertNotNull($execution);
        $this->assertEquals('SUCCESS', $execution->execution_status);

        Event::assertDispatched(ReservationCreated::class);
    }

    public function test_date_conflict_detection_prevents_overlapping_reservation(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Conflict Workspace',
            'code' => 'WS-CONF-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-res-2',
            'ada' => '402',
            'parsel' => '11',
        ]);

        // First booking: Aug 10 to Aug 15
        $this->reservationService->createReservation($property, [
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-15',
            'islem_tutari' => 15000.00,
        ]);

        // Second booking with overlapping dates: Aug 12 to Aug 18 (Should fail)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Date conflict detected');

        $this->reservationService->createReservation($property, [
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-18',
            'islem_tutari' => 18000.00,
        ]);
    }
}
