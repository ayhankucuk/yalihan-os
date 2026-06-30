<?php

namespace App\Jobs;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * n8n'e Yeni Görev Bildirimi Job
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Multi-Channel (Telegram, WhatsApp, Email) destekli görev oluşturma bildirimi
 */
class NotifyN8nAboutNewGorev implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Görev ID
     */
    public int $gorevId;

    /**
     * Bildirim kanalları (Telegram, WhatsApp, Email)
     */
    public array $notificationChannels;

    /**
     * Create a new job instance.
     *
     * @param int $gorevId
     * @param array $notificationChannels
     */
    public function __construct(int $gorevId, array $notificationChannels = ['telegram', 'whatsapp', 'email'])
    {
        $this->gorevId = $gorevId;
        $this->notificationChannels = $notificationChannels;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.gorev_created_webhook_url');

        if (empty($webhookUrl)) {
            LogService::warning('n8n gorev created webhook URL not configured', [
                'gorev_id' => $this->gorevId,
            ]);

            return;
        }

        try {
            // Görev bilgilerini çek
            $gorev = Gorev::with(['danisman', 'admin', 'musteri', 'proje'])->find($this->gorevId);

            if (!$gorev) {
                LogService::warning('Gorev not found for notification', [
                    'gorev_id' => $this->gorevId,
                ]);

                return;
            }

            // n8n'e gönderilecek payload
            $payload = [
                'event' => 'GorevCreated',
                'gorev_id' => $gorev->id,
                'gorev' => [
                    'id' => $gorev->id,
                    'baslik' => $gorev->baslik,
                    'aciklama' => $gorev->aciklama,
                    'oncelik' => $gorev->oncelik,
                    'durum' => $gorev->gorev_durumu,
                    'tip' => $gorev->tip,
                    'bitis_tarihi' => $gorev->bitis_tarihi?->toISOString(),
                    'tahmini_sure' => $gorev->tahmini_sure,
                    'danisman_adi' => $gorev->danisman?->name ?? null,
                    'danisman_telefon' => $gorev->danisman?->telefon ?? null,
                    'musteri_adi' => $gorev->musteri?->tam_ad ?? null,
                    'proje_adi' => $gorev->proje?->baslik ?? null,
                    'url' => url('/admin/takim-yonetimi/gorevler/' . $gorev->id),
                ],
                'notification_channels' => $this->notificationChannels, // Multi-channel support
                'timestamp' => now()->toISOString(),
                'metadata' => [
                    'source' => 'laravel',
                    'version' => '1.0.0',
                ],
            ];

            LogService::info('Sending n8n webhook notification for gorev created', [
                'gorev_id' => $gorev->id,
                'webhook_url' => $webhookUrl,
                'channels' => $this->notificationChannels,
            ]);

            $webhookSecret = config('services.n8n.webhook_secret', '');
            $timeout = config('services.n8n.timeout', 30);

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-N8N-SECRET' => $webhookSecret,
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                LogService::info('n8n webhook notification sent successfully for gorev created', [
                    'gorev_id' => $gorev->id,
                    'islem_durumu' => $response->getStatusCode(),
                    'response' => $response->json(),
                ]);
            } else {
                LogService::error('n8n webhook notification failed for gorev created', [
                    'gorev_id' => $gorev->id,
                    'islem_durumu' => $response->getStatusCode(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('n8n webhook failed with durum: ' . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            LogService::error('n8n webhook notification exception for gorev created', [
                'gorev_id' => $this->gorevId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $e, LogService::CHANNEL_AI);

            throw $e;
        }
    }
}
