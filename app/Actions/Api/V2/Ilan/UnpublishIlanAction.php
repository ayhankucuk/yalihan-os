<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Domain\Listing\ListingCrudService;

/**
 * UnpublishIlanAction
 *
 * Sprint 12A: Publish Workflow
 *
 * Transitions a listing from Published to Pasif state.
 * Uses ListingCrudService::unpublish() which:
 * - Validates listing is in Published state
 * - Dispatches ListingUnpublished domain event
 */
class UnpublishIlanAction
{
    public function __construct(
        private readonly ListingCrudService $listingCrudService,
    ) {}

    /**
     * Unpublish listing (Published → Pasif).
     */
    public function handle(Ilan $ilan): Ilan
    {
        return $this->listingCrudService->unpublish($ilan);
    }
}
