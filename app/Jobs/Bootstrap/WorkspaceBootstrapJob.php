<?php

namespace App\Jobs\Bootstrap;

use App\Services\BC001\BC001Orchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * WorkspaceBootstrapJob
 *
 * Sprint 5.0 — BC-001 Epic 1: Workspace Bootstrap
 *
 * Drive workspace + subfolder oluşturur.
 */
class WorkspaceBootstrapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $ilanId,
    ) {
        $this->onQueue('bc001-workspace');
    }

    public function handle(BC001Orchestrator $orchestrator): void
    {
        Log::info('[WorkspaceBootstrapJob] Starting', ['ilan_id' => $this->ilanId]);

        try {
            $orchestrator->runWorkspaceBootstrap($this->ilanId);
        } catch (\Throwable $e) {
            Log::error('[WorkspaceBootstrapJob] Failed', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[WorkspaceBootstrapJob] Permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $e->getMessage(),
        ]);
    }
}
