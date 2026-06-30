<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function send(string $phone, string $message): bool
    {
        // Placeholder implementation
        Log::info("SMS Sent to {$phone}: {$message}");
        return true;
    }
}
