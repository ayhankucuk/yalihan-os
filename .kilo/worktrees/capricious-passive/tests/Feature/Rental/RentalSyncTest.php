<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\User;
use App\Services\ReservationService;
use Tests\TestCase;
use Exception;
use Carbon\Carbon;

class RentalSyncTest extends TestCase
{

    public function test_ical_sync_conflict_and_reconciliation()
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'user_id' => $user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        $service = app(ReservationService::class);

        // 1. Airbnb feed blocks 10-15 July
        $startBlock = Carbon::parse('2026-07-10');
        $endBlock = Carbon::parse('2026-07-15');

        $dates = [];
        $current = $startBlock->copy();
        while ($current->lt($endBlock)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            PropertyAvailability::create([
                'property_id' => $ilan->id,
                'date' => $date,
                'is_available' => false,
                'source_system' => 'airbnb_ical',
                'external_ref' => 'airbnb_test_sync',
                'block_reason' => 'airbnb sync block'
            ]);
        }

        // 2. Manual rez 12-14 July shouldn't work
        $failed = false;
        try {
            $service->createReservation($ilan->id, '2026-07-12', '2026-07-14', [
                'guest_name' => 'Conflict Test'
            ], $user->id);
        } catch (Exception $e) {
            $failed = true;
            $this->assertStringContainsString('Conflict on', $e->getMessage());
        }
        $this->assertTrue($failed, "Manual reservation bypassed Airbnb block!");

        // 3. Reconciliation Test: Airbnb feed no longer has 10-15 block, sync runs
        // Emulate SyncPropertyCalendarFeedJob logic where it removes blocks that are no longer in the feed.
        // Actually, let's create a manual reservation on 20-25 July to make sure it's kept safe.
        $internalReservation = $service->createReservation($ilan->id, '2026-07-20', '2026-07-25', [
            'guest_name' => 'Internal Safe'
        ], $user->id);

        // Emulate sync where the external ref doesn't exist anymore in the fresh payload
        // The job deletes anything `source_system = airbnb_ical` that is not in the new payload.
        PropertyAvailability::where('property_id', $ilan->id)
            ->where('source_system', 'airbnb_ical')
            ->delete();

        // Check if 12 July is now available for internal booking
        $success = false;
        try {
            $reservation = $service->createReservation($ilan->id, '2026-07-12', '2026-07-14', [
                'guest_name' => 'Now Valid'
            ], $user->id);
            $success = true;
        } catch (Exception $e) {
        }
        $this->assertTrue($success, "Could not book dates after Airbnb blocks were removed via reconciliation.");

        // Check internal reservation is still safe
        $this->assertDatabaseHas('property_reservations', [
            'id' => $internalReservation->id,
            'reservation_state' => 'confirmed'
        ]);
        $this->assertDatabaseHas('property_availabilities', [
            'reservation_id' => $internalReservation->id,
            'is_available' => false
        ]);
    }
}
