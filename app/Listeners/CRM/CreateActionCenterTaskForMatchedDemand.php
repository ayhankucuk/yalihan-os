<?php

namespace App\Listeners\CRM;

use App\Events\CRM\DemandMatched;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CreateActionCenterTaskForMatchedDemand — ActionCenter Integration Listener
 *
 * Listens to DemandMatched event and creates an ActionCenter Gorev for the consultant
 * with strict idempotency and duplicate event protection.
 */
class CreateActionCenterTaskForMatchedDemand
{
    /**
     * Handle the event.
     */
    public function handle(DemandMatched $event): void
    {
        $ilan = $event->ilan;
        $result = $event->matchResult;
        $idempotencyKey = $event->idempotencyKey;

        // 1. Idempotency Check via Cache / Lookup to prevent duplicate task creation
        $cacheKey = "idempotency:demand_match:{$idempotencyKey}";
        if (Cache::has($cacheKey)) {
            Log::info('CRM Saga: Duplicate DemandMatched event ignored by cache key', [
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }
        Cache::put($cacheKey, true, 3600);

        // Check if task already exists in DB for this exact match
        $existingTask = Gorev::where('source_event', 'DemandMatched')
            ->where('ilan_id', $ilan->id)
            ->where('notlar', $idempotencyKey)
            ->first();

        if ($existingTask) {
            Log::info('CRM Saga: Task already exists in DB for match, skipping', [
                'idempotency_key' => $idempotencyKey,
                'gorev_id'        => $existingTask->id,
            ]);
            return;
        }

        // 2. Determine Priority and Title
        $priority = match ($result->matchLevel) {
            'STRONG' => 'acil',
            'GOOD'   => 'yuksek',
            default  => 'normal',
        };

        $assignedUserId = $result->danismanId ?? $ilan->user_id;

        $reasonsText = implode(', ', $result->matchReasons);
        $baslik = sprintf('Eşleşen Talep: %s (Skor: %%%d)', $result->talepBaslik, (int) $result->score);
        $aciklama = sprintf(
            "İlan #%d (%s) ile Talep #%d (%s) arasında %%%d oranında uyum tespit edildi.\nNedenler: %s",
            $ilan->id,
            $ilan->baslik,
            $result->talepId,
            $result->talepBaslik,
            (int) $result->score,
            $reasonsText
        );

        // 3. Create Task
        DB::transaction(function () use ($ilan, $result, $priority, $assignedUserId, $baslik, $aciklama, $idempotencyKey) {
            $gorev = Gorev::create([
                'tenant_id'           => $ilan->tenant_id,
                'baslik'              => $baslik,
                'aciklama'            => $aciklama,
                'oncelik'             => $priority,
                'gorev_durumu'        => 'beklemede',
                'gorev_tipi'          => 'talep_eslestirme',
                'atanan_user_id'      => $assignedUserId,
                'ilan_id'             => $ilan->id,
                'source_event'        => 'DemandMatched',
                'source_module'       => 'CRM',
                'ai_confidence_score' => $result->score,
                'ai_reasoning'        => implode(', ', $result->matchReasons),
                'notlar'              => $idempotencyKey,
            ]);

            LogService::info('CRM Saga: ActionCenter task created for matched demand', [
                'gorev_id'        => $gorev->id,
                'ilan_id'         => $ilan->id,
                'talep_id'        => $result->talepId,
                'assigned_to'     => $assignedUserId,
                'score'           => $result->score,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
}
