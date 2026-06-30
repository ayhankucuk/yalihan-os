<?php

namespace App\Console\Commands\Cortex;

use App\Services\Cortex\OpportunityHunter;
use Illuminate\Console\Command;
use App\Services\Logging\LogService;

class HuntOpportunitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:hunt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cortex Avcı Modülü: Sıcak fırsatları tarar ve tebliğ eder.';

    /**
     * Execute the console command.
     */
    public function handle(OpportunityHunter $hunter)
    {
        $this->info('🏹 Cortex Avcı Modülü başlatılıyor...');
        
        $startTime = microtime(true);
        
        $opportunities = $hunter->scanForOpportunities();
        
        $duration = round(microtime(true) - $startTime, 2);
        $count = count($opportunities);

        $this->info("✅ Tarama tamamlandı. {$count} yeni fırsat yakalandı.");
        $this->info("⏱️ Süre: {$duration} saniye.");

        LogService::info("HuntOpportunitiesCommand: Tarama tamamlandı.", [
            'count' => $count,
            'duration' => $duration
        ]);

        return Command::SUCCESS;
    }
}
