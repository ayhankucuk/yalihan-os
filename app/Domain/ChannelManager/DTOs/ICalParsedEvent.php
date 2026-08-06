<?php

namespace App\Domain\ChannelManager\DTOs;

use App\Domain\ChannelManager\Enums\Channel;
use DateTimeImmutable;

/**
 * ICalParsedEvent — Normalized DTO for a parsed iCal VEVENT
 *
 * CHANNEL_MANAGER Wave 1: ICalAdapter
 *
 * Represents a single calendar event extracted from an iCal feed.
 * Uses [start, end) semantics consistent with YALIHAN's date handling.
 *
 * @property-read string           $uid          Unique identifier from iCal (used as idempotency key)
 * @property-read string           $summary      Event summary/title
 * @property-read DateTimeImmutable $dtStart     Event start (all-day or datetime)
 * @property-read DateTimeImmutable $dtEnd       Event end (exclusive, all-day = next day)
 * @property-read bool             $isAllDay     Whether this is an all-day event
 * @property-read string|null      $description  Event description
 * @property-read string|null      $location     Event location
 */
final readonly class ICalParsedEvent
{
    public function __construct(
        public string $uid,
        public string $summary,
        public DateTimeImmutable $dtStart,
        public DateTimeImmutable $dtEnd,
        public bool $isAllDay,
        public ?string $description = null,
        public ?string $location = null,
    ) {}

    /**
     * Create from parsed iCal VEVENT array
     *
     * @param array $vevent Parsed VEVENT properties
     * @return self
     * @throws \InvalidArgumentException If required fields missing
     */
    public static function fromVEvent(array $vevent): self
    {
        if (empty($vevent['UID'])) {
            throw new \InvalidArgumentException('VEVENT must have a UID');
        }

        if (empty($vevent['DTSTART'])) {
            throw new \InvalidArgumentException('VEVENT must have a DTSTART');
        }

        $dtStart = self::parseIcalDate($vevent['DTSTART']);
        $dtEnd = isset($vevent['DTEND'])
            ? self::parseIcalDate($vevent['DTEND'])
            : $dtStart->modify('+1 day'); // Default duration: 1 day

        // All-day events have DATE format (no time component)
        $isAllDay = !isset($vevent['DTSTART']['params']['VALUE'])
            && strlen($vevent['DTSTART']) === 8;

        return new self(
            uid: trim($vevent['UID']),
            summary: trim($vevent['SUMMARY'] ?? 'Busy'),
            dtStart: $dtStart,
            dtEnd: $dtEnd,
            isAllDay: $isAllDay,
            description: isset($vevent['DESCRIPTION']) ? trim($vevent['DESCRIPTION']) : null,
            location: isset($vevent['LOCATION']) ? trim($vevent['LOCATION']) : null,
        );
    }

    /**
     * Get all dates covered by this event (using [start, end) semantics)
     *
     * @return string[] Array of YYYY-MM-DD strings
     */
    public function getDateRange(): array
    {
        $dates = [];
        $cursor = $this->dtStart;
        $end = $this->dtEnd;

        while ($cursor < $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+1 day');
        }

        return $dates;
    }

    /**
     * Get the number of nights this event blocks
     */
    public function getNightCount(): int
    {
        return count($this->getDateRange());
    }

    /**
     * Generate idempotency key for this event
     *
     * Format: ical:{channel_id}:{property_id}:{uid}
     */
    public function getIdempotencyKey(int $propertyId, Channel $channel = Channel::ICAL): string
    {
        return sprintf('ical:%s:%d:%s', $channel->value, $propertyId, $this->uid);
    }

    /**
     * Parse iCal DATE or DATETIME format
     */
    private static function parseIcalDate(string|array $value): DateTimeImmutable
    {
        // Handle arrays (parameterized values like DTSTART;VALUE=DATE:20260901)
        if (is_array($value)) {
            $value = $value[0] ?? reset($value);
        }

        $value = trim($value);

        // DATE format: YYYYMMDD (all-day event)
        if (preg_match('/^\d{8}$/', $value)) {
            return DateTimeImmutable::createFromFormat('Ymd', $value)
                ?: throw new \InvalidArgumentException("Invalid DATE format: {$value}");
        }

        // DATETIME format: YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
        if (preg_match('/^\d{8}T\d{6}/', $value)) {
            // Remove timezone suffix for parsing
            $cleaned = preg_replace('/Z$/', '', $value);
            $dt = DateTimeImmutable::createFromFormat('Ymd\THis', $cleaned);
            if ($dt) {
                return $dt;
            }
            // Try with format that includes TZID
            $dt = DateTimeImmutable::createFromFormat('Ymd\THis', substr($cleaned, 0, 15));
            if ($dt) {
                return $dt;
            }
        }

        throw new \InvalidArgumentException("Cannot parse iCal date: {$value}");
    }

    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'summary' => $this->summary,
            'dtstart' => $this->dtStart->format('Y-m-d H:i:s'),
            'dtend' => $this->dtEnd->format('Y-m-d H:i:s'),
            'is_all_day' => $this->isAllDay,
            'description' => $this->description,
            'location' => $this->location,
        ];
    }
}
