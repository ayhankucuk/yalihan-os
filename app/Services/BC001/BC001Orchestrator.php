<?php

namespace App\Services\BC001;

use App\Jobs\Bootstrap\WorkspaceBootstrapJob;
use App\Jobs\Bootstrap\KnowledgeBootstrapJob;
use App\Jobs\Bootstrap\AiBootstrapJob;
use App\Jobs\Bootstrap\PublishingBootstrapJob;
use App\Models\Ilan;
use App\Services\Drive\DriveWorkspaceService;
use App\Services\Workspace\WorkspaceExecutionService;
use App\Models\WorkspaceExecution;
use Illuminate\Support\Facades\Log;

/**
 * BC001Orchestrator
 *
 * Sprint 5.0 — First Advisor Experience
 *
 * IlanCreated eventini dinler ve BC-001 bootstrap sürecini başlatır.
 * Tüm bootstrap adımları queue'ya dispatch edilir.
 *
 * Write path: IlanCrudService (tek yetkili)
 * Read path: Owner dashboard, Readiness panel
 */
class BC001Orchestrator
{
    public function __construct(
        private readonly DriveWorkspaceService $driveService,
        private readonly WorkspaceExecutionService $executionService,
    ) {}

    /**
     * IlanCreated eventinden çağrılır.
     * Tüm bootstrap job'larını queue'ya dispatch eder.
     */
    public function bootstrap(Ilan $ilan): void
    {
        $startTime = microtime(true);

        Log::info('[BC001Orchestrator] Bootstrap started', [
            'ilan_id' => $ilan->id,
            'referans_no' => $ilan->referans_no,
        ]);

        // 1. WorkspaceBootstrapJob — en kritik, ilk olarak başlar
        WorkspaceBootstrapJob::dispatch($ilan->id)
            ->onQueue('bc001-workspace');

        // 2. KnowledgeBootstrapJob — Workspace'den sonra
        KnowledgeBootstrapJob::dispatch($ilan->id)
            ->onQueue('bc001-knowledge');

        // 3. AiBootstrapJob — fotoğraf varsa tetikle
        if ($ilan->fotograflar()->exists()) {
            AiBootstrapJob::dispatch($ilan->id)
                ->onQueue('bc001-ai');
        }

        // 4. PublishingBootstrapJob — en son
        PublishingBootstrapJob::dispatch($ilan->id)
            ->onQueue('bc001-publishing');

        // BC001 chain ID oluştur — child job'lar bu ID'yi kullanır
        $chainId = 'BC001-' . $ilan->id . '-' . now()->format('His');
        Log::info('[BC001Orchestrator] Chain started', [
            'ilan_id' => $ilan->id,
            'chain_id' => $chainId,
        ]);

        $duration = round((microtime(true) - $startTime) * 1000);

        Log::info('[BC001Orchestrator] Bootstrap dispatched', [
            'ilan_id' => $ilan->id,
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Workspace bootstrap — Drive folder oluşturur.
     * WorkspaceBootstrapJob'dan çağrılır.
     */
    public function runWorkspaceBootstrap(int $ilanId): void
    {
        $ilan = Ilan::withTrashed()->find($ilanId);
        if (!$ilan) {
            Log::warning('[BC001Orchestrator] Ilan not found', ['ilan_id' => $ilanId]);
            return;
        }

        Log::info('[BC001Orchestrator] Workspace bootstrap starting', [
            'ilan_id' => $ilanId,
        ]);

        $referansNo = $ilan->referans_no ?? 'REF-' . $ilanId;
        $baslik = $ilan->baslik ?? 'Portföy';

        // Drive workspace oluştur
        $result = $this->driveService->createWorkspace($referansNo, $baslik, $ilan->tenant_id);

        if (!$result->isSuccessful()) {
            Log::error('[BC001Orchestrator] Drive workspace creation failed', [
                'ilan_id' => $ilanId,
                'error' => $result->errorMessage,
            ]);
            return;
        }

        // Subfolder'ları oluştur
        $subfolders = $this->driveService->createSubfolders(
            $result->rootFolderId,
            $referansNo
        );

        // Database metadata kaydet
        $workspace = $this->driveService->storeWorkspaceMeta(
            ilanId: $ilanId,
            tenantId: $ilan->tenant_id,
            rootFolderId: $result->rootFolderId,
            rootFolderUrl: $result->rootFolderUrl,
            rootFolderName: $result->metadata['folder_name'] ?? $referansNo,
            portfolioNo: $referansNo,
            subfolders: $subfolders
        );

        // Workspace state machine — DRAFT → WORKSPACE_CREATED
        $workspace->markWorkspaceCreated();

        // Execution kaydet
        $this->executionService->dispatch(
            $workspace,
            'bc001-workspace',
            'Drive Workspace Oluşturuldu',
            ['ilan_id' => $ilanId],
            ['folder_id' => $result->rootFolderId]
        );

        Log::info('[BC001Orchestrator] Workspace bootstrap complete', [
            'ilan_id' => $ilanId,
            'folder_id' => $result->rootFolderId,
        ]);
    }
}
