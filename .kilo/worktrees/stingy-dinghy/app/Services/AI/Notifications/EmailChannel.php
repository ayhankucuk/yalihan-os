<?php

namespace App\Services\AI\Notifications;

use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannel
{
    public function send(array $alert): bool
    {
        if (!config('ai-alerts.channels.email')) {
            return false;
        }

        $to = config('ai-alerts.email.to');
        $subject = config('ai-alerts.email.subject_prefix') . ' ' . ($alert['message'] ?? 'Alert');

        // Mock implementation - log instead of actual email send
        Log::info('[MOCK] Email notification would be sent', [
            'to' => $to,
            'subject' => $subject,
            'alert' => $alert
        ]);

        // In production, this would use Laravel Mail
        // Mail::to($to)->send(new AiAlertMail($alert));

        return true;
    }
}
