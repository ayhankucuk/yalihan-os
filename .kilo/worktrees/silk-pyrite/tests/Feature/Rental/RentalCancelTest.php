<?php

namespace Tests\Feature\Rental;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\User;
use App\Services\ReservationService;
use Tests\TestCase;
use Exception;

class RentalCancelTest extends TestCase
{

    public function test_cancel_frees_up_availability()
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'user_id' => $user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        $service = app(ReservationService::class);
        $checkIn = '2026-09-01';
        $checkOut = '2026-09-05';
        $guestData = ['guest_name' => 'Cancel Test', 'guest_count' => 1];

        // 1. Create Reservation
        $reservation = $service->createReservation($ilan->id, $checkIn, $checkOut, $guestData, $user->id);

        $this->assertEquals(ReservationState::CONFIRMED, $reservation->reservation_state);
        $this->assertDatabaseHas('property_availabilities', [
            'property_id' => $ilan->id,
            'date' => '2026-09-01',
            'is_available' => false,
            'reservation_id' => $reservation->id
        ]);

        // 2. Cancel it
        $service->cancelReservation($reservation->id);

        $reservation->refresh();
        $this->assertEquals(ReservationState::CANCELLED, $reservation->reservation_state);

        // 3. Verify availability is reopened
        $this->assertDatabaseHas('property_availabilities', [
            'property_id' => $ilan->id,
            'date' => '2026-09-01',
            'is_available' => true,
            'reservation_id' => null
        ]);

        // 4. Try to book same dates again to ensure it works
        $reservation2 = $service->createReservation($ilan->id, $checkIn, $checkOut, $guestData, $user->id);
        $this->assertNotNull($reservation2->id);
    }
}
