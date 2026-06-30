<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class RentalLargeRangeSyncTest extends TestCase
{

    public function test_large_range_ical_sync_performance()
    {
        $user = User::factory()->create();
        $ilan = Ilan::factory()->create([
            'user_id' => $user->id,
            'rental_enabled' => true,
            'min_stay_nights' => 1,
        ]);

        // Simulate 6-month block from Airbnb (180 days)
        $startDate = Carbon::parse('2026-07-01');
        $endDate = Carbon::parse('2026-12-31');
        $uid = 'airbnb_large_' . uniqid();

        $startTime = microtime(true);

        $insertData = [];
        $current = $startDate->copy();
        $now = now();
        while ($current->lte($endDate)) {
            $insertData[] = [
                'property_id' => $ilan->id,
                'date' => $current->format('Y-m-d'),
                'is_available' => false,
                'source_system' => 'airbnb_ical',
                'external_ref' => $uid,
                'block_reason' => 'ical sync',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $current->addDay();
        }

        // Bulk upsert (matches idempotent sync job behavior)
        PropertyAvailability::insertOrIgnore($insertData);

        $elapsed = microtime(true) - $startTime;

        // Performance assertion: must complete within 2 seconds
        $this->assertLessThan(2.0, $elapsed, "Large range sync took too long: {$elapsed}s");

        // Unique constraint: no duplicates
        $count = PropertyAvailability::where('property_id', $ilan->id)
            ->where('external_ref', $uid)
            ->count();

        $this->assertEquals(count($insertData), $count, "Duplicate rows detected!");

        // Run again (idempotency check)
        PropertyAvailability::insertOrIgnore($insertData);

        $countAfter = PropertyAvailability::where('property_id', $ilan->id)
            ->where('external_ref', $uid)
            ->count();

        $this->assertEquals($count, $countAfter, "insertOrIgnore is not idempotent!");
    }
}
