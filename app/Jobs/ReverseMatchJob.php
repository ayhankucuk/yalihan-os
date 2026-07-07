<?php

namespace App\Jobs;

use App\Models\Talep;
use App\Services\Matching\DemandMatchingEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ReverseMatchJob
 *
 * Context7 Standard: C7-TELEGRAM-REVERSE-MATCH-JOB-2026-01-04
 *
 * Yeni talep yayınlandığında:
 * 1. Müşterileri bu talep ile eşleştir
 * 2. Relevant ilanları bul
 * 3. Notifications gönder
 *
 * Queue: high priority
 * Timeout: 300 saniye (5 dakika)
 *
 * @package App\Jobs
 * @version 1.0.0
 */
class ReverseMatchJob implements ShouldQueue, \App\Queue\Contracts\TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Talep $talep;
    public int $tries = 3;
    public int $timeout = 300;
    public int $taleId;

    public function __construct(int $taleId)
    {
        $this->taleId = $taleId;
        $this->talep = Talep::find($taleId);
        $this->onQueue('high');
    }

    public function getTenantId(): ?int
    {
        if ($this->talep) {
            return $this->talep->tenant_id;
        }
        $talep = Talep::withoutGlobalScopes()->find($this->taleId);
        return $talep?->tenant_id;
    }

    public function getUserId(): ?int
    {
        if ($this->talep) {
            return $this->talep->danisman_id;
        }
        $talep = Talep::withoutGlobalScopes()->find($this->taleId);
        return $talep?->danisman_id;
    }

    public function middleware(): array
    {
        return [new \App\Queue\Middleware\RestoreTenantContext(app(\App\Services\SaaS\TenantContextService::class))];
    }

    /**
     * Reverse matching işlemini çalıştır
     *
     * @return void
     */
    public function handle(): void
    {
        if (!$this->talep) {
            Log::warning('ReverseMatchJob: Talep bulunamadı', [
                'talep_id' => $this->job->payload()['data']['command'] ?? 'unknown',
            ]);
            return;
        }

        try {
            Log::info('ReverseMatchJob: Başlanıyor', [
                'talep_id' => $this->talep->id,
                'baslik' => $this->talep->baslik,
            ]);

            // DemandMatchingEngine ile işlemi yap
            $matchingEngine = app(DemandMatchingEngine::class);

            // Talep için relevant ilanları bul ve notify et
            $results = $matchingEngine->matchDemand($this->talep);

            Log::info('ReverseMatchJob: Tamamlandı', [
                'talep_id' => $this->talep->id,
                'matched_properties' => count($results),
            ]);
        } catch (\Exception $e) {
            Log::error('ReverseMatchJob: Hata', [
                'talep_id' => $this->talep->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry'a izin ver
            throw $e;
        }
    }

    /**
     * Job başarısız olduğunda
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ReverseMatchJob: Başarısız', [
            'talep_id' => $this->talep->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
