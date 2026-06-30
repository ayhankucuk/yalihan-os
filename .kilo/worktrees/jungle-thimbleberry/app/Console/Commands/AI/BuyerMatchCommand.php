<?php

namespace App\Console\Commands\AI;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use Illuminate\Console\Command;

/**
 * ️ SAB SEALED
 * AI Buyer Match Scan Command
 * Scans active listings and generates buyer matches via Cortex.
 */
class BuyerMatchCommand extends Command
{
    protected $signature = 'ai:scan-buyer-matches {--ilan_id= : Specific listing ID} {--limit=100 : Processing limit}';
    protected $description = 'Scan active listings and detect potential buyer matches (SAB v16.4)';

    public function __construct(
        private YalihanCortex $cortex
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ilanId = $this->option('ilan_id');
        $limit = (int) $this->option('limit');

        $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value);

        if ($ilanId) {
            $query->where('id', $ilanId);
        }

        $ilanlar = $query->limit($limit)->get();

        $this->info("Scanning " . $ilanlar->count() . " listings for potential buyers...");

        $bar = $this->output->createProgressBar($ilanlar->count());
        $bar->start();

        foreach ($ilanlar as $ilan) {
            try {
                $this->cortex->detectBuyerMatches($ilan);
            } catch (\Exception $e) {
                $this->error("\nError processing Ilan #{$ilan->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nScan completed successfully.");

        return Command::SUCCESS;
    }
}
