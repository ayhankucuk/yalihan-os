<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Domain\Listing\ListingCrudService;

/**
 * PublishIlanAction
 *
 * Sprint 12A: Publish Workflow
 *
 * Transitions a listing from ReadyForReview to Published state.
 * Uses ListingCrudService::publish() which:
 * - Computes completion_score and quality_score before transition
 * - Validates guards (completion >= 100, quality >= 40, coordinates valid)
 * - Dispatches ListingPublished domain event
 */
class PublishIlanAction
{
    public function __construct(
        private readonly ListingCrudService $listingCrudService,
    ) {}

    /**
     * Publish listing (ReadyForReview → Published).
     *
     * @throws DomainException If guards fail (completion, quality, template)
     */
    public function handle(Ilan $ilan): Ilan
    {
        return $this->listingCrudService->publish($ilan);
    }
}
