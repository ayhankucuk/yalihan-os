<?php

namespace App\Listeners\BC001;

use App\Events\IlanCreated;
use App\Services\BC001\BC001Orchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * IlanCreatedListener
 *
 * Sprint 5.0 — BC-001 Epic 0: Bootstrap Orchestration
 *
 * Yeni ilan oluşturulduğunda BC-001 bootstrap sürecini başlatır.
 * ShouldQueue — background'da çalışır, UI'ı bloklamaz.
 */
class IlanCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly BC001Orchestrator $orchestrator,
    ) {}

    /**
     * IlanCreated eventini işler.
     */
    public function handle(IlanCreated $event): void
    {
        $ilan = $event->ilan;

        Log::info('[IlanCreatedListener] BC-001 bootstrap triggered', [
            'ilan_id' => $ilan->id,
            'referans_no' => $ilan->referans_no,
        ]);

        try {
            $this->orchestrator->bootstrap($ilan);
        } catch (\Throwable $e) {
            Log::error('[IlanCreatedListener] BC-001 bootstrap failed', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
            ]);
            // Job başarısız — queue DLQ'ya düşer
            throw $e;
        }
    }

    public function failed(IlanCreated $event, \Throwable $e): void
    {
        Log::error('[IlanCreatedListener] BC-001 permanently failed', [
            'ilan_id' => $event->ilan->id,
            'error' => $e->getMessage(),
        ]);
    }
}
