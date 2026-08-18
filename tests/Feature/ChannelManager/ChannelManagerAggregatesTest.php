<?php

namespace Tests\Feature\ChannelManager;

use App\Domain\ChannelManager\Aggregates\AvailabilitySyncAggregate;
use App\Domain\ChannelManager\Aggregates\ChannelManagerAggregate;
use App\Domain\ChannelManager\Models\ChannelApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Channel Manager Aggregates Test
 *
 * Sprint 13 E01: Domain Foundation
 *
 * Tests:
 * ✓ AvailabilitySyncAggregate: conflict detection, idempotency, replay
 * ✓ ChannelManagerAggregate: connection state, sync job lifecycle
 */
class ChannelManagerAggregatesTest extends TestCase
{
    use RefreshDatabase;
    // ─── AvailabilitySyncAggregate Tests ─────────────────────────────

    /** @test */
    public function it_blocks_availability_for_date(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->setAvailability('2026-08-01', false);

        $state = $aggregate->getDateState('2026-08-01');
        $this->assertFalse($state);
    }

    /** @test */
    public function it_unblocks_availability_for_date(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->setAvailability('2026-08-01', false);
        $aggregate->setAvailability('2026-08-01', true);

        $state = $aggregate->getDateState('2026-08-01');
        $this->assertTrue($state);
    }

    /** @test */
    public function it_detects_conflict_when_remote_differs_from_local(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        // Local: blocked
        $aggregate->setAvailability('2026-08-01', false);

        // Remote: available (different state = conflict)
        $conflict = $aggregate->receiveAvailability('2026-08-01', true);

        $this->assertNotNull($conflict);
        $this->assertEquals('2026-08-01', $conflict['date']);
        $this->assertFalse($conflict['local_state']); // local = blocked
        $this->assertTrue($conflict['remote_state']); // remote = available
        $this->assertEquals(1, $aggregate->getConflictCount());
    }

    /** @test */
    public function it_does_not_conflict_when_states_match(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        // Local: blocked
        $aggregate->setAvailability('2026-08-01', false);

        // Remote: also blocked (same state = no conflict)
        $conflict = $aggregate->receiveAvailability('2026-08-01', false);

        $this->assertNull($conflict);
        $this->assertEquals(0, $aggregate->getConflictCount());
    }

    /** @test */
    public function it_returns_null_conflict_when_no_local_state(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        // No local state, receive remote
        $conflict = $aggregate->receiveAvailability('2026-08-01', true);

        $this->assertNull($conflict);
        $this->assertEquals(0, $aggregate->getConflictCount());
    }

    /** @test */
    public function it_tracks_dirty_dates(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->setAvailability('2026-08-01', false);
        $aggregate->setAvailability('2026-08-02', false);
        $aggregate->setAvailability('2026-08-03', true);

        $dirty = $aggregate->getDirtyDates();

        // All 3 dates are dirty: 2 blocked + 1 unblocked
        $this->assertCount(3, $dirty);
        $this->assertArrayHasKey('2026-08-01', $dirty);
        $this->assertArrayHasKey('2026-08-02', $dirty);
        $this->assertArrayHasKey('2026-08-03', $dirty);
    }

    /** @test */
    public function it_marks_date_as_synced(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->setAvailability('2026-08-01', false);
        $dirtyBefore = $aggregate->getDirtyDates();
        $this->assertArrayHasKey('2026-08-01', $dirtyBefore);

        $aggregate->markSynced('2026-08-01');
        $dirtyAfter = $aggregate->getDirtyDates();
        $this->assertArrayNotHasKey('2026-08-01', $dirtyAfter);
    }

    /** @test */
    public function it_resolves_conflict_and_clears_count(): void
    {
        $aggregate = new AvailabilitySyncAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        // Create conflict
        $aggregate->setAvailability('2026-08-01', false);
        $aggregate->receiveAvailability('2026-08-01', true);
        $this->assertEquals(1, $aggregate->getConflictCount());

        // Resolve conflict
        $aggregate->resolveConflict('local_wins', [
            'date' => '2026-08-01',
            'available' => false,
        ]);

        $this->assertEquals(0, $aggregate->getConflictCount());
    }

    // ─── ChannelManagerAggregate Tests ─────────────────────────────

    /** @test */
    public function it_connects_channel(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $this->assertFalse($aggregate->isConnected());

        $aggregate->connectChannel();

        $this->assertTrue($aggregate->isConnected());
        $this->assertCount(1, $aggregate->getUncommittedEvents());
    }

    /** @test */
    public function it_disconnects_channel(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->connectChannel();
        $this->assertTrue($aggregate->isConnected());

        $aggregate->disconnectChannel();

        $this->assertFalse($aggregate->isConnected());
    }

    /** @test */
    public function it_is_idempotent_on_connect(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->connectChannel();
        $aggregate->connectChannel(); // Idempotent

        // Should only have ONE event
        $this->assertCount(1, $aggregate->getUncommittedEvents());
    }

    /** @test */
    public function it_records_sync_job_lifecycle(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $this->assertFalse($aggregate->hasPendingSyncs());

        $aggregate->startSyncJob('push');
        $this->assertTrue($aggregate->hasPendingSyncs());

        $aggregate->completeSyncJob('push', 5);

        $this->assertFalse($aggregate->hasPendingSyncs());
        $this->assertEquals('success', $aggregate->getState()['last_sync_status']);
    }

    /** @test */
    public function it_records_sync_job_failure(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->startSyncJob('push');
        $aggregate->failSyncJob('push', 'API timeout');

        $state = $aggregate->getState();
        $this->assertEquals('failed', $state['last_sync_status']);
        $this->assertFalse($aggregate->hasPendingSyncs());
    }

    /** @test */
    public function it_pushes_availability_and_records_result(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $aggregate->connectChannel();
        $aggregate->pushAvailability(
            ['date' => '2026-08-01', 'available' => false],
            ChannelApiResponse::success('ref-123')
        );

        $state = $aggregate->getState();
        $this->assertEquals('success', $state['last_sync_status']);
        $this->assertNotNull($state['last_sync_at']);
    }

    /** @test */
    public function it_records_conflict_on_push(): void
    {
        $aggregate = new ChannelManagerAggregate(
            aggregateId: 1,
            tenantId: 1,
            channelId: 'airbnb',
            propertyId: 100,
        );

        $response = ChannelApiResponse::failure('Conflict', 'CONFLICT')
            ->withMetadata([
                'conflict' => [
                    'date' => '2026-08-01',
                    'local_state' => false,
                    'remote_state' => true,
                ],
            ]);

        $aggregate->connectChannel();
        $aggregate->pushAvailability(
            ['date' => '2026-08-01', 'available' => false],
            $response
        );

        $state = $aggregate->getState();
        $this->assertEquals('failed', $state['last_sync_status']);
    }
}
