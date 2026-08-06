<?php

namespace App\Services\Property;

use App\Models\PropertyAvailability;
use Illuminate\Support\Facades\DB;

/**
 * AvailabilityTimelineService — RESERVATION_CORE Phase 4
 *
 * Immutable event log for availability changes.
 *
 * Mimari Kural:
 * - Timeline kayıtları ASLA değiştirilemez (immutable)
 * - Timeline kayıtları ASLA silinemez
 * - Her availability değişikliği yeni bir event kaydı üretir
 * - Timeline replay-safe: Event log'dan availability yeniden üretilebilir
 *
 * Event Types:
 * - RESERVATION_CONFIRMED: Reservation confirmed
 * - RESERVATION_CANCELLED: Reservation cancelled
 * - BLOCK_CREATED: Manual/system block created
 * - BLOCK_RELEASED: Block released
 * - EXTERNAL_SYNC: External channel sync
 * - REBUILD: Projection rebuild
 *
 * Sources:
 * - reservation: Canonical reservation
 * - owner: Owner personal use
 * - maintenance: Maintenance block
 * - external: Airbnb/Booking/ical
 * - system: Automated system
 */
class AvailabilityTimelineService
{
    /*=======================================================================
     * Event Types
     *=======================================================================*/

    public const EVENT_RESERVATION_CONFIRMED = 'RESERVATION_CONFIRMED';
    public const EVENT_RESERVATION_CANCELLED = 'RESERVATION_CANCELLED';
    public const EVENT_RESERVATION_COMPLETED = 'RESERVATION_COMPLETED';
    public const EVENT_BLOCK_CREATED = 'BLOCK_CREATED';
    public const EVENT_BLOCK_RELEASED = 'BLOCK_RELEASED';
    public const EVENT_EXTERNAL_SYNC = 'EXTERNAL_SYNC';
    public const EVENT_REBUILD = 'REBUILD';

    /*=======================================================================
     * Record Event (Immutable)
     *=======================================================================*/

    /**
     * Record an availability change event.
     *
     * This method creates an immutable timeline record.
     * Records CANNOT be modified or deleted.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $date
     * @param string $eventType
     * @param array $previousState
     * @param array $newState
     * @param array $metadata
     * @return int Event ID
     */
    public function recordEvent(
        int $tenantId,
        int $propertyId,
        string $date,
        string $eventType,
        array $previousState,
        array $newState,
        array $metadata = []
    ): int {
        return DB::table('availability_timeline')->insertGetId([
            'tenant_id' => $tenantId,
            'property_id' => $propertyId,
            'date' => $date,
            'event_type' => $eventType,
            'previous_state' => json_encode($previousState),
            'new_state' => json_encode($newState),
            'reservation_id' => $metadata['reservation_id'] ?? null,
            'source' => $metadata['source'] ?? 'system',
            'actor_id' => $metadata['actor_id'] ?? null,
            'actor_type' => $metadata['actor_type'] ?? 'system',
            'correlation_id' => $metadata['correlation_id'] ?? null,
            'metadata' => empty($metadata) ? null : json_encode($metadata),
            'created_at' => now(),
        ]);
    }

    /**
     * Record multiple dates for the same event.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param array $dates
     * @param string $eventType
     * @param array $previousState
     * @param array $newState
     * @param array $metadata
     * @return array Event IDs
     */
    public function recordEventForDates(
        int $tenantId,
        int $propertyId,
        array $dates,
        string $eventType,
        array $previousState,
        array $newState,
        array $metadata = []
    ): array {
        $eventIds = [];
        $now = now();

        $records = [];
        foreach ($dates as $date) {
            $records[] = [
                'tenant_id' => $tenantId,
                'property_id' => $propertyId,
                'date' => $date,
                'event_type' => $eventType,
                'previous_state' => json_encode($previousState),
                'new_state' => json_encode($newState),
                'reservation_id' => $metadata['reservation_id'] ?? null,
                'source' => $metadata['source'] ?? 'system',
                'actor_id' => $metadata['actor_id'] ?? null,
                'actor_type' => $metadata['actor_type'] ?? 'system',
                'correlation_id' => $metadata['correlation_id'] ?? null,
                'metadata' => empty($metadata) ? null : json_encode($metadata),
                'created_at' => $now,
            ];
        }

        DB::table('availability_timeline')->insert($records);

        // Fetch IDs (last inserted will have highest ID)
        $insertedIds = DB::table('availability_timeline')
            ->where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $now)
            ->orderBy('id', 'desc')
            ->limit(count($dates))
            ->pluck('id')
            ->toArray();

        return array_reverse($insertedIds);
    }

    /*=======================================================================
     * Query Events
     *=======================================================================*/

    /**
     * Get timeline for a property and date range.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getTimeline(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        return DB::table('availability_timeline')
            ->where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($row) => $this->mapRow($row))
            ->toArray();
    }

    /**
     * Get events for a specific reservation.
     *
     * @param int $tenantId
     * @param int $reservationId
     * @return array
     */
    public function getEventsForReservation(int $tenantId, int $reservationId): array
    {
        return DB::table('availability_timeline')
            ->where('tenant_id', $tenantId)
            ->where('reservation_id', $reservationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($row) => $this->mapRow($row))
            ->toArray();
    }

    /**
     * Get latest event for each date in range.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param array $dates
     * @return array Date => Latest event
     */
    public function getLatestEventsForDates(int $tenantId, int $propertyId, array $dates): array
    {
        $events = DB::table('availability_timeline')
            ->where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->whereIn('date', $dates)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('date')
            ->map(fn($group) => $this->mapRow($group->first()));

        $result = [];
        foreach ($dates as $date) {
            $result[$date] = $events[$date] ?? null;
        }

        return $result;
    }

    /**
     * Check if timeline is empty for a date range (no events ever recorded).
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function isTimelineEmpty(int $tenantId, int $propertyId, string $startDate, string $endDate): bool
    {
        return DB::table('availability_timeline')
            ->where('tenant_id', $tenantId)
            ->where('property_id', $propertyId)
            ->where('date', '>=', $startDate)
            ->where('date', '<', $endDate)
            ->doesntExist();
    }

    /*=======================================================================
     * Replay
     *=======================================================================*/

    /**
     * Reconstruct availability state from timeline events.
     *
     * Replay is read-only. It does not modify PropertyAvailability.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array Date => Canonical state
     */
    public function replayAvailability(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $events = $this->getTimeline($tenantId, $propertyId, $startDate, $endDate);

        // Apply events in order to reconstruct state
        $states = [];
        foreach ($events as $event) {
            $date = $event['date'];
            $states[$date] = $event['new_state'];
        }

        return $states;
    }

    /*=======================================================================
     * Validation
     *=======================================================================*/

    /**
     * Verify timeline integrity.
     *
     * Returns any anomalies found in the timeline.
     *
     * @param int $tenantId
     * @param int $propertyId
     * @param string $startDate
     * @param string $endDate
     * @return array Anomalies (empty if clean)
     */
    public function verifyIntegrity(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array {
        $anomalies = [];
        $events = $this->getTimeline($tenantId, $propertyId, $startDate, $endDate);

        // Check for duplicate latest-state events on same date
        $dateEvents = collect($events)->groupBy('date');
        foreach ($dateEvents as $date => $group) {
            $latest = $group->last();
            if ($latest['event_type'] === self::EVENT_REBUILD) {
                // Rebuild events can overwrite, this is expected
                continue;
            }

            // Check if there are conflicting states
            $states = $group->pluck('new_state')->unique()->values();
            if ($states->count() > 1) {
                $anomalies[] = [
                    'type' => 'CONFLICTING_STATES',
                    'date' => $date,
                    'property_id' => $propertyId,
                    'state_count' => $states->count(),
                ];
            }
        }

        return $anomalies;
    }

    /*=======================================================================
     * Private Helpers
     *=======================================================================*/

    private function mapRow($row): array
    {
        return [
            'id' => $row->id,
            'tenant_id' => $row->tenant_id,
            'property_id' => $row->property_id,
            'date' => $row->date,
            'event_type' => $row->event_type,
            'previous_state' => json_decode($row->previous_state, true),
            'new_state' => json_decode($row->new_state, true),
            'reservation_id' => $row->reservation_id,
            'source' => $row->source,
            'actor_id' => $row->actor_id,
            'actor_type' => $row->actor_type,
            'correlation_id' => $row->correlation_id,
            'metadata' => $row->metadata ? json_decode($row->metadata, true) : [],
            'created_at' => $row->created_at,
        ];
    }
}
