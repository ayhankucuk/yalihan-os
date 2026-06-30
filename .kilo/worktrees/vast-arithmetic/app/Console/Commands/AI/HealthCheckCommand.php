<?php

namespace App\Console\Commands\AI;

use App\Services\AI\CortexMonitoringService;
use App\Services\Logging\LogService;
use Illuminate\Console\Command;

/**
 * Phase K: AI Health Check Command
 *
 * Monitors AI system health and alerts on anomalies
 * Context7: Read-only monitoring, observer mode
 */
class HealthCheckCommand extends Command
{
    protected $signature = 'ai:health-check';
    protected $description = 'Check AI system health and alert on issues';

    protected CortexMonitoringService $monitoringService;

    public function __construct(CortexMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    public function handle()
    {
        $this->info('✅ Running AI health check...');

        // Get metrics for last 15 minutes
        $metrics = $this->monitoringService->getMetrics(0.25); // 15 min = 0.25 hours
        $publishMetrics = $this->monitoringService->getPublishMetrics(0.25);

        $thresholds = config('ups_ai.monitoring');
        $hasIssues = false;

        // Check error rate
        if ($metrics['error_rate'] > ($thresholds['error_rate_threshold'] * 100)) {
            LogService::warning('AI error rate high', [
                'error_rate' => $metrics['error_rate'],
                'threshold' => $thresholds['error_rate_threshold'] * 100,
                'total_requests' => $metrics['total_requests'],
            ]);
            $this->warn("⚠️ Error rate: {$metrics['error_rate']}% (threshold: " . ($thresholds['error_rate_threshold'] * 100) . "%)");
            $hasIssues = true;
        }

        // Check P95 latency
        if ($metrics['p95_duration_ms'] > $thresholds['p95_threshold_ms']) {
            LogService::warning('AI P95 latency high', [
                'p95_ms' => $metrics['p95_duration_ms'],
                'threshold_ms' => $thresholds['p95_threshold_ms'],
            ]);
            $this->warn("⚠️ P95 latency: {$metrics['p95_duration_ms']}ms (threshold: {$thresholds['p95_threshold_ms']}ms)");
            $hasIssues = true;
        }

        // Check block rate
        if ($publishMetrics['block_rate'] > ($thresholds['block_rate_threshold'] * 100)) {
            LogService::info('AI publish block rate high (investigate)', [
                'block_rate' => $publishMetrics['block_rate'],
                'threshold' => $thresholds['block_rate_threshold'] * 100,
                'total_decisions' => $publishMetrics['total_decisions'],
            ]);
            $this->info("🔍 Block rate: {$publishMetrics['block_rate']}% (investigate threshold: " . ($thresholds['block_rate_threshold'] * 100) . "%)");
        }

        if (!$hasIssues) {
            $this->info('✅ All health checks passed');
        }

        return 0;
    }
}
