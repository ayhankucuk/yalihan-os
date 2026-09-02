<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PropertyWorkspace;

use App\Domain\PropertyWorkspace\Exceptions\InvalidStateTransitionException;
use App\Domain\PropertyWorkspace\PropertyWorkspaceAggregate;
use App\Models\PropertyWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class PropertyWorkspaceAggregateTest
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Tests for PropertyWorkspace aggregate and state machine.
 *
 * @package Tests\Unit\Domain\PropertyWorkspace
 */
class PropertyWorkspaceAggregateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test workspace creation
     */
    public function test_workspace_can_be_created(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(
            propertyId: 1,
            intent: 'rental_listing',
            tenantId: 42,
            templateId: 'template_123'
        );

        // Note: workspace_id is set by initializeWorkspace() in service layer
        $this->assertEquals(1, $aggregate->getState()['property_id']);
        $this->assertEquals('rental_listing', $aggregate->getState()['intent']);
        $this->assertEquals('template_123', $aggregate->getState()['template_id']);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED, $aggregate->getWorkspaceState());
        // S6.1-E01: Verify tenantId is correctly propagated without auth() session dependency
        $this->assertEquals(42, $aggregate->getState()['tenant_id']);
    }

    /**
     * Test valid state transition: workspace_created → draft
     */
    public function test_can_transition_from_workspace_created_to_draft(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $aggregate->getWorkspaceState());
    }

    /**
     * Test valid state transition: draft → ready_for_review
     */
    public function test_can_transition_from_draft_to_ready_for_review(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW, $aggregate->getWorkspaceState());
    }

    /**
     * Test valid state transition: ready_for_review → published
     */
    public function test_can_transition_from_ready_for_review_to_published(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_PUBLISHED, $aggregate->getWorkspaceState());
    }

    /**
     * Test valid state transition: ready_for_review → draft (rejected)
     */
    public function test_can_reject_from_ready_for_review_to_draft(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $aggregate->getWorkspaceState());
    }

    /**
     * Test valid state transition: published → archived
     */
    public function test_can_archive_from_published(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_ARCHIVED);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_ARCHIVED, $aggregate->getWorkspaceState());
    }

    /**
     * Test valid state transition: published → draft (unpublished)
     */
    public function test_can_unpublish_from_published_to_draft(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $aggregate->getWorkspaceState());
    }

    /**
     * Test admin can archive from any state
     */
    public function test_admin_can_archive_from_any_state(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');

        // Admin archives directly from workspace_created
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_ARCHIVED, isAdmin: true);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_ARCHIVED, $aggregate->getWorkspaceState());
    }

    /**
     * Test invalid state transition throws exception
     */
    public function test_invalid_state_transition_throws_exception(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');

        $this->expectException(InvalidStateTransitionException::class);

        // Cannot go directly from workspace_created to published
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);
    }

    /**
     * Test cannot transition from archived state
     */
    public function test_cannot_transition_from_archived_state(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_ARCHIVED);

        $this->expectException(InvalidStateTransitionException::class);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
    }

    /**
     * Test intent selection
     */
    public function test_can_select_intent(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'initial_intent');

        $aggregate->selectIntent('new_intent');

        $this->assertEquals('new_intent', $aggregate->getState()['intent']);
    }

    /**
     * Test template application
     */
    public function test_can_apply_template(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');

        $aggregate->applyTemplate('new_template_id');

        $this->assertEquals('new_template_id', $aggregate->getState()['template_id']);
    }

    /**
     * Test events are recorded on state transition
     * Note: createWorkspace() creates aggregate in memory only.
     * WorkspaceCreated event is recorded by initializeWorkspace() (service layer).
     */
    public function test_events_are_recorded_on_state_transition(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'test_intent');

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $events = $aggregate->getUncommittedEvents();

        // Only StateChanged event since createWorkspace() doesn't commit to event store
        $this->assertNotEmpty($events);
        $this->assertCount(1, $events);
        $this->assertEquals('StateChanged', $events[0]['event_type']);
    }

    /**
     * Test get valid states
     */
    public function test_get_valid_states(): void
    {
        $states = PropertyWorkspaceAggregate::getValidStates();

        $this->assertContains(PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED, $states);
        $this->assertContains(PropertyWorkspaceAggregate::STATE_DRAFT, $states);
        $this->assertContains(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW, $states);
        $this->assertContains(PropertyWorkspaceAggregate::STATE_PUBLISHED, $states);
        $this->assertContains(PropertyWorkspaceAggregate::STATE_ARCHIVED, $states);
    }

    /**
     * Test get valid transitions from state
     */
    public function test_get_valid_transitions(): void
    {
        $transitions = PropertyWorkspaceAggregate::getValidTransitions(
            PropertyWorkspaceAggregate::STATE_DRAFT
        );

        $this->assertEquals(
            [PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW],
            $transitions
        );
    }

    /**
     * Test full happy path workflow
     */
    public function test_full_happy_path_workflow(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'rental', tenantId: 1);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_WORKSPACE_CREATED, $aggregate->getWorkspaceState());

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $aggregate->getWorkspaceState());

        $aggregate->selectIntent('sale');
        $this->assertEquals('sale', $aggregate->getState()['intent']);

        $aggregate->applyTemplate('premium_template');
        $this->assertEquals('premium_template', $aggregate->getState()['template_id']);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW, $aggregate->getWorkspaceState());

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);
        $this->assertEquals(PropertyWorkspaceAggregate::STATE_PUBLISHED, $aggregate->getWorkspaceState());
    }

    /**
     * Test rejection workflow
     */
    public function test_rejection_workflow(): void
    {
        $aggregate = PropertyWorkspaceAggregate::createWorkspace(1, 'rental', tenantId: 1);

        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);

        // Reviewer rejects
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_DRAFT);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_DRAFT, $aggregate->getWorkspaceState());

        // Make changes and resubmit
        $aggregate->applyTemplate('updated_template');
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_READY_FOR_REVIEW);
        $aggregate->transitionTo(PropertyWorkspaceAggregate::STATE_PUBLISHED);

        $this->assertEquals(PropertyWorkspaceAggregate::STATE_PUBLISHED, $aggregate->getWorkspaceState());
    }
}
