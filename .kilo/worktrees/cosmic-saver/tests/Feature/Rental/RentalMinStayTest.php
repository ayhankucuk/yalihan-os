<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\User;
use App\Services\ReservationService;
use Tests\TestCase;
use Exception;

class RentalMinStayTest extends TestCase
{

    public function test_minimum_stay_backend_enforcement()
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'user_id' => $user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 3, // Required minimum 3 nights
        ]);

        $service = app(ReservationService::class);
        $guestData = ['guest_name' => 'Min Stay Test', 'guest_count' => 1];

        // Ensure 2 nights fails
        $failed = false;
        try {
            $service->createReservation($ilan->id, '2026-10-01', '2026-10-03', $guestData, $user->id); // 2 nights
        } catch (Exception $e) {
            $failed = true;
            $this->assertStringContainsString('Minimum stay is 3 nights', $e->getMessage());
        }
        $this->assertTrue($failed, "Failed to enforce minimum stay of 3 nights on 2 night request.");

        // Ensure 3 nights succeeds
        $reservation = $service->createReservation($ilan->id, '2026-10-01', '2026-10-04', $guestData, $user->id); // 3 nights
        $this->assertNotNull($reservation->id);
    }
}
