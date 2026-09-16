<?php

namespace App\Listeners\Wizard;

use App\Domain\Ilan\Events\WizardStepCompleted;
use App\Services\CacheManager;
use App\Jobs\SyncListingProjectionJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * HandleWizardStepCompleted Listener
 *
 * Her sihirbaz adımı tamamlandığında tetiklenir.
 * Kısmi cache invalidation ve analytics projection güncellenir.
 *
 * FAZ 4B: EventServiceProvider entegrasyonu
 */
class HandleWizardStepCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly CacheManager $cache,
    ) {}

    public function handle(WizardStepCompleted $event): void
    {
        $ilan = $event->ilan;

        Log::debug('HandleWizardStepCompleted: step completed', [
            'ilan_id' => $ilan->id,
            'step' => $event->completedStep,
        ]);

        // Kısmi analytics güncelleme — draft/projection güncellenir
        SyncListingProjectionJob::dispatch($ilan->id);

        // Sadece o ilanın cache'ini invalidat et (tam flush değil)
        $this->cache->forget((string) $ilan->id, 'ilan');
    }

    public function failed(WizardStepCompleted $event, \Throwable $exception): void
    {
        Log::error('HandleWizardStepCompleted: failed', [
            'ilan_id' => $event->ilan->id,
            'step' => $event->completedStep,
            'error' => $exception->getMessage(),
        ]);
    }
}
