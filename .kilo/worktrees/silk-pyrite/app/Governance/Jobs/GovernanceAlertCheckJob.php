<?php

namespace App\Governance\Jobs;

use App\Governance\Alerting\GovernanceAlerter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4C — Governance Alert Checker Job
 *
 * Belirli aralıklarla (scheduler üzerinden) tetiklenir ve
 * yönetişim metriklerini tarayıp alarm üretir.
 */
class GovernanceAlertCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Kuyruk adı: governance
     * Priority: Medium (Business path'i etkilemez)
     */
    public $queue = 'governance';

    /**
     * Deneme sayısı: 3
     */
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(GovernanceAlerter $alerter): void
    {
        try {
            $alerter->checkAndAlert();
        } catch (\Throwable $e) {
            // Fail-open: Kuyruk hatası sistemi bozmamalı
            $this->failed($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[GovernanceAlertCheckJob] Job başarısız oldu', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
