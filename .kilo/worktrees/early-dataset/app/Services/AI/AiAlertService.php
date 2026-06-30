<?php

namespace App\Services\AI;

use App\Services\AI\Notifications\LogChannel;
use App\Services\AI\Notifications\SlackChannel;
use App\Services\AI\Notifications\EmailChannel;
use Illuminate\Support\Facades\Log;

class AiAlertService
{
    protected array $channels = [];

    public function __construct()
    {
        if (config('ai-alerts.enabled')) {
            if (config('ai-alerts.channels.log')) {
                $this->channels[] = new LogChannel();
            }
            if (config('ai-alerts.channels.slack')) {
                $this->channels[] = new SlackChannel();
            }
            if (config('ai-alerts.channels.email')) {
                $this->channels[] = new EmailChannel();
            }
        }
    }

    /**
     * Send alert through all configured channels
     */
    public function sendAlert(string $type, string $severity, string $message, array $data = []): void
    {
        if (!config('ai-alerts.enabled')) {
            return;
        }

        $alert = [
            'type' => $type, // context7-ignore
            'severity' => $severity,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String()
        ];

        foreach ($this->channels as $channel) {
            try {
                $channel->send($alert);
            } catch (\Exception $e) {
                Log::error('Alert channel failed', [
                    'channel' => get_class($channel),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Cost Guard budget alert
     */
    public function costGuardAlert(float $usageRatio, float $currentSpend, float $limit): void
    {
        $percentage = round($usageRatio * 100, 1);

        if ($usageRatio >= config('ai-alerts.cost_guard.kill_switch_threshold')) {
            $this->sendAlert(
                'cost_guard_kill_switch',
                'emergency',
                "🚨 AI Budget KILL SWITCH activated at {$percentage}%",
                [
                    'current_spend' => $currentSpend,
                    'budget_limit' => $limit,
                    'usage_ratio' => $usageRatio,
                    'action' => 'All AI requests blocked'
                ]
            );
        } elseif ($usageRatio >= config('ai-alerts.cost_guard.critical_threshold')) {
            $this->sendAlert(
                'cost_guard_critical',
                'critical',
                "⚠️ AI Budget CRITICAL at {$percentage}% - Provider downgrade act' . 'ive",
                [
                    'current_spend' => $currentSpend,
                    'budget_limit' => $limit,
                    'usage_ratio' => $usageRatio,
                    'action' => 'Downgraded to cheapest provider'
                ]
            );
        } elseif ($usageRatio >= config('ai-alerts.cost_guard.warning_threshold')) {
            $this->sendAlert(
                'cost_guard_warning',
                'warning',
                "⚡ AI Budget WARNING at {$percentage}%",
                [
                    'current_spend' => $currentSpend,
                    'budget_limit' => $limit,
                    'usage_ratio' => $usageRatio,
                    'action' => 'Monitor closely'
                ]
            );
        }
    }

    /**
     * Provider error rate alert
     */
    public function providerErrorAlert(string $provider, float $errorRate, int $sampleSize): void
    {
        $percentage = round($errorRate * 100, 1);

        if ($errorRate >= config('ai-alerts.provider_errors.critical_threshold')) {
            $this->sendAlert(
                'provider_error_critical',
                'critical',
                "🔴 Provider {$provider} error rate CRITICAL at {$percentage}%",
                [
                    'provider' => $provider,
                    'error_rate' => $errorRate,
                    'sample_size' => $sampleSize,
                    'action' => 'Auto-cooldown act' . 'ivated'
                ]
            );
        } elseif ($errorRate >= config('ai-alerts.provider_errors.warning_threshold')) {
            $this->sendAlert(
                'provider_error_warning',
                'warning',
                "🟡 Provider {$provider} error rate elevated at {$percentage}%",
                [
                    'provider' => $provider,
                    'error_rate' => $errorRate,
                    'sample_size' => $sampleSize,
                    'action' => 'Monitor provider health'
                ]
            );
        }
    }
}
