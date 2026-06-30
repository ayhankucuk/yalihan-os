<?php

namespace App\Console\Commands;

use App\Models\Ilan;
use App\Services\AI\SemanticSearchService;
use Illuminate\Console\Command;

class SyncSemanticEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:semantic-sync {--limit=10 : Limit number of listings to sync} {--all : Sync all listings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Existing ilanları semantik vektör veri tabanına senkronize eder.';

    /**
     * Execute the console command.
     */
    public function handle(SemanticSearchService $semanticService)
    {
        $limit = $this->option('limit');
        $all = $this->option('all');

        $query = Ilan::query();

        if (!$all) {
            $query->limit($limit);
        }

        $listings = $query->get();
        $total = $listings->count();

        $this->info("Toplam {$total} ilan senkronize ediliyor...");
        $bar = $this->output->createProgressBar($total);

        $successCount = 0;
        foreach ($listings as $listing) {
            if ($semanticService->syncIlan($listing)) {
                $successCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Senkronizasyon tamamlandı: {$successCount} başarılı, " . ($total - $successCount) . " başarısız.");
    }
}
