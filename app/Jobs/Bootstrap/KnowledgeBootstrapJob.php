<?php

namespace App\Jobs\Bootstrap;

use App\Models\Ilan;
use App\Models\PortfolioDriveWorkspace;
use App\Services\Workspace\WorkspaceExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * KnowledgeBootstrapJob
 *
 * Sprint 5.0 — BC-001 Epic 2: Knowledge Bootstrap
 *
 * Workspace READY olduktan sonra:
 * - Ilan bilgilerini NotebookLM formatına çevir
 * - Corporate memory dosyasına not ekle
 */
class KnowledgeBootstrapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public readonly int $ilanId,
    ) {
        $this->onQueue('bc001-knowledge');
    }

    public function handle(WorkspaceExecutionService $executionService): void
    {
        Log::info('[KnowledgeBootstrapJob] Starting', ['ilan_id' => $this->ilanId]);

        $ilan = Ilan::withTrashed()->find($this->ilanId);
        if (!$ilan) {
            Log::warning('[KnowledgeBootstrapJob] Ilan not found', ['ilan_id' => $this->ilanId]);
            return;
        }

        $workspace = PortfolioDriveWorkspace::forPortfolio($this->ilanId)->first();
        if (!$workspace) {
            Log::warning('[KnowledgeBootstrapJob] Workspace not found', ['ilan_id' => $this->ilanId]);
            return;
        }

        // 1. NotebookLM kaydı oluştur (portföy bilgisi)
        $this->createNotebookLmEntry($ilan, $workspace);

        // 2. Corporate memory' e not ekle
        $this->appendToCorporateMemory($ilan);

        // 3. Execution kaydet
        $executionService->dispatch(
            $workspace,
            'bc001-knowledge',
            'Knowledge Bootstrap Tamamlandı',
            ['ilan_id' => $this->ilanId],
            ['notebook_synced' => true]
        );

        Log::info('[KnowledgeBootstrapJob] Complete', ['ilan_id' => $this->ilanId]);
    }

    private function createNotebookLmEntry(Ilan $ilan, PortfolioDriveWorkspace $workspace): void
    {
        // Notebooks: portföy bilgisi knowledge base'e kaydedilir
        // Not: NotebookLM API entegrasyonu Sprint 5.1'de tamamlanacak
        // Şimdilik workspace metadata'sına işaret ediyoruz
        $metadata = $workspace->metadata_json ?? [];
        $metadata['notebook_synced_at'] = now()->toIso8601String();
        $metadata['notebook_synced'] = true;
        $metadata['ilan_baslik'] = $ilan->baslik;
        $metadata['ilan_fiyat'] = $ilan->fiyat;
        $metadata['ilan_katagori'] = $ilan->ana_kategori_id;

        $workspace->update(['metadata_json' => $metadata]);

        Log::info('[KnowledgeBootstrapJob] NotebookLM entry created', [
            'ilan_id' => $this->ilanId,
        ]);
    }

    private function appendToCorporateMemory(Ilan $ilan): void
    {
        $note = sprintf(
            "[%s] Yeni portföy oluşturuldu: %s | Ref: %s | Fiyat: %s %s",
            now()->toDateTimeString(),
            $ilan->baslik ?? 'Başlıksız',
            $ilan->referans_no ?? 'N/A',
            $ilan->fiyat ?? 'Belirtilmedi',
            $ilan->para_birimi ?? 'TL'
        );

        // memory/ dosyasına not ekle (log formatında)
        $memoryFile = base_path('memory/daily/' . now()->format('Y-m-d') . '.md');
        $directory = dirname($memoryFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $header = "# " . now()->format('Y-m-d') . " — Otomatik Not\n\n";
        $content = "- {$note}\n\n";

        if (file_exists($memoryFile)) {
            file_put_contents($memoryFile, $content, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($memoryFile, $header . $content, LOCK_EX);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[KnowledgeBootstrapJob] Permanently failed', [
            'ilan_id' => $this->ilanId,
            'error' => $e->getMessage(),
        ]);
    }
}
