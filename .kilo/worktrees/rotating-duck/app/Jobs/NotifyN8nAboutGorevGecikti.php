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
 * n8n'e Görev Gecikti Bildirimi Job
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Multi-Channel (Telegram, WhatsApp, Email) destekli gecikme bildirimi
 */
class NotifyN8nAboutGorevGecikti implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $gorevId;
    public int $gecikmeGunu;
    public array $notificationChannels;

    public function __construct(
        int $gorevId,
        int $gecikmeGunu,
        array $notificationChannels = ['telegram', 'whatsapp', 'email']
    ) {
        $this->gorevId = $gorevId;
        $this->gecikmeGunu = $gecikmeGunu;
        $this->notificationChannels = $notificationChannels;
    }

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.gorev_gecikti_webhook_url');

        if (empty($webhookUrl)) {
            LogService::warning('n8n gorev gecikti webhook URL not configured', [
                'gorev_id' => $this->gorevId,
            ]);
            return;
        }

        try {
            $gorev = Gorev::with(['danisman', 'admin', 'musteri'])->find($this->gorevId);

            if (!$gorev) {
                LogService::warning('Gorev not found for gecikme notification', [
                    'gorev_id' => $this->gorevId,
                ]);
                return;
            }

            $payload = [
                'event' => 'GorevGecikti',
                'gorev_id' => $gorev->id,
                'gorev' => [
                    'id' => $gorev->id,
                    'baslik' => $gorev->baslik,
                    'gorev_durumu' => $gorev->gorev_durumu,
                    'bitis_tarihi' => $gorev->bitis_tarihi?->toISOString(),
                    'danisman_adi' => $gorev->danisman?->name ?? null,
                    'url' => url('/admin/takim-yonetimi/gorevler/' . $gorev->id),
                ],
                'gecikme' => [
                    'gecikme_gunu' => $this->gecikmeGunu,
                    'bitis_tarihi' => $gorev->bitis_tarihi?->toISOString(),
                    'acil' => true,
                ],
                'notification_channels' => $this->notificationChannels,
                'timestamp' => now()->toISOString(),
            ];

            $response = Http::timeout(config('services.n8n.timeout', 30))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-N8N-SECRET' => config('services.n8n.webhook_secret', ''),
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                LogService::info('n8n webhook notification sent successfully for gorev gecikti', [
                    'gorev_id' => $gorev->id,
                ]);
            } else {
                throw new \Exception('n8n webhook failed with HTTP code: ' . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            LogService::error('n8n webhook notification exception for gorev gecikti', [
                'gorev_id' => $this->gorevId,
                'error' => $e->getMessage(),
            ], $e, LogService::CHANNEL_AI);
            throw $e;
        }
    }
}
