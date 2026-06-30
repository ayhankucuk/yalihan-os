<?php

namespace App\Services\AI\Notifications;

use Illuminate\Support\Facades\Log;

class SlackChannel implements NotificationChannel
{
    public function send(array $alert): bool
    {
        if (!config('ai-alerts.channels.slack')) {
            return false;
        }

        $webhookUrl = config('ai-alerts.slack.webhook_url');
        
        // Mock implementation - log instead of actual Slack API call
        Log::info('[MOCK] Slack notification would be sent', [
            'webhook' => $webhookUrl ? 'configured' : 'not_configured',
            'channel' => config('ai-alerts.slack.channel'),
            'alert' => $alert
        ]);

        // In production, this would make actual HTTP request to Slack webhook
        // Http::post($webhookUrl, $this->formatSlackPayload($alert));

        return true;
    }

    private function formatSlackPayload(array $alert): array
    {
        $emoji = match($alert['severity'] ?? 'info') {
            'emergency' => ':rotating_light:',
            'critical' => ':warning:',
            'warning' => ':yellow_circle:',
            default => ':information_source:'
        };

        return [
            'channel' => config('ai-alerts.slack.channel'),
            'username' => config('ai-alerts.slack.username'),
            'icon_emoji' => config('ai-alerts.slack.icon_emoji'),
            'text' => "{$emoji} *{$alert['message']}*",
            'attachments' => [
                [
                    'color' => $this->getSeverityColor($alert['severity'] ?? 'info'),
                    'fields' => $this->formatFields($alert['data'] ?? []),
                    'footer' => 'Yalıhan AI Monitor',
                    'ts' => now()->timestamp
                ]
            ]
        ];
    }

    private function getSeverityColor(string $severity): string
    {
        return match($severity) {
            'emergency', 'critical' => 'danger',
            'warning' => 'warning',
            default => 'good'
        };
    }

    private function formatFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = [
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'value' => is_numeric($value) ? number_format($value, 2) : $value,
                'short' => true
            ];
        }
        return $fields;
    }
}
