<?php

namespace App\Listeners\Reservation;

use App\Events\GorevDurumChanged;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Reservation\GuestArrivalReadinessService;
use App\Services\Reservation\OperationalGorevService;
use Illuminate\Support\Facades\Log;

/**
 * ListenGorevReadinessUpdate — Wave 2: Updates readiness when hazirlik task is completed.
 *
 * CHECKIN_CHECKOUT Wave 2
 *
 * Triggered by: GorevDurumChanged event (fired by GorevObserver on gorev_durumu change)
 *
 * Logic:
 * - Only handles hazirlik task type
 * - Only fires when new status = 'tamamlandi'
 * - Calls GuestArrivalReadinessService::onHazirlikTaskCompleted()
 *
 * Runs after: NotifyN8nOnGorevDurumChanged (Wave 2: n8n handled separately)
 */
class ListenGorevReadinessUpdate
{
    public function handle(GorevDurumChanged $event): void
    {
        $task = $event->gorev;

        // Only handle hazirlik tasks
        if ($task->gorev_tipi !== OperationalGorevService::TASK_HAZIRLIK) {
            return;
        }

        // Only fire when task is completed
        if ($event->yeniDurum !== 'tamamlandi') {
            return;
        }

        Log::info('ListenGorevReadinessUpdate: hazirlik task completed, updating readiness', [
            'gorev_id' => $task->id,
            'reservation_id' => $task->reservation_id,
        ]);

        try {
            $service = app(GuestArrivalReadinessService::class);
            $service->onHazirlikTaskCompleted($task);
        } catch (\Throwable $e) {
            Log::error('ListenGorevReadinessUpdate: failed to update readiness', [
                'gorev_id' => $task->id,
                'reservation_id' => $task->reservation_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
