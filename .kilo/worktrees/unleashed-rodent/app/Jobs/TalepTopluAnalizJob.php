<?php

namespace App\Jobs;

use App\Models\Talep;
use App\Modules\TalepAnaliz\Services\AIAnalizService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Talep Toplu Analiz Job
 *
 * Context7 Standardı: C7-QUEUE-JOB-2025-11-05
 *
 * Birden fazla talebi queue'da analiz eder
 */
class TalepTopluAnalizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Analiz edilecek talep ID'leri
     */
    public array $talepIds;

    /**
     * Job ID (progress tracking için)
     */
    public string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $talepIds, string $jobId)
    {
        $this->talepIds = $talepIds;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIAnalizService $analizService): void
    {
        $total = count($this->talepIds);
        $processed = 0;
        $success = 0;
        $failed = 0;
        $results = [];

        // Progress başlangıcı
        $this->updateProgress(0, $total, 0, 0);

        foreach ($this->talepIds as $talepId) {
            try {
                $talep = Talep::find($talepId);

                if (! $talep) {
                    $failed++;
                    $processed++;

                    continue;
                }

                // Analiz et
                $sonuc = $analizService->analizEt($talep);

                $results[] = [
                    'talep_id' => $talepId,
                    'status' => 'success',
                    'sonuc' => $sonuc,
                ];

                $success++;
            } catch (\Exception $e) {
                Log::error('Talep toplu analiz hatası', [
                    'talep_id' => $talepId,
                    'job_id' => $this->jobId,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'talep_id' => $talepId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                $failed++;
            }

            $processed++;

            // Progress güncelle
            $this->updateProgress($processed, $total, $success, $failed);
        }

        // Sonuçları cache'e kaydet
        Cache::put("talep_toplu_analiz_{$this->jobId}_results", [
            'total' => $total,
            'processed' => $processed,
            'success' => $success,
            'failed' => $failed,
            'results' => $results,
            'completed_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        // Progress tamamlandı
        $this->updateProgress($total, $total, $success, $failed, true);
    }

    /**
     * Progress'i güncelle (Redis/Cache)
     */
    protected function updateProgress(int $processed, int $total, int $success, int $failed, bool $completed = false): void
    {
        $progress = [
            'job_id' => $this->jobId,
            'processed' => $processed,
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'percentage' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'status' => $completed ? 'completed' : 'processing',
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::put("talep_toplu_analiz_{$this->jobId}_progress", $progress, now()->addHours(24));
    }

    /**
     * Job failed olduğunda
     */
    public function failed(\Throwable $exception): void
    {
        Cache::put("talep_toplu_analiz_{$this->jobId}_progress", [
            'job_id' => $this->jobId,
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        Log::error('Talep toplu analiz job başarısız', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
