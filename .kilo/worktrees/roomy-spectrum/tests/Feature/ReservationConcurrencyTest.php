<?php

namespace Tests\Feature;

use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyReservation;
use App\Services\ReservationService;
use Carbon\Carbon;
use Tests\TestCase;

class ReservationConcurrencyTest extends TestCase
{

    protected ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReservationService::class);
    }

    /** @test */
    public function it_prevents_double_booking_within_transaction()
    {
        // 1. Setup Property
        $ilan = Ilan::create([
            'baslik' => 'Test Villa',
            'fiyat' => 1000,
            'para_birimi' => 'TRY',
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'yayin_durumu' => 'yayinda',
        ]);

        $startDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(7)->format('Y-m-d');

        $guestData = [
            'guest_name' => 'John Doe',
            'guest_phone' => '123456789',
        ];

        // 2. First Reservation - Success
        $res1 = $this->service->createReservation($ilan->id, $startDate, $endDate, $guestData);
        $this->assertDatabaseHas('property_reservations', ['id' => $res1->id]);

        // 3. Second Reservation - Should fail due to conflict
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Conflict detected');

        $this->service->createReservation($ilan->id, $startDate, $endDate, [
            'guest_name' => 'Jane Collision',
            'guest_phone' => '987654321',
        ]);
    }

    /** @test */
    public function it_correctly_reopens_availability_on_cancellation()
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Villa 2',
            'fiyat' => 2000,
            'para_birimi' => 'TRY',
            'rental_enabled' => true,
            'min_stay_nights' => 1,
            'yayin_durumu' => 'yayinda',
        ]);

        $startDate = Carbon::now()->addDays(10)->format('Y-m-d');
        $endDate = Carbon::now()->addDays(12)->format('Y-m-d');

        $res = $this->service->createReservation($ilan->id, $startDate, $endDate, ['guest_name' => 'Canceller']);

        // Assert dates are blocked
        $this->assertEquals(0, PropertyAvailability::where('property_id', $ilan->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_available', true)
            ->count());

        // Cancel
        $this->service->cancelReservation($res->id);

        // Assert dates are free
        $this->assertEquals(2, PropertyAvailability::where('property_id', $ilan->id)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->where('is_available', true)
            ->count());

        $this->assertDatabaseHas('property_reservations', [
            'id' => $res->id,
            'reservation_state' => 'cancelled'
        ]);
    }

    /** @test */
    public function it_enforces_minimum_stay_nights()
    {
        $ilan = Ilan::create([
            'baslik' => 'Min Stay Villa',
            'fiyat' => 1500,
            'rental_enabled' => true,
            'min_stay_nights' => 3,
            'yayin_durumu' => 'yayinda',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Minimum stay is 3 nights');

        $this->service->createReservation(
            $ilan->id,
            Carbon::now()->addDays(1)->format('Y-m-d'),
            Carbon::now()->addDays(2)->format('Y-m-d'),
            ['guest_name' => 'Quick Guest']
        );
    }
}
