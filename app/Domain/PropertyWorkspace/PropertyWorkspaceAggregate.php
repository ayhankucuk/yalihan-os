<?php

declare(strict_types=1);

namespace App\Domain\PropertyWorkspace;

use App\Domain\CQRS\AggregateRoot;
use App\Domain\PropertyWorkspace\Events\IntentSelected;
use App\Domain\PropertyWorkspace\Events\StateChanged;
use App\Domain\PropertyWorkspace\Events\TemplateApplied;
use App\Domain\PropertyWorkspace\Events\WorkspaceCreated;
use App\Domain\PropertyWorkspace\Exceptions\InvalidStateTransitionException;
use App\Models\PropertyWorkspace;
use Illuminate\Support\Str;

/**
 * Class PropertyWorkspaceAggregate
 *
 * Sprint 6.0: PropertyWorkspace Foundation
 * Event-sourced aggregate for property workspace lifecycle.
 *
 * Valid State Transitions:
 * - workspace_created → draft
 * - draft → ready_for_review
 * - ready_for_review → published
 * - ready_for_review → draft (rejected)
 * - published → archived
 * - published → draft (unpublished)
 * - Any state → archived (admin)
 *
 * @package App\Domain\PropertyWorkspace
 */
class PropertyWorkspaceAggregate extends AggregateRoot
{
    /**
     * Workspace states
     */
    public const STATE_WORKSPACE_CREATED = 'workspace_created';
    public const STATE_DRAFT = 'draft';
    public const STATE_READY_FOR_REVIEW = 'ready_for_review';
    public const STATE_PUBLISHED = 'published';
    public const STATE_ARCHIVED = 'archived';

    /**
     * Valid state transitions map
     *
     * @var array<string, array<string>>
     */
    protected const TRANSITIONS = [
        self::STATE_WORKSPACE_CREATED => [self::STATE_DRAFT],
        self::STATE_DRAFT => [self::STATE_READY_FOR_REVIEW],
        self::STATE_READY_FOR_REVIEW => [self::STATE_PUBLISHED, self::STATE_DRAFT],
        self::STATE_PUBLISHED => [self::STATE_ARCHIVED, self::STATE_DRAFT],
        self::STATE_ARCHIVED => [],
    ];

    /**
     * Workspace state (reconstructed from events)
     *
     * @var array<string, mixed>
     */
    protected array $state = [
        'workspace_id' => null,
        'tenant_id' => null,
        'property_id' => null,
        'intent' => null,
        'template_id' => null,
        'state' => self::STATE_WORKSPACE_CREATED,
        'created_at' => null,
    ];

    /**
     * Set property_id after workspace creation
     *
     * @param int $propertyId
     * @return void
     */
    public function setPropertyId(int $propertyId): void
    {
        $this->state['property_id'] = $propertyId;
    }

    /**
     * Factory: create new workspace (requires DB record to be created first, then call initializeWorkspace)
     *
     * Tenant ID must be passed explicitly from the application/service layer.
     * Domain layer must NOT resolve tenant from auth session (S6.1-E01).
     *
     * @param int $propertyId The canonical Property ID
     * @param string $intent Workspace intent
     * @param int $tenantId Tenant ID — must be passed by caller, never resolved via auth() here
     * @param string|null $templateId
     * @return self
     */
    public static function createWorkspace(int $propertyId, string $intent, int $tenantId = 0, ?string $templateId = null): self
    {
        $aggregate = new self(0, $tenantId);
        $aggregate->state['tenant_id'] = $tenantId;
        $aggregate->state['property_id'] = $propertyId;
        $aggregate->state['intent'] = $intent;
        $aggregate->state['template_id'] = $templateId;
        $aggregate->state['state'] = self::STATE_WORKSPACE_CREATED;
        $aggregate->state['created_at'] = now()->toIso8601String();
        return $aggregate;
    }

    /**
      * Initialize workspace after DB record created - records the event
      *
      * @param string $workspaceId
      * @param int $propertyId The canonical Property ID
      * @param string $intent
      * @param string|null $templateId
      * @return void
      */
    public function initializeWorkspace(string $workspaceId, int $propertyId, string $intent, ?string $templateId): void
    {
        $this->state['workspace_id'] = $workspaceId;
        $this->state['property_id'] = $propertyId;
        $this->state['intent'] = $intent;
        $this->state['template_id'] = $templateId;
        $this->state['created_at'] = now()->toIso8601String();

        $this->recordEvent('WorkspaceCreated', [
            'workspace_id' => $workspaceId,
            'tenant_id' => $this->tenantId,
            'property_id' => $propertyId,
            'intent' => $intent,
            'template_id' => $templateId,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

     /**
      * Select workspace intent
     *
     * @param string $intent
     * @return void
     */
    public function selectIntent(string $intent): void
    {
        $this->recordEvent('IntentSelected', [
            'workspace_id' => $this->state['workspace_id'],
            'intent' => $intent,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->state['intent'] = $intent;
    }

    /**
     * Apply template to workspace
     *
     * @param string $templateId
     * @return void
     */
    public function applyTemplate(string $templateId): void
    {
        $this->recordEvent('TemplateApplied', [
            'workspace_id' => $this->state['workspace_id'],
            'template_id' => $templateId,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->state['template_id'] = $templateId;
    }

    /**
     * Transition to a new state
     *
     * @param string $newState
     * @param bool $isAdmin Bypass transition rules for admin
     * @return void
     * @throws InvalidStateTransitionException
     */
    public function transitionTo(string $newState, bool $isAdmin = false): void
    {
        $currentState = $this->state['state'];

        if ($isAdmin && $newState === self::STATE_ARCHIVED) {
            // Admin can archive from any state
            $this->recordAndApplyStateChange($currentState, $newState);
            return;
        }

        $allowedTransitions = self::TRANSITIONS[$currentState] ?? [];

        if (!in_array($newState, $allowedTransitions, true)) {
            throw new InvalidStateTransitionException($currentState, $newState);
        }

        $this->recordAndApplyStateChange($currentState, $newState);
    }

    /**
     * Record and apply state change event
     *
     * @param string $fromState
     * @param string $toState
     * @return void
     */
    protected function recordAndApplyStateChange(string $fromState, string $toState): void
    {
        $this->recordEvent('StateChanged', [
            'workspace_id' => $this->state['workspace_id'],
            'from_state' => $fromState,
            'to_state' => $toState,
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->state['state'] = $toState;
    }

    /**
     * Apply event to reconstruct state
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function applyEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            'WorkspaceCreated' => $this->applyWorkspaceCreated($payload),
            'IntentSelected' => $this->applyIntentSelected($payload),
            'TemplateApplied' => $this->applyTemplateApplied($payload),
            'StateChanged' => $this->applyStateChanged($payload),
            default => null,
        };
    }

    /**
     * Apply WorkspaceCreated event
     *
     * @param array $payload
     * @return void
     */
    protected function applyWorkspaceCreated(array $payload): void
    {
        $this->state['workspace_id'] = $payload['workspace_id'];
        $this->state['tenant_id'] = (int) $payload['tenant_id'];
        $this->state['property_id'] = (int) $payload['property_id'];
        $this->state['intent'] = $payload['intent'];
        $this->state['template_id'] = $payload['template_id'];
        $this->state['state'] = self::STATE_WORKSPACE_CREATED;
        $this->state['created_at'] = $payload['timestamp'];
    }

    /**
     * Apply IntentSelected event
     *
     * @param array $payload
     * @return void
     */
    protected function applyIntentSelected(array $payload): void
    {
        $this->state['intent'] = $payload['intent'];
    }

    /**
     * Apply TemplateApplied event
     *
     * @param array $payload
     * @return void
     */
    protected function applyTemplateApplied(array $payload): void
    {
        $this->state['template_id'] = $payload['template_id'];
    }

    /**
     * Apply StateChanged event
     *
     * @param array $payload
     * @return void
     */
    protected function applyStateChanged(array $payload): void
    {
        $this->state['state'] = $payload['to_state'];
    }

    /**
     * Get current workspace state
     *
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Get workspace ID
     *
     * @return string|null
     */
    public function getWorkspaceId(): ?string
    {
        return $this->state['workspace_id'];
    }

    /**
     * Get workspace state
     *
     * @return string
     */
    public function getWorkspaceState(): string
    {
        return $this->state['state'];
    }

    /**
     * Reconstruct aggregate from PropertyWorkspace model
     *
     * @param PropertyWorkspace $model
     * @return self
     */
    public static function fromModel(PropertyWorkspace $model): self
    {
        $aggregate = new self((int) $model->id, (int) $model->tenant_id);
        $aggregate->state['workspace_id'] = $model->workspace_uuid;
        $aggregate->state['tenant_id'] = (int) $model->tenant_id;
        $aggregate->state['property_id'] = (int) $model->property_id;
        $aggregate->state['intent'] = $model->intent;
        $aggregate->state['template_id'] = $model->template_id;
        $aggregate->state['state'] = $model->state;
        $aggregate->state['created_at'] = $model->created_at?->toIso8601String();

        // Replay events if available
        $aggregate->replayEvents();

        return $aggregate;
    }

    /**
     * Get valid states
     *
     * @return array<string>
     */
    public static function getValidStates(): array
    {
        return [
            self::STATE_WORKSPACE_CREATED,
            self::STATE_DRAFT,
            self::STATE_READY_FOR_REVIEW,
            self::STATE_PUBLISHED,
            self::STATE_ARCHIVED,
        ];
    }

    /**
     * Get valid transitions from a given state
     *
     * @param string $fromState
     * @return array<string>
     */
    public static function getValidTransitions(string $fromState): array
    {
        return self::TRANSITIONS[$fromState] ?? [];
    }
}
