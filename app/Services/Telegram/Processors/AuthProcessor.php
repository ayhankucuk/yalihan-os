<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AuthProcessor
 *
 * Context7 Standard: C7-TELEGRAM-AUTH-2025-12-01
 *
 * Telegram kullanıcı eşleştirme işlemlerini yönetir.
 * 6 haneli pairing code ile kullanıcıları Telegram'a bağlar.
 */
class AuthProcessor
{
    /**
     * Eşleştirme işlemini yönet
     *
     * @param string $chatId Telegram Chat ID
     * @param array $message Telegram mesaj verisi
     * @return void
     */
    public function handle(string $chatId, array $message): void
    {
        $text = $message['text'] ?? '';

        // 6 haneli kod kontrolü
        if (preg_match('/^\d{6}$/', $text)) {
            $this->pairUser($chatId, $text);
        } else {
            $this->sendPairingInstructions($chatId);
        }
    }

    /**
     * Kullanıcıyı Telegram'a eşleştir
     */
    private function pairUser(string $chatId, string $code): void
    {
        try {
            $user = User::where('telegram_pairing_code', $code)->first();

            if (!$user) {
                $this->sendMessage($chatId, "❌ Geçersiz kod. Lütfen panelden aldığınız 6 haneli kodu girin.");
                return;
            }

            // Eşleştirme
            $user->telegram_id = $chatId;
            $user->telegram_pairing_code = null; // Kod tek kullanımlık
            $user->telegram_paired_at = now();
            $user->save();

            Log::info('TelegramAuth: Kullanıcı eşleştirildi', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
            ]);

            $this->sendMessage($chatId, "✅ *Eşleşme Başarılı!*\n\nHoş geldiniz, {$user->name}!\n\n/yardim yazarak komutları görebilirsiniz.");
        } catch (\Exception $e) {
            Log::error('TelegramAuth: Eşleştirme hatası', [
                'chat_id' => $chatId,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($chatId, "❌ Eşleştirme sırasında hata oluştu. Lütfen tekrar deneyin.");
        }
    }

    /**
     * Eşleştirme talimatları gönder
     */
    private function sendPairingInstructions(string $chatId): void
    {
        $message = "⛔ *Telegram Eşleştirme*\n\n";
        $message .= "Bu botu kullanmak için önce hesabınızı eşleştirmeniz gerekiyor.\n\n";
        $message .= "📋 *Adımlar:*\n";
        $message .= "1. Admin paneline giriş yapın\n";
        $message .= "2. Profil sayfanızdan 6 haneli eşleştirme kodunuzu alın\n";
        $message .= "3. Bu kodu buraya yazın\n\n";
        $message .= "💡 *Örnek:* `123456`\n\n";
        $message .= "Kodunuzu aldıktan sonra buraya yazın.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Mesaj gönder
     */
    private function sendMessage(string $chatId, string $text): void
    {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
            $telegramService->sendMessage((int) $chatId, $text);
        } catch (\Exception $e) {
            Log::error('AuthProcessor: Mesaj gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
