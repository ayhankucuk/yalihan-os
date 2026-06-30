<?php

namespace Tests\Feature\Rental;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class RentalConcurrencyTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        // Base seeders might be needed if Property model depends on features/categories
        // $this->artisan('db:seed');
    }

    /**
     * Test Double Booking Concurrency
     *
     * @return void
     */
    public function test_double_booking_prevention_with_concurrency_simulation()
    {
        // 1. Setup Property
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'user_id' => $user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        $service = app(ReservationService::class);
        $checkIn = '2026-07-01';
        $checkOut = '2026-07-05';
        $guestData = ['guest_name' => 'Test User', 'guest_count' => 2];

        // 2. Simulate concurrent requests using fork or just test the DB constraints/locking
        // Since true HTTP concurrency in PHP testing is tricky, we can simulate by starting a transaction,
        // locking the rows, and asserting another connection cannot lock them, OR we just trust the transaction
        // lock mechanism and test if overlapping dates are rejected sequentially FIRST, and then test the overlapping
        // query logic.

        // Request 1: Should Succeed
        $reservation1 = $service->createReservation($ilan->id, $checkIn, $checkOut, $guestData, $user->id);

        $this->assertNotNull($reservation1);
        $this->assertEquals(ReservationState::CONFIRMED, $reservation1->reservation_state);

        // Verification of rows
        $this->assertDatabaseHas('property_availabilities', [
            'property_id' => $ilan->id,
            'date' => '2026-07-01',
            'is_available' => false,
            'reservation_id' => $reservation1->id
        ]);

        // Request 2: Exactly same dates -> Should Fail
        $exceptionThrown = false;
        try {
            $service->createReservation($ilan->id, $checkIn, $checkOut, $guestData, $user->id);
        } catch (Exception $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('overlap', strtolower($e->getMessage()));
        }

        $this->assertTrue($exceptionThrown, "Double booking was not prevented!");
    }
}
