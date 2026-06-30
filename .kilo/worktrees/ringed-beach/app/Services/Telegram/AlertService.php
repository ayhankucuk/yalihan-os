<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AlertService
 *
 * Context7 Standard: C7-TELEGRAM-ALERT-SERVICE-2026-01-04
 *
 * Voice-to-CRM işlemleri sonrası uyarılar gönderir:
 * - Telegram: Danışman bildirimi
 * - SMS: Opsiyonel müşteri bildirimi
 * - Email: Opsiyonel log
 *
 * @package App\Services\Telegram
 * @version 1.0.0
 */
class AlertService
{
    /**
     * Voice draft oluşturma uyarısı gönder
     *
     * Danışmana Telegram üzerinden:
     * ✅ Draft oluşturuldu
     * 🎯 {N} benzer müşteri bulundu
     * 📝 Detayları gözden geçir
     *
     * @param User $danisman Danışman
     * @param array $talep Oluşturulan talep
     * @param int $matchCount Benzer müşteri sayısı
     * @param int $chatId Telegram chat ID
     *
     * @return bool Success
     */
    public function sendVoiceDraftAlert(
        User $danisman,
        array $talep,
        int $matchCount,
        int $chatId
    ): bool {
        try {
            $message = "✅ *Voice Draft Oluşturuldu*\n\n";
            $message .= "📋 *Talep:* " . ($talep['baslik'] ?? 'Belirtilmedi') . "\n";

            if ($matchCount > 0) {
                $message .= "🎯 *Eşleşme:* {$matchCount} müşteri bulundu\n";
            } else {
                $message .= "🔍 *Eşleşme:* Şu an müşteri yok (panel'de kontrol edin)\n";
            }

            $message .= "\n📝 Detayları gözden geçirmek için düzenleme düğmesini kullanın.";

            return $this->sendTelegramMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('AlertService: Voice draft alert hatası', [
                'danisman_id' => $danisman->id,
                'talep_id' => $talep['id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Talep yayınlama uyarısı gönder
     *
     * @param int $chatId Telegram chat ID
     * @param array $talep Yayınlanan talep
     *
     * @return bool Success
     */
    public function sendPublishedAlert(int $chatId, array $talep): bool
    {
        try {
            $message = "🎉 *Talep Yayınlandı!*\n\n";
            $message .= "📋 *Başlık:* " . ($talep['baslik'] ?? 'Belirtilmedi') . "\n";
            $message .= "📊 *ID:* `{$talep['id']}`\n";
            $message .= "\n✅ Talep sistem tarafından işlenmeye başlandı.";

            return $this->sendTelegramMessage($chatId, $message);
        } catch (\Exception $e) {
            Log::error('AlertService: Published alert hatası', [
                'talep_id' => $talep['id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Telegram mesajı gönder
     *
     * @param int $chatId Chat ID
     * @param string $message Mesaj içeriği
     * @param array $replyMarkup Inline keyboard (opsiyonel)
     *
     * @return bool Success
     */
    public function sendTelegramMessage(
        int $chatId,
        string $message,
        array $replyMarkup = []
    ): bool {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);

            $options = ['parse_mode' => 'Markdown'];

            if (!empty($replyMarkup)) {
                $options['reply_markup'] = json_encode($replyMarkup);
            }

            $telegramService->sendMessage($chatId, $message, $options);

            Log::info('AlertService: Telegram mesajı gönderildi', [
                'chat_id' => $chatId,
                'message_length' => strlen($message),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AlertService: Telegram mesajı gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * SMS uyarısı gönder (müşteriye)
     *
     * "Merhaba {kisi_adi}, seçtiğiniz ilanla ilgili yeni bir talep bulunmaktadır."
     *
     * @param string $telefon Müşteri telefon numarası
     * @param array $talep İlişkili talep
     * @param array $kisi İlişkili kişi
     *
     * @return bool Success
     */
    public function sendSMSAlert(
        string $telefon,
        array $talep,
        array $kisi
    ): bool {
        try {
            // SMS servisini al
            $smsService = app(\App\Services\SmsService::class);

            $message = sprintf(
                "Merhaba %s, seçtiğiniz ilanlarla ilgili yeni bir talep bulunmaktadır. Panel üzerinden kontrol edin.",
                $kisi['ad_soyad'] ?? 'Müşteri'
            );

            $smsService->send($telefon, $message);

            Log::info('AlertService: SMS mesajı gönderildi', [
                'telefon' => substr($telefon, -4), // Son 4 hanesi
                'kisi_id' => $kisi['id'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('AlertService: SMS gönderme hatası (devam ediliyor)', [
                'telefon' => substr($telefon, -4),
                'error' => $e->getMessage(),
            ]);

            // SMS başarısız olsa da devam et (non-critical)
            return false;
        }
    }

    /**
     * Email uyarısı gönder (log amaçlı)
     *
     * @param User $danisman Danışman
     * @param array $talep Oluşturulan talep
     *
     * @return bool Success
     */
    public function sendEmailAlert(User $danisman, array $talep): bool
    {
        try {
            // E-mail impl. yapılacak (opsiyonel)
            Log::info('AlertService: Email uyarısı harita kaydı', [
                'danisman_id' => $danisman->id,
                'talep_id' => $talep['id'] ?? null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AlertService: Email uyarısı hatası', [
                'danisman_id' => $danisman->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array $recipients [['type' => 'telegram', 'id' => 12345], ...] // context7-ignore
     * @return int Başarılı gönderim sayısı
     */
    public function sendBatchAlert(
        array $recipients,
        string $message,
        array $replyMarkup = []
    ): int {
        $successCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $type = $recipient['type'] ?? 'telegram'; // context7-ignore

                switch ($type) {
                    case 'telegram':
                        if ($this->sendTelegramMessage(
                            (int) $recipient['id'],
                            $message,
                            $replyMarkup
                        )) {
                            $successCount++;
                        }
                        break;

                    case 'sms':
                        if ($this->sendSMSAlert(
                            $recipient['id'],
                            [],
                            []
                        )) {
                            $successCount++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                Log::warning('AlertService: Batch uyarı hatası', [
                    'recipient' => $recipient['id'] ?? 'unknown',
                    'type' => $recipient['type'] ?? 'unknown', // context7-ignore
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AlertService: Batch uyarılar gönderildi', [
            'total' => count($recipients),
            'success' => $successCount,
        ]);

        return $successCount;
    }
}
