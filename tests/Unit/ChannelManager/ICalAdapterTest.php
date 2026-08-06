<?php

namespace Tests\Unit\ChannelManager;

use App\Infrastructure\ChannelManager\Adapters\ICalAdapter;
use PHPUnit\Framework\TestCase;

/**
 * ICalAdapterTest — Unit tests without database
 *
 * CHANNEL_MANAGER Wave 1: ICalAdapter Tests
 *
 * Tests iCal parsing and normalization WITHOUT database dependencies.
 * Adapter uses existing canonical write path - does not write directly.
 */
class ICalAdapterTest extends TestCase
{
    /** @test */
    public function valid_ical_feed_is_parsed(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
BEGIN:VEVENT
UID:test-event-1@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260905
SUMMARY:Test Reservation
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-10');

        $this->assertCount(1, $events);
        $this->assertEquals('test-event-1@example.com', $events[0]->uid);
        $this->assertEquals('Test Reservation', $events[0]->summary);
        $this->assertEquals('2026-09-01', $events[0]->dtStart->format('Y-m-d'));
    }

    /** @test */
    public function multiple_events_are_parsed(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:event-1@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260903
SUMMARY:Event 1
END:VEVENT
BEGIN:VEVENT
UID:event-2@example.com
DTSTART;VALUE=DATE:20260905
DTEND;VALUE=DATE:20260908
SUMMARY:Event 2
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-10');

        $this->assertCount(2, $events);
        $this->assertEquals('event-1@example.com', $events[0]->uid);
        $this->assertEquals('event-2@example.com', $events[1]->uid);
    }

    /** @test */
    public function date_ranges_use_inclusive_exclusive_semantics(): void
    {
        $adapter = new ICalAdapter();

        // Event: Sep 1-5 (DTEND exclusive = nights 1,2,3,4)
        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:range-test@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260905
SUMMARY:Range Test
END:VEVENT
END:VCALENDAR
ICAL;

        // Query for [Sep 1, Sep 5) = Sep 1,2,3,4 — event overlaps
        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-05');
        $this->assertCount(1, $events);
        $this->assertEquals(['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04'], $events[0]->getDateRange());

        // Query for [Sep 5, Sep 10) = Sep 5,6,7,8,9 — event does NOT overlap
        $events = $adapter->parseIcalContent($icalContent, '2026-09-05', '2026-09-10');
        $this->assertCount(0, $events);
    }

    /** @test */
    public function malformed_ical_feed_is_handled_gracefully(): void
    {
        $adapter = new ICalAdapter();

        // Invalid: missing VCALENDAR wrapper
        $malformed = <<<ICAL
BEGIN:VEVENT
UID:bad@example.com
DTSTART;VALUE=DATE:20260901
END:VEVENT
ICAL;

        // Should not throw, should return empty array
        $events = $adapter->parseIcalContent($malformed, '2026-09-01', '2026-09-10');
        $this->assertIsArray($events);
    }

    /** @test */
    public function malformed_event_without_uid_is_skipped(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260903
SUMMARY:Missing UID
END:VEVENT
BEGIN:VEVENT
UID:valid-event@example.com
DTSTART;VALUE=DATE:20260905
DTEND;VALUE=DATE:20260908
SUMMARY:Valid Event
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-10');

        // Only the valid event should be parsed
        $this->assertCount(1, $events);
        $this->assertEquals('valid-event@example.com', $events[0]->uid);
    }

    /** @test */
    public function duplicate_uid_is_handled(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:same-uid@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260903
SUMMARY:First
END:VEVENT
BEGIN:VEVENT
UID:same-uid@example.com
DTSTART;VALUE=DATE:20260905
DTEND;VALUE=DATE:20260908
SUMMARY:Second with same UID
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-10');

        // Both events parsed (calendars can have duplicate UIDs)
        $this->assertCount(2, $events);

        // Idempotency key should be based on UID
        $idempotencyKey = $events[0]->getIdempotencyKey(1);
        $this->assertStringContainsString('same-uid@example.com', $idempotencyKey);
        $this->assertStringContainsString('ical', $idempotencyKey);
    }

    /** @test */
    public function datetime_events_are_parsed_correctly(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:datetime-event@example.com
DTSTART:20260901T100000
DTEND:20260901T120000
SUMMARY:Timed Meeting
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-02');

        $this->assertCount(1, $events);
        $this->assertEquals('2026-09-01', $events[0]->dtStart->format('Y-m-d'));
        $this->assertEquals('10:00:00', $events[0]->dtStart->format('H:i:s'));
    }

    /** @test */
    public function events_outside_date_range_are_filtered(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:before@example.com
DTSTART;VALUE=DATE:20260801
DTEND;VALUE=DATE:20260805
SUMMARY:August Event
END:VEVENT
BEGIN:VEVENT
UID:september@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260905
SUMMARY:September Event
END:VEVENT
BEGIN:VEVENT
UID:after@example.com
DTSTART;VALUE=DATE:20261001
DTEND;VALUE=DATE:20261005
SUMMARY:October Event
END:VEVENT
END:VCALENDAR
ICAL;

        // Query September only
        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $events);
        $this->assertEquals('september@example.com', $events[0]->uid);
    }

    /** @test */
    public function adapter_does_not_directly_write_property_availability(): void
    {
        $adapter = new ICalAdapter();

        // Verify adapter does not import PropertyAvailability model
        $reflection = new \ReflectionClass($adapter);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $property) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                $this->assertStringNotContainsString(
                    'PropertyAvailability',
                    $typeName,
                    'ICalAdapter should not directly use PropertyAvailability'
                );
            }
        }
    }

    /** @test */
    public function adapter_does_not_run_conflict_detection(): void
    {
        $adapter = new ICalAdapter();
        $reflection = new \ReflectionClass($adapter);
        $adapterCode = file_get_contents($reflection->getFileName());

        // Strip docblocks/comments first
        $codeWithoutComments = preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*$/m', '', $adapterCode);

        // Verify adapter doesn't USE conflict detection classes
        $forbiddenUses = [
            'ConflictDetectionService',
            'PriorityResolution',
            'OverrideAuthorization',
        ];

        foreach ($forbiddenUses as $ref) {
            $this->assertStringNotContainsString(
                $ref,
                $codeWithoutComments,
                "ICalAdapter should not use {$ref}"
            );
        }
    }

    /** @test */
    public function get_channel_returns_ical(): void
    {
        $adapter = new ICalAdapter();

        $this->assertEquals('ical', $adapter->getChannel()->value);
        $this->assertEquals('iCal', $adapter->getChannelName());
        $this->assertTrue($adapter->supportsPush());
        $this->assertTrue($adapter->supportsPull());
    }

    /** @test */
    public function night_count_is_calculated_correctly(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:nights-test@example.com
DTSTART;VALUE=DATE:20260901
DTEND;VALUE=DATE:20260908
SUMMARY:Week Stay
END:VEVENT
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $events);
        // Sep 1-8 = 7 nights: Sep 1,2,3,4,5,6,7
        $this->assertEquals(7, $events[0]->getNightCount());
    }

    /** @test */
    public function empty_ical_content_returns_empty_array(): void
    {
        $adapter = new ICalAdapter();

        $events = $adapter->parseIcalContent('', '2026-09-01', '2026-09-10');
        $this->assertCount(0, $events);

        $events = $adapter->parseIcalContent('   ', '2026-09-01', '2026-09-10');
        $this->assertCount(0, $events);
    }

    /** @test */
    public function ical_content_without_events_returns_empty_array(): void
    {
        $adapter = new ICalAdapter();

        $icalContent = <<<ICAL
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//EN
END:VCALENDAR
ICAL;

        $events = $adapter->parseIcalContent($icalContent, '2026-09-01', '2026-09-10');
        $this->assertCount(0, $events);
    }
}
