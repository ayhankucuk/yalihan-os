<?php

namespace App\Console\Commands\AI;

use App\Models\Ilan;
use App\Jobs\AI\GenerateDealPredictionsJob;
use Illuminate\Console\Command;

/**
 * ️ SAB SEALED
 * Scan Deals Command
 * Batch processes listings for AI Deal Predictions.
 */
class ScanDealsCommand extends Command
{
    protected $signature = 'ai:scan-deals {--ilan_id= : Specific listing ID} {--limit=100 : Batch limit}';
    protected $description = 'Batch generate AI Deal Predictions for listings';

    public function handle()
    {
        $listingId = $this->option('ilan_id');
        $limit = (int) $this->option('limit');

        if ($listingId) {
            $ilan = Ilan::find($listingId);
            if (!$ilan) {
                $this->error("Listing not found: {$listingId}");
                return 1;
            }

            GenerateDealPredictionsJob::dispatch($ilan);
            $this->info("Dispatched prediction job for listing: {$listingId}");
            return 0;
        }

        // Sequential scan for listings without recent predictions
        $listings = Ilan::whereDoesntHave('dealPredictions', function ($query) {
                $query->where('created_at', '>=', now()->subDays(3));
            })
            ->limit($limit)
            ->get();

        $this->info("Dispatched jobs for " . $listings->count() . " listings.");

        foreach ($listings as $ilan) {
            GenerateDealPredictionsJob::dispatch($ilan);
        }

        return 0;
    }
}
