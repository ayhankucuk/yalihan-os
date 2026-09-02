<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PropertyWorkspace\Timeline;

use App\Domain\PropertyWorkspace\Timeline\WorkspaceTimeline;
use App\Domain\PropertyWorkspace\Timeline\WorkspaceEvent;
use App\Domain\PropertyWorkspace\Timeline\Events\WorkspaceInitiated;
use App\Domain\PropertyWorkspace\Timeline\Events\IntentSelected;
use App\Domain\PropertyWorkspace\Timeline\Events\TemplateApplied;
use App\Domain\PropertyWorkspace\Timeline\Events\StateTransition;
use App\Domain\PropertyWorkspace\Timeline\Events\CapabilityExecuted;
use App\Domain\PropertyWorkspace\Timeline\Events\CapabilityFailed;
use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use App\Models\EtkiAlaniOlayi;
use App\Models\Ilan;
use App\Models\PropertyWorkspace;
use App\Models\SaaS\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class WorkspaceTimelineTest
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Tests for WorkspaceTimeline event sourcing functionality.
 *
 * @package Tests\Unit\Domain\PropertyWorkspace\Timeline
 */
class WorkspaceTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Tenant $otherTenant;
    protected User $user;
    protected User $otherUser;
    protected Ilan $ilan;
    protected PropertyWorkspace $workspace;
    protected WorkspaceTimeline $timeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Timeline Test Tenant',
            'domain' => 'timeline.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'domain' => 'other.test',
            'aktiflik_durumu' => 1,
        ]);

        $this->user = User::create([
            'name' => 'Timeline User',
            'email' => 'timeline@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
        ]);

        $this->otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->otherTenant->id,
        ]);

        $this->ilan = Ilan::create([
            'tenant_id' => $this->tenant->id,
            'baslik' => 'Test Ilan for Timeline',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        $this->workspace = PropertyWorkspace::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $this->ilan->id,
            'workspace_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'intent' => 'create',
            'state' => PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
        ]);

        $this->timeline = new WorkspaceTimeline();
    }

    /** @test */
    public function it_appends_workspace_initiated_event_to_timeline(): void
    {
        $event = new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create',
            templateId: null
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $this->assertDatabaseHas('etki_alani_olaylari', [
            'tenant_id' => $this->tenant->id,
            'aggregate_type' => 'App\\Domain\\PropertyWorkspace\\PropertyWorkspaceAggregate',
            'event_type' => 'WorkspaceInitiated',
        ]);
    }

    /** @test */
    public function it_appends_intent_selected_event_to_timeline(): void
    {
        $event = new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance',
            previousIntent: 'create'
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(1, $events);
        $this->assertEquals('IntentSelected', $events->first()->event_type);
    }

    /** @test */
    public function it_appends_template_applied_event_to_timeline(): void
    {
        $event = new TemplateApplied(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            templateId: 'template-123',
            templateData: ['name' => 'Premium Template']
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(1, $events);
        $this->assertEquals('TemplateApplied', $events->first()->event_type);
    }

    /** @test */
    public function it_appends_state_transition_event_to_timeline(): void
    {
        $event = new StateTransition(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            fromState: PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
            toState: PropertyWorkspaceAggregate::STATE_DRAFT,
            triggeredBy: 'user'
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(1, $events);
        $this->assertEquals('StateTransition', $events->first()->event_type);
    }

    /** @test */
    public function it_appends_capability_executed_event_to_timeline(): void
    {
        $event = new CapabilityExecuted(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            capability: 'photo_enhancement',
            result: ['status' => 'success', 'photos_processed' => 5],
            durationMs: 1500
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(1, $events);
        $this->assertEquals('CapabilityExecuted', $events->first()->event_type);
    }

    /** @test */
    public function it_appends_capability_failed_event_to_timeline(): void
    {
        $event = new CapabilityFailed(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            capability: 'ai_description',
            error: 'Model timeout exceeded',
            context: ['attempt' => 1, 'max_retries' => 3],
            durationMs: 30000
        );

        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, $event);

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(1, $events);
        $this->assertEquals('CapabilityFailed', $events->first()->event_type);
    }

    /** @test */
    public function it_replays_events_to_reconstruct_workspace_state(): void
    {
        $this->actingAs($this->user);

        // Append multiple events
        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new TemplateApplied(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            templateId: 'template-456'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new StateTransition(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            fromState: PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
            toState: PropertyWorkspaceAggregate::STATE_DRAFT
        ));

        // Replay events
        $state = $this->timeline->replay($this->workspace->workspace_uuid);

        // Verify reconstructed state
        $this->assertEquals($this->workspace->workspace_uuid, $state['workspace_id']);
        $this->assertEquals($this->tenant->id, $state['tenant_id']);
        // replay() now uses property_id (canonical schema, not legacy ilan_id)
        $this->assertEquals($this->workspace->property_id, $state['property_id']);
        $this->assertEquals('enhance', $state['intent']);
        $this->assertEquals('template-456', $state['template_id']);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $state['state']);
    }

    /** @test */
    public function it_returns_correct_event_count(): void
    {
        $this->actingAs($this->user);

        $this->assertEquals(0, $this->timeline->getEventCount($this->workspace->workspace_uuid));

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $this->assertEquals(1, $this->timeline->getEventCount($this->workspace->workspace_uuid));

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        ));

        $this->assertEquals(2, $this->timeline->getEventCount($this->workspace->workspace_uuid));
    }

    /** @test */
    public function it_returns_last_event(): void
    {
        $this->actingAs($this->user);

        $event1 = new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        );

        $event2 = new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        );

        $this->timeline->append($this->workspace->workspace_uuid, $event1);
        $this->timeline->append($this->workspace->workspace_uuid, $event2);

        $lastEvent = $this->timeline->getLastEvent($this->workspace->workspace_uuid);

        $this->assertNotNull($lastEvent);
        $this->assertEquals('IntentSelected', $lastEvent->event_type);
    }

    /** @test */
    public function it_returns_null_for_last_event_when_no_events(): void
    {
        $this->actingAs($this->user);

        $lastEvent = $this->timeline->getLastEvent($this->workspace->workspace_uuid);

        $this->assertNull($lastEvent);
    }

    /** @test */
    public function it_returns_events_since_timestamp(): void
    {
        $this->actingAs($this->user);

        $now = \Illuminate\Support\Carbon::now();
        \Illuminate\Support\Carbon::setTestNow($now);
        $sinceTime = \Illuminate\Support\Carbon::now();

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        \Illuminate\Support\Carbon::setTestNow($now->copy()->addSecond());
        $sinceTime2 = \Illuminate\Support\Carbon::now();

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        ));

        // Get events since first timestamp (should include all)
        $eventsSince = $this->timeline->getEventsSince(
            $this->workspace->workspace_uuid,
            $sinceTime
        );
        $this->assertCount(2, $eventsSince);

        // Get events since second timestamp (should include only second)
        $eventsSince2 = $this->timeline->getEventsSince(
            $this->workspace->workspace_uuid,
            $sinceTime2
        );
        $this->assertCount(1, $eventsSince2);

        \Illuminate\Support\Carbon::setTestNow(); // Reset test time
    }

    /** @test */
    public function it_returns_unique_event_types(): void
    {
        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'create' // Same type, different payload
        ));

        $eventTypes = $this->timeline->getEventTypes($this->workspace->workspace_uuid);

        $this->assertCount(2, $eventTypes);
        $this->assertContains('WorkspaceInitiated', $eventTypes);
        $this->assertContains('IntentSelected', $eventTypes);
    }

    /** @test */
    public function it_returns_audit_trail_with_correct_format(): void
    {
        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $auditTrail = $this->timeline->getAuditTrail($this->workspace->workspace_uuid);

        $this->assertIsArray($auditTrail);
        $this->assertCount(1, $auditTrail);

        $firstEntry = $auditTrail[0];
        $this->assertArrayHasKey('sequence', $firstEntry);
        $this->assertArrayHasKey('event_type', $firstEntry);
        $this->assertArrayHasKey('occurred_at', $firstEntry);
        $this->assertArrayHasKey('payload', $firstEntry);
        $this->assertArrayHasKey('user_id', $firstEntry);
    }

    /** @test */
    public function it_enforces_tenant_isolation_on_get_events(): void
    {
        $this->actingAs($this->user);

        // Create workspace for other tenant
        $otherIlan = Ilan::create([
            'tenant_id' => $this->otherTenant->id,
            'baslik' => 'Other Tenant Ilan',
            'yayin_durumu' => 'aktif',
            'aktiflik_durumu' => 1,
        ]);

        $otherWorkspace = PropertyWorkspace::create([
            'tenant_id' => $this->otherTenant->id,
            'ilan_id' => $otherIlan->id,
            'workspace_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'intent' => 'create',
            'state' => PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
        ]);

        // Add event to other tenant's workspace
        EtkiAlaniOlayi::create([
            'tenant_id' => $this->otherTenant->id,
            'aggregate_type' => 'App\\Domain\\PropertyWorkspace\\PropertyWorkspaceAggregate',
            'aggregate_id' => $otherWorkspace->id,
            'event_type' => 'WorkspaceInitiated',
            'sequence_number' => 1,
            'payload' => [
                'workspace_id' => $otherWorkspace->workspace_uuid,
                'tenant_id' => $this->otherTenant->id,
                'ilan_id' => $otherIlan->id,
                'intent' => 'create',
                'timestamp' => now()->toIso8601String(),
            ],
            'user_id' => $this->otherUser->id,
        ]);

        // Add event to current tenant's workspace
        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        // Verify current user only sees their tenant's events
        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);
        $this->assertCount(1, $events);

        // Verify other tenant's workspace returns empty for current user
        $otherEvents = $this->timeline->getEvents($otherWorkspace->workspace_uuid);
        $this->assertCount(0, $otherEvents);
    }

    /** @test */
    public function it_enforces_tenant_isolation_on_replay(): void
    {
        $this->actingAs($this->user);

        // Create another workspace for the same tenant
        $secondWorkspace = PropertyWorkspace::create([
            'tenant_id' => $this->tenant->id,
            'ilan_id' => $this->ilan->id,
            'workspace_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'intent' => 'create',
            'state' => PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
        ]);

        // Add event to second workspace
        $this->timeline->append($secondWorkspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $secondWorkspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'enhance'
        ));

        // Verify state is correctly isolated
        $this->workspace->update(['intent' => null]);
        $state1 = $this->timeline->replay($this->workspace->workspace_uuid);
        $state2 = $this->timeline->replay($secondWorkspace->workspace_uuid);

        $this->assertNull($state1['intent']); // No events for workspace 1
        $this->assertEquals('enhance', $state2['intent']); // Event for workspace 2
    }

    /** @test */
    public function it_throws_exception_when_appending_to_nonexistent_workspace(): void
    {
        $this->actingAs($this->user);

        $event = new WorkspaceInitiated(
            workspaceId: 'nonexistent-uuid',
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Workspace not found');

        $this->timeline->append('nonexistent-uuid', $event);
    }

    /** @test */
    public function it_throws_exception_on_tenant_mismatch(): void
    {
        $this->actingAs($this->user);

        $event = new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->otherTenant->id, // Wrong tenant!
            ilanId: $this->ilan->id,
            intent: 'create'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant mismatch');

        $this->timeline->append($this->workspace->workspace_uuid, $event);
    }

    /** @test */
    public function it_handles_nonexistent_workspace_gracefully(): void
    {
        $this->actingAs($this->user);

        $events = $this->timeline->getEvents('nonexistent-uuid');
        $this->assertCount(0, $events);

        $count = $this->timeline->getEventCount('nonexistent-uuid');
        $this->assertEquals(0, $count);

        $lastEvent = $this->timeline->getLastEvent('nonexistent-uuid');
        $this->assertNull($lastEvent);

        $eventTypes = $this->timeline->getEventTypes('nonexistent-uuid');
        $this->assertCount(0, $eventTypes);
    }

    /** @test */
    public function it_handles_replay_with_empty_event_store(): void
    {
        $this->actingAs($this->user);

        $state = $this->timeline->replay($this->workspace->workspace_uuid);

        $this->assertIsArray($state);
        $this->assertEquals($this->workspace->workspace_uuid, $state['workspace_id']);
        $this->assertEquals($this->tenant->id, $state['tenant_id']);
        // State comes from the workspace model directly when no events exist (property_id from DB, not legacy ilan_id)
        $this->assertEquals($this->workspace->property_id, $state['property_id']);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED, $state['state']);
    }

    /** @test */
    public function it_increments_sequence_numbers_correctly(): void
    {
        $this->actingAs($this->user);

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new IntentSelected(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            intent: 'enhance'
        ));

        $this->timeline->append($this->workspace->workspace_uuid, new StateTransition(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            fromState: PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED,
            toState: PropertyWorkspaceAggregate::STATE_DRAFT
        ));

        $events = $this->timeline->getEvents($this->workspace->workspace_uuid);

        $this->assertCount(3, $events);
        $this->assertEquals(1, $events[0]->sequence_number);
        $this->assertEquals(2, $events[1]->sequence_number);
        $this->assertEquals(3, $events[2]->sequence_number);
    }

    /** @test */
    public function it_can_be_instantiated_with_empty_constructor(): void
    {
        $timeline = new WorkspaceTimeline();

        $this->assertInstanceOf(WorkspaceTimeline::class, $timeline);
    }

    /** @test */
    public function workspace_event_base_class_has_correct_properties(): void
    {
        $event = new class('ws-123', 1, []) extends WorkspaceEvent {
            public function __construct(
                public readonly string $workspaceId,
                public readonly int $tenantId,
                public array $payload = []
            ) {
                parent::__construct($workspaceId, $tenantId, $payload);
            }
        };

        $this->assertEquals('ws-123', $event->workspace_id);
        $this->assertEquals(1, $event->tenant_id);
        $this->assertNotEmpty($event->occurred_at);
        $this->assertIsString($event->event_type);
        $this->assertIsArray($event->payload);
    }

    /** @test */
    public function events_implement_json_serializable(): void
    {
        $event = new WorkspaceInitiated(
            workspaceId: 'ws-456',
            tenantId: 1,
            ilanId: 100,
            intent: 'create',
            templateId: 'template-789'
        );

        $json = json_encode($event);

        $this->assertIsString($json);

        $decoded = json_decode($json, true);

        $this->assertEquals('ws-456', $decoded['workspace_id']);
        $this->assertEquals(1, $decoded['tenant_id']);
        $this->assertEquals(100, $decoded['payload']['ilan_id']);
        $this->assertEquals('create', $decoded['payload']['intent']);
        $this->assertEquals('template-789', $decoded['payload']['template_id']);
    }

    /** @test */
    public function it_has_events_returns_correct_boolean(): void
    {
        $this->actingAs($this->user);

        $this->assertFalse($this->timeline->hasEvents($this->workspace->workspace_uuid));

        $this->timeline->append($this->workspace->workspace_uuid, new WorkspaceInitiated(
            workspaceId: $this->workspace->workspace_uuid,
            tenantId: $this->tenant->id,
            ilanId: $this->ilan->id,
            intent: 'create'
        ));

        $this->assertTrue($this->timeline->hasEvents($this->workspace->workspace_uuid));
    }
}
