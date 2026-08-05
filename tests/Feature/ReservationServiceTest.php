<?php

namespace Tests\Feature;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use Exception;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{

    protected ReservationService $reservationService;
    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservationService = app(ReservationService::class);
        $this->user = User::factory()->create();

        $this->ilan = clone Ilan::factory()->create([
            'rental_enabled' => true,
            'min_stay_nights' => 3,
        ]);
    }

    public function test_fails_if_min_stay_not_met()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Minimum stay is 3 nights.");

        $this->reservationService->createReservation(
            $this->ilan->id,
            now()->format('Y-m-d'),
            now()->addDays(2)->format('Y-m-d'),
            ['guest_name' => 'John Doe'],
            $this->user->id
        );
    }

    public function test_reservation_creates_as_pending_and_blocks_availability_after_confirm()
    {
        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(9)->format('Y-m-d'); // 4 gece

        // Phase 1: createReservation() returns PENDING — no availability blocks yet
        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $start,
            $end,
            ['guest_name' => 'John Valid'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $reservation->reservation_state);
        $this->assertEquals(4, $reservation->nights);

        // No blocks yet
        $this->assertEquals(0, PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('is_available', false)
            ->where('reservation_id', $reservation->id)
            ->count());

        // Phase 1: confirmReservation() blocks availability
        $confirmed = $this->reservationService->confirmReservation($reservation->id);

        $this->assertEquals(ReservationState::CONFIRMED, $confirmed->reservation_state);

        $availabilities = PropertyAvailability::where('property_id', $this->ilan->id)
            ->whereIn('date', [
                now()->addDays(5)->format('Y-m-d'),
                now()->addDays(6)->format('Y-m-d'),
                now()->addDays(7)->format('Y-m-d'),
                now()->addDays(8)->format('Y-m-d'),
            ])->get();

        $this->assertCount(4, $availabilities);
        foreach ($availabilities as $avail) {
            $this->assertFalse((bool) $avail->is_available);
            $this->assertEquals('internal', $avail->source_system);
            $this->assertEquals($reservation->id, $avail->reservation_id);
        }
    }

    public function test_fails_if_dates_overlap_with_airbnb()
    {
        $start = now()->addDays(10)->format('Y-m-d');

        // Block a date directly in availability (airbnb external block)
        PropertyAvailability::create([
            'property_id'   => $this->ilan->id,
            'date'          => $start,
            'is_available'  => false,
            'source_system' => 'airbnb_ical',
            'block_reason'  => 'airbnb_busy',
        ]);

        // Phase 1: createReservation does NOT check availability — only conflict check on reservations
        // The airbnb availability block will be checked at confirmReservation() time
        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $start,
            now()->addDays(14)->format('Y-m-d'),
            ['guest_name' => 'John Overlap'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::PENDING, $reservation->reservation_state);

        // Confirm should fail because airbnb block exists
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Dates are not available");

        $this->reservationService->confirmReservation($reservation->id);
    }

    public function test_cancel_releases_internal_blocks_but_keeps_airbnb()
    {
        $start = now()->addDays(20)->format('Y-m-d');
        $end = now()->addDays(24)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id, $start, $end, ['guest_name' => 'Cancel Test'], $this->user->id
        );

        // Confirm to create availability blocks
        $this->reservationService->confirmReservation($reservation->id);

        // Make one block look like an airbnb block
        $midDate = now()->addDays(21)->format('Y-m-d');
        PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('date', $midDate)
            ->update(['source_system' => 'airbnb_ical']);

        $this->reservationService->cancelReservation($reservation->id);

        $this->assertEquals(ReservationState::CANCELLED, $reservation->fresh()->reservation_state);

        // Internal block (start date) should be released
        $firstAvail = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('date', $start)
            ->first();
        $this->assertTrue((bool) $firstAvail->is_available);

        // Airbnb block should remain
        $midAvail = PropertyAvailability::where('property_id', $this->ilan->id)
            ->where('date', $midDate)
            ->first();
        $this->assertFalse((bool) $midAvail->is_available);
    }
}
