<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send WhatsApp Message via Meta Business API
 *
 * Queued job (Redis) with automatic retry on failure
 *
 * Ultra-Think: Hata Simülasyonu - Meta API fail → retry logic
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $phoneNumber;
    protected string $message;
    protected int $attempt = 0;

    public function __construct(string $phoneNumber, string $message)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            // 1. Validate configuration
            $errors = \App\Services\Notification\WhatsAppNotificationManager::validateConfiguration();
            if (!empty($errors)) {
                Log::error('WhatsApp configuration invalid', ['errors' => $errors]);
                $this->fail(new \Exception('Configuration error'));
                return;
            }

            // 2. Build Meta API request
            $businessAccountId = config('services.whatsapp.business_account_id');
            $accessToken = config('services.whatsapp.access_token');
            $apiVersion = config('services.whatsapp.api_version', 'v18.0');
            $phoneNumberId = config('services.whatsapp.phone_number_id');

            $url = "https://graph.instagram.com/{$apiVersion}/{$phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $this->message,
                ],
            ];

            // 3. Send request with retry logic
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, $payload);

            // 4. Handle response
            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => substr($this->phoneNumber, 0, 3) . '***',
                    'message_id' => $response->json('messages.0.id'),
                ]);
                return;
            }

            // 5. Handle errors
            $error = $response->json('error.message', 'Unknown error');
            Log::warning('WhatsApp API error', [
                'status' => $response->status(),
                'error' => $error,
                'attempts' => $this->attempts(),
            ]);

            // 6. Retry with exponential backoff
            if ($this->attempts() < 3) {
                $delay = 60 * (2 ** ($this->attempts() - 1)); // 60s, 120s, 240s
                $this->release($delay);
            } else {
                // Max retries exceeded
                Log::error('WhatsApp message failed after max retries', [
                    'phone' => substr($this->phoneNumber, 0, 3) . '***',
                    'attempts' => $this->attempts(),
                ]);
                $this->fail(new \Exception("Max retries exceeded: {$error}"));
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp job exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->attempts() < 3) {
                $delay = 60 * (2 ** ($this->attempts() - 1));
                $this->release($delay);
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Job failed handler
     *
     * Triggered when max retries exhausted
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message delivery failed', [
            'phone' => substr($this->phoneNumber, 0, 3) . '***',
            'message' => substr($this->message, 0, 100) . '...',
            'error' => $exception->getMessage(),
        ]);

        // Admin notification or support ticket creation will go here in future implementations
    }
}
