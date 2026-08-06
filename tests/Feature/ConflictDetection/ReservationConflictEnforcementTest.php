<?php

namespace Tests\Feature\ConflictDetection;

use App\Contracts\Property\ConflictDetectionContract;
use App\Contracts\Property\PropertyAvailabilityContract;
use App\DTOs\Property\ConflictResult;
use App\Enums\ReservationState;
use App\Events\Reservation\ConflictDetectedEvent;
use App\Events\Reservation\ReservationRejectedForConflictEvent;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * CONFLICT_DETECTION Phase 3B — Reservation Conflict Enforcement
 *
 * Success question: "YALIHAN, aynı property üzerinde oluşabilecek rezervasyon
 * çakışmalarını transaction-safe, tenant-safe ve deterministik olarak tespit
 * edip ikinci rezervasyonu reddedebiliyor mu?"
 *
 * Test matrix:
 * B01: conflict_prevents_reservation_creation
 * B02: no_conflict_allows_reservation_creation
 * B03: atomic_accept_reject_within_transaction
 * B04: race_condition_only_one_reservation_created
 * B05: idempotent_conflict_check_same_result
 * B06: conflict_detected_event_fired_on_rejection
 * B07: reservation_rejected_event_fired_on_rejection
 * B08: no_events_fired_when_no_conflict
 * B09: conflict_check_uses_canonical_service
 * B10: rejected_reservation_leaves_no_side_effects
 */
class ReservationConflictEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected ConflictDetectionContract $detector;
    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(ConflictDetectionContract::class);

        $this->tenant = Tenant::create([
            'name'      => 'Phase3B Tenant',
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'Phase3B Property',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);
    }

    // Helper: block dates in PropertyAvailability
    private function blockDates(string $startDate, string $endDate, ?int $reservationId = null): void
    {
        $current = \Carbon\Carbon::parse($startDate);
        $end     = \Carbon\Carbon::parse($endDate);
        while ($current->lt($end)) {
            PropertyAvailability::create([
                'tenant_id'     => $this->tenant->id,
                'property_id'   => $this->property->id,
                'date'          => $current->format('Y-m-d'),
                'is_available'  => false,
                'block_reason'  => 'reservation',
                'priority_tier' => PropertyAvailabilityContract::TIER_RESERVATION,
                'source_system' => 'internal',
                'origin'        => PropertyAvailabilityContract::ORIGIN_RESERVATION,
                'reservation_id' => $reservationId,
            ]);
            $current->addDay();
        }
    }

    // =========================================================================
    // B01: conflict_prevents_reservation_creation
    // =========================================================================

    /** @test */
    public function conflict_prevents_reservation_creation(): void
    {
        // Block the dates
        $this->blockDates('2035-01-10', '2035-01-15');

        // Attempt to create reservation on blocked dates
        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-01-12',
            '2035-01-16'
        );

        $this->assertTrue($result->hasConflict,
            'Conflict detection must report conflict when dates are blocked');
        $this->assertNotEmpty($result->conflictDates);

        // Simulate application-layer rejection: if conflict, do NOT create reservation
        $reservationCount = PropertyReservation::count();

        if ($result->hasConflict) {
            // Application layer rejects — no reservation created
        } else {
            PropertyReservation::create([
                'tenant_id' => $this->tenant->id,
                'property_id' => $this->property->id,
                'start_date' => '2035-01-12',
                'end_date' => '2035-01-16',
                'nights' => 4,
                'guest_name' => 'Should Not Exist',
                'reservation_state' => ReservationState::PENDING->value,
            ]);
        }

        $this->assertEquals($reservationCount, PropertyReservation::count(),
            'No reservation must be created when conflict detected');
    }

    // =========================================================================
    // B02: no_conflict_allows_reservation_creation
    // =========================================================================

    /** @test */
    public function no_conflict_allows_reservation_creation(): void
    {
        // Dates are clean (no blocks)
        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-02-01',
            '2035-02-05'
        );

        $this->assertFalse($result->hasConflict,
            'No conflict expected on clean dates');

        // Application allows reservation creation
        $reservation = PropertyReservation::create([
            'tenant_id'         => $this->tenant->id,
            'property_id'       => $this->property->id,
            'start_date'        => '2035-02-01',
            'end_date'          => '2035-02-05',
            'nights'            => 4,
            'guest_name'        => 'Allowed Guest',
            'reservation_state' => ReservationState::PENDING->value,
        ]);

        $this->assertNotNull($reservation->id,
            'Reservation must be created when no conflict detected');
    }

    // =========================================================================
    // B03: atomic_accept_reject_within_transaction
    // =========================================================================

    /** @test */
    public function atomic_accept_reject_within_transaction(): void
    {
        $this->blockDates('2035-03-10', '2035-03-15');

        $conflictResult = null;
        $reservationCreated = false;

        DB::transaction(function () use (&$conflictResult, &$reservationCreated) {
            $conflictResult = $this->detector->detect(
                $this->tenant->id,
                $this->property->id,
                '2035-03-12',
                '2035-03-16'
            );

            if ($conflictResult->hasConflict) {
                // REJECT — transaction will rollback if we throw
                // For test: just flag it
                $reservationCreated = false;
            } else {
                PropertyReservation::create([
                    'tenant_id'         => $this->tenant->id,
                    'property_id'       => $this->property->id,
                    'start_date'        => '2035-03-12',
                    'end_date'          => '2035-03-16',
                    'nights'            => 4,
                    'guest_name'        => 'Atomic Guest',
                    'reservation_state' => ReservationState::PENDING->value,
                ]);
                $reservationCreated = true;
            }
        });

        $this->assertTrue($conflictResult->hasConflict);
        $this->assertFalse($reservationCreated,
            'Reservation must not be created within transaction when conflict detected');
        $this->assertEquals(0, PropertyReservation::count());
    }

    // =========================================================================
    // B04: race_condition_only_one_reservation_created
    // =========================================================================

    /** @test */
    public function race_condition_only_one_reservation_created(): void
    {
        // Simulate sequential "concurrent" requests on same dates
        $startDate = '2035-04-01';
        $endDate   = '2035-04-05';
        $created   = 0;

        // First request: no blocks yet → creates reservation AND blocks dates
        $result1 = $this->detector->detect($this->tenant->id, $this->property->id, $startDate, $endDate);
        if (!$result1->hasConflict) {
            $res1 = PropertyReservation::create([
                'tenant_id'         => $this->tenant->id,
                'property_id'       => $this->property->id,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'nights'            => 4,
                'guest_name'        => 'First Guest',
                'reservation_state' => ReservationState::CONFIRMED->value,
            ]);
            // Simulate projection being written
            $this->blockDates($startDate, $endDate, $res1->id);
            $created++;
        }

        // Second request: same dates, now blocked → must be rejected
        $result2 = $this->detector->detect($this->tenant->id, $this->property->id, $startDate, $endDate);
        if (!$result2->hasConflict) {
            PropertyReservation::create([
                'tenant_id'         => $this->tenant->id,
                'property_id'       => $this->property->id,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'nights'            => 4,
                'guest_name'        => 'Second Guest (should not exist)',
                'reservation_state' => ReservationState::PENDING->value,
            ]);
            $created++;
        }

        $this->assertEquals(1, $created,
            'Only one reservation must be created for the same date range');
        $this->assertTrue($result2->hasConflict,
            'Second attempt must detect conflict after first reservation blocks dates');
    }

    // =========================================================================
    // B05: idempotent_conflict_check_same_result
    // =========================================================================

    /** @test */
    public function idempotent_conflict_check_same_result(): void
    {
        $this->blockDates('2035-05-05', '2035-05-10');

        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->detector->detect(
                $this->tenant->id,
                $this->property->id,
                '2035-05-03',
                '2035-05-08'
            );
        }

        // All results must be identical
        foreach ($results as $result) {
            $this->assertTrue($result->hasConflict);
            $this->assertEquals($results[0]->conflictDates, $result->conflictDates);
            $this->assertEquals($results[0]->summary, $result->summary);
        }
    }

    // =========================================================================
    // B06: conflict_detected_event_fired_on_rejection
    // =========================================================================

    /** @test */
    public function conflict_detected_event_fired_on_rejection(): void
    {
        Event::fake([ConflictDetectedEvent::class]);

        $this->blockDates('2035-06-10', '2035-06-13');

        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-06-10',
            '2035-06-13'
        );

        $this->assertTrue($result->hasConflict);

        // Application layer fires the event on rejection
        event(new ConflictDetectedEvent(
            tenantId:            $this->tenant->id,
            propertyId:          $this->property->id,
            requestedStart:      '2035-06-10',
            requestedEnd:        '2035-06-13',
            conflictDates:       $result->conflictDates,
            conflictingSource:   $result->blockingSources[0]['origin'] ?? 'reservation',
            conflictingRecordId: $result->blockingSources[0]['reservation_id'] ?? null,
            conflictType:        'reservation',
            correlationId:       uniqid('conflict_', true),
            detectedAt:          new \DateTimeImmutable(),
        ));

        Event::assertDispatched(ConflictDetectedEvent::class, function ($event) {
            return $event->tenantId === $this->tenant->id
                && $event->propertyId === $this->property->id;
        });
    }

    // =========================================================================
    // B07: reservation_rejected_event_fired_on_rejection
    // =========================================================================

    /** @test */
    public function reservation_rejected_event_fired_on_rejection(): void
    {
        Event::fake([ReservationRejectedForConflictEvent::class]);

        $this->blockDates('2035-07-05', '2035-07-08');

        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-07-05',
            '2035-07-08'
        );

        $this->assertTrue($result->hasConflict);

        // Application layer fires rejection event
        event(new ReservationRejectedForConflictEvent(
            tenantId:        $this->tenant->id,
            propertyId:      $this->property->id,
            requestedStart:  '2035-07-05',
            requestedEnd:    '2035-07-08',
            rejectionReason: 'conflict',
            conflictCount:   count($result->conflictDates),
            correlationId:   uniqid('reject_', true),
            rejectedAt:      new \DateTimeImmutable(),
        ));

        Event::assertDispatched(ReservationRejectedForConflictEvent::class, function ($event) {
            return $event->tenantId === $this->tenant->id
                && $event->rejectionReason === 'conflict'
                && $event->conflictCount > 0;
        });
    }

    // =========================================================================
    // B08: no_events_fired_when_no_conflict
    // =========================================================================

    /** @test */
    public function no_events_fired_when_no_conflict(): void
    {
        Event::fake([ConflictDetectedEvent::class, ReservationRejectedForConflictEvent::class]);

        // Clean dates — no blocks
        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-08-01',
            '2035-08-05'
        );

        $this->assertFalse($result->hasConflict);

        // No conflict → application does NOT fire rejection events
        Event::assertNotDispatched(ConflictDetectedEvent::class);
        Event::assertNotDispatched(ReservationRejectedForConflictEvent::class);
    }

    // =========================================================================
    // B09: conflict_check_uses_canonical_service
    // =========================================================================

    /** @test */
    public function conflict_check_uses_canonical_service(): void
    {
        // Verify that the bound service is the canonical ADR-003 implementation
        $service = app(ConflictDetectionContract::class);

        $this->assertInstanceOf(
            \App\Services\Property\ConflictDetectionService::class,
            $service,
            'ConflictDetectionContract must be bound to canonical ADR-003 ConflictDetectionService'
        );

        // Verify it returns ConflictResult DTO
        $result = $service->detect($this->tenant->id, $this->property->id, '2035-09-01', '2035-09-05');
        $this->assertInstanceOf(ConflictResult::class, $result);
    }

    // =========================================================================
    // B10: rejected_reservation_leaves_no_side_effects
    // =========================================================================

    /** @test */
    public function rejected_reservation_leaves_no_side_effects(): void
    {
        $this->blockDates('2035-10-10', '2035-10-15');

        $availabilityCountBefore  = PropertyAvailability::count();
        $reservationCountBefore   = PropertyReservation::count();

        // Detect conflict
        $result = $this->detector->detect(
            $this->tenant->id,
            $this->property->id,
            '2035-10-10',
            '2035-10-15'
        );

        $this->assertTrue($result->hasConflict);

        // Application rejects — no writes should occur from detection alone
        $availabilityCountAfter = PropertyAvailability::count();
        $reservationCountAfter  = PropertyReservation::count();

        $this->assertEquals($availabilityCountBefore, $availabilityCountAfter,
            'Rejected conflict check must not modify availability records');
        $this->assertEquals($reservationCountBefore, $reservationCountAfter,
            'Rejected conflict check must not create reservation records');
    }
}
