<?php

namespace App\Infrastructure\ChannelManager\Adapters;

use App\Contracts\ChannelManager\ChannelSyncContract;
use App\Domain\ChannelManager\DTOs\ChannelSyncResponse;
use App\Domain\ChannelManager\DTOs\ICalParsedEvent;
use App\Domain\ChannelManager\Enums\Channel;
use App\Domain\ChannelManager\Enums\SyncDirection;
use App\Models\Ilan;
use Illuminate\Support\Facades\Log;

/**
 * ICalAdapter — iCal feed adapter for availability import/export
 *
 * CHANNEL_MANAGER Wave 1: ICalAdapter
 *
 * RESPONSIBILITIES:
 * - Parse valid VEVENT blocks from iCal feeds
 * - Use [start, end) date semantics (consistent with YALIHAN)
 * - Normalize UID as external idempotency reference
 * - Reject malformed feeds safely
 * - Prevent cross-tenant access
 *
 * DOES NOT:
 * - Contain domain decision logic
 * - Resolve conflicts (ConflictDetectionService owns this)
 * - Write directly to PropertyAvailability (uses existing canonical write path)
 * - Make availability decisions
 * - Log credentials or secrets
 *
 * IMPORT FLOW:
 * External iCal Feed → ICalAdapter → Normalized DTOs → Caller uses canonical write path
 */
class ICalAdapter implements ChannelSyncContract
{
    private const CHANNEL = Channel::ICAL;

    /**
     * iCal parser using LarsO\ICal
     * @var \LarsOle\ICal\ICal|null
     */
    private ?object $parser = null;

    public function __construct()
    {
        // Parser initialized lazily
    }

    /**
     * @inheritDoc
     */
    public function getChannel(): Channel
    {
        return self::CHANNEL;
    }

    /**
     * @inheritDoc
     */
    public function getChannelName(): string
    {
        return 'iCal';
    }

    /**
     * @inheritDoc
     */
    public function supportsPush(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function supportsPull(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     *
     * For iCal, push generates an .ics file content.
     * Returns the calendar as a string for the caller to serve/email.
     */
    public function pushAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        array  $availabilityData,
    ): ChannelSyncResponse {
        // Validate tenant access
        $property = $this->resolveProperty($tenantId, $propertyId);
        if ($property === null) {
            return ChannelSyncResponse::failure(
                channel: self::CHANNEL,
                direction: SyncDirection::EXPORT,
                correlationId: $correlationId,
                errorCode: 'PROPERTY_NOT_FOUND',
                errorMessage: "Property {$propertyId} not found or not accessible to tenant",
                retryable: false,
            );
        }

        // Generate iCal content
        $ical = $this->generateIcal($property, $availabilityData);

        return ChannelSyncResponse::success(
            channel: self::CHANNEL,
            direction: SyncDirection::EXPORT,
            correlationId: $correlationId,
            channelRef: "ical:export:{$propertyId}",
            metadata: [
                'property_id' => $propertyId,
                'event_count' => count(array_filter($availabilityData, fn($a) => !$a['available'])),
                'generated_at' => now()->toIso8601String(),
            ],
        )->withIcalContent($ical);
    }

    /**
     * @inheritDoc
     *
     * Pull parses an iCal feed URL or content.
     * Returns normalized events for the caller to process via canonical write path.
     */
    public function pullAvailability(
        int    $tenantId,
        int    $propertyId,
        string $correlationId,
        string $fromDate,
        string $toDate,
    ): ChannelSyncResponse {
        // Validate tenant access
        $property = $this->resolveProperty($tenantId, $propertyId);
        if ($property === null) {
            return ChannelSyncResponse::failure(
                channel: self::CHANNEL,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                errorCode: 'PROPERTY_NOT_FOUND',
                errorMessage: "Property {$propertyId} not found or not accessible to tenant",
                retryable: false,
            );
        }

        // Get iCal source URL from property config
        $icalUrl = $this->getIcalUrl($property);
        if (empty($icalUrl)) {
            return ChannelSyncResponse::failure(
                channel: self::CHANNEL,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                errorCode: 'NO_ICAL_SOURCE',
                errorMessage: "No iCal source URL configured for property {$propertyId}",
                retryable: false,
            );
        }

        try {
            // Fetch and parse iCal
            $events = $this->parseIcalFeed($icalUrl, $fromDate, $toDate);

            return ChannelSyncResponse::success(
                channel: self::CHANNEL,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                channelRef: "ical:import:{$propertyId}",
                metadata: [
                    'property_id' => $propertyId,
                    'event_count' => count($events),
                    'events' => array_map(fn(ICalParsedEvent $e) => $e->toArray(), $events),
                    'parsed_at' => now()->toIso8601String(),
                ],
            );
        } catch (\Throwable $e) {
            Log::error('ICalAdapter: Failed to pull iCal feed', [
                'property_id' => $propertyId,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return ChannelSyncResponse::failure(
                channel: self::CHANNEL,
                direction: SyncDirection::IMPORT,
                correlationId: $correlationId,
                errorCode: 'PULL_FAILED',
                errorMessage: 'Failed to fetch or parse iCal feed: ' . $e->getMessage(),
                retryable: true,
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(int $tenantId): ChannelSyncResponse
    {
        // iCal doesn't have a connection to test
        return ChannelSyncResponse::success(
            channel: self::CHANNEL,
            direction: SyncDirection::IMPORT,
            correlationId: 'ical:test:' . uniqid(),
            channelRef: 'ical:available',
            metadata: [
                'mode' => 'passive',
                'message' => 'iCal adapter is available',
            ],
        );
    }

    /**
     * Parse iCal feed content or URL
     *
     * @param string $source URL or raw iCal content
     * @param string $fromDate Inclusive start (YYYY-MM-DD)
     * @param string $toDate Exclusive end (YYYY-MM-DD)
     * @return ICalParsedEvent[]
     */
    public function parseIcalFeed(string $source, string $fromDate, string $toDate): array
    {
        // Detect if source is URL or raw content
        $rawContent = $this->fetchIcalContent($source);

        return $this->parseIcalContent($rawContent, $fromDate, $toDate);
    }

    /**
     * Parse raw iCal content
     *
     * @param string $content Raw iCal (.ics) content
     * @param string $fromDate Inclusive start (YYYY-MM-DD)
     * @param string $toDate Exclusive end (YYYY-MM-DD)
     * @return ICalParsedEvent[]
     */
    public function parseIcalContent(string $content, string $fromDate, string $toDate): array
    {
        if (empty(trim($content))) {
            return [];
        }

        $events = [];
        $fromTs = strtotime($fromDate);
        $toTs = strtotime($toDate);

        // Parse using regex (compatible, no external dependency)
        $blocks = $this->extractVEventBlocks($content);

        foreach ($blocks as $block) {
            try {
                $event = $this->parseVEventBlock($block);

            // Filter by date range [fromDate, toDate)
            // Event blocks dates [dtStart, dtEnd) — inclusive start, exclusive end
            // Overlap check: event blocks any date in [queryFrom, queryTo)
            // Event overlaps if: max(event_start, query_start) < min(event_end, query_end)
            $eventStartTs = $event->dtStart->getTimestamp();
            $eventEndTs = $event->dtEnd->getTimestamp();
            $fromTs = strtotime($fromDate);
            $toTs = strtotime($toDate);

            // Check if ranges overlap: [eventStart, eventEnd) ∩ [from, to) ≠ ∅
            $overlapStart = max($eventStartTs, $fromTs);
            $overlapEnd = min($eventEndTs, $toTs);

            if ($overlapStart < $overlapEnd) {
                $events[] = $event;
            }
            } catch (\Throwable $e) {
                // Skip malformed events gracefully (no DB/log dependency in unit tests)
                if (app()->bound('log')) {
                    Log::debug('ICalAdapter: Skipping malformed VEVENT', [
                        'error' => $e->getMessage(),
                    ]);
                }
                continue;
            }
        }

        return $events;
    }

    /**
     * Extract VEVENT blocks from iCal content
     *
     * @param string $content Raw iCal content
     * @return array<string, array<string, string|array>> Array of VEVENT blocks
     */
    private function extractVEventBlocks(string $content): array
    {
        $blocks = [];
        $inEvent = false;
        $currentBlock = [];
        $currentKey = '';

        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // Unfold long lines (RFC 5545)
        $content = preg_replace('/\n[ \t]/', '', $content);

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'BEGIN:VEVENT')) {
                $inEvent = true;
                $currentBlock = [];
                $currentKey = '';
                continue;
            }

            if (str_starts_with($line, 'END:VEVENT')) {
                $inEvent = false;
                if (!empty($currentBlock)) {
                    $blocks[] = $currentBlock;
                }
                continue;
            }

            if (!$inEvent) {
                continue;
            }

            // Parse property:VALUE;PARAM=param:data
            if (preg_match('/^([^;:]+)(;[^:]*)?:(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $params = $matches[2] ?? '';
                $value = $matches[3];

                // Handle array values for parameterized properties
                if (isset($currentBlock[$key])) {
                    if (is_array($currentBlock[$key])) {
                        $currentBlock[$key][] = $value;
                    } else {
                        $currentBlock[$key] = [$currentBlock[$key], $value];
                    }
                } else {
                    $currentBlock[$key] = $value;
                }

                // Also store with params for DATE/DATETIME distinction
                if (!empty($params) && str_contains($params, 'VALUE=DATE')) {
                    $currentBlock[$key . ';VALUE=DATE'] = $value;
                }
            }
        }

        return $blocks;
    }

    /**
     * Parse a single VEVENT block into ICalParsedEvent
     */
    private function parseVEventBlock(array $block): ICalParsedEvent
    {
        // Get UID (required)
        $uid = $block['UID'] ?? null;
        if (empty($uid)) {
            throw new \InvalidArgumentException('VEVENT missing UID');
        }

        // Get DTSTART (required)
        $dtstart = $this->parseIcalDateValue($block['DTSTART'] ?? null, $block['DTSTART;VALUE=DATE'] ?? null);
        if ($dtstart === null) {
            throw new \InvalidArgumentException('VEVENT missing DTSTART');
        }

        // Get DTEND (optional, defaults to next day)
        $dtend = $this->parseIcalDateValue(
            $block['DTEND'] ?? null,
            $block['DTEND;VALUE=DATE'] ?? null
        );

        // Handle DURATION if DTEND not present
        if ($dtend === null && isset($block['DURATION'])) {
            $dtend = $dtstart->modify($this->parseDuration($block['DURATION']));
        }

        // Default: 1 day duration for all-day events
        if ($dtend === null) {
            $isAllDay = isset($block['DTSTART;VALUE=DATE']) || (is_string($block['DTSTART'] ?? '') && strlen($block['DTSTART']) === 8);
            $dtend = $isAllDay
                ? \DateTimeImmutable::createFromFormat('Y-m-d', $dtstart->format('Y-m-d'))->modify('+1 day')
                : $dtstart->modify('+1 hour'); // Default 1 hour for timed events
        }

        return new ICalParsedEvent(
            uid: $uid,
            summary: $block['SUMMARY'] ?? 'Busy',
            dtStart: $dtstart,
            dtEnd: $dtend,
            isAllDay: isset($block['DTSTART;VALUE=DATE']) || strlen($block['DTSTART'] ?? '') === 8,
            description: $block['DESCRIPTION'] ?? null,
            location: $block['LOCATION'] ?? null,
        );
    }

    /**
     * Parse an iCal DATE or DATETIME value
     */
    private function parseIcalDateValue(?string $datetime, ?string $date): ?\DateTimeImmutable
    {
        $value = $date ?? $datetime ?? null;
        if ($value === null) {
            return null;
        }

        // DATE format: YYYYMMDD (all-day event)
        // Parse as start of day for consistent handling
        if (preg_match('/^\d{8}$/', $value)) {
            $dateStr = substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2);
            return \DateTimeImmutable::createFromFormat('Y-m-d', $dateStr)
                ?->setTime(0, 0, 0) ?: null;
        }

        // DATETIME format: YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
        if (preg_match('/^(\d{8})T(\d{6})/', $value, $matches)) {
            $datePart = $matches[1];
            $timePart = $matches[2];

            $dateStr = substr($datePart, 0, 4) . '-' . substr($datePart, 4, 2) . '-' . substr($datePart, 6, 2);
            $timeStr = substr($timePart, 0, 2) . ':' . substr($timePart, 2, 2) . ':' . substr($timePart, 4, 2);

            $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);
            return $dt ?: null;
        }

        return null;
    }

    /**
     * Parse iCal DURATION value (e.g., "P1D", "PT1H")
     */
    private function parseDuration(string $duration): string
    {
        // Convert iCal duration to PHP DateInterval string
        $duration = strtoupper($duration);

        if (str_starts_with($duration, 'P')) {
            // Convert to DateInterval format
            $php = str_replace(['P', 'D', 'T', 'H', 'M', 'S'], ['P0DT', 'D', 'T0H', 'H', 'M', 'S'], $duration);
            // Handle PT format
            if (str_starts_with($php, 'PT')) {
                $php = 'P0DT' . substr($php, 2);
            }
            return $php;
        }

        return '+1 day'; // Default fallback
    }

    /**
     * Fetch iCal content from URL or return as-is if already content
     */
    private function fetchIcalContent(string $source): string
    {
        // If it looks like a URL, fetch it
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->get($source);

                if ($response->successful()) {
                    return $response->body();
                }

                throw new \RuntimeException("HTTP {$response->status()}: {$response->body()}");
            } catch (\Throwable $e) {
                throw new \RuntimeException("Failed to fetch iCal URL: " . $e->getMessage());
            }
        }

        // Otherwise treat as raw iCal content
        return $source;
    }

    /**
     * Get iCal source URL for a property
     */
    private function getIcalUrl(Ilan $property): ?string
    {
        // Look for iCal URL in property configuration
        // This could be from a config table, IlanTakvimSync, or property meta
        return $property->ilan_ical_url
            ?? $property->getAttribute('ical_url')
            ?? $property->getAttribute('takvim_sync_url')
            ?? null;
    }

    /**
     * Resolve property with tenant isolation
     */
    private function resolveProperty(int $tenantId, int $propertyId): ?Ilan
    {
        return Ilan::where('id', $propertyId)
            ->where('tenant_id', $tenantId)
            ->first(['id', 'tenant_id', 'ilan_ical_url']);
    }

    /**
     * Generate iCal content from availability data
     */
    private function generateIcal(Ilan $property, array $availabilityData): string
    {
        $uid = uniqid('yalihan_', true);
        $now = now()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Yalihan Emlak//Availability Feed//TR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Yalıhan - ' . ($property->baslik ?? 'Property'),
        ];

        foreach ($availabilityData as $item) {
            if (!($item['available'] ?? true)) {
                // Generate VEVENT for blocked dates
                $date = $item['date'] ?? null;
                if (empty($date)) {
                    continue;
                }

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID: blockage_' . $uid . '_' . $date . '@yalihan.emlak';
                $lines[] = 'DTSTAMP:' . $now;
                $lines[] = 'DTSTART;VALUE=DATE:' . str_replace('-', '', $date);
                $lines[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($date . ' +1 day'));
                $lines[] = 'SUMMARY:' . ($item['block_reason'] ?? 'Not Available');
                $lines[] = 'STATUS:CONFIRMED';
                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }
}
