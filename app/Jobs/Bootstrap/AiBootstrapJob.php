<?php

namespace App\Jobs\Bootstrap;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Services\AI\YalihanCortex;
use App\Services\Workspace\WorkspaceExecutionService;
use App\DTOs\AI\CortexRequestData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AiBootstrapJob
 *
 * Sprint 5.0 — BC-001 Epic 3: AI Bootstrap
 *
 * Fotoğraflar + Ilan → AI analizi → readiness score + açıklama
 */
class AiBootstrapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public readonly int $ilanId,
    ) {
        $this->onQueue('bc001-ai');
    }

    public function handle(
        YalihanCortex $cortex,
        WorkspaceExecutionService $executionService,
    ): void {
        Log::info('[AiBootstrapJob] Starting', ['ilan_id' => $this->ilanId]);

        $ilan = Ilan::with(['fotograflar', 'ilanDetay'])->find($this->ilanId); // @sab-ignore — Laravel relationship
        if (!$ilan) {
            Log::warning('[AiBootstrapJob] Ilan not found', ['ilan_id' => $this->ilanId]);
            return;
        }

        $workspace = PortfolioDriveWorkspace::forPortfolio($this->ilanId)->first();

        // 1. AI Quality Check
        $quality = $cortex->checkIlanQuality($ilan);

        // 2. Workspace AI completion flags
        if ($workspace) {
            $workspace->markAiAgentComplete('photo_agent', [
                'photo_count' => $ilan->fotograflar->count(),
                'quality_score' => $quality['quality_score'] ?? 0,
            ]);
        }

        // 3. Execution kaydet
        if ($workspace) {
            $executionService->dispatch(
                $workspace,
                'bc001-ai',
                'AI Bootstrap Tamamlandı',
                ['ilan_id' => $this->ilanId],
                [
                    'quality_score' => $quality['quality_score'] ?? null,
                    'missing_fields' => $quality['missing_fields'] ?? [],
                ]
            );
        }

        Log::info('[AiBootstrapJob] Complete', [
            'ilan_id' => $this->ilanId,
            'quality_score' => $quality['quality_score'] ?? null,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[AiBootstrapJob] Permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $e->getMessage(),
        ]);
    }
}
