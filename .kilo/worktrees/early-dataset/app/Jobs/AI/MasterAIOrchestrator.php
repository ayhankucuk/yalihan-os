<?php

namespace App\Jobs\AI;

use App\Jobs\AI\VisionAnalysisJob;
use App\Jobs\AI\GenerateStorytellingJob;
use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Master AI Orchestrator Job
 * Context7-Hybrid: Vision + Storytelling Pipeline
 * 
 * Tek tuşla tüm AI sihirini başlatır:
 * 1. Fotoğrafları analiz et (Vision Engine)
 * 2. Duygusal metin oluştur (Storytelling AI)
 * 3. Danışmana hazır paketi sun
 */
class MasterAIOrchestrator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $ilanId,
        protected array $fotografYollari = [],
        protected string $ton = 'profesyonel'
    ) {}

    public function handle(): void
    {
        LogService::info('Master AI Orchestrator: Başlatıldı', [
            'ilan_id' => $this->ilanId,
            'foto_sayisi' => count($this->fotografYollari),
            'ton' => $this->ton
        ]);

        // 1. Vision Engine: Tüm fotoğrafları paralel analiz et
        foreach ($this->fotografYollari as $yol) {
            VisionAnalysisJob::dispatch($this->ilanId, $yol);
        }

        // 2. Storytelling AI: Vision tamamlandıktan 30 saniye sonra çalış
        GenerateStorytellingJob::dispatch($this->ilanId, $this->ton)
            ->delay(now()->addSeconds(30));

        LogService::info('Master AI Orchestrator: Pipeline kuruldu', [
            'ilan_id' => $this->ilanId,
            'vision_jobs' => count($this->fotografYollari),
            'storytelling_delay' => '30s'
        ]);
    }
}
