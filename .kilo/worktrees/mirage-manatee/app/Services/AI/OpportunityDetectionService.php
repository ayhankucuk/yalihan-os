<?php

namespace App\Services\AI;

use App\Models\Projections\ListingSearchProjection;
use Illuminate\Support\Collection;

class OpportunityDetectionService
{
    /**
     * Scan the projection for potential opportunity candidates.
     */
    public function scanCandidates(): Collection
    {
        // Simple logic: return all items from projection for analysis
        // In a real scenario, this could filter by "new" or "recently updated"
        return ListingSearchProjection::all();
    }
}
