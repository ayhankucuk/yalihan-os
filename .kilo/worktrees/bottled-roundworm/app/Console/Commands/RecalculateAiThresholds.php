<?php

namespace App\Console\Commands;

use App\Services\AI\AdaptiveThresholdEngine;
use Illuminate\Console\Command;

class RecalculateAiThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:recalculate-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate adaptive AI confidence thresholds based on user learning signals';

    /**
     * Execute the console command.
     */
    public function handle(AdaptiveThresholdEngine $engine)
    {
        $this->info('Starting AI Threshold Recalculation...');
        
        $updatedCount = $engine->recalculateAll();
        
        $this->success("AI Thresholds updated. Total profiles adjusted: {$updatedCount}");
        
        return self::SUCCESS;
    }

    protected function success($message)
    {
        $this->line("<fg=green>✔</> {$message}");
    }
}
