<?php

namespace App\Services\Notification;

use App\Models\Lead;
use App\Models\Ilan;
use App\Services\Cortex\CortexROIEngine;
use App\Services\Location\PoiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;

/**
 * WhatsApp Notification Manager (Phase 4)
 *
 * Sends WhatsApp notifications when leads engage
 * Enriches messages with ROI Engine + POI data
 *
 * Ultra-Think Protocol:
 * 1. Anayasal Denetim: Field names (platform_phone, platform_user_id) ✅
 * 2. Mimari Uyum: CortexROIEngine + PoiService integration
 * 3. Hata Simülasyonu: Redis retry queue on Meta API failure
 * 4. Hafıza Senkronizasyonu: .env.example.master validation
 */
class WhatsAppNotificationManager
{
    protected CortexROIEngine $roiEngine;
    protected PoiService $poiService;

    public function __construct(CortexROIEngine $roiEngine, PoiService $poiService)
    {
        $this->roiEngine = $roiEngine;
        $this->poiService = $poiService;
    }

    /**
     * Send notification when lead receives matching property
     *
     * Ultra-Think: Mimari Uyum - ROI + POI data enrichment
     */
    public function notifyLeadOfMatch(Lead $lead, Ilan $ilan): bool
    {
        try {
            // 1. Extract lead contact info (Context7: platform_phone, platform_user_id)
            if ($lead->platform !== 'whatsapp' || !$lead->platform_phone) {
                Log::warning('Lead not WhatsApp or missing phone', ['lead_id' => $lead->id]);
                return false;
            }

            // 2. Build enriched message with ROI + POI data
            $message = $this->buildEnrichedMessage($lead, $ilan);

            // 3. Send via Dispatcher (Async enforced)
            $dispatcher = app(NotificationDispatcher::class);
            $notification = GenericNotification::make(
                'whatsapp',
                $lead->platform_phone,
                'lead_match_notification',
                ['body' => $message]
            );
            $dispatcher->dispatch($notification);

            // 4. Log activity
            $lead->activities()->create([
                'activity_type' => 'message_sent',
                'description' => "Property match notification sent: {$ilan->baslik}",
                'activity_date' => now(),
            ]);

            Log::info('WhatsApp notification queued', [
                'lead_id' => $lead->id,
                'phone' => $this->maskPhone($lead->platform_phone),
                'ilan_id' => $ilan->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to queue WhatsApp notification', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Build message enriched with ROI + POI analysis
     *
     * Ultra-Think: Mimari Uyum - Combine Cortex engines
     */
    protected function buildEnrichedMessage(Lead $lead, Ilan $ilan): string
    {
        // 1. Base property info
        $messageLines = [
            "🏠 *{$ilan->baslik}*",
            "",
            "📍 Konum: {$ilan->il}, {$ilan->ilce}",
            "💰 Fiyat: ₺" . number_format($ilan->fiyat, 0, ',', '.'),
        ];

        // 2. ROI Analysis (if arsa)
        if (strtolower($ilan->kategori->adi ?? '') === 'arsa') {
            try {
                $roi = $this->roiEngine->calculateROI($ilan); // Context7: calculateROI (was calculateArsaROI)

                if (isset($roi['roi_score']) && $roi['roi_score'] > 0) {
                     $messageLines[] = "";
                     $messageLines[] = "📈 *Yatırım Analizi*";
                     $messageLines[] = "ROI Skoru: " . $roi['roi_score'];
                     $messageLines[] = "Amortisman: " . $roi['payback_period_years'] . " yıl";
                }

            } catch (\Exception $e) {
                Log::warning('ROI calculation failed', ['ilan_id' => $ilan->id]);
            }
        }

        // 3. POI Analysis (neighborhood insights)
        try {
            // Context7: Use getHighlights with 1km radius
            $highlights = $this->poiService->getHighlights(
                $ilan->lat ?? 0,
                $ilan->lng ?? 0,
                1 // 1km radius
            );

            if (!empty($highlights)) {
                $messageLines[] = "";
                $messageLines[] = "✨ *Çevre Özellikleri*";
                $slice = array_slice($highlights, 0, 2);
                foreach ($slice as $highlight) {
                    $messageLines[] = "• {$highlight}";
                }
            }

        } catch (\Exception $e) {
            Log::warning('POI analysis failed', ['ilan_id' => $ilan->id]);
        }

        // 4. Call-to-action
        $messageLines[] = "";
        $messageLines[] = "📞 İlgilenmek için: [İletişim]";

        return implode("\n", $messageLines);
    }

    /**
     * Lead durum değişikliğinde bildirim gönder
     *
     * Lead yeni -> ulaşıldı -> kalifiye aşamasına geçtiğinde tetiklenir
     */
    public function notifyLeadStatusChange(Lead $lead, string $newStatus): bool
    {
        if ($lead->platform !== 'whatsapp') {
            return false;
        }

        $statusMessages = [
            'contacted' => "✅ Talebin kaydedildi! Danışmanlarımız sana kısa sürede ulaşacak.",
            'qualified' => "🎉 Senin için uygun bir mülk bulduk! Detaylar için tıkla.",
            'won' => "🏆 Satın alma işleminde başarılandığınızı duymak bizi mutlu etti!",
        ];

        $message = $statusMessages[$newStatus] ?? "Talep durumunuz güncellendi: {$newStatus}";

        $dispatcher = app(NotificationDispatcher::class);
        $notification = GenericNotification::make(
            'whatsapp',
            $lead->platform_phone,
            'lead_status_change',
            ['body' => $message]
        );
        $dispatcher->dispatch($notification);

        Log::info('Status change notification sent', [ // context7-ignore
            'lead_id' => $lead->id,
            'lead_status' => $newStatus,
        ]);

        return true;
    }

    /**
     * Send bulk notification to leads matching criteria
     *
     * Example: New luxury properties for high-budget leads
     */
    public function notifyMatchingLeads(callable $leadFilter, string $message): int
    {
        $leads = Lead::where('platform', 'whatsapp')
            ->whereNotNull('platform_phone')
            ->filter($leadFilter)
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            try {
                $dispatcher = app(NotificationDispatcher::class);
                $notification = GenericNotification::make(
                    'whatsapp',
                    $lead->platform_phone,
                    'bulk_notification',
                    ['body' => $message]
                );
                $dispatcher->dispatch($notification);

                $count++;

            } catch (\Exception $e) {
                Log::error('Bulk notification failed', [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bulk WhatsApp notifications queued', ['count' => $count]);
        return $count;
    }

    /**
     * Mask phone number for logging
     *
     * Context7: Security - don't log full phone numbers
     */
    protected function maskPhone(string $phone): string
    {
        return substr($phone, 0, 3) . '***' . substr($phone, -2);
    }

    /**
     * @deprecated N1-B: Validation is now handled in WhatsAppAdapter using ConfigurationRegistry.
     */
    public static function validateConfiguration(): array
    {
        return [];
    }
}
