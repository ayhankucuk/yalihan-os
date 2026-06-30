<?php

namespace App\Jobs;

use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Notify N8n About New Ilan Job
 *
 * Context7: Yeni ilan oluşturulduğunda n8n'e webhook bildirimi gönderir
 *
 * @package App\Jobs
 */
class NotifyN8nAboutNewIlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * İlan ID
     */
    public int $ilanId;

    /**
     * Max deneme sayısı
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $ilanId)
    {
        $this->ilanId = $ilanId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.new_ilan_webhook_url');
        $webhookSecret = config('services.n8n.webhook_secret', '');

        if (empty($webhookUrl)) {
            LogService::warning('N8n webhook URL not configured', [
                'ilan_id' => $this->ilanId,
            ]);

            return;
        }

        try {
            LogService::info('Sending n8n webhook notification for new ilan', [
                'ilan_id' => $this->ilanId,
                'webhook_url' => $webhookUrl,
            ]);

            $response = Http::timeout(config('services.n8n.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-N8N-SECRET' => $webhookSecret,
                ])
                ->post($webhookUrl, [
                    'event' => 'ilan_created',
                    'ilan_id' => $this->ilanId,
                    'timestamp' => now()->toISOString(),
                ]);

            if ($response->successful()) {
                LogService::info('N8n webhook notification sent successfully', [
                    'ilan_id' => $this->ilanId,
                    'http_status' => (int) $response->toPsrResponse()->getStatusCode(),
                    'response' => $response->json(),
                ]);
            } else {
                LogService::error('N8n webhook notification failed', [
                    'ilan_id' => $this->ilanId,
                    'http_status' => (int) $response->toPsrResponse()->getStatusCode(),
                    'body' => $response->body(),
                ]);

                // Job'u tekrar denemek için throw et
                throw new \Exception('N8n webhook failed with http_code: '.(int) $response->toPsrResponse()->getStatusCode());
            }
        } catch (\Exception $e) {
            LogService::error('N8n webhook notification exception', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
            ], $e);

            // Job'u tekrar denemek için throw et
            throw $e;
        }
    }
}












