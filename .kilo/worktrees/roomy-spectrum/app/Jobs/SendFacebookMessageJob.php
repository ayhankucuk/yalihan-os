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
 * SendFacebookMessageJob
 *
 * Queued job for sending Facebook Messenger messages via Meta Business API
 * Supports quick reply buttons and structured messages
 * Implements retry logic with exponential backoff
 */
class SendFacebookMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $recipientId;
    protected string $message;
    protected array $quickReplies;
    protected ?int $leadId;

    public int $tries = 3;
    public array $backoff = [60, 120, 240]; // seconds: 1min, 2min, 4min

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $recipientId,
        string $message,
        array $quickReplies = [],
        ?int $leadId = null
    ) {
        $this->recipientId = $recipientId;
        $this->message = $message;
        $this->quickReplies = $quickReplies;
        $this->leadId = $leadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate configuration
            $pageAccessToken = config('services.facebook.page_access_token');
            $apiVersion = config('services.facebook.api_version', 'v18.0');

            if (!$pageAccessToken) {
                throw new \Exception('Facebook Page access token missing');
            }

            // Build message payload
            $payload = [
                'recipient' => [
                    'id' => $this->recipientId,
                ],
                'message' => [
                    'text' => $this->message,
                ],
            ];

            // Add quick reply buttons if provided
            if (!empty($this->quickReplies)) {
                $payload['message']['quick_replies'] = array_map(function ($reply) {
                    return [
                        'content_type' => 'text',
                        'title' => $reply['title'],
                        'payload' => $reply['payload'],
                    ];
                }, $this->quickReplies);
            }

            // Meta Business API endpoint for Facebook Messenger
            $url = "https://graph.facebook.com/{$apiVersion}/me/messages";

            $response = Http::withToken($pageAccessToken)
                ->timeout(30)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('Facebook message sent successfully', [
                    'lead_id' => $this->leadId,
                    'recipient_id' => substr($this->recipientId, 0, 5) . '***',
                    'message_length' => strlen($this->message),
                    'quick_replies_count' => count($this->quickReplies),
                    'message_id' => $response->json('message_id'),
                ]);
            } else {
                throw new \Exception(
                    "Facebook API error: {$response->status()} - {$response->body()}"
                );
            }
        } catch (\Exception $e) {
            Log::error('Facebook message send failed', [
                'lead_id' => $this->leadId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'recipient_id' => substr($this->recipientId, 0, 5) . '***',
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
        Log::error('Facebook message job failed permanently', [
            'lead_id' => $this->leadId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'recipient_id' => substr($this->recipientId, 0, 5) . '***',
        ]);

        // Support ticket for failed message will be handled by external monitoring
        // Admin notification for persistent failure will be handled by external monitoring
    }
}
