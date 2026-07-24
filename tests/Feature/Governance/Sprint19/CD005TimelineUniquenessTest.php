<?php

namespace Tests\Feature\Governance\Sprint19;

use App\Models\CommercialOffering;
use App\Models\Hermes\HermesEventLog;
use App\Models\Property;
use App\Models\PropertyWorkspace;
use App\Services\Property\CommercialOfferingService;
use App\Domain\Property\Events\CommercialOfferingCreated;
use App\Domain\Property\Events\CommercialOfferingActivated;
use App\Listeners\Property\RecordCommercialOfferingOnTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CD005TimelineUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private RecordCommercialOfferingOnTimeline $listener;
    private CommercialOfferingService $offeringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new RecordCommercialOfferingOnTimeline();
        $this->offeringService = new CommercialOfferingService();
    }

    /**
     * Requirement 1: Same source event processed twice creates exactly one record.
     */
    public function test_same_source_event_processed_twice_creates_one_record(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'CD005 Test Workspace 1',
            'code' => 'WS-CD5-01',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cd5-1',
        ]);

        $offering = $this->offeringService->createOffering($property, [
            'offering_type' => 'SATILIK',
            'fiyat' => 5000000.00,
        ]);

        $event = new CommercialOfferingCreated($offering);

        // Process twice
        $this->listener->handleCreated($event);
        $this->listener->handleCreated($event);

        $count = HermesEventLog::where('tenant_id', 1)
            ->where('projection_type', 'CommercialOfferingCreated')
            ->where('source_event_id', 'offering-' . $offering->id . '-created')
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Requirement 2: Concurrent duplicate DB insertion is blocked by unique constraint.
     */
    public function test_concurrent_duplicate_processing_resolves_idempotently_without_error(): void
    {
        $tenantId = 1;
        $projectionType = 'TestProjection';
        $sourceEventId = 'source-event-100';

        // First creation succeeds
        HermesEventLog::create([
            'tenant_id' => $tenantId,
            'event_name' => 'Test Event',
            'event_class' => 'TestEventClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sourceEventId,
            'occurred_at' => now(),
            'payload' => ['test' => true],
        ]);

        // Direct concurrent duplicate insert attempt must throw QueryException due to DB constraint
        $duplicateAttemptCaught = false;
        try {
            HermesEventLog::create([
                'tenant_id' => $tenantId,
                'event_name' => 'Test Event Duplicate',
                'event_class' => 'TestEventClass',
                'projection_type' => $projectionType,
                'source_event_id' => $sourceEventId,
                'occurred_at' => now(),
                'payload' => ['test' => true],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $duplicateAttemptCaught = true;
        }

        $this->assertTrue($duplicateAttemptCaught, 'DB Unique constraint must reject duplicate tenant/projection/source combination.');

        $count = HermesEventLog::where('tenant_id', $tenantId)
            ->where('projection_type', $projectionType)
            ->where('source_event_id', $sourceEventId)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Requirement 3: Different source events create separate records.
     */
    public function test_different_source_events_create_separate_records(): void
    {
        HermesEventLog::create([
            'tenant_id' => 1,
            'event_name' => 'Event A',
            'event_class' => 'EventAClass',
            'projection_type' => 'TypeA',
            'source_event_id' => 'source-1',
            'occurred_at' => now(),
            'payload' => [],
        ]);

        HermesEventLog::create([
            'tenant_id' => 1,
            'event_name' => 'Event B',
            'event_class' => 'EventBClass',
            'projection_type' => 'TypeA',
            'source_event_id' => 'source-2',
            'occurred_at' => now(),
            'payload' => [],
        ]);

        $this->assertEquals(2, HermesEventLog::where('tenant_id', 1)->count());
    }

    /**
     * Requirement 4: Same source reference in different tenants does not conflict.
     */
    public function test_same_source_reference_in_different_tenants_does_not_conflict(): void
    {
        $projectionType = 'TenantTestType';
        $sourceEventId = 'shared-source-id';

        // Tenant 1
        HermesEventLog::create([
            'tenant_id' => 1,
            'event_name' => 'Tenant 1 Event',
            'event_class' => 'TestClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sourceEventId,
            'occurred_at' => now(),
            'payload' => [],
        ]);

        // Tenant 2 with same projection_type and source_event_id
        HermesEventLog::create([
            'tenant_id' => 2,
            'event_name' => 'Tenant 2 Event',
            'event_class' => 'TestClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sourceEventId,
            'occurred_at' => now(),
            'payload' => [],
        ]);

        $this->assertEquals(1, HermesEventLog::where('tenant_id', 1)->count());
        $this->assertEquals(1, HermesEventLog::where('tenant_id', 2)->count());
    }

    /**
     * Requirement 5: Replay does not duplicate existing projection.
     */
    public function test_replay_does_not_duplicate_existing_projection(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'CD005 Replay Test Workspace',
            'code' => 'WS-CD5-REP',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cd5-rep',
        ]);

        $offering = $this->offeringService->createOffering($property, [
            'offering_type' => 'KIRALIK',
            'fiyat' => 40000.00,
        ]);

        $event = new CommercialOfferingActivated($offering);

        // Replay 3 times
        for ($i = 0; $i < 3; $i++) {
            $this->listener->handleActivated($event);
        }

        $count = HermesEventLog::where('tenant_id', 1)
            ->where('projection_type', 'CommercialOfferingActivated')
            ->where('source_event_id', 'offering-' . $offering->id . '-activated')
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Requirement 6: Migration rollback succeeds.
     */
    public function test_migration_rollback_succeeds(): void
    {
        $this->assertTrue(Schema::hasColumn('hermes_event_logs', 'projection_type'));
        $this->assertTrue(Schema::hasColumn('hermes_event_logs', 'source_event_id'));

        // Execute rollback of CD-005 migration
        $migration = require database_path('migrations/2026_07_25_000007_add_uniqueness_to_hermes_event_logs_table.php');
        $migration->down();

        $this->assertFalse(Schema::hasColumn('hermes_event_logs', 'projection_type'));
        $this->assertFalse(Schema::hasColumn('hermes_event_logs', 'source_event_id'));

        // Re-run migration up for subsequent test isolation
        $migration->up();
        $this->assertTrue(Schema::hasColumn('hermes_event_logs', 'projection_type'));
    }
}
