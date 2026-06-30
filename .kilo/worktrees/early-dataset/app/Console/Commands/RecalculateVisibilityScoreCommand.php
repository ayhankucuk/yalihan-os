<?php

namespace App\Console\Commands;

use App\Services\Ranking\ListingRankingService;
use Illuminate\Console\Command;

class RecalculateVisibilityScoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ilan:recalc-visibility {--chunk=500 : Process listings in batches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculates the visibility_score for all active listings based on the hardened ranking formula.';

    /**
     * Execute the console command.
     */
    public function handle(ListingRankingService $rankingService): int
    {
        $chunkSize = (int) $this->option('chunk');

        $this->info("Starting visibility ranking recalculation (Batch size: {$chunkSize})...");

        $rankingService->recalculateBatch($chunkSize);

        $this->info('Successfully recalculated visibility scores for all listings.');

        return self::SUCCESS;
    }
}
