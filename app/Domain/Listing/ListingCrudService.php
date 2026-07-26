<?php

declare(strict_types=1);

namespace App\Domain\Listing;

use App\Domain\Listing\Events\ListingCreated;
use App\Domain\Listing\Events\ListingReadyForReview;
use App\Domain\Listing\Events\ListingPublished;
use App\Domain\Listing\Events\ListingUnpublished;
use App\Domain\Listing\Events\ListingArchived;
use App\Models\Ilan;
use App\Models\Property;
use App\Enums\IlanDurumu;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Listing\ListingScoreService;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Log;

/**
 * ListingCrudService
 *
 * Sprint 11 M2: Property Runtime
 * Sprint 12A: Property Lifecycle
 * Sprint 12B: Workspace Tenant Isolation
 *
 * Canonical write authority for Listing aggregate.
 * All writes go through this service — no direct Eloquent create/update in controllers.
 *
 * Tenant Isolation (SAB §1):
 * - Every operation validates tenant_id matches current context
 * - Cross-tenant access is blocked with DomainException
 *
 * Lifecycle transitions enforce:
 * - Draft → ReadyForReview → Published → Archived
 * - Invariants per transition
 * - Domain events
 * - Audit trail
 * - Idempotency
 */
class ListingCrudService
{
    protected YalihanLifecycle $lifecycle;
    protected ListingScoreService $scoreService;
    protected TenantContextService $tenantContext;

    public function __construct(
        ?YalihanLifecycle $lifecycle = null,
        ?ListingScoreService $scoreService = null,
        ?TenantContextService $tenantContext = null,
    ) {
        $this->lifecycle = $lifecycle ?? app(YalihanLifecycle::class);
        $this->scoreService = $scoreService ?? app(ListingScoreService::class);
        $this->tenantContext = $tenantContext ?? app(TenantContextService::class);
    }

    /**
     * Validate tenant isolation for a listing.
     *
     * @throws \DomainException If listing belongs to different tenant
     */
    protected function validateTenantAccess(Ilan $ilan): void
    {
        if (!$this->tenantContext->hasTenant()) {
            Log::warning('ListingCrudService: No tenant context set', [
                'listing_id' => $ilan->id,
            ]);
            return;
        }

        $currentTenant = $this->tenantContext->getTenant();

        if ($ilan->tenant_id !== $currentTenant->id) {
            Log::alert('Cross-tenant listing access blocked', [
                'listing_id' => $ilan->id,
                'listing_tenant_id' => $ilan->tenant_id,
                'current_tenant_id' => $currentTenant->id,
            ]);

            throw new \DomainException(
                "Cross-tenant access denied: Listing [{$ilan->id}] belongs to tenant [{$ilan->tenant_id}], " .
                "but current tenant is [{$currentTenant->id}]."
            );
        }
    }

    /**
     * Validate workspace ownership for a listing.
     *
     * Sprint 12B: Workspace Tenant Isolation
     * Ensures listing operations only affect listings in the current workspace.
     *
     * @throws \DomainException If listing belongs to different workspace
     */
    protected function validateWorkspaceOwnership(Ilan $ilan): void
    {
        // If no workspace context is set, skip validation (backward compatibility)
        if (!$this->tenantContext->hasWorkspace()) {
            Log::warning('ListingCrudService: No workspace context set, skipping workspace validation', [
                'listing_id' => $ilan->id,
                'listing_workspace_id' => $ilan->workspace_id,
            ]);
            return;
        }

        $currentWorkspace = $this->tenantContext->getWorkspace();

        // Check if listing belongs to the current workspace
        if ($ilan->workspace_id !== $currentWorkspace->id) {
            Log::alert('Cross-workspace listing access blocked', [
                'listing_id' => $ilan->id,
                'listing_workspace_id' => $ilan->workspace_id,
                'current_workspace_id' => $currentWorkspace->id,
                'current_tenant_id' => $currentWorkspace->tenant_id ?? null,
            ]);

            throw new \DomainException(
                "Workspace ownership validation failed: Listing [{$ilan->id}] belongs to workspace [{$ilan->workspace_id}], " .
                "but current workspace is [{$currentWorkspace->id}]."
            );
        }
    }

    /**
     * Create a Listing from a Property (Draft state).
     *
     * Invariants enforced:
     * - Property must be ACTIVE (enforced by ListingFactory)
     * - Every Listing requires property_id (enforced by Ilan::booted hook)
     * - Idempotent: same idempotency_key returns existing Listing
     */
    public function createFromProperty(Property $property, array $data = [], ?int $workspaceId = null): Ilan
    {
        // Idempotency check (Invariant 6)
        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = Ilan::byIdempotencyKey($idempotencyKey)->first();
            if ($existing) {
                Log::info('Listing idempotent read', [
                    'listing_id' => $existing->id,
                    'idempotency_key' => $idempotencyKey,
                ]);
                return $existing;
            }
        }

        // Build Listing from Property
        $ilan = ListingFactory::fromProperty($property, $data);
        $ilan->workspace_id = $workspaceId;
        $ilan->save();

        $this->dispatchListingCreated($ilan, $workspaceId);

        return $ilan;
    }

    /**
     * Submit Listing for review: Draft → ReadyForReview.
     *
     * Invariant: Listing must be in Draft state.
     * Tenant Isolation: Validates current tenant can access this listing.
     * Workspace Isolation (Sprint 12B): Validates listing belongs to current workspace.
     */
    public function submitForReview(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        $this->validateTenantAccess($ilan);
        $this->validateWorkspaceOwnership($ilan);

        $previousState = $this->getStateValue($ilan);
        $saved = $this->lifecycle->transition($ilan, IlanDurumu::BEKLEMEDE, $aktanId, [
            'source' => 'submit_for_review',
        ]);

        $this->dispatchListingReadyForReview($saved, $previousState);

        return $saved;
    }

    /**
     * Publish Listing: ReadyForReview → Published.
     *
     * Invariant: Listing must be in ReadyForReview state.
     * Guard: completion_score >= 100, quality_score >= 40 (enforced by YalihanLifecycle).
     * Tenant Isolation: Validates current tenant can access this listing.
     * Workspace Isolation (Sprint 12B): Validates listing belongs to current workspace.
     *
     * Scores are computed before transition to ensure guards pass.
     */
    public function publish(Ilan $ilan, ?int $aktanId = null): Ilan
    {
        $this->validateTenantAccess($ilan);
        $this->validateWorkspaceOwnership($ilan);

        $ilan->completion_score = $this->scoreService->computeCompletionScore($ilan);
        $ilan->quality_score = $this->scoreService->computeQualityScore($ilan);
        $ilan->saveQuietly();

        // Read pre-transition state from a fresh model to guarantee accuracy.
        $fresh = Ilan::find($ilan->id);
        $previousState = $this->getStateValue($fresh);
        $saved = $this->lifecycle->transition($ilan, IlanDurumu::YAYINDA, $aktanId, [
            'source' => 'publish',
        ]);

        $this->dispatchListingPublished($saved, $previousState);

        // Sync fresh state back to original model so callers get a fully updated instance.
        $ilan->setRawAttributes($saved->getAttributes(), true);
        $ilan->syncOriginal();
        return $ilan;
    }

    /**
     * Unpublish Listing: Published → Pasif.
     *
     * Invariant: Listing must be in Published state.
     * Tenant Isolation: Validates current tenant can access this listing.
     * Workspace Isolation (Sprint 12B): Validates listing belongs to current workspace.
     * Pasif = yayından kaldırıldı, içerik korunur, tekrar yayına alınabilir.
     */
    public function unpublish(Ilan $ilan, string $reason = '', ?int $aktanId = null): Ilan
    {
        $this->validateTenantAccess($ilan);
        $this->validateWorkspaceOwnership($ilan);

        // Read pre-transition state from a fresh model to guarantee accuracy.
        $fresh = Ilan::find($ilan->id);
        $previousState = $this->getStateValue($fresh);
        $saved = $this->lifecycle->transition($ilan, IlanDurumu::PASIF, $aktanId, [
            'source' => 'unpublish',
            'reason' => $reason,
        ]);

        $this->dispatchListingUnpublished($saved, $previousState, $reason);

        return $saved;
    }

    /**
     * Archive Listing: Any → Archived.
     *
     * Invariant: Archived listings cannot be directly re-published — must go through review.
     * Tenant Isolation: Validates current tenant can access this listing.
     * Workspace Isolation (Sprint 12B): Validates listing belongs to current workspace.
     */
    public function archive(Ilan $ilan, string $reason = '', ?int $aktanId = null): Ilan
    {
        $this->validateTenantAccess($ilan);
        $this->validateWorkspaceOwnership($ilan);

        // Read pre-transition state from a fresh model to guarantee accuracy.
        $fresh = Ilan::find($ilan->id);
        $previousState = $this->getStateValue($fresh);
        $saved = $this->lifecycle->transition($ilan, IlanDurumu::ARSIV, $aktanId, [
            'source' => 'archive',
            'reason' => $reason,
        ]);

        $this->dispatchListingArchived($saved, $previousState, $reason);

        return $saved;
    }

    /**
     * Update a Listing.
     *
     * Supports state transition via yayin_durumu in data:
     * - Any state transition is validated by YalihanLifecycle.
     * - If yayin_durumu is present, lifecycle->transition() enforces invariants.
     * - Tenant Isolation: Validates current tenant can access this listing.
     * - Workspace Isolation (Sprint 12B): Validates listing belongs to current workspace.
     *
     * Note: property_id cannot be changed after creation (invariant enforced by Ilan::booted).
     */
    public function update(Ilan $ilan, array $data): Ilan
    {
        $this->validateTenantAccess($ilan);
        $this->validateWorkspaceOwnership($ilan);

        $stateChange = null;

        // Handle state transition if yayin_durumu is in data
        if (isset($data['yayin_durumu'])) {
            $targetEnum = IlanDurumu::normalize($data['yayin_durumu']);
            if ($targetEnum) {
                $stateChange = $targetEnum;
                unset($data['yayin_durumu']); // Remove from fill to prevent double-set
            }
        }

        // Fill non-state fields
        $ilan->fill($data);
        $ilan->save();

        // Execute state transition if requested
        if ($stateChange) {
            $ilan = $this->lifecycle->transition($ilan, $stateChange, null, [
                'source' => 'crud_update',
            ]);
        }

        return $ilan;
    }

    /**
     * Archive a Listing (soft delete via lifecycle).
     *
     * Invariant: Archived listings are not hard-deleted — audit trail preserved.
     * Tenant Isolation: Delegates to archive() which validates tenant access.
     */
    public function delete(Ilan $ilan, string $reason = '', ?int $aktanId = null): Ilan
    {
        return $this->archive($ilan, $reason ?: 'User deleted', $aktanId);
    }

    // ── Event dispatch helpers ──────────────────────────────────────────

    protected function dispatchListingCreated(Ilan $ilan, ?int $workspaceId): void
    {
        $yayinDurumu = $this->getStateValue($ilan);
        event(new ListingCreated(
            $ilan->id,
            $ilan->tenant_id,
            (int) $ilan->property_id,
            $workspaceId,
            (string) $ilan->uuid,
            $yayinDurumu,
            now()->toDateTimeString(),
        ));

        Log::info('ListingCreated', [
            'listing_id' => $ilan->id,
            'property_id' => $ilan->property_id,
            'tenant_id' => $ilan->tenant_id,
            'workspace_id' => $workspaceId,
            'yayin_durumu' => $yayinDurumu,
        ]);
    }

    protected function dispatchListingReadyForReview(Ilan $ilan, string $previousState): void
    {
        event(new ListingReadyForReview(
            $ilan->id,
            $ilan->tenant_id,
            (int) $ilan->property_id,
            $ilan->workspace_id,
            $ilan->uuid,
            $previousState,
            now()->toDateTimeString(),
        ));

        Log::info('ListingReadyForReview', [
            'listing_id' => $ilan->id,
            'property_id' => $ilan->property_id,
            'previous_state' => $previousState,
        ]);
    }

    protected function dispatchListingPublished(Ilan $ilan, string $previousState): void
    {
        event(new ListingPublished(
            $ilan->id,
            $ilan->tenant_id,
            (int) $ilan->property_id,
            $ilan->workspace_id,
            $ilan->uuid,
            $ilan->kanal ?? 'yalihan',
            $previousState,
            now()->toDateTimeString(),
        ));

        Log::info('ListingPublished', [
            'listing_id' => $ilan->id,
            'property_id' => $ilan->property_id,
            'kanal' => $ilan->kanal ?? 'yalihan',
            'previous_state' => $previousState,
        ]);
    }

    protected function dispatchListingUnpublished(Ilan $ilan, string $previousState, string $reason): void
    {
        event(new ListingUnpublished(
            $ilan->id,
            $ilan->tenant_id,
            (int) $ilan->property_id,
            $ilan->workspace_id,
            $ilan->uuid,
            $ilan->kanal ?? 'yalihan',
            $previousState,
            $reason,
            now()->toDateTimeString(),
        ));

        Log::info('ListingUnpublished', [
            'listing_id' => $ilan->id,
            'property_id' => $ilan->property_id,
            'reason' => $reason,
        ]);
    }

    protected function dispatchListingArchived(Ilan $ilan, string $previousState, string $reason): void
    {
        event(new ListingArchived(
            $ilan->id,
            $ilan->tenant_id,
            (int) $ilan->property_id,
            $ilan->workspace_id,
            $ilan->uuid,
            $ilan->kanal ?? 'yalihan',
            $previousState,
            $reason,
            now()->toDateTimeString(),
        ));

        Log::info('ListingArchived', [
            'listing_id' => $ilan->id,
            'property_id' => $ilan->property_id,
            'reason' => $reason,
        ]);
    }

    protected function getStateValue(Ilan $ilan): string
    {
        $state = $ilan->yayin_durumu;
        return $state instanceof IlanDurumu ? $state->value : (string) $state;
    }
}
