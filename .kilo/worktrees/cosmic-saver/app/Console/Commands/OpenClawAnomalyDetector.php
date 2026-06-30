<?php

namespace App\Console\Commands;

use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * OpenClawAnomalyDetector — Detects abnormal agent behavior patterns.
 *
 * Analyzes the openclaw_audit_logs table for:
 * - Write violation bursts (>threshold in time window)
 * - High block rates (above configured percentage)
 * - Token proliferation (too many unique tokens)
 * - Rate spikes (>2x normal request volume)
 *
 * Schedule: Every 10 minutes (production), hourly (staging).
 * Output: security log + bekci log + exit code (0=clean, 1=anomaly detected)
 */
class OpenClawAnomalyDetector extends Command
{
    protected $signature = 'openclaw:detect-anomalies
                            {--window=10 : Analysis window in minutes}
                            {--dry-run : Print results without logging alerts}';

    protected $description = 'Detect anomalous agent behavior patterns from OpenClaw audit logs';

    private array $anomalies = [];

    public function handle(OpenClawAuditService $auditService): int
    {
        $window = (int) $this->option('window');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("OpenClaw Anomaly Detection — window: {$window} min");

        $stats = $auditService->getWindowStats($window);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', $stats['total_requests']],
                ['Blocked', $stats['blocked_count']],
                ['Write Violations', $stats['violation_count']],
                ['Unique Tokens', $stats['unique_tokens']],
                ['Block Rate', ($stats['block_rate'] * 100) . '%'],
            ]
        );

        // Check anomaly rules
        $this->checkWriteViolationBurst($window);
        $this->checkHighBlockRate($stats);
        $this->checkTokenProliferation($stats, $window);
        $this->checkRateSpike($stats, $window);

        if (empty($this->anomalies)) {
            $this->info('✓ No anomalies detected.');
            return self::SUCCESS;
        }

        $this->error(count($this->anomalies) . ' anomaly(ies) detected:');
        foreach ($this->anomalies as $anomaly) {
            $this->warn("  [{$anomaly['severity']}] {$anomaly['rule']}: {$anomaly['description']}");
        }

        if (!$dryRun) {
            $this->logAnomalies();
        }

        return self::FAILURE;
    }

    private function checkWriteViolationBurst(int $window): void
    {
        $threshold = (int) config('openclaw.anomaly_detection.violation_burst_threshold', 3);
        $since = now()->subMinutes($window);

        $violations = OpenClawAuditLog::violations()->since($since)->count();

        if ($violations >= $threshold) {
            $this->anomalies[] = [
                'rule' => 'write_violation_burst',
                'severity' => 'CRITICAL',
                'description' => "{$violations} write violations in {$window} min (threshold: {$threshold})",
                'value' => $violations,
                'threshold' => $threshold,
            ];
        }
    }

    private function checkHighBlockRate(array $stats): void
    {
        $threshold = (float) config('openclaw.anomaly_detection.block_rate_threshold', 0.5);

        if ($stats['total_requests'] >= 5 && $stats['block_rate'] > $threshold) {
            $pct = round($stats['block_rate'] * 100, 1);
            $this->anomalies[] = [
                'rule' => 'high_block_rate',
                'severity' => 'HIGH',
                'description' => "Block rate {$pct}% exceeds {$threshold}% threshold ({$stats['blocked_count']}/{$stats['total_requests']})",
                'value' => $stats['block_rate'],
                'threshold' => $threshold,
            ];
        }
    }

    private function checkTokenProliferation(array $stats, int $window): void
    {
        $threshold = (int) config('openclaw.anomaly_detection.token_proliferation_threshold', 5);

        if ($stats['unique_tokens'] >= $threshold) {
            $this->anomalies[] = [
                'rule' => 'token_proliferation',
                'severity' => 'HIGH',
                'description' => "{$stats['unique_tokens']} unique tokens in {$window} min (threshold: {$threshold})",
                'value' => $stats['unique_tokens'],
                'threshold' => $threshold,
            ];
        }
    }

    private function checkRateSpike(array $stats, int $window): void
    {
        $baselinePerMin = (float) config('openclaw.anomaly_detection.baseline_requests_per_minute', 10);
        $multiplier = (float) config('openclaw.anomaly_detection.spike_multiplier', 2.0);

        $expectedMax = $baselinePerMin * $window * $multiplier;

        if ($stats['total_requests'] > $expectedMax) {
            $this->anomalies[] = [
                'rule' => 'rate_spike',
                'severity' => 'MEDIUM',
                'description' => "{$stats['total_requests']} requests in {$window} min (expected max: {$expectedMax})",
                'value' => $stats['total_requests'],
                'threshold' => $expectedMax,
            ];
        }
    }

    private function logAnomalies(): void
    {
        $payload = [
            'anomalies' => $this->anomalies,
            'anomaly_count' => count($this->anomalies),
            'detected_at' => now()->toIso8601String(),
        ];

        Log::channel(config('openclaw.audit.log_channel', 'security'))
            ->critical('openclaw_anomaly_detected', $payload);

        // Also log to bekci channel for governance audit trail
        try {
            Log::channel('bekci')->critical('openclaw_anomaly_detected', $payload);
        } catch (\Throwable) {
            // bekci channel may not exist in test env
        }
    }
}
