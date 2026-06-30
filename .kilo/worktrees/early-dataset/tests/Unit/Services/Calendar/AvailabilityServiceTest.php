<?php

namespace Tests\Unit\Services\Calendar;

use App\Models\Deprecated\IlanReservation;
use App\Services\Calendar\AvailabilityService;
use Carbon\Carbon;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{

    public function test_no_conflict_when_empty()
    {
        $this->markTestSkipped('IlanReservation (Deprecated) table missing in Schema');
        $service = new AvailabilityService();
        $hasConflict = $service->hasConflict(1, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-10'));
        $this->assertFalse($hasConflict);
    }

    public function test_detects_overlap_conflict()
    {
        $this->markTestSkipped('IlanReservation (Deprecated) table missing in Schema');
        IlanReservation::factory()->create([
            'ilan_id' => 1,
            'starts_at' => '2026-07-05',
            'ends_at' => '2026-07-08',
            'aktiflik_durumu' => true,
        ]);

        $service = new AvailabilityService();
        $hasConflict = $service->hasConflict(1, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-10'));
        $this->assertTrue($hasConflict);
    }
}
