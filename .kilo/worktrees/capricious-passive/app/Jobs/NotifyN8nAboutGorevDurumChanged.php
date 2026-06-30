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
 * n8n'e Görev Durumu Değişikliği Bildirimi Job
 *
 * Context7: Takım Yönetimi Otomasyonu - Temel Event Sistemi
 * Multi-Channel (Telegram, WhatsApp, Email) destekli görev durumu değişikliği bildirimi
 */
class NotifyN8nAboutGorevDurumChanged implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $gorevId;
    public string $eskiDurum;
    public string $yeniDurum;
    public array $notificationChannels;

    public function __construct(
        int $gorevId,
        string $eskiDurum,
        string $yeniDurum,
        array $notificationChannels = ['telegram', 'whatsapp', 'email']
    ) {
        $this->gorevId = $gorevId;
        $this->eskiDurum = $eskiDurum;
        $this->yeniDurum = $yeniDurum;
        $this->notificationChannels = $notificationChannels;
    }

    public function handle(): void
    {
        $webhookUrl = config('services.n8n.gorev_durum_changed_webhook_url');

        if (empty($webhookUrl)) {
            LogService::warning('n8n gorev durumu değişikliği webhook URL yapılandırılmamış', [
                'gorev_id' => $this->gorevId,
            ]);
            return;
        }

        try {
            $gorev = Gorev::with(['danisman', 'admin', 'musteri'])->find($this->gorevId);

            if (!$gorev) {
                LogService::warning('Görev bulunamadı (durum değişikliği bildirimi)', [
                    'gorev_id' => $this->gorevId,
                ]);
                return;
            }

            $payload = [
                'event' => 'GorevDurumChanged',
                'gorev_id' => $gorev->id,
                'gorev' => [
                    'id' => $gorev->id,
                    'baslik' => $gorev->baslik,
                    'gorev_durumu' => $gorev->gorev_durumu,
                    'danisman_adi' => $gorev->danisman?->name ?? null,
                    'url' => url('/admin/takim-yonetimi/gorevler/' . $gorev->id),
                ],
                'durum_degisim' => [
                    'eski_durum' => $this->eskiDurum,
                    'yeni_durum' => $this->yeniDurum,
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
                LogService::info('n8n webhook bildirimi başarıyla gönderildi (görev durumu değişikliği)', [
                    'gorev_id' => $gorev->id,
                ]);
            } else {
                $httpCode = $response->getStatusCode();
                throw new \Exception('n8n webhook başarısız (HTTP kodu: ' . $httpCode . ')');
            }
        } catch (\Exception $e) {
            LogService::error('n8n webhook bildirimi hatası (görev durumu değişikliği)', [
                'gorev_id' => $this->gorevId,
                'http_code' => method_exists($e, 'getCode') ? $e->getCode() : null,
                'error' => $e->getMessage(),
            ], $e, LogService::CHANNEL_AI);
            throw $e;
        }
    }
}
