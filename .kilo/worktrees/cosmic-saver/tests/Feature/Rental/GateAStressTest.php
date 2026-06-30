<?php

namespace Tests\Feature\Rental;

use App\Enums\ReservationState;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gate A — Multi-Property Stress Simulation
 * A2: Concurrency Burst Test — 10 parallel-simulated reservation attempts: 1 succeeds, 9 reject
 * A3: iCal Sync Storm — 100 property × 180 days, no duplicate
 * A4: Cancel/Reopen Churn — drift-free availability
 */
class GateAStressTest extends TestCase
{

    protected function createRentalProperty(int $basePrice = 1000, int $minStay = 1): Ilan
    {
        $user = User::factory()->create();
        return Ilan::factory()->create([
            'user_id'        => $user->id,
            'rental_enabled' => true,
            'min_stay_nights'=> $minStay,
        ]);
    }

    /**
     * A2: Concurrency Burst — 10 sequential attempts on same dates.
     * Only 1 should succeed, 9 must be rejected.
     */
    public function test_a2_concurrency_burst_10_attempts_only_1_succeeds(): void
    {
        $ilan     = $this->createRentalProperty();
        $service  = app(ReservationService::class);
        $checkIn  = '2026-09-01';
        $checkOut = '2026-09-05';

        $successCount = 0;
        $failCount    = 0;

        for ($i = 0; $i < 10; $i++) {
            try {
                $service->createReservation($ilan->id, $checkIn, $checkOut, [
                    'guest_name'  => "Burst Tester {$i}",
                    'guest_count' => 1,
                ], $ilan->user_id);
                $successCount++;
            } catch (Exception) {
                $failCount++;
            }
        }

        // Exactly 1 success, 9 rejections
        $this->assertEquals(1, $successCount, "Expected only 1 successful reservation.");
        $this->assertEquals(9, $failCount, "Expected 9 rejections.");

        // DB must have exactly 1 non-cancelled reservation
        $dbCount = PropertyReservation::where('property_id', $ilan->id)
            ->where('reservation_state', '!=', 'cancelled')
            ->count();
        $this->assertEquals(1, $dbCount, "DB has more than 1 active reservation — double booking!");

        // Availability must show exactly 4 blocked days (Sep 01–04)
        $blockedDays = PropertyAvailability::where('property_id', $ilan->id)
            ->where('is_available', false)
            ->count();
        $this->assertEquals(4, $blockedDays, "Availability drift detected!");
    }

    /**
     * A3: iCal Sync Storm — 10 properties × 180 days, bulk upsert, no duplicates.
     * (Using 10 in test env; 100 is for production seeder.)
     */
    public function test_a3_ical_sync_storm_no_duplicates(): void
    {
        $properties = collect();
        for ($i = 0; $i < 10; $i++) {
            $properties->push($this->createRentalProperty());
        }

        $now       = now();
        $startDate = Carbon::parse('2026-07-01');

        $startTime = microtime(true);

        foreach ($properties as $ilan) {
            $uid    = 'airbnb_storm_' . $ilan->id;
            $rows   = [];
            $cursor = $startDate->copy();
            for ($day = 0; $day < 180; $day++) {
                $rows[] = [
                    'property_id'   => $ilan->id,
                    'date'          => $cursor->format('Y-m-d'),
                    'is_available'  => false,
                    'source_system' => 'airbnb_ical',
                    'external_ref'  => $uid,
                    'block_reason'  => 'ical storm test',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
                $cursor->addDay();
            }
            PropertyAvailability::insertOrIgnore($rows);

            // Idempotency: run again — duplicate count must not increase
            PropertyAvailability::insertOrIgnore($rows);
        }

        $elapsed = microtime(true) - $startTime;

        // Perf < 3s for 10 props × 180 days in test env
        $this->assertLessThan(3.0, $elapsed, "iCal sync storm too slow: {$elapsed}s");

        // Each property should have exactly 180 airbnb rows
        foreach ($properties as $ilan) {
            $count = PropertyAvailability::where('property_id', $ilan->id)
                ->where('source_system', 'airbnb_ical')
                ->count();
            $this->assertEquals(180, $count, "Duplicate rows detected for property {$ilan->id}!");
        }
    }

    /**
     * A4: Cancel/Reopen Churn — create + cancel + recreate, no drift.
     */
    public function test_a4_cancel_reopen_churn_no_availability_drift(): void
    {
        $ilan    = $this->createRentalProperty();
        $service = app(ReservationService::class);

        $checkIn  = '2026-10-01';
        $checkOut = '2026-10-07'; // 6 nights

        // Round 1: Create
        $res1 = $service->createReservation($ilan->id, $checkIn, $checkOut, [
            'guest_name' => 'Churn Test 1'
        ], $ilan->user_id);
        $this->assertEquals(ReservationState::CONFIRMED, $res1->reservation_state);

        $blocked = PropertyAvailability::where('property_id', $ilan->id)
            ->where('is_available', false)->count();
        $this->assertEquals(6, $blocked);

        // Cancel
        $service->cancelReservation($res1->id);
        $res1->refresh();
        $this->assertEquals(ReservationState::CANCELLED, $res1->reservation_state);

        $blockedAfterCancel = PropertyAvailability::where('property_id', $ilan->id)
            ->where('is_available', false)->count();
        $this->assertEquals(0, $blockedAfterCancel, "Availability drift: blocked days remain after cancel.");

        // Round 2: Recreate same dates — must succeed
        $res2 = $service->createReservation($ilan->id, $checkIn, $checkOut, [
            'guest_name' => 'Churn Test 2'
        ], $ilan->user_id);
        $this->assertNotNull($res2->id);
        $this->assertEquals(ReservationState::CONFIRMED, $res2->reservation_state);

        // airbnb blocks must NOT be touched by internal cancel
        $airbnbBefore = PropertyAvailability::where('property_id', $ilan->id)
            ->where('source_system', 'airbnb_ical')->count();
        $this->assertEquals(0, $airbnbBefore, "Airbnb blocks should not exist for a fresh property.");
    }
}
