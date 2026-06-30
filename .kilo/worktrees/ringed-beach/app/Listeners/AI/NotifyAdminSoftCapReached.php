<?php

namespace App\Listeners\AI;

use App\Events\AI\AISoftCapReached;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminSoftCapReached implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AISoftCapReached $event): void
    {
        // 1. Log warning (PII safe)
        Log::warning('AI Soft Cap Reached', [
            'scope' => $event->scopeTipi,
            'scope_id' => $event->scopeDegeri, // Assuming ID/Hash is safe for admin logs
            'window' => $event->pencere,
            'usage_pct' => round($event->kullanimOrani * 100, 2) . '%',
            'limit' => $event->limit,
            'current' => $event->kullanim,
        ]);

        // 2. Notification System Integration (Future/If exists)
        // For now, consistent heavy logging serves as the trace.
    }
}
