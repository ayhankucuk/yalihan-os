<?php

namespace Tests\Feature\Reservation\Sprint18;

use App\Models\Property;
use App\Models\PropertyAvailabilityBlock;
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

class ReservationConcurrencyAndIdempotencyTest extends TestCase
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

    public function test_same_day_checkout_and_checkin_is_allowed(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Same Day Checkout/Checkin Workspace',
            'code' => 'WS-SAMEDAY-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-sameday-1',
            'ada' => '501',
            'parsel' => '1',
        ]);

        // First guest: Aug 1 to Aug 5 (checkout Aug 5)
        $res1 = $this->reservationService->createReservation($property, [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'islem_tutari' => 10000.00,
        ]);

        // Second guest: Aug 5 to Aug 10 (checkin Aug 5 - MUST BE ALLOWED under [start, end) half-open interval rule)
        $res2 = $this->reservationService->createReservation($property, [
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-10',
            'islem_tutari' => 12000.00,
        ]);

        $this->assertNotNull($res1->id);
        $this->assertNotNull($res2->id);

        $blocks = PropertyAvailabilityBlock::where('property_id', $property->id)->get();
        $this->assertCount(2, $blocks);
    }

    public function test_idempotency_key_replay_returns_same_reservation_without_duplicate_blocks(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Idempotency Workspace',
            'code' => 'WS-IDEMP-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-idemp-1',
            'ada' => '502',
            'parsel' => '2',
        ]);

        $idempotencyKey = 'idemp-req-unique-99';

        $res1 = $this->reservationService->createReservation($property, [
            'idempotency_key' => $idempotencyKey,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'islem_tutari' => 15000.00,
        ]);

        // Second request with SAME idempotency_key
        $res2 = $this->reservationService->createReservation($property, [
            'idempotency_key' => $idempotencyKey,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'islem_tutari' => 15000.00,
        ]);

        $this->assertEquals($res1->id, $res2->id);

        // Verify only 1 availability block was created
        $blocks = PropertyAvailabilityBlock::where('property_id', $property->id)->get();
        $this->assertCount(1, $blocks);
    }

    public function test_overlapping_date_range_is_rejected(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'Overlap Workspace',
            'code' => 'WS-OVERLAP-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-overlap-1',
            'ada' => '503',
            'parsel' => '3',
        ]);

        $this->reservationService->createReservation($property, [
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-20',
            'islem_tutari' => 25000.00,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Date conflict detected');

        // Overlapping request: Sept 15 to Sept 25
        $this->reservationService->createReservation($property, [
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-25',
            'islem_tutari' => 20000.00,
        ]);
    }
}
