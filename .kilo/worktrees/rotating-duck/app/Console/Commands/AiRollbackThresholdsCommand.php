<?php
// context7-ignore: 'runId' bu dosyada PHP değişken adı (camelCase local var). DB kolon adı değil.

namespace App\Console\Commands;

use App\Models\AiOptimizationRun;
use App\Models\AiThresholdOverride;
use Illuminate\Console\Command;

class AiRollbackThresholdsCommand extends Command
{
    protected $signature = 'ai:rollback-thresholds {runId : Optimization run id}';

    protected $description = 'Rollback threshold overrides created by a specific optimization run';

    public function handle(): int
    {
        $runId = (int) $this->argument('runId');
        $run = AiOptimizationRun::findOrFail($runId);

        if (! $this->confirm("Are you sure you want to rollback optimization run #{$run->id}?")) {
            $this->warn('Rollback cancelled');
            return self::SUCCESS;
        }

        AiThresholdOverride::where('run_id', $run->id)->delete();

        $this->info("Rollback completed for run #{$run->id}");
        return self::SUCCESS;
    }
}
