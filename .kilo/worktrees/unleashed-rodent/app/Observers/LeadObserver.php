<?php

namespace App\Observers;

use App\Models\Lead;
use App\Jobs\AI\SyncLeadToIntelligence;

class LeadObserver
{
    /**
     * Handle the Lead "saved" event.
     */
    public function saved(Lead $lead): void
    {
        // Skip in testing environment if needed, but here we want it for sync
        if (app()->runningUnitTests()) {
            return;
        }

        // 🧠 CRM Intelligence Sync
        SyncLeadToIntelligence::dispatch($lead);
    }
}
