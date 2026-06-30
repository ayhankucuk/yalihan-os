<?php

namespace App\Console\Commands;

use App\Services\Telemetry\AnomalyDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Telemetry Anomaly Detection Command
 * Part of L5 Self-Protecting System
 *
 * Schedule: Every 10 minutes in production, hourly in staging
 * Purpose: Detect and alert on performance/error anomalies
 *
 * Usage:
 *   php artisan telemetry:detect-anomalies
 *   php artisan telemetry:detect-anomalies --verbose
 */
class DetectTelemetryAnomalies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:detect-anomalies
                            {--verbose : Display detailed metrics}
                            {--alert : Force alert even for minor anomalies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect performance and error anomalies from telemetry data';

    /**
     * Anomaly detector instance
     *
     * @var AnomalyDetector
     */
    protected AnomalyDetector $detector;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->detector = new AnomalyDetector();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🔍 Telemetry Anomaly Detection');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Collect metrics from various sources
        $metrics = $this->collectMetrics();

        if ($this->option('verbose')) {
            $this->displayMetrics($metrics);
        }

        // Run anomaly detection
        $this->info('Running anomaly detection...');
        $anomalies = $this->detector->check($metrics);

        // Display results
        if (empty($anomalies)) {
            $this->info('✅ No anomalies detected');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->warn("⚠️  {count($anomalies)} anomal" . (count($anomalies) > 1 ? 'ies' : 'y') . ' detected');
        $this->newLine();

        foreach ($anomalies as $index => $anomaly) {
            $this->displayAnomaly($index + 1, $anomaly);
        }

        return Command::SUCCESS;
    }

    /**
     * Collect metrics from telemetry logs and system
     *
     * @return array
     */
    protected function collectMetrics(): array
    {
        $metrics = [];

        // 1. Error rate from today's telemetry log
        $errorRate = $this->calculateErrorRate();
        if ($errorRate !== null) {
            $metrics['error_rate'] = $errorRate;
        }

        // 2. Performance metrics from baseline file
        $baselineFile = base_path('reports/performance-baseline.json');
        if (File::exists($baselineFile)) {
            $baseline = json_decode(File::get($baselineFile), true);

            if (isset($baseline['wizard_context_api']['p95'])) {
                $metrics['wizard_context_p95'] = $baseline['wizard_context_api']['p95'];
            }
        }

        // 3. AI cost metrics (if tracking enabled)
        $metrics['ai_cost_daily'] = $this->getAICostToday();
        $metrics['ai_budget_daily'] = config('ai-budgets.daily_budget_usd', 50);

        // 4. Disk usage
        $metrics['disk_usage_percent'] = $this->getDiskUsage();

        return $metrics;
    }

    /**
     * Calculate error rate from today's telemetry log
     *
     * @return float|null
     */
    protected function calculateErrorRate(): ?float
    {
        $logFile = storage_path('logs/telemetry-' . now()->format('Y-m-d') . '.log');

        if (!File::exists($logFile)) {
            return null;
        }

        $content = File::get($logFile);
        $lines = explode("\n", $content);

        $totalEvents = 0;
        $errorEvents = 0;

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Parse JSON log line
            $data = json_decode($line, true);
            if (!$data) {
                continue;
            }

            // Check if it's a frontend event
            if (isset($data['message']) && $data['message'] === 'frontend_event') {
                $totalEvents++;

                // Check for error indicators
                $context = $data['context'] ?? [];
                $payload = $context['payload'] ?? [];

                if (
                    isset($payload['basarili']) && $payload['basarili'] === false ||
                    isset($payload['hata_mesaji']) ||
                    strpos($data['message'] ?? '', 'error') !== false
                ) {
                    $errorEvents++;
                }
            }
        }

        if ($totalEvents === 0) {
            return 0.0;
        }

        return round(($errorEvents / $totalEvents) * 100, 2);
    }

    /**
     * Get today's AI cost (from logs or cache)
     *
     * @return float
     */
    protected function getAICostToday(): float
    {
        // This would integrate with actual AI cost tracking
        // For now, return 0 (implement based on your AI logging)
        return 0.0;
    }

    /**
     * Get disk usage percentage
     *
     * @return float
     */
    protected function getDiskUsage(): float
    {
        $df = shell_exec('df -h ' . base_path() . ' | tail -1');
        preg_match('/(\d+)%/', $df, $matches);

        return (float) ($matches[1] ?? 0);
    }

    /**
     * Display collected metrics
     *
     * @param array $metrics
     * @return void
     */
    protected function displayMetrics(array $metrics): void
    {
        $this->info('📊 Collected Metrics:');
        $this->table(
            ['Metric', 'Value'],
            collect($metrics)->map(function ($value, $key) {
                return [$key, is_numeric($value) ? round($value, 2) : $value];
            })->values()->toArray()
        );
        $this->newLine();
    }

    /**
     * Display anomaly details
     *
     * @param int $index
     * @param array $anomaly
     * @return void
     */
    protected function displayAnomaly(int $index, array $anomaly): void
    {
        $emoji = match ($anomaly['severity']) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            default => 'ℹ️',
        };

        $this->line("{$emoji} <options=bold>Anomaly #{$index}: {$anomaly['type']}</>");
        $this->line("   Severity: <fg=yellow>{$anomaly['severity']}</>");
        $this->line("   Message: {$anomaly['message']}");
        $this->line("   Action: <fg=cyan>{$anomaly['recommended_action']}</>");
        $this->newLine();
    }
}
