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
 * n8n'e Görev Deadline Yaklaşıyor Bildirimi Job
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Multi-Channel (Telegram, WhatsApp, Email) destekli deadline hatırlatma bildirimi
 */
class NotifyN8nAboutGorevDeadlineYaklasiyor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $gorevId;
    public int $kalanGun;
    public array $notificationChannels;

    public function __construct(
        int $gorevId,
        int $kalanGun,
        array $notificationChannels = ['telegram', 'whatsapp', 'email']
    ) {
        $this->gorevId = $gorevId;
        $this->kalanGun = $kalanGun;
        $this->notificationChannels = $notificationChannels;
    }

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.gorev_deadline_yaklasiyor_webhook_url');

        if (empty($webhookUrl)) {
            LogService::warning('n8n gorev deadline yaklasiyor webhook URL not configured', [
                'gorev_id' => $this->gorevId,
            ]);
            return;
        }

        try {
            $gorev = Gorev::with(['danisman', 'admin', 'musteri'])->find($this->gorevId);

            if (!$gorev) {
                LogService::warning('Gorev not found for deadline notification', [
                    'gorev_id' => $this->gorevId,
                ]);
                return;
            }

            $payload = [
                'event' => 'GorevDeadlineYaklasiyor',
                'gorev_id' => $gorev->id,
                'gorev' => [
                    'id' => $gorev->id,
                    'baslik' => $gorev->baslik,
                    'bitis_tarihi' => $gorev->bitis_tarihi?->toISOString(),
                    'danisman_adi' => $gorev->danisman?->name ?? null,
                    'url' => url('/admin/takim-yonetimi/gorevler/' . $gorev->id),
                ],
                'deadline' => [
                    'kalan_gun' => $this->kalanGun,
                    'bitis_tarihi' => $gorev->bitis_tarihi?->toISOString(),
                    'acil' => $this->kalanGun <= 1,
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
                LogService::info('n8n webhook notification sent successfully for gorev deadline yaklasiyor', [
                    'gorev_id' => $gorev->id,
                ]);
            } else {
                throw new \Exception('n8n webhook failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            LogService::error('n8n webhook notification exception for gorev deadline yaklasiyor', [
                'gorev_id' => $this->gorevId,
                'error' => $e->getMessage(),
            ], $e, LogService::CHANNEL_AI);
            throw $e;
        }
    }
}
