<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Models\User;
use App\Services\RentalKpiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Gate C — Enterprise KPI & Analytics Tests
 */
class GateCKpiTest extends TestCase
{

    protected RentalKpiService $service;
    protected Ilan $ilan;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RentalKpiService::class);
        $this->user    = User::factory()->create();
        $this->ilan    = Ilan::factory()->create([
            'user_id'        => $this->user->id,
            'rental_enabled' => true,
            'min_stay_nights'=> 1,
            'fiyat'          => 1000,
        ]);
    }

    /**
     * C1: Occupancy rate correct & cache-backed
     */
    public function test_c1_monthly_occupancy_correct_and_cached(): void
    {
        // Seed 15 blocked days in October 2026 (31 days total)
        $now = now();
        $rows = [];
        for ($d = 1; $d <= 15; $d++) {
            $rows[] = [
                'property_id'   => $this->ilan->id,
                'date'          => sprintf('2026-10-%02d', $d),
                'is_available'  => false,
                'source_system' => 'internal',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        PropertyAvailability::insertOrIgnore($rows);

        $occupancy = $this->service->monthlyOccupancy($this->ilan->id, 2026, 10);

        $expected = round((15 / 31) * 100, 2);
        $this->assertEquals($expected, $occupancy);

        // Assert value is cached
        $cached = Cache::get("rental_kpi.occupancy.{$this->ilan->id}.2026.10");
        $this->assertEquals($expected, $cached);
    }

    /**
     * C2: ADR calculation
     */
    public function test_c2_adr_calculation(): void
    {
        // Two reservations: 3 nights × 1000 TRY = 3000, 5 nights × 1000 TRY = 5000
        PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => '2026-11-01',
            'end_date'          => '2026-11-04',
            'nights'            => 3,
            'guest_name'        => 'ADR Test 1',
            'reservation_state' => 'confirmed',
            'total_amount'      => 3000,
            'confirmed_at'      => now(),
        ]);
        PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => '2026-11-10',
            'end_date'          => '2026-11-15',
            'nights'            => 5,
            'guest_name'        => 'ADR Test 2',
            'reservation_state' => 'confirmed',
            'total_amount'      => 5000,
            'confirmed_at'      => now(),
        ]);

        $adr = $this->service->adr($this->ilan->id, '2026-11-01', '2026-11-30');
        // Total: 8000 / 8 nights = 1000
        $this->assertEquals(1000.0, $adr);
    }

    /**
     * C2: Total revenue
     */
    public function test_c2_total_revenue(): void
    {
        PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => '2026-11-01',
            'end_date'          => '2026-11-04',
            'nights'            => 3,
            'guest_name'        => 'Rev Test',
            'reservation_state' => 'confirmed',
            'total_amount'      => 4500,
            'confirmed_at'      => now(),
        ]);

        // Cancelled reservation must NOT count
        PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => '2026-11-05',
            'end_date'          => '2026-11-08',
            'nights'            => 3,
            'guest_name'        => 'Cancelled Rev',
            'reservation_state' => 'cancelled',
            'total_amount'      => 3000,
        ]);

        $revenue = $this->service->totalRevenue($this->ilan->id, '2026-11-01', '2026-11-30');
        $this->assertEquals(4500.0, $revenue, "Cancelled reservation should not contribute to revenue.");
    }

    /**
     * C3: Upcoming check-ins returns only confirmed future reservations
     */
    public function test_c3_upcoming_checkins(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $dayAfter = Carbon::tomorrow()->addDays(3)->format('Y-m-d');

        PropertyReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => $tomorrow,
            'end_date'          => $dayAfter,
            'nights'            => 3,
            'guest_name'        => 'Upcoming Guest',
            'reservation_state' => 'confirmed',
            'confirmed_at'      => now(),
        ]);

        $upcoming = $this->service->upcomingCheckIns($this->ilan->id, 7);
        $this->assertCount(1, $upcoming);
        $this->assertEquals('Upcoming Guest', $upcoming->first()->guest_name);
    }

    /**
     * C3: Cancel rate calculation
     */
    public function test_c3_cancel_rate(): void
    {
        $now  = '2026-11-15 10:00:00'; // Fixed date inside the queried range
        $from = '2026-11-01';
        $to   = '2026-11-30';

        $rows = [
            ['property_id' => $this->ilan->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-03', 'nights' => 2, 'guest_name' => 'G1', 'reservation_state' => 'confirmed', 'created_at' => $now, 'updated_at' => $now],
            ['property_id' => $this->ilan->id, 'start_date' => '2026-11-05', 'end_date' => '2026-11-07', 'nights' => 2, 'guest_name' => 'G2', 'reservation_state' => 'confirmed', 'created_at' => $now, 'updated_at' => $now],
            ['property_id' => $this->ilan->id, 'start_date' => '2026-11-10', 'end_date' => '2026-11-12', 'nights' => 2, 'guest_name' => 'G3', 'reservation_state' => 'cancelled', 'created_at' => $now, 'updated_at' => $now],
            ['property_id' => $this->ilan->id, 'start_date' => '2026-11-15', 'end_date' => '2026-11-17', 'nights' => 2, 'guest_name' => 'G4', 'reservation_state' => 'cancelled', 'created_at' => $now, 'updated_at' => $now],
        ];
        \Illuminate\Support\Facades\DB::table('property_reservations')->insert($rows);

        $rate = $this->service->cancelRate($this->ilan->id, $from, $to);
        $this->assertEquals(50.0, $rate, "Cancel rate should be 50% (2 of 4 cancelled).");
    }
}
