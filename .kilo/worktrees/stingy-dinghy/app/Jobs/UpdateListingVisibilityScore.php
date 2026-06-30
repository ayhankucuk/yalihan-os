<?php

namespace App\Jobs;

use App\Models\Ilan;
use App\Services\Ranking\ListingRankingService;
use App\Services\Seo\SeoEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Phase 19: Async Visibility Score Update Job
 *
 * - Idempotent: Cache lock prevents spam (30s per ilan)
 * - Fail-safe: Exception → visibility_score = 0 (never null)
 * - saveQuietly: Prevents Observer loop
 * - Feature flag: config('context7_features.visibility_score_enabled')
 */
class UpdateListingVisibilityScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $ilanId
    ) {
        $this->onQueue('ranking');
    }

    /**
     * Execute the job.
     */
    public function handle(
        ListingRankingService $rankingService,
        SeoEngineService $seoService
    ): void {
        // Feature flag check
        if (!config('context7_features.visibility_score_enabled', true)) {
            return;
        }

        // Cache lock: prevent spam for same ilan (30s window)
        $lockKey = "ranking:update:{$this->ilanId}";
        $lock = Cache::lock($lockKey, 30);

        if (!$lock->get()) {
            Log::debug("⏭️ Ranking update skipped (locked): #{$this->ilanId}");
            return;
        }

        try {
            $ilan = Ilan::find($this->ilanId);

            // Fail silent if deleted
            if (!$ilan) {
                $lock->release();
                return;
            }

            // Non-published listings get score 0
            if (!$ilan->yayindami) {
                $ilan->visibility_score = 0;
                $ilan->saveQuietly();
                $lock->release();
                return;
            }

            // 1. SEO Meta generation (idempotent — regenerate each time for freshness)
            if (config('context7_features.seo_engine_enabled', true)) {
                $ilan->seo_meta = $seoService->generateSeoMeta($ilan);
            }

            // 2. Calculate scores (deterministic, 0-10000)
            $ilan->visibility_score = $rankingService->calculateScore($ilan);
            $ilan->seo_score = $rankingService->calculateSeoScore($ilan);
            $ilan->quality_score = $rankingService->calculateQualityScore($ilan);

            // 3. Persist (saveQuietly prevents Observer loop)
            $ilan->saveQuietly();

            // Log high-value updates
            if ($ilan->visibility_score > 8000) {
                Log::info("🌟 High Visibility: #{$ilan->id} → {$ilan->visibility_score}");
            }

        } catch (\Exception $e) {
            // Fail-safe: never leave visibility_score null
            Log::error("🔥 Ranking Job Failed #{$this->ilanId}: " . $e->getMessage());

            try {
                $ilan = Ilan::find($this->ilanId);
                if ($ilan && $ilan->visibility_score === null) {
                    $ilan->visibility_score = 0;
                    $ilan->saveQuietly();
                }
            } catch (\Exception $inner) {
                Log::error("🔥 Ranking Job fail-safe also failed #{$this->ilanId}");
            }

            $this->fail($e);
        } finally {
            $lock->release();
        }
    }
}
