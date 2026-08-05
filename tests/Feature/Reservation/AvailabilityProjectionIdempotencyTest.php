<?php

namespace Tests\Feature\Reservation;

use App\Contracts\Property\AvailabilityProjectionContract;
use App\Events\Reservation\ReservationCancelledEvent;
use App\Events\Reservation\ReservationConfirmedEvent;
use App\Listeners\Reservation\ProjectConfirmedReservationListener;
use App\Listeners\Reservation\ReleaseCancelledReservationListener;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\Tenant;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RESERVATION_CORE Phase 2: E02 — Idempotency
 *
 * Başarı sorusu (SAAB):
 * "Aynı confirm veya cancel event'i birden fazla kez işlendiğinde
 *  sistem tek ve doğru availability sonucunu koruyor mu?"
 *
 * Test matrix:
 * E02.1 confirm_event_processed_three_times_creates_one_projection
 * E02.2 cancel_event_processed_three_times_is_safe
 * E02.3 listener_retry_does_not_duplicate_projection
 * E02.4 projection_identity_is_deterministic
 * E02.5 concurrent_confirm_produces_single_projection
 */
class AvailabilityProjectionIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityProjectionContract $projection;
    protected ReservationService $reservationService;
    protected Tenant $tenant;
    protected Ilan $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projection        = app(AvailabilityProjectionContract::class);
        $this->reservationService = app(ReservationService::class);

        $this->tenant = Tenant::create([
            'name'   => 'E02 Landlord',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->property = Ilan::create([
            'baslik'          => 'E02 Villa',
            'fiyat'           => 1000,
            'para_birimi'     => 'TRY',
            'yayin_durumu'    => 'yayinda',
            'aktiflik_durumu' => true,
            'tenant_id'       => $this->tenant->id,
            'rental_enabled'  => true,
            'min_stay_nights' => 1,
        ]);

        // Force tenant_id via raw DB (bypasses BelongsToTenant scope in test env)
        DB::table('ilanlar')
            ->where('id', $this->property->id)
            ->update(['tenant_id' => $this->tenant->id]);
    }

    // =========================================================================
    // E02.1 — confirm 3x creates exactly 1 projection
    // =========================================================================

    /** @test */
    public function confirm_event_processed_three_times_creates_one_projection(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2029-01-10';
        $endDate    = '2029-01-13'; // 3 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 3,
            'guest_name'        => 'E02 Guest',
            'reservation_state' => 'pending',
        ]);

        // Process confirm event 3 times
        for ($i = 0; $i < 3; $i++) {
            $result = $this->projection->projectConfirm(
                $reservation->id,
                $tenantId,
                $propertyId,
                $startDate,
                $endDate
            );
            $this->assertTrue($result['success'], "Call #{$i} must succeed");
        }

        // Exactly 3 availability rows — no duplicates
        $count = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->count();

        $this->assertEquals(3, $count,
            'Three identical confirm calls must produce exactly 3 daily blocks (no duplicates)');

        // All must be blocked
        $blockedCount = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('is_available', false)
            ->count();

        $this->assertEquals(3, $blockedCount);
    }

    // =========================================================================
    // E02.2 — cancel 3x is safe
    // =========================================================================

    /** @test */
    public function cancel_event_processed_three_times_is_safe(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2029-02-01';
        $endDate    = '2029-02-04'; // 3 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 3,
            'guest_name'        => 'E02 Cancel Guest',
            'reservation_state' => 'pending',
        ]);

        // First confirm
        $this->projection->projectConfirm(
            $reservation->id, $tenantId, $propertyId, $startDate, $endDate
        );

        // Process cancel 3 times — all must succeed without error
        for ($i = 0; $i < 3; $i++) {
            $result = $this->projection->projectCancel(
                $reservation->id,
                $tenantId,
                $startDate,
                $endDate
            );
            $this->assertTrue($result['success'], "Cancel call #{$i} must succeed");
        }

        // All rows must be freed
        $blockedCount = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('is_available', false)
            ->count();

        $this->assertEquals(0, $blockedCount,
            'After 3x cancel, no blocked rows must remain for this reservation');
    }

    // =========================================================================
    // E02.3 — listener retry does not duplicate projection
    // =========================================================================

    /** @test */
    public function listener_retry_does_not_duplicate_projection(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2029-03-05';
        $endDate    = '2029-03-08'; // 3 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 3,
            'guest_name'        => 'E02 Retry Guest',
            'reservation_state' => 'pending',
        ]);

        $event = new ReservationConfirmedEvent(
            reservationId: $reservation->id,
            tenantId:      $tenantId,
            propertyId:    $propertyId,
            startDate:     $startDate,
            endDate:       $endDate,
            nights:        3,
            guestName:     'E02 Retry Guest',
        );

        // Simulate queue retry: listener handles the same event 3 times
        $listener = app(ProjectConfirmedReservationListener::class);
        $listener->handle($event);
        $listener->handle($event);
        $listener->handle($event);

        // Exactly 3 daily blocks (1 per night) — no multiplication
        $count = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->count();

        $this->assertEquals(3, $count,
            'Listener retry (3x handle) must produce exactly 3 daily blocks — no duplication');
    }

    // =========================================================================
    // E02.4 — projection identity is deterministic
    // =========================================================================

    /** @test */
    public function projection_identity_is_deterministic(): void
    {
        $reservationId = 77;
        $date          = '2029-04-01';

        // Same inputs always produce same key
        $key1 = $this->projection->getProjectionKey($reservationId, $date);
        $key2 = $this->projection->getProjectionKey($reservationId, $date);
        $key3 = $this->projection->getProjectionKey($reservationId, $date);

        $this->assertEquals($key1, $key2);
        $this->assertEquals($key2, $key3);
        $this->assertEquals("reservation:77:2029-04-01", $key1);

        // Different reservation → different key
        $keyOtherRes = $this->projection->getProjectionKey(78, $date);
        $this->assertNotEquals($key1, $keyOtherRes);

        // Different date → different key
        $keyOtherDate = $this->projection->getProjectionKey($reservationId, '2029-04-02');
        $this->assertNotEquals($key1, $keyOtherDate);

        // Verify projected rows carry the canonical key
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2029-04-10';
        $endDate    = '2029-04-12'; // 2 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 2,
            'guest_name'        => 'Identity Guest',
            'reservation_state' => 'pending',
        ]);

        $this->projection->projectConfirm(
            $reservation->id, $tenantId, $propertyId, $startDate, $endDate
        );

        $rows = PropertyAvailability::where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->get();

        foreach ($rows as $row) {
            $expectedKey = $this->projection->getProjectionKey(
                $reservation->id,
                \Carbon\Carbon::parse($row->date)->format('Y-m-d')
            );
            $this->assertEquals($expectedKey, $row->idempotency_key,
                "Row {$row->date} must carry canonical projection key");
        }
    }

    // =========================================================================
    // E02.5 — concurrent confirm produces single projection
    // =========================================================================

    /** @test */
    public function concurrent_confirm_produces_single_projection(): void
    {
        $propertyId = $this->property->id;
        $tenantId   = $this->tenant->id;
        $startDate  = '2029-05-01';
        $endDate    = '2029-05-04'; // 3 nights

        $reservation = PropertyReservation::create([
            'tenant_id'         => $tenantId,
            'property_id'       => $propertyId,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'nights'            => 3,
            'guest_name'        => 'Concurrent Guest',
            'reservation_state' => 'pending',
        ]);

        // Simulate concurrent calls by calling projectConfirm twice within
        // the same test transaction. lockForUpdate in the service serializes
        // concurrent DB access — the second call must detect the first's rows.
        $result1 = $this->projection->projectConfirm(
            $reservation->id, $tenantId, $propertyId, $startDate, $endDate
        );
        $result2 = $this->projection->projectConfirm(
            $reservation->id, $tenantId, $propertyId, $startDate, $endDate
        );

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertTrue($result2['idempotent'],
            'Second concurrent confirm must be detected as idempotent');

        // Single projection — exactly 3 rows
        $count = PropertyAvailability::where('property_id', $propertyId)
            ->where('reservation_id', $reservation->id)
            ->where('source_system', 'internal')
            ->count();

        $this->assertEquals(3, $count,
            'Concurrent confirm calls must produce exactly 3 daily blocks');

        // isProjectionComplete must confirm the projection is whole
        $complete = $this->projection->isProjectionComplete(
            $reservation->id, $tenantId, $startDate, $endDate
        );
        $this->assertTrue($complete,
            'isProjectionComplete must return true after single projection is written');
    }
}
