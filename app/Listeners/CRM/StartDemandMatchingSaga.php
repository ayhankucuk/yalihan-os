<?php

namespace App\Listeners\CRM;

use App\Application\CRM\Services\MatchDemandsForListingUseCase;
use App\Domain\Ilan\Events\WizardSubmitted;
use App\Events\IlanYayinlandiEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * StartDemandMatchingSaga — Decoupled Listener
 *
 * Starts the demand matching saga when a listing is published or submitted via wizard.
 * Completely independent of wizard presentation logic.
 */
class StartDemandMatchingSaga
{
    public function __construct(
        private readonly MatchDemandsForListingUseCase $matchUseCase
    ) {}

    /**
     * Handle the event.
     */
    public function handle(IlanYayinlandiEvent|WizardSubmitted $event): void
    {
        if (!config('crm.demand_matching_enabled', false)) {
            return;
        }

        $ilan = $event->ilan ?? null;
        if (!$ilan) {
            return;
        }

        try {
            // Execute match use case, which dispatches DemandMatched for valid matches
            $matches = $this->matchUseCase->execute($ilan, dispatchEvents: true);

            Log::info('CRM Saga: Demand matching executed for listing', [
                'ilan_id'       => $ilan->id,
                'matches_found' => $matches->count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CRM Saga: Demand matching failed', [
                'ilan_id' => $ilan->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
