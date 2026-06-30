<?php

namespace App\Jobs;

use App\Models\Ilan;
use App\Services\Ranking\VisibilityScoreService;
use App\Services\SEO\SeoEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateVisibilityScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ilanId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ilanId)
    {
        $this->ilanId = $ilanId;
    }

    /**
     * Execute the job.
     */
    public function handle(VisibilityScoreService $rankingService, SeoEngineService $seoService): void
    {
        try {
            $ilan = Ilan::find($this->ilanId);

            // Fail silent if deleted
            if (!$ilan) return;

            // 1. SEO Hardening (Persist First)
            // Ensure SEO tags are idempotent and present
            $seoService->generateAndPersist($ilan);

            // 2. Calculate New Score (Deterministic)
            // Uses saveQuietly() internally if changed
            $newScore = $rankingService->updateScore($ilan);

            // Log high-value updates for monitoring
            if ($newScore > 8000) {
                Log::info("🌟 High Visibility Score: #{$ilan->id} -> {$newScore}");
            }

        } catch (\Exception $e) {
            Log::error("🔥 Visibility Score Job Failed #{$this->ilanId}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
