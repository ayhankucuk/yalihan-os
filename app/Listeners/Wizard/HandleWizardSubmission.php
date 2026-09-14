<?php

namespace App\Listeners\Wizard;

use App\Domain\Ilan\Events\WizardSubmitted;
use App\Events\IlanCreated;
use App\Services\CacheManager;
use App\Jobs\SyncListingProjectionJob;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleWizardSubmission Listener
 *
 * WizardSubmitted domain event'ini yakalar ve mevcut IlanCreated listener
 * zincirine proxy yapar. Bu sayede mevcut listener'ların tip imzalarını
 * değiştirmeye gerek kalmaz — WizardSubmitted, bir IlanCreated olarak
 * yeniden fırlatılır.
 *
 * Idempotent: Sadece yayınlanmış (yayinda/yayinda_bekleyen) ilanlar için
 * lead matching ve n8n bildirimi gönderir. Zaten yayınlanmış bir ilan
 * tekrar proxy'lenmez (submitWizard() birden fazla çağrılsa bile).
 *
 * FAZ 4B: EventServiceProvider entegrasyonu
 */
class HandleWizardSubmission implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly CacheManager $cache,
        private readonly ActionCenterService $actionCenterService,
    ) {}

    public function handle(WizardSubmitted $event): void
    {
        $ilan = $event->ilan;

        Log::info('HandleWizardSubmission: wizard submitted', [
            'ilan_id' => $ilan->id,
            'yayin_durumu' => $ilan->yayin_durumu,
        ]);

        // Idempotency: Sadece yayınlanmış ilanlar için lead matching ve n8n tetikle.
        // Taslak/taslak_inceleme aşamasında ise sadece cache + analytics güncellenir.
        $yayindaStates = ['yayinda', 'yayinda_bekleyen', 'yayinlandi'];
        $isPublished = in_array($ilan->yayin_durumu, $yayindaStates, true);

        if ($isPublished) {
            // Proxy #1: IlanCreated fırlat → FindMatchingDemands, n8n, ActionCenter tetiklenir
            event(new IlanCreated($ilan));
        } else {
            Log::debug('HandleWizardSubmission: ilan henüz yayınlanmadı, lead matching atlandı', [
                'ilan_id' => $ilan->id,
                'yayin_durumu' => $ilan->yayin_durumu,
            ]);
        }

        // Her durumda: cache invalidation + analytics güncellemesi
        $this->cache->forget((string) $ilan->id, 'ilan');
        $this->cache->flushTag('ilan');
        SyncListingProjectionJob::dispatch($ilan->id);

        // Action Center görevleri — yayın durumundan bağımsız
        $this->actionCenterService->generateActionsFromEvent(
            new \App\Events\IlanCreated($ilan)
        );

        Log::info('HandleWizardSubmission: proxy chain complete', [
            'ilan_id' => $ilan->id,
            'is_published' => $isPublished,
        ]);
    }

    public function failed(WizardSubmitted $event, \Throwable $exception): void
    {
        Log::error('HandleWizardSubmission: failed', [
            'ilan_id' => $event->ilan->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
