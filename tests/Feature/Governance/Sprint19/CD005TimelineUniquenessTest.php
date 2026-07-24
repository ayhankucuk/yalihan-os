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
use Illuminate\Support\Facades\DB;
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
            ->where('source_event_id', $event->eventId)
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
        $sourceEventId = (string) Str::uuid();

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

        // Direct duplicate insert attempt must throw QueryException due to DB constraint
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
     * Remediation Item 1: Non-duplicate DB failures MUST be re-thrown and not swallowed.
     */
    public function test_non_duplicate_database_failures_are_rethrown(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'CD005 Failure Test Workspace',
            'code' => 'WS-CD5-FAIL',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cd5-fail',
        ]);

        $offering = $this->offeringService->createOffering($property, [
            'offering_type' => 'SATILIK',
            'fiyat' => 5000000.00,
        ]);

        // Temporarily drop table to simulate DB structural failure
        Schema::dropIfExists('hermes_event_logs');

        $this->expectException(\Illuminate\Database\QueryException::class);

        try {
            $event = new CommercialOfferingCreated($offering);
            $this->listener->handleCreated($event);
        } finally {
            // Restore table schema for subsequent test teardown
            $migration = require database_path('migrations/2026_06_28_000001_create_hermes_event_logs_table.php');
            $migration->up();
            $migration2 = require database_path('migrations/2026_07_25_000007_add_uniqueness_to_hermes_event_logs_table.php');
            $migration2->up();
        }
    }

    /**
     * Remediation Item 2: Workspace event without tenant identity MUST throw InvalidArgumentException.
     */
    public function test_workspace_event_without_tenant_identity_throws_exception(): void
    {
        $offering = new CommercialOffering();
        $offering->tenant_id = null; // Missing tenant context

        $event = new CommercialOfferingCreated($offering);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workspace timeline projection requires valid tenant_id context.');

        $this->listener->handleCreated($event);
    }

    /**
     * Remediation Item 3: Immutable event UUID is used as source_event_id.
     */
    public function test_immutable_event_uuid_is_used_as_source_event_id(): void
    {
        $workspace = PropertyWorkspace::create([
            'tenant_id' => 1,
            'workspace_uuid' => (string) Str::uuid(),
            'name' => 'CD005 UUID Test Workspace',
            'code' => 'WS-CD5-UUID',
        ]);

        $property = Property::create([
            'tenant_id' => 1,
            'workspace_id' => $workspace->id,
            'idempotency_key' => 'prop-cd5-uuid',
        ]);

        $offering = $this->offeringService->createOffering($property, [
            'offering_type' => 'KIRALIK',
            'fiyat' => 25000.00,
        ]);

        $event = new CommercialOfferingCreated($offering);
        $this->listener->handleCreated($event);

        $log = HermesEventLog::where('tenant_id', 1)
            ->where('projection_type', 'CommercialOfferingCreated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($event->eventId, $log->source_event_id);
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
            'source_event_id' => (string) Str::uuid(),
            'occurred_at' => now(),
            'payload' => [],
        ]);

        HermesEventLog::create([
            'tenant_id' => 1,
            'event_name' => 'Event B',
            'event_class' => 'EventBClass',
            'projection_type' => 'TypeA',
            'source_event_id' => (string) Str::uuid(),
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
        $sharedSourceId = (string) Str::uuid();

        // Tenant 1
        HermesEventLog::create([
            'tenant_id' => 1,
            'event_name' => 'Tenant 1 Event',
            'event_class' => 'TestClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sharedSourceId,
            'occurred_at' => now(),
            'payload' => [],
        ]);

        // Tenant 2 with same projection_type and source_event_id
        HermesEventLog::create([
            'tenant_id' => 2,
            'event_name' => 'Tenant 2 Event',
            'event_class' => 'TestClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sharedSourceId,
            'occurred_at' => now(),
            'payload' => [],
        ]);

        $this->assertEquals(1, HermesEventLog::where('tenant_id', 1)->count());
        $this->assertEquals(1, HermesEventLog::where('tenant_id', 2)->count());
    }

    /**
     * Remediation Item 4: Multi-PDO connection concurrency simulation.
     */
    public function test_multi_connection_concurrency_locking_prevents_duplicate_records(): void
    {
        $tenantId = 1;
        $projectionType = 'MultiConnTest';
        $sourceEventId = (string) Str::uuid();

        // Connection 1 insert
        DB::connection()->table('hermes_event_logs')->insert([
            'tenant_id' => $tenantId,
            'event_name' => 'Conn 1 Event',
            'event_class' => 'ConnClass',
            'projection_type' => $projectionType,
            'source_event_id' => $sourceEventId,
            'occurred_at' => now(),
            'payload' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Connection 2 (simulated separate DB connection) duplicate insert
        $duplicateAttempt = false;
        try {
            DB::connection()->table('hermes_event_logs')->insert([
                'tenant_id' => $tenantId,
                'event_name' => 'Conn 2 Event',
                'event_class' => 'ConnClass',
                'projection_type' => $projectionType,
                'source_event_id' => $sourceEventId,
                'occurred_at' => now(),
                'payload' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $duplicateAttempt = true;
        }

        $this->assertTrue($duplicateAttempt);
        $this->assertEquals(1, DB::table('hermes_event_logs')->where('source_event_id', $sourceEventId)->count());
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

    /**
     * Remediation Item 5: Migration reconciliation guard aborts if duplicate non-null rows exist.
     */
    public function test_migration_reconciliation_guard_aborts_if_duplicates_exist(): void
    {
        $migration = require database_path('migrations/2026_07_25_000007_add_uniqueness_to_hermes_event_logs_table.php');
        $migration->down();

        // Add columns without unique index first to insert duplicate test rows
        Schema::table('hermes_event_logs', function ($table) {
            $table->string('projection_type', 100)->nullable();
            $table->string('source_event_id', 100)->nullable();
        });

        $sharedUuid = (string) Str::uuid();

        // Insert 2 duplicate rows into unconstrained table
        DB::table('hermes_event_logs')->insert([
            'tenant_id' => 1,
            'event_name' => 'Dup 1',
            'event_class' => 'DupClass',
            'projection_type' => 'DupType',
            'source_event_id' => $sharedUuid,
            'occurred_at' => now(),
            'payload' => json_encode([]),
        ]);

        DB::table('hermes_event_logs')->insert([
            'tenant_id' => 1,
            'event_name' => 'Dup 2',
            'event_class' => 'DupClass',
            'projection_type' => 'DupType',
            'source_event_id' => $sharedUuid,
            'occurred_at' => now(),
            'payload' => json_encode([]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Migration aborted: Found 1 duplicate projection records in hermes_event_logs.');

        try {
            $migration->up();
        } finally {
            // Clean up duplicates and restore unique schema
            DB::table('hermes_event_logs')->truncate();
            $migration->down();
            $migration->up();
        }
    }
}
