<?php

namespace App\Services\AI\Notifications;

use Illuminate\Support\Facades\Log;

class LogChannel implements NotificationChannel
{
    public function send(array $alert): bool
    {
        $severity = $alert['severity'] ?? 'info';
        $message = $alert['message'] ?? 'AI Alert';
        $context = [
            'type' => $alert['type'] ?? 'unknown', // context7-ignore
            'data' => $alert['data'] ?? [],
            'timestamp' => $alert['timestamp'] ?? now()->toIso8601String()
        ];

        match($severity) {
            'emergency', 'critical' => Log::critical($message, $context),
            'warning' => Log::warning($message, $context),
            default => Log::info($message, $context)
        };

        return true;
    }
}
