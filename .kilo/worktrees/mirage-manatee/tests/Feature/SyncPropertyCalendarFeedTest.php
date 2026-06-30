<?php

namespace Tests\Feature;

use App\Jobs\SyncPropertyCalendarFeedJob;
use App\Models\Ilan;
use App\Models\PropertyAvailability;
use App\Models\PropertyCalendarFeed;
use App\Services\ICalParserService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPropertyCalendarFeedTest extends TestCase
{

    protected Ilan $ilan;
    protected PropertyCalendarFeed $feed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ilan = clone Ilan::factory()->create(['rental_enabled' => true]);
        $this->feed = PropertyCalendarFeed::create([
            'property_id' => $this->ilan->id,
            'provider' => 'airbnb',
            'ical_url' => 'http://example.com/ical',
            'sync_enabled' => true,
            'sync_frequency_minutes' => 60,
        ]);
    }

    public function test_syncs_and_creates_availability_blocks()
    {
        $icalData = "BEGIN:VCALENDAR\nBEGIN:VEVENT\nUID:event1@airbnb\nDTSTART;VALUE=DATE:" . now()->addDays(2)->format('Ymd') . "\nDTEND;VALUE=DATE:" . now()->addDays(5)->format('Ymd') . "\nEND:VEVENT\nEND:VCALENDAR";

        Http::fake([
            'example.com/ical' => Http::response($icalData, 200)
        ]);

        $job = new SyncPropertyCalendarFeedJob($this->feed->id);
        $job->handle(app(ICalParserService::class));

        // 3 days should be blocked (day 2, 3, 4)
        $blocks = PropertyAvailability::where('property_id', $this->ilan->id)->get();
        $this->assertCount(3, $blocks);

        foreach ($blocks as $block) {
            $this->assertFalse((bool) $block->is_available);
            $this->assertEquals('airbnb_ical', $block->source_system);
            $this->assertEquals('event1@airbnb', $block->external_ref);
        }

        $this->feed->refresh();
        $this->assertEquals(md5($icalData), $this->feed->last_sync_hash);
    }

    public function test_idempotency_ignores_same_hash()
    {
        $icalData = "BEGIN:VCALENDAR\nBEGIN:VEVENT\nUID:event1@airbnb\nDTSTART;VALUE=DATE:" . now()->addDays(2)->format('Ymd') . "\nDTEND;VALUE=DATE:" . now()->addDays(5)->format('Ymd') . "\nEND:VEVENT\nEND:VCALENDAR";

        $this->feed->update(['last_sync_hash' => md5($icalData)]);

        Http::fake([
            'example.com/ical' => Http::response($icalData, 200)
        ]);

        $job = new SyncPropertyCalendarFeedJob($this->feed->id);
        $job->handle(app(ICalParserService::class));

        // Since hash matched, no processing occurs, count should be 0
        $this->assertEquals(0, PropertyAvailability::count());
    }

    public function test_reconciliation_clears_cancelled_events()
    {
        // Add a pre-existing airbnb block for tomorrow
        PropertyAvailability::create([
            'property_id' => $this->ilan->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'is_available' => false,
            'source_system' => 'airbnb_ical',
            'block_reason' => 'airbnb_busy',
            'external_ref' => 'deleted_event@airbnb'
        ]);

        // The URL returns EMPTY CALENDAR (no events)
        $icalData = "BEGIN:VCALENDAR\nEND:VCALENDAR";

        Http::fake([
            'example.com/ical' => Http::response($icalData, 200)
        ]);

        $job = new SyncPropertyCalendarFeedJob($this->feed->id);
        $job->handle(app(ICalParserService::class));

        // The block should now be available=true and source_system=internal
        $block = PropertyAvailability::where('property_id', $this->ilan->id)->first();
        $this->assertTrue((bool) $block->is_available);
        $this->assertEquals('internal', $block->source_system);
        $this->assertNull($block->external_ref);
    }
}
