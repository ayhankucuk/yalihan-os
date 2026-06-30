<?php

namespace App\Listeners;

use App\Events\IlanYayinlandiEvent;
use App\Services\Notification\WhatsAppNotificationService;
use App\Services\Cortex\CortexROIEngine;
use App\Services\Location\PoiService;
use App\Services\Logging\LogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * SendWhatsAppNotificationListener
 * İlan yayınlandığında Meta API üzerinden zengin içerikli bildirim gönderir.
 * Context7: Asenkron işlem ve asansör/durum kelime yasaklarına tam uyum.
 */
class SendWhatsAppNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    // İşlem başarısız olursa 3 kez tekrar dene
    public $tries = 3;

    // Denemeler arası bekleme süresi (saniye)
    public $backoff = 60;

    public function __construct(
        protected WhatsAppNotificationService $whatsappService,
        protected CortexROIEngine $roiEngine,
        protected PoiService $poiService
    ) {}

    /**
     * Event tetiklendiğinde çalışır.
     */
    public function handle(IlanYayinlandiEvent $event): void
    {
        $ilan = $event->ilan;

        try {
            // 🧠 1. Cortex ROI Analizi yap
            $roiData = $this->roiEngine->calculateROI($ilan);
            $roiScore = $roiData['roi_score'] ?? 0;

            // 📍 2. POI (Noktasal İlgi Alanları) Analizi yap
            $poiHighlights = $this->poiService->getHighlights($ilan->latitude, $ilan->longitude);
            $poiText = !empty($poiHighlights) ? implode(', ', array_slice($poiHighlights, 0, 3)) : "Merkezi konumda";

            // 📞 3. Alıcı numarasını bul (Yalıhan kuralı: Danışman veya Müşteri)
            // Şimdilik test amaçlı ENV'den veya ilan sahibi/danışmandan alınabilir
            $to = $ilan->danisman?->telefon ?? config('services.whatsapp.test_number');

            if (!$to) {
                LogService::warning("[WhatsAppListener] No recipient found for Ilan ID: {$ilan->id}");
                return;
            }

            // 📝 4. Mesaj Şablonu Parametrelerini Hazırla (Meta Model)
            // Not: Şablon isimleri Meta Panel'de tanımlanmış olmalıdır.
            $components = [
                [
                    'type' => 'header',
                    'parameters' => [
                        ['type' => 'text', 'text' => $ilan->baslik]
                    ]
                ],
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $ilan->ilce->adi ?? 'Bodrum'],
                        ['type' => 'text', 'text' => number_format($ilan->fiyat, 0, ',', '.') . ' ' . $ilan->para_birimi],
                        ['type' => 'text', 'text' => "ROI Skoru: %{$roiScore}"],
                        ['type' => 'text', 'text' => $poiText]
                    ]
                ]
            ];

            // 🚀 5. Gönderimi Yap
            $result = $this->whatsappService->sendTemplateMessage(
                $to,
                'ilan_yayinlandi_v2', // Meta'daki şablon adı
                $components
            );

            if (!$result) {
                throw new \Exception("WhatsApp API response failed.");
            }

            LogService::info("[WhatsAppListener] Notification successfully queued for Ilan ID: {$ilan->id}");

        } catch (\Exception $e) {
            LogService::error("[WhatsAppListener] Error processing notification: " . $e->getMessage(), [
                'ilan_id' => $ilan->id
            ], $e);

            // Hata durumunda queue'ya tekrar bırak
            $this->release(300);
        }
    }
}
