<?php

namespace App\Jobs;

use App\Models\Ilan;
use App\Services\Logging\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * n8n'e İlan Fiyat Değişikliği Bildirimi Job
 *
 * Context7: Otonom Fiyat Değişim Takibi ve n8n Entegrasyonu
 * Multi-Channel (Telegram, WhatsApp, Email) destekli fiyat değişikliği bildirimi
 */
class NotifyN8nAboutIlanPriceChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * İlan ID
     */
    public int $ilanId;

    /**
     * Eski fiyat
     */
    public ?float $oldPrice;

    /**
     * Yeni fiyat
     */
    public ?float $newPrice;

    /**
     * Para birimi
     */
    public string $currency;

    /**
     * Bildirim kanalları (Telegram, WhatsApp, Email)
     */
    public array $notificationChannels;

    /**
     * Create a new job instance.
     *
     * @param int $ilanId
     * @param float|null $oldPrice
     * @param float|null $newPrice
     * @param string $currency
     * @param array $notificationChannels
     */
    public function __construct(
        int $ilanId,
        ?float $oldPrice,
        ?float $newPrice,
        string $currency = 'TRY',
        array $notificationChannels = ['telegram', 'whatsapp', 'email']
    ) {
        $this->ilanId = $ilanId;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
        $this->currency = $currency;
        $this->notificationChannels = $notificationChannels;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('services.n8n.ilan_price_changed_webhook_url');

        if (empty($webhookUrl)) {
            LogService::warning('n8n ilan price changed webhook URL not configured', [
                'ilan_id' => $this->ilanId,
            ]);

            return;
        }

        try {
            // İlan bilgilerini çek
            $ilan = Ilan::with(['il', 'ilce', 'mahalle', 'ilanSahibi'])->find($this->ilanId);

            if (!$ilan) {
                LogService::warning('Ilan not found for price change notification', [
                    'ilan_id' => $this->ilanId,
                ]);

                return;
            }

            // Fiyat değişim yüzdesini hesapla
            $priceChangePercent = null;
            if ($this->oldPrice && $this->oldPrice > 0 && $this->newPrice) {
                $priceChangePercent = round((($this->newPrice - $this->oldPrice) / $this->oldPrice) * 100, 2);
            }

            // n8n'e gönderilecek payload
            $payload = [
                'event' => 'IlanPriceChanged',
                'ilan_id' => $ilan->id,
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'para_birimi' => $ilan->para_birimi ?? 'TRY',
                    'il_adi' => $ilan->il->il_adi ?? null,
                    'ilce_adi' => $ilan->ilce->ilce_adi ?? null,
                    'mahalle_adi' => $ilan->mahalle->mahalle_adi ?? null,
                    'status' => $ilan->status,
                    'url' => url('/admin/ilanlar/' . $ilan->id),
                ],
                'price_change' => [
                    'old_price' => $this->oldPrice,
                    'new_price' => $this->newPrice,
                    'currency' => $this->currency,
                    'change_percent' => $priceChangePercent,
                    'is_increase' => $this->newPrice && $this->oldPrice && $this->newPrice > $this->oldPrice,
                    'is_decrease' => $this->newPrice && $this->oldPrice && $this->newPrice < $this->oldPrice,
                ],
                'notification_channels' => $this->notificationChannels, // Multi-channel support
                'timestamp' => now()->toISOString(),
                'metadata' => [
                    'source' => 'laravel',
                    'version' => '1.0.0',
                ],
            ];

            LogService::info('Sending n8n webhook notification for ilan price change', [
                'ilan_id' => $ilan->id,
                'webhook_url' => $webhookUrl,
                'old_price' => $this->oldPrice,
                'new_price' => $this->newPrice,
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
                LogService::info('n8n webhook notification sent successfully for ilan price change', [
                    'ilan_id' => $ilan->id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            } else {
                LogService::error('n8n webhook notification failed for ilan price change', [
                    'ilan_id' => $ilan->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Job'u tekrar denemek için throw et
                throw new \Exception('n8n webhook failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            LogService::error('n8n webhook notification exception for ilan price change', [
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $e, LogService::CHANNEL_AI);

            // Job'u tekrar denemek için throw et
            throw $e;
        }
    }
}
