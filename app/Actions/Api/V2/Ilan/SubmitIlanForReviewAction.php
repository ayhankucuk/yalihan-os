<?php

namespace App\Actions\Api\V2\Ilan;

use App\Models\Ilan;
use App\Domain\Listing\ListingCrudService;

/**
 * SubmitIlanForReviewAction
 *
 * Sprint 12A: Publish Workflow
 *
 * Transitions a listing from Draft to ReadyForReview state.
 * This action delegates to ListingCrudService as the single write authority.
 *
 * Workflow: Draft → ReadyForReview → Published
 */
class SubmitIlanForReviewAction
{
    public function __construct(
        private readonly ListingCrudService $listingCrudService,
    ) {}

    /**
     * Submit listing for review (Draft → ReadyForReview).
     *
     * @throws DomainException If listing is not in Draft state
     */
    public function handle(Ilan $ilan): Ilan
    {
        return $this->listingCrudService->submitForReview($ilan);
    }
}
