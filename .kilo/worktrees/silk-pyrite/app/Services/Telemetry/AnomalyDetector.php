<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Telemetry Anomaly Detector
 * Part of L5 Self-Protecting System
 *
 * Purpose: Real-time detection of performance/error anomalies
 * Triggers: Automated alerts when thresholds breached
 *
 * @version 1.0.0
 * @since 2026-02-15
 */
class AnomalyDetector
{
    /**
     * Anomaly detection rules from config
     *
     * @var array
     */
    protected array $rules;

    /**
     * Alert channels configuration
     *
     * @var array
     */
    protected array $alertChannels;

    public function __construct()
    {
        $this->rules = config('telemetry-events.anomaly_detection', [
            'enabled' => true,
            'alert_threshold_percentage' => 50,
            'alert_channels' => ['log', 'slack'],
        ]);

        $this->alertChannels = $this->rules['alert_channels'] ?? ['log'];
    }

    /**
     * Check metrics for anomalies and trigger alerts
     *
     * @param array $metrics
     * @return array Detected anomalies
     */
    public function check(array $metrics): array
    {
        if (!$this->rules['enabled']) {
            return [];
        }

        $anomalies = [];

        // Check 1: Error rate spike
        if (isset($metrics['error_rate']) && $metrics['error_rate'] > 2.0) {
            $anomalies[] = $this->detectErrorRateSpike($metrics['error_rate']);
        }

        // Check 2: Wizard context latency spike
        if (isset($metrics['wizard_context_p95']) && $metrics['wizard_context_p95'] > 400) {
            $anomalies[] = $this->detectLatencySpike('wizard_context', $metrics['wizard_context_p95'], 400);
        }

        // Check 3: AI generation latency spike
        if (isset($metrics['ai_generation_p95']) && $metrics['ai_generation_p95'] > 3000) {
            $anomalies[] = $this->detectLatencySpike('ai_generation', $metrics['ai_generation_p95'], 3000);
        }

        // Check 4: Dashboard load spike
        if (isset($metrics['dashboard_load_p95']) && $metrics['dashboard_load_p95'] > 1500) {
            $anomalies[] = $this->detectLatencySpike('dashboard_load', $metrics['dashboard_load_p95'], 1500);
        }

        // Check 5: AI cost surge
        if (isset($metrics['ai_cost_daily']) && isset($metrics['ai_budget_daily'])) {
            $utilization = ($metrics['ai_cost_daily'] / $metrics['ai_budget_daily']) * 100;
            if ($utilization > 80) {
                $anomalies[] = $this->detectCostSurge($utilization, $metrics['ai_cost_daily']);
            }
        }

        // Check 6: Disk usage
        if (isset($metrics['disk_usage_percent']) && $metrics['disk_usage_percent'] > 85) {
            $anomalies[] = $this->detectDiskUsage($metrics['disk_usage_percent']);
        }

        // Trigger alerts for detected anomalies
        foreach ($anomalies as $anomaly) {
            $this->triggerAlert($anomaly);
        }

        return array_filter($anomalies);
    }

    /**
     * Detect error rate spike anomaly
     *
     * @param float $errorRate
     * @return array|null
     */
    protected function detectErrorRateSpike(float $errorRate): ?array
    {
        $severity = $errorRate > 5.0 ? 'critical' : 'high';

        return [
            'type' => 'error_rate_spike', // context7-ignore
            'severity' => $severity,
            'current_value' => $errorRate,
            'threshold' => 2.0,
            'message' => "Error rate spike detected: {$errorRate}% (threshold: 2%)",
            'recommended_action' => 'Check recent deployments, review error logs, verify external service health',
        ];
    }

    /**
     * Detect latency spike anomaly
     *
     * @param string $metric
     * @param float $currentValue
     * @param float $threshold
     * @return array|null
     */
    protected function detectLatencySpike(string $metric, float $currentValue, float $threshold): ?array
    {
        // Get baseline from cache or config
        $baseline = Cache::get("perf_baseline_{$metric}", $threshold);
        $spike = (($currentValue - $baseline) / $baseline) * 100;

        if ($spike < $this->rules['alert_threshold_percentage']) {
            return null; // Within tolerance
        }

        $severity = $spike > 100 ? 'critical' : 'medium';

        return [
            'type' => 'latency_spike', // context7-ignore
            'metric' => $metric,
            'severity' => $severity,
            'current_value' => $currentValue,
            'baseline' => $baseline,
            'threshold' => $threshold,
            'spike_percentage' => round($spike, 2),
            'message' => "{$metric} latency spike: {$currentValue}ms (baseline: {$baseline}ms, +{$spike}%)",
            'recommended_action' => 'Check database query performance, verify cache hit rate, review N+1 queries',
        ];
    }

    /**
     * Detect AI cost surge
     *
     * @param float $utilization
     * @param float $currentCost
     * @return array|null
     */
    protected function detectCostSurge(float $utilization, float $currentCost): ?array
    {
        $severity = $utilization > 95 ? 'critical' : 'high';

        return [
            'type' => 'ai_cost_surge', // context7-ignore
            'severity' => $severity,
            'utilization' => round($utilization, 2),
            'current_cost' => $currentCost,
            'message' => "AI cost at {$utilization}% of daily budget (\${$currentCost})",
            'recommended_action' => 'Switch to fallback provider, review token usage, check for retry loops',
        ];
    }

    /**
     * Detect high disk usage
     *
     * @param float $usagePercent
     * @return array|null
     */
    protected function detectDiskUsage(float $usagePercent): ?array
    {
        $severity = $usagePercent > 90 ? 'critical' : 'high';

        return [
            'type' => 'disk_usage_high', // context7-ignore
            'severity' => $severity,
            'usage_percent' => $usagePercent,
            'message' => "Disk usage at {$usagePercent}%",
            'recommended_action' => 'Trigger log rotation, prune old telemetry, check for large temp files',
        ];
    }

    /**
     * Trigger alert through configured channels
     *
     * @param array $anomaly
     * @return void
     */
    protected function triggerAlert(array $anomaly): void
    {
        // Log channel (always enabled)
        $logChannel = $anomaly['severity'] === 'critical' ? 'security' : 'telemetry';
        Log::channel($logChannel)->warning('anomaly_detected', $anomaly);

        // Slack channel (if configured)
        if (in_array('slack', $this->alertChannels) && config('services.alerts.slack_webhook_url')) {
            $this->sendSlackAlert($anomaly);
        }

        // Email channel (if configured)
        if (in_array('email', $this->alertChannels) && config('services.alerts.email')) {
            $this->sendEmailAlert($anomaly);
        }

        // Cache anomaly for dashboard display
        $this->cacheAnomaly($anomaly);
    }

    /**
     * Send Slack alert
     *
     * @param array $anomaly
     * @return void
     */
    protected function sendSlackAlert(array $anomaly): void
    {
        $emoji = $anomaly['severity'] === 'critical' ? '🔴' : '🟠';
        $color = $anomaly['severity'] === 'critical' ? 'danger' : 'warning';

        $payload = [
            'text' => "{$emoji} *Anomaly Detected*",
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Type', // context7-ignore
                            'value' => $anomaly['type'], // context7-ignore
                            'short' => true,
                        ],
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($anomaly['severity']),
                            'short' => true,
                        ],
                        [
                            'title' => 'Message',
                            'value' => $anomaly['message'],
                            'short' => false,
                        ],
                        [
                            'title' => 'Action',
                            'value' => $anomaly['recommended_action'],
                            'short' => false,
                        ],
                    ],
                    'footer' => 'Yalıhan Anomaly Detector',
                    'ts' => time(),
                ],
            ],
        ];

        try {
            Http::timeout(5)
                ->post(config('services.alerts.slack_webhook_url'), $payload);
        } catch (\Exception $e) {
            Log::error('slack_alert_failed', [
                'hata_mesaji' => $e->getMessage(), // ✅ SAB compliant
                'anomaly_type' => $anomaly['type'] ?? 'unknown', // context7-ignore
            ]);
        }
    }

    /**
     * Send email alert
     *
     * @param array $anomaly
     * @return void
     */
    protected function sendEmailAlert(array $anomaly): void
    {
        // Implement email alert logic
        // For now, just log
        Log::info('email_alert_triggered', $anomaly);
    }

    /**
     * Cache anomaly for dashboard display
     *
     * @param array $anomaly
     * @return void
     */
    protected function cacheAnomaly(array $anomaly): void
    {
        $key = 'recent_anomalies';
        $anomalies = Cache::get($key, []);

        // Add timestamp
        $anomaly['detected_at'] = now()->toIso8601String();

        // Keep only last 50 anomalies
        $anomalies[] = $anomaly;
        if (count($anomalies) > 50) {
            array_shift($anomalies);
        }

        Cache::put($key, $anomalies, now()->addDays(7));
    }

    /**
     * Get recent anomalies from cache
     *
     * @return array
     */
    public static function getRecentAnomalies(): array
    {
        return Cache::get('recent_anomalies', []);
    }
}
