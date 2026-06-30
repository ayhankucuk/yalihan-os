<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Console\Command;

class RecordAnalyticsMetricCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:record-metric
                           {type : Metric type (git_activity, context7_health, etc.)}
                           {name : Metric name}
                           {data : Metric data}
                           {--value= : Numerical value}
                           {--source=system : Source of the metric}
                           {--severity=info : Severity level}';

    /**
     * The console command description.
     */
    protected $description = 'Record an analytics metric for Context7 Dashboard';

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $type = $this->argument('type');
            $name = $this->argument('name');
            $data = $this->argument('data');
            $value = $this->option('value') ? (float) $this->option('value') : null;
            $source = $this->option('source');
            $severity = $this->option('severity');

            // If data is numeric and no value provided, use data as value
            if (is_null($value) && is_numeric($data)) {
                $value = (float) $data;
            }

            $metric = $this->analyticsService->recordMetric(
                $type,
                $name,
                $data,
                $value,
                $source,
                $severity
            );

            $this->info("✅ Metric recorded: {$type}.{$name} = {$data}".($value ? " (value: {$value})" : ''));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to record metric: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
