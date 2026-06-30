<?php

namespace App\Services\Notification;

use App\Models\Lead;
use App\Models\LeadMessage;
use App\Jobs\SendFacebookMessageJob;
use Illuminate\Support\Facades\Log;

/**
 * FacebookAutoReplyService
 * 
 * Handles automatic replies to Facebook Messenger messages
 * Supports quick reply buttons and structured messages
 */
class FacebookAutoReplyService
{
    /**
     * Send auto-reply with quick reply buttons
     */
    public function sendAutoReplyWithButtons(Lead $lead, string $message, array $quickReplies = []): bool
    {
        try {
            // Validate lead has Facebook contact info
            if (!$lead->platform_phone || $lead->platform !== 'facebook') {
                Log::warning('Facebook auto-reply: Invalid lead platform', [
                    'lead_id' => $lead->id,
                    'platform' => $lead->platform,
                ]);
                return false;
            }

            // Queue the message with quick replies
            SendFacebookMessageJob::dispatch(
                recipientId: $lead->platform_phone,
                message: $message,
                quickReplies: $quickReplies,
                leadId: $lead->id
            );

            Log::info('Facebook auto-reply queued', [
                'lead_id' => $lead->id,
                'platform_user' => $lead->platform_phone,
                'quick_replies_count' => count($quickReplies),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Facebook auto-reply failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send simple text reply without buttons
     */
    public function sendSimpleReply(Lead $lead, string $message): bool
    {
        return $this->sendAutoReplyWithButtons($lead, $message);
    }

    /**
     * Send welcome message with action buttons
     */
    public function sendWelcomeMessage(Lead $lead): bool
    {
        $message = "👋 Merhaba " . ($lead->first_name ?? 'arkadaş') . "!\n\n"
            . "Yalıhan Emlak'a hoş geldin. 🏠\n"
            . "Sana uygun mülkleri bulmaktan mutluluk duyarız. Başlayalım! 💼";

        $quickReplies = [
            ['title' => '🔍 Mülk Ara', 'payload' => 'search_property'],
            ['title' => '💰 Fiyat Soruştur', 'payload' => 'ask_price'],
            ['title' => '📞 Danışman Çağır', 'payload' => 'call_agent'],
        ];

        return $this->sendAutoReplyWithButtons($lead, $message, $quickReplies);
    }

    /**
     * Send status change notification with relevant actions
     */
    public function notifyStatusChange(Lead $lead, string $newStatus): bool
    {
        $statusMessages = [
            'contacted' => [
                'text' => "✅ Talebin başarıyla kaydedildi!\n\n"
                    . "Bir danışmanımız senin için uygun mülkleri aramaya başladı. "
                    . "Yakında sizinle iletişime geçilecektir.",
                'buttons' => [
                    ['title' => '⏰ Sonra Çağrıl', 'payload' => 'call_later'],
                    ['title' => '❓ Soru Sor', 'payload' => 'ask_question'],
                ],
            ],
            'qualified' => [
                'text' => "🎯 Harika haber {name}!\n\n"
                    . "Senin için 3 uygun mülk bulduk. Detayları görmek ister misin?",
                'buttons' => [
                    ['title' => '🏠 Mülkleri Gör', 'payload' => 'view_properties'],
                    ['title' => '📅 Görüşme Planla', 'payload' => 'schedule_visit'],
                ],
            ],
            'lost' => [
                'text' => "😔 Anlaşılan bu seçenekler sana uygun olmadı.\n\n"
                    . "Başka seçenekler hakkında konuşmak ister misin?",
                'buttons' => [
                    ['title' => '🔄 Yeni Arama', 'payload' => 'new_search'],
                    ['title' => '❌ Kapat', 'payload' => 'close'],
                ],
            ],
            'won' => [
                'text' => "🎉 Tebrikler {name}!\n\n"
                    . "Satın alma işleminde başarılandı. İyi yaşamalar! 🏡",
                'buttons' => [
                    ['title' => '📝 Evrak Gönder', 'payload' => 'send_docs'],
                    ['title' => '💬 İletişime Geç', 'payload' => 'contact_again'],
                ],
            ],
        ];

        $config = $statusMessages[$newStatus] ?? $statusMessages['contacted'];
        $message = str_replace('{name}', $lead->first_name ?? 'arkadaş', $config['text']);

        return $this->sendAutoReplyWithButtons($lead, $message, $config['buttons']);
    }

    /**
     * Send property catalog with navigation buttons
     */
    public function sendPropertyCatalog(Lead $lead, array $properties): bool
    {
        $propertyList = '';
        foreach (array_slice($properties, 0, 5) as $prop) {
            $propertyList .= "• {$prop['title']} - {$prop['price']}\n";
        }

        $message = "📚 *Senin İçin Seçilmiş Mülkler*\n\n"
            . $propertyList
            . "\nDetayları görmek için aşağıdaki butonları kullan 👇";

        $quickReplies = [
            ['title' => '➡️ Daha Fazla', 'payload' => 'show_more'],
            ['title' => '⭐ Favoriler', 'payload' => 'show_favorites'],
            ['title' => '📞 Danışman', 'payload' => 'contact_agent'],
        ];

        return $this->sendAutoReplyWithButtons($lead, $message, $quickReplies);
    }

    /**
     * Send appointment confirmation with location and time
     */
    public function sendAppointmentConfirmation(Lead $lead, \DateTime $appointmentTime, string $location, string $agentName): bool
    {
        $message = sprintf(
            "📅 *Görüşme Onaylandı*\n\n" .
            "🕐 Saat: %s\n" .
            "📍 Konum: %s\n" .
            "👤 Danışman: %s\n\n" .
            "Unutma, %d dakika kala hazırlan! 🎯",
            $appointmentTime->format('H:i d.m.Y'),
            $location,
            $agentName,
            30
        );

        $quickReplies = [
            ['title' => '✅ Onaylıyorum', 'payload' => 'confirm_appointment'],
            ['title' => '📞 Değişiklik', 'payload' => 'reschedule'],
            ['title' => '❌ İptal', 'payload' => 'cancel_appointment'],
        ];

        return $this->sendAutoReplyWithButtons($lead, $message, $quickReplies);
    }

    /**
     * Send satisfaction survey after interaction
     */
    public function sendSatisfactionSurvey(Lead $lead): bool
    {
        $message = "⭐ Hizmetimizden memnun musun?\n\n"
            . "Geri bildirim vermek bize çok yardımcı olur! 💬";

        $quickReplies = [
            ['title' => '😍 Çok İyi', 'payload' => 'rating_5'],
            ['title' => '😊 İyi', 'payload' => 'rating_4'],
            ['title' => '😐 Orta', 'payload' => 'rating_3'],
            ['title' => '😕 Kötü', 'payload' => 'rating_2'],
        ];

        return $this->sendAutoReplyWithButtons($lead, $message, $quickReplies);
    }

    /**
     * Validate Facebook configuration
     */
    public function validateConfiguration(): array
    {
        $cfg = config('services.facebook');
        $required = [
            'page_id' => 'FACEBOOK_PAGE_ID',
            'page_access_token' => 'FACEBOOK_PAGE_ACCESS_TOKEN',
            'api_version' => 'FACEBOOK_API_VERSION',
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
