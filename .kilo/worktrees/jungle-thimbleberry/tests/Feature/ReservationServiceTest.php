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

    public function test_reservation_creates_successfully_and_blocks_availability()
    {
        $start = now()->addDays(5)->format('Y-m-d');
        $end = now()->addDays(9)->format('Y-m-d'); // 4 gece

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id,
            $start,
            $end,
            ['guest_name' => 'John Valid'],
            $this->user->id
        );

        $this->assertEquals(ReservationState::CONFIRMED, $reservation->reservation_state);
        $this->assertEquals(4, $reservation->nights);

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

        PropertyAvailability::create([
            'property_id' => $this->ilan->id,
            'date' => $start,
            'is_available' => false,
            'source_system' => 'airbnb_ical',
            'block_reason' => 'airbnb_busy'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Dates are not available");

        $this->reservationService->createReservation(
            $this->ilan->id,
            $start,
            now()->addDays(14)->format('Y-m-d'),
            ['guest_name' => 'John Overlap'],
            $this->user->id
        );
    }

    public function test_cancel_releases_internal_blocks_but_keeps_airbnb()
    {
        $start = now()->addDays(20)->format('Y-m-d');
        $end = now()->addDays(24)->format('Y-m-d');

        $reservation = $this->reservationService->createReservation(
            $this->ilan->id, $start, $end, ['guest_name' => 'Cancel Test'], $this->user->id
        );

        // Edge case: Sistemin internal kaydını bozup Airbnb yapalım
        $midDate = now()->addDays(21)->format('Y-m-d');
        PropertyAvailability::where('date', $midDate)->update([
            'source_system' => 'airbnb_ical'
        ]);

        $this->reservationService->cancelReservation($reservation->id);

        $this->assertEquals(ReservationState::CANCELLED, $reservation->fresh()->reservation_state);

        $firstAvail = PropertyAvailability::where('date', $start)->first();
        $this->assertTrue((bool) $firstAvail->is_available);

        $midAvail = PropertyAvailability::where('date', $midDate)->first();
        $this->assertFalse((bool) $midAvail->is_available);
    }
}
