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
 * SendInstagramMessageJob
 *
 * Queued job for sending Instagram DM messages via Meta Business API
 * Implements retry logic with exponential backoff
 */
class SendInstagramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $phoneNumberOrUsername;
    protected string $message;
    protected ?int $leadId;

    public int $tries = 3;
    public array $backoff = [60, 120, 240]; // seconds: 1min, 2min, 4min

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumberOrUsername, string $message, ?int $leadId = null)
    {
        $this->phoneNumberOrUsername = $phoneNumberOrUsername;
        $this->message = $message;
        $this->leadId = $leadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate configuration
            $accountId = config('services.instagram.business_account_id');
            $token = config('services.instagram.access_token');
            $apiVersion = config('services.instagram.api_version', 'v18.0');

            if (!$accountId || !$token) {
                throw new \Exception('Instagram configuration missing');
            }

            // Meta Business API endpoint for Instagram DM
            $url = "https://graph.instagram.com/{$apiVersion}/{$accountId}/messages";

            $response = Http::withToken($token)
                ->timeout(30)
                ->post($url, [
                    'recipient' => [
                        'username' => $this->phoneNumberOrUsername,
                    ],
                    'message' => [
                        'text' => $this->message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('Instagram message sent successfully', [
                    'lead_id' => $this->leadId,
                    'recipient' => substr($this->phoneNumberOrUsername, 0, 3) . '***',
                    'message_length' => strlen($this->message),
                    'response_id' => $response->json('id'),
                ]);
            } else {
                throw new \Exception(
                    "Instagram API error: {$response->status()} - {$response->body()}"
                );
            }
        } catch (\Exception $e) {
            Log::error('Instagram message send failed', [
                'lead_id' => $this->leadId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'recipient' => substr($this->phoneNumberOrUsername, 0, 3) . '***',
            ]);

            // Retry with backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Instagram message job failed permanently', [
            'lead_id' => $this->leadId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'recipient' => substr($this->phoneNumberOrUsername, 0, 3) . '***',
        ]);

        // Support ticket for failed message will be handled by external monitoring
        // Admin notification for persistent failure will be handled by external monitoring
    }
}
