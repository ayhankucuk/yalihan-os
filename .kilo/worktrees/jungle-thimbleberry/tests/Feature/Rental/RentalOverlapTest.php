<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\User;
use App\Services\ReservationService;
use Tests\TestCase;
use Exception;

class RentalOverlapTest extends TestCase
{

    protected $service;
    protected $ilan;
    protected $user;
    protected $guestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'user_id' => $this->user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        $this->service = app(ReservationService::class);
        $this->guestData = ['guest_name' => 'Overlap Test', 'guest_count' => 1];
    }

    /**
     * Helper mode to catch overlap exceptions
     */
    private function assertFailsOverlap(string $start, string $end)
    {
        $failed = false;
        try {
            $this->service->createReservation($this->ilan->id, $start, $end, $this->guestData, $this->user->id);
        } catch (Exception $e) {
            $failed = true;
        }
        $this->assertTrue($failed, "Expected reservation to fail with overlap: $start to $end");
    }

    private function assertSucceedsOverlap(string $start, string $end)
    {
        $res = $this->service->createReservation($this->ilan->id, $start, $end, $this->guestData, $this->user->id);
        $this->assertNotNull($res->id);
    }

    public function test_overlap_matrix()
    {
        // Setup initial reservation: 1-5
        // Let's assume month of August to avoid any past date constraints if they exist
        $baseStart = '2026-08-01';
        $baseEnd = '2026-08-05';

        $this->assertSucceedsOverlap($baseStart, $baseEnd);

        // Test 1 – Partial Overlap: Mevcut: 1–5, Yeni: 4–7 -> FAIL
        $this->assertFailsOverlap('2026-08-04', '2026-08-07');

        // Test 2 – Edge Touch (Exclusive End): Mevcut: 1–5, Yeni: 5–8 -> SUCCESS
        $this->assertSucceedsOverlap('2026-08-05', '2026-08-08');

        // Test 3 – Full Contain: Mevcut: 1–10, Yeni: 3–6 -> FAIL
        // First delete existing logic to be sure, or just run with new dates
        $this->ilan->reservations()->delete();
        $this->ilan->availabilities()->delete();

        $this->assertSucceedsOverlap('2026-08-01', '2026-08-10'); // Base 1-10
        $this->assertFailsOverlap('2026-08-03', '2026-08-06');   // Inner 3-6

        // Test 4 – Reverse Contain: Mevcut: 3–6, Yeni: 1–10 -> FAIL
        $this->ilan->reservations()->delete();
        $this->ilan->availabilities()->delete();

        $this->assertSucceedsOverlap('2026-08-03', '2026-08-06'); // Base 3-6
        $this->assertFailsOverlap('2026-08-01', '2026-08-10');   // Outer 1-10
    }
}
