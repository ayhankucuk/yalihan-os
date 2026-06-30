<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SyncListingProjectionJob;

class UpdateAnalyticsProjections
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Many ways to do this, here we check for common properties
        // related to Ilan models. If your events (like IlanCreated)
        // have an $ilan property, we sync it.

        if (isset($event->ilan) && $event->ilan instanceof \App\Models\Ilan) {
            SyncListingProjectionJob::dispatch($event->ilan->id);
            return;
        }

        if (isset($event->ilanId)) {
            SyncListingProjectionJob::dispatch($event->ilanId);
            return;
        }
    }
}
