<?php

namespace App\Domain\CQRS;

use App\Models\EtkiAlaniOlayi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Abstract Class AggregateRoot
 *
 * SAB Phase 15 Sprint 1: CQRS Event Sourcing Base Class
 * Provides event recording, replay, and state reconstruction capabilities.
 *
 * Anayasal Kararlar:
 * - Madde 1: All domain state changes MUST be recorded as events
 * - Madde 2: Events are immutable and append-only
 * - Madde 3: Aggregate state can be reconstructed from event stream
 * - Madde 4: Tenant isolation enforced at event level
 *
 * @package App\Domain\CQRS
 */
abstract class AggregateRoot
{
    /**
     * Uncommitted domain events
     *
     * @var array
     */
    protected array $uncommittedEvents = [];

    /**
     * Current sequence number for this aggregate
     *
     * @var int
     */
    protected int $currentSequence = 0;

    /**
     * Aggregate type identifier
     *
     * @var string
     */
    protected string $aggregateType;

    /**
     * Aggregate ID
     *
     * @var int
     */
    protected int $aggregateId;

    /**
     * Tenant ID for isolation
     *
     * @var int
     */
    protected int $tenantId;

    /**
     * AggregateRoot constructor.
     *
     * @param int $aggregateId
     * @param int $tenantId
     */
    public function __construct(int $aggregateId, int $tenantId)
    {
        $this->aggregateId = $aggregateId;
        $this->tenantId = $tenantId;
        $this->aggregateType = static::class;

        // Load current sequence from event store
        $this->currentSequence = $this->getLastSequenceNumber();
    }

    /**
     * Record a domain event (in-memory, not persisted yet)
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function recordEvent(string $eventType, array $payload): void
    {
        $this->uncommittedEvents[] = [
            'event_type' => $eventType,
            'payload' => $payload,
            'sequence_number' => ++$this->currentSequence,
        ];
    }

    /**
     * Commit all uncommitted events to the event store
     *
     * SAB Madde 2: Thin Controller - Transaction management in service layer
     *
     * @return void
     * @throws \Throwable
     */
    public function commit(): void
    {
        if (empty($this->uncommittedEvents)) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->uncommittedEvents as $event) {
                EtkiAlaniOlayi::create([
                    'tenant_id' => $this->tenantId,
                    'aggregate_type' => $this->aggregateType,
                    'aggregate_id' => $this->aggregateId,
                    'event_type' => $event['event_type'],
                    'sequence_number' => $event['sequence_number'],
                    'payload' => $event['payload'],
                    'user_id' => Auth::id(),
                    'ip_adresi' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        // Clear uncommitted events after successful commit
        $this->uncommittedEvents = [];
    }

    /**
     * Replay all events to reconstruct aggregate state
     *
     * @return void
     */
    public function replayEvents(): void
    {
        $events = EtkiAlaniOlayi::forAggregate($this->aggregateType, $this->aggregateId)
            ->orderBy('sequence_number')
            ->get();

        foreach ($events as $event) {
            $this->applyEvent($event->event_type, $event->payload);
        }
    }

    /**
     * Apply an event to update aggregate state
     * Must be implemented by concrete aggregates
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    abstract protected function applyEvent(string $eventType, array $payload): void;

    /**
     * Get the last sequence number for this aggregate
     *
     * @return int
     */
    protected function getLastSequenceNumber(): int
    {
        $lastEvent = EtkiAlaniOlayi::forAggregate($this->aggregateType, $this->aggregateId)
            ->orderByDesc('sequence_number')
            ->first();

        return $lastEvent ? $lastEvent->sequence_number : 0;
    }

    /**
     * Get all uncommitted events
     *
     * @return array
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Get aggregate ID
     *
     * @return int
     */
    public function getAggregateId(): int
    {
        return $this->aggregateId;
    }

    /**
     * Get tenant ID
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }
}
