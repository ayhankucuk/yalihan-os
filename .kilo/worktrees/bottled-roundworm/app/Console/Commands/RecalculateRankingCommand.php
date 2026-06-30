<?php

namespace App\Console\Commands;

use App\Enums\IlanDurumu;

use Illuminate\Console\Command;
use App\Models\Ilan;
use App\Jobs\UpdateListingVisibilityScore;
use App\Services\Visibility\ListingRankingService;
use App\Services\Seo\SeoEngineService;

class RecalculateRankingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:recalculate-all {--chunk=500} {--dry : Check only mode} {--sync : Force sync execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate visibility scores and SEO for all listings (Phase 19)';

    /**
     * Execute the console command.
     */
    public function handle(ListingRankingService $rankingService, SeoEngineService $seoService)
    {
        $chunkSize = (int) $this->option('chunk');
        $dry = $this->option('dry');
        $sync = $this->option('sync');

        $this->info("🚀 DAP Ranking Engine v2");
        $this->info("--------------------------------");
        $this->info("📦 Chunk: {$chunkSize}");
        $this->info("🛡️  Mode: " . ($dry ? 'DRY-RUN (No Persistence)' : 'APPLY (Persist)'));
        $this->info("⚡ Exec: " . ($sync || $dry ? 'Sync' : 'Async Queue'));

        $query = Ilan::query()->whereIn('yayin_durumu', [IlanDurumu::YAYINDA->value, 'yayinda']);
        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $startTime = microtime(true);

        $stats = [
            'processed' => 0,
            'changed' => 0,
            'min_score' => 10000,
            'max_score' => 0,
            'avg_score' => 0,
            'sum_score' => 0,
            'distribution' => [
                '0-2000' => 0,
                '2001-4000' => 0,
                '4001-6000' => 0,
                '6001-8000' => 0,
                '8001-10000' => 0,
            ]
        ];

        $query->chunkById($chunkSize, function ($ilanlar) use ($bar, $rankingService, $seoService, $dry, $sync, &$stats) {
            foreach ($ilanlar as $ilan) {
                // 1. Calculate Expected Score
                // We must generate SEO meta first if we want accurate SEO score
                if (empty($ilan->seo_meta)) {
                    $ilan->seo_meta = $seoService->generateSeoMeta($ilan);
                }

                $currentScore = $ilan->visibility_score;
                $newScore = $rankingService->calculateVisibilityScore($ilan); // Deterministic
                $qualityScore = $rankingService->calculateQualityScore($ilan);
                $seoScore = $rankingService->calculateSeoScore($ilan);

                // 2. Stats Collection
                $stats['processed']++;
                $stats['sum_score'] += $newScore;
                $stats['min_score'] = min($stats['min_score'], $newScore);
                $stats['max_score'] = max($stats['max_score'], $newScore);

                if ($currentScore !== $newScore) {
                    $stats['changed']++;
                }

                // Bucket Distribution
                if ($newScore <= 2000) $stats['distribution']['0-2000']++;
                elseif ($newScore <= 4000) $stats['distribution']['2001-4000']++;
                elseif ($newScore <= 6000) $stats['distribution']['4001-6000']++;
                elseif ($newScore <= 8000) $stats['distribution']['6001-8000']++;
                else $stats['distribution']['8001-10000']++;

                // 3. Action (Dry vs Apply)
                if (!$dry) {
                    if ($sync) {
                        $ilan->visibility_score = $newScore;
                        $ilan->seo_score = $seoScore;
                        $ilan->quality_score = $qualityScore;
                        // seo_meta is already set on the model instance above if missing
                        $ilan->saveQuietly();
                    } else {
                        // For queue, we just dispatch. The job will re-calculate.
                        // But wait, the job re-calculates. The stats here might differ if DB data changes in usage.
                        // For REPORTING purposes, we used the calculated values.
                        UpdateListingVisibilityScore::dispatch($ilan);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Calculate Average
        $stats['avg_score'] = $stats['processed'] > 0 ? round($stats['sum_score'] / $stats['processed']) : 0;
        $stats['runtime'] = round(microtime(true) - $startTime, 2) . 's';

        // Report
        $this->generateReport($stats, $dry);
    }

    protected function generateReport(array $stats, bool $dry)
    {
        $report = "# RANKING BACKFILL REPORT (Phase 19)\n";
        $report .= "Date: " . now()->toDateTimeString() . "\n";
        $report .= "Mode: " . ($dry ? "DRY-RUN" : "APPLIED") . "\n";
        $report .= "Runtime: " . $stats['runtime'] . "\n\n";

        $report .= "## Summary\n";
        $report .= "- Total Processed: {$stats['processed']}\n";
        $report .= "- Impacted (Changed): {$stats['changed']}\n";
        $report .= "- Min Score: {$stats['min_score']}\n";
        $report .= "- Max Score: {$stats['max_score']}\n";
        $report .= "- Average Score: {$stats['avg_score']}\n\n";

        $report .= "## Score Distribution\n";
        foreach ($stats['distribution'] as $range => $count) {
            $report .= "- {$range}: {$count}\n";
        }

        // Output to console
        $this->table(['Metric', 'Value'], [
            ['Total', $stats['processed']],
            ['Changed', $stats['changed']],
            ['Min', $stats['min_score']],
            ['Max', $stats['max_score']],
            ['Avg', $stats['avg_score']],
            ['Runtime', $stats['runtime']],
        ]);

        // Output to file
        $path = base_path('docs/_reports/RANKING_BACKFILL_REPORT.md');
        file_put_contents($path, $report);
        $this->info("\n📄 Report generated: docs/_reports/RANKING_BACKFILL_REPORT.md");
    }
}
