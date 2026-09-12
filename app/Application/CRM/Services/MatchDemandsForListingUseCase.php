<?php

namespace App\Application\CRM\Services;

use App\Domain\CRM\DTOs\DemandMatchResult;
use App\Domain\CRM\Policies\DemandMatchingPolicy;
use App\Domain\CRM\Services\DemandMatchingService;
use App\Events\CRM\DemandMatched;
use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * MatchDemandsForListingUseCase — Application Service / Saga Coordinator
 *
 * Orchestrates demand retrieval, domain scoring via DemandMatchingService,
 * and dispatching DemandMatched domain events with strict tenant isolation.
 */
class MatchDemandsForListingUseCase
{
    public function __construct(
        private readonly DemandMatchingService $matchingService = new DemandMatchingService(),
        private readonly DemandMatchingPolicy $policy = new DemandMatchingPolicy()
    ) {}

    /**
     * Execute demand matching for a given Listing.
     *
     * @param Ilan $ilan
     * @param bool $dispatchEvents If true, fires DemandMatched event for actionable matches
     * @return Collection<int, DemandMatchResult>
     */
    public function execute(Ilan $ilan, bool $dispatchEvents = true): Collection
    {
        // Multi-tenant isolation: Only fetch demands for the SAME tenant
        $query = Talep::query();

        if (!empty($ilan->tenant_id)) {
            $query->where('tenant_id', $ilan->tenant_id);
        }

        // Active demands only
        $talepler = $query->where(function ($q) {
            $q->where('talep_durumu', 'Aktif')
              ->orWhere('talep_durumu', 'Yeni')
              ->orWhere('talep_durumu', 'yayinda')
              ->orWhereNull('talep_durumu');
        })
        ->with(['kisi', 'danisman'])
        ->get();

        $matchedResults = collect();

        foreach ($talepler as $talep) {
            $result = $this->matchingService->evaluateMatch($ilan, $talep);

            // Filter out scores below threshold (IGNORE)
            if ($result->isActionable() && $result->score >= $this->policy->minScoreThreshold) {
                $matchedResults->push($result);

                if ($dispatchEvents && config('crm.demand_matching_enabled', false)) {
                    $idempotencyKey = sprintf(
                        '%s:%s:%s:demand_match',
                        $ilan->tenant_id ?? 'global',
                        $ilan->id,
                        $talep->id
                    );

                    Event::dispatch(new DemandMatched(
                        ilan: $ilan,
                        matchResult: $result,
                        idempotencyKey: $idempotencyKey
                    ));
                }
            }
        }

        return $matchedResults->sortByDesc('score')->values();
    }
}
