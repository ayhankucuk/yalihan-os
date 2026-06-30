<?php

namespace App\Services\Notification;

use App\Models\Lead;
use App\Models\LeadMessage;
use App\Jobs\SendInstagramMessageJob;
use Illuminate\Support\Facades\Log;

/**
 * InstagramAutoReplyService
 *
 * Handles automatic replies to Instagram DM messages
 * Lead durumu ile entegre olur ve zenginleştirilmiş yanıtlar sunar
 */
class InstagramAutoReplyService
{
    /**
     * Send auto-reply to a lead on Instagram DM
     */
    public function sendAutoReply(Lead $lead, string $message, array $context = []): bool
    {
        try {
            // Validate lead has Instagram contact info
            if (!$lead->platform_phone || $lead->platform !== 'instagram') {
                Log::warning('Instagram auto-reply: Invalid lead platform', [
                    'lead_id' => $lead->id,
                    'platform' => $lead->platform,
                ]);
                return false;
            }

            // Build enriched response
            $response = $this->buildAutoReplyMessage($lead, $context);

            // Queue the message for sending
            SendInstagramMessageJob::dispatch(
                phoneNumberOrUsername: $lead->platform_phone,
                message: $response,
                leadId: $lead->id
            );

            // Log activity
            Log::info('Instagram auto-reply queued', [
                'lead_id' => $lead->id,
                'platform_user' => $lead->platform_phone,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Instagram auto-reply failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Instagram üzerinden durum değişikliği bildirimi gönder
     */
    public function notifyStatusChange(Lead $lead, string $newStatus): bool
    {
        $messages = [
            'contacted' => '👋 Merhaba! Talebin alındı ve bir danışmanımız yakında iletişime geçecek.',
            'qualified' => '✨ Harika haber! Senin için uygun mülkler bulduk. Detaylar için tıkla 👇',
            'lost' => '😔 Anlaşılan bu fırsat sana uygun olmadı. Başka seçenekler için yazabilirsin.',
            'won' => '🎉 Tebrikler! Satın alma işleminde başarılandı. İyi yaşamalar!',
        ];

        $message = $messages[$newStatus] ?? 'Talep durumun güncellendi.';

        return $this->sendAutoReply($lead, $message, [
            'type' => 'status_change', // context7-ignore
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(Lead $lead, \DateTime $appointmentTime, string $location): bool
    {
        $message = sprintf(
            "📅 *Görüşme Hatırlatması*\n" .
            "🕐 Zaman: %s\n" .
            "📍 Konum: %s\n\n" .
            "Unutma, %d dakika sonra buluşuyoruz! 👇",
            $appointmentTime->format('H:i d.m.Y'),
            $location,
            30
        );

        return $this->sendAutoReply($lead, $message, [
            'type' => 'appointment_reminder', // context7-ignore
            'appointment_time' => $appointmentTime,
        ]);
    }

    /**
     * Send property inquiry response
     */
    public function sendPropertyInquiryResponse(Lead $lead, string $propertyTitle, string $propertyUrl): bool
    {
        $message = sprintf(
            "🏠 *%s*\n\n" .
            "Detaylar için aşağıdaki linke tıkla 👇\n" .
            "%s\n\n" .
            "Daha fazla soru için yazabilirsin! 💬",
            $propertyTitle,
            $propertyUrl
        );

        return $this->sendAutoReply($lead, $message, [
            'type' => 'property_inquiry', // context7-ignore
            'property_url' => $propertyUrl,
        ]);
    }

    /**
     * Send quick action menu
     */
    public function sendQuickActionMenu(Lead $lead): bool
    {
        $message = "👇 Aşağıdakilerden birini seç:\n"
            . "1️⃣ Mülk ara\n"
            . "2️⃣ Fiyat bilgisi\n"
            . "3️⃣ Görüşme planla\n"
            . "4️⃣ Daha fazla bilgi";

        return $this->sendAutoReply($lead, $message, [
            'type' => 'quick_menu', // context7-ignore
        ]);
    }

    /**
     * Build auto-reply message based on context
     */
    private function buildAutoReplyMessage(Lead $lead, array $context = []): string
    {
        $type = $context['type'] ?? 'default'; // context7-ignore

        $templates = [
            'default' => "👋 Merhaba {name}!\n\nTalebin başarıyla kaydedildi. "
                . "Senin için uygun mülkleri bulmaya başladık. Yakında iletişime geçeceğiz! 🏠",

            'welcome' => "🎉 Yalıhan Emlak'a hoş geldin {name}!\n\n"
                . "Emlak arayışında yardımcı olmak için buradayız. Sana uygun mülkleri bulalım! 💼",

            'follow_up' => "📞 Hâlâ senin için uygun mülkler arıyoruz {name}.\n\n"
                . "Pazarı yakından takip ediyoruz. Yeni bir şey bulunca haberimiz olacak! 🔔",
        ];

        $message = $templates[$type] ?? $templates['default'];

        return str_replace('{name}', $lead->first_name ?? 'arkadaş', $message);
    }

    /**
     * Validate Instagram configuration
     */
    public function validateConfiguration(): array
    {
        $cfg = config('services.instagram');
        $required = [
            'business_account_id' => 'INSTAGRAM_BUSINESS_ACCOUNT_ID',
            'access_token' => 'INSTAGRAM_ACCESS_TOKEN',
            'api_version' => 'INSTAGRAM_API_VERSION',
        ];

        $missing = [];
        foreach ($required as $configKey => $envKey) {
            if (empty($cfg[$configKey])) {
                $missing[] = $envKey;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }
}
