<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\Telegram\Processors\AuthProcessor;
use App\Services\Telegram\Processors\ContactProcessor;
use App\Services\Telegram\Processors\FinanceProcessor;
use App\Services\Telegram\Processors\PortfolioProcessor;
use App\Services\Telegram\Processors\TaskProcessor;
use App\Services\VoiceCommandProcessor;
use Illuminate\Support\Facades\Log;

/**
 * TelegramBrain
 *
 * Context7 Standard: C7-TELEGRAM-CORTEX-2025-12-01
 *
 * Telegram mesajlarını karşılayan ve dağıtan ana servis.
 * Cortex Architecture'ın merkezi yönlendirici servisi.
 */
class TelegramBrain
{
    private AuthProcessor $authProcessor;
    private TaskProcessor $taskProcessor;
    private PortfolioProcessor $portfolioProcessor;
    private ContactProcessor $contactProcessor;
    private VoiceCommandProcessor $voiceCommandProcessor;
    private FinanceProcessor $financeProcessor;

    public function __construct(
        AuthProcessor $authProcessor,
        TaskProcessor $taskProcessor,
        PortfolioProcessor $portfolioProcessor,
        ContactProcessor $contactProcessor,
        VoiceCommandProcessor $voiceCommandProcessor,
        FinanceProcessor $financeProcessor
    ) {
        $this->authProcessor = $authProcessor;
        $this->taskProcessor = $taskProcessor;
        $this->portfolioProcessor = $portfolioProcessor;
        $this->contactProcessor = $contactProcessor;
        $this->voiceCommandProcessor = $voiceCommandProcessor;
        $this->financeProcessor = $financeProcessor;
    }

    /**
     * Gelen webhook update'ini işle
     *
     * İşleyebileceği update tipleri:
     * - message: Mesaj, voice, location, contact
     * - callback_query: Inline button tıklaması
     *
     * @param array $update Telegram webhook update data
     * @return void
     */
    public function handle(array $update): void
    {
        try {
            // ✅ Callback query (inline button tıklaması) işle
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

            // ✅ Message işle
            if (!isset($update['message'])) {
                return;
            }

            $message = $update['message'];
            $chatId = (string) ($message['chat']['id'] ?? '');
            $text = $message['text'] ?? '';
            $from = $message['from'] ?? [];

            Log::info('TelegramBrain: Mesaj alındı', [
                'chat_id' => $chatId,
                'has_text' => !empty($text),
                'has_voice' => isset($message['voice']),
                'has_location' => isset($message['location']),
                'has_contact' => isset($message['contact']),
            ]);

            // 1. Kimlik Kontrolü
            $user = User::where('telegram_id', $chatId)->first();

            if (!$user) {
                // Kullanıcı yoksa -> Eşleştirme Modülü
                $this->authProcessor->handle($chatId, $message);
                return;
            }

            // 2. Kullanıcı varsa -> Mesaj tipine göre işle

            // Contact (Kişi Kartı) mesaj
            if (isset($message['contact'])) {
                $this->sendChatAction($chatId, 'typing');
                $this->contactProcessor->handle($user, $message['contact']);
                return;
            }

            // Voice mesaj
            if (isset($message['voice'])) {
                $this->sendChatAction($chatId, 'upload_voice');
                // Voice-to-CRM işlemi (mevcut sistem)
                $this->handleVoiceMessage($chatId, $message['voice'], $from, $user);
                return;
            }

            // Location mesaj
            if (isset($message['location'])) {
                $this->sendChatAction($chatId, 'find_location');
                $lat = $message['location']['latitude'] ?? null;
                $lon = $message['location']['longitude'] ?? null;
                if ($lat && $lon) {
                    $this->portfolioProcessor->findNearMe($user, $lat, $lon);
                }
                return;
            }

            // Komut işleme
            if (str_starts_with($text, '/')) {
                $this->handleCommand($chatId, $text, $user);
                return;
            }

            // Normal mesaj
            $this->handleNormalMessage($chatId, $text, $user);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Hata', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Inline button tıklamasını işle (callback_query)
     *
     * @param array $callbackQuery Telegram callback_query
     * @return void
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        try {
            $chatId = (string) ($callbackQuery['message']['chat']['id'] ?? '');
            $userId = (int) ($callbackQuery['from']['id'] ?? 0);

            Log::info('TelegramBrain: Callback query alındı', [
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);

            // User bul
            $user = User::where('telegram_id', $chatId)->first();

            if (!$user) {
                Log::warning('TelegramBrain: Callback query user bulunamadı', [
                    'chat_id' => $chatId,
                ]);
                return;
            }

            // Callback processor'ı çağır
            $processor = app(\App\Services\Telegram\Processors\CallbackQueryProcessor::class);
            $processor->process($callbackQuery, $user);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Callback query hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Komut işle
     */
    private function handleCommand(string $chatId, string $text, User $user): void
    {
        $this->sendChatAction($chatId, 'typing');

        $command = strtolower(trim($text));

        switch ($command) {
            case '/ozet':
                $this->taskProcessor->dailySummary($user);
                break;

            case '/gorevler':
                $this->taskProcessor->pendingTasks($user);
                break;

            case '/yardim':
            case '/help':
                $this->sendHelpMessage($chatId);
                break;

            default:
                $this->sendMessage($chatId, "❌ Bilinmeyen komut: {$text}\n\n/yardim yazarak mevcut komutları görebilirsiniz.");
                break;
        }
    }

    /**
     * Normal mesaj işle
     * 
     * 🔥 HİBRİT AVCI: Finansal Pattern Recognition
     */
    private function handleNormalMessage(string $chatId, string $text, User $user): void
    {
        $this->sendChatAction($chatId, 'typing');
        
        // 1️⃣ FİNANSAL ZEKA: Önce finansal işlem kontrolü
        $financeResponse = $this->financeProcessor->handle($user, $text);
        
        if ($financeResponse) {
            // Finansal işlem algılandı ve kaydedildi
            $this->sendMessage($chatId, $financeResponse);
            return;
        }
        
        // 2️⃣ GELECEK: Voice-to-Listing, Lead-to-Meeting buraya eklenecek
        
        // 3️⃣ DEFAULT: Bilinmeyen mesaj
        $this->sendMessage($chatId, "💡 *Yalıhan Cortex Bot*\n\n" .
                                    "📝 Finansal işlem kaydetmek için:\n" .
                                    "\"500 TL kahve masrafı\"\n" .
                                    "\"10,000 TL kira geliri\"\n\n" .
                                    "📌 Komutlar için: /yardim");
    }

    /**
     * Voice mesaj işle (Voice-to-Draft implementasyonu)
     *
     * İş Akışı:
     * 1. Typing indicator gönder
     * 2. VoiceProcessor ile ses işle
     * 3. Interactive message döndür
     * 4. Alert gönder
     *
     * Timeline: 30s ses → 5s processing → Draft + Matches
     */
    private function handleVoiceMessage(string $chatId, array $voice, array $from, User $user): void
    {
        try {
            $botToken = config('services.telegram.bot_token');

            Log::info('TelegramBrain: Voice mesaj işleniyor', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'file_id' => $voice['file_id'] ?? 'unknown',
            ]);

            // Processor'ı al ve voice işle
            $processor = app(\App\Services\Telegram\Processors\VoiceProcessor::class);
            $result = $processor->processVoiceMessage($chatId, $voice, $user, $botToken);

            if (!$result['success']) {
                $this->sendMessage($chatId, $result['message']['text'] ?? "❌ Hata oluştu");
                return;
            }

            // Interactive message gönder
            $message = $result['message']['text'] ?? '';
            $replyMarkup = $result['message']['reply_markup'] ?? [];

            $this->sendInteractiveMessage($chatId, $message, $replyMarkup);

            // Alert gönder (opsiyonel)
            $alertService = app(\App\Services\Telegram\AlertService::class);
            $talep = $result['talep'] ?? [];
            $matches = $result['matches'] ?? [];

            if (!empty($talep)) {
                $alertService->sendVoiceDraftAlert(
                    $user,
                    $talep,
                    count($matches),
                    (int) $chatId
                );
            }

            Log::info('TelegramBrain: Voice mesaj başarılı', [
                'user_id' => $user->id,
                'talep_id' => $talep['id'] ?? null,
                'matches' => count($matches),
            ]);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Voice mesaj hatası', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->sendMessage(
                $chatId,
                "❌ *Hata*\n\nSes işleme sırasında bir hata oluştu.\n\nHata: {$e->getMessage()}"
            );
        }
    }

    /**
     * Interactive message gönder (inline keyboard ile)
     */
    private function sendInteractiveMessage(
        string $chatId,
        string $text,
        array $replyMarkup = []
    ): void {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);

            $options = [
                'parse_mode' => 'Markdown',
            ];

            if (!empty($replyMarkup)) {
                $options['reply_markup'] = json_encode($replyMarkup);
            }

            $telegramService->sendMessage((int) $chatId, $text, $options);

            Log::info('TelegramBrain: Interactive message gönderildi', [
                'chat_id' => $chatId,
                'has_buttons' => !empty($replyMarkup),
            ]);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Interactive message gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            // Fallback: Basit metin gönder
            $this->sendMessage($chatId, $text);
        }
    }

    /**
     * Yardım mesajı gönder
     */
    private function sendHelpMessage(string $chatId): void
    {
        $message = "📚 *Yalıhan Cortex Bot - Yardım Menüsü*\n\n";
        $message .= "🔹 *Komutlar:*\n";
        $message .= "• `/ozet` - Günlük özet (randevular, acil işler)\n";
        $message .= "• `/gorevler` - Bekleyen görevleriniz\n";
        $message .= "• `/yardim` - Bu yardım menüsü\n\n";
        $message .= "💰 *Finansal İşlemler:*\n";
        $message .= "Doğal dille finansal kayıt oluşturabilirsiniz:\n";
        $message .= "• \"500 TL kahve masrafı\"\n";
        $message .= "• \"10,000 TL kira geliri\"\n";
        $message .= "• \"1500 dolar komisyon\"\n\n";
        $message .= "🎤 *Sesli Not:*\n";
        $message .= "Sesli mesaj göndererek CRM notu oluşturabilirsiniz.\n\n";
        $message .= "📍 *Konum:*\n";
        $message .= "Konum paylaşarak yakınınızdaki ilanları görebilirsiniz.\n\n";
        $message .= "👤 *Kişi Kartı:*\n";
        $message .= "Kişi kartı paylaşarak CRM'e otomatik ekleyebilirsiniz.\n\n";
        $message .= "💡 *Daha fazla bilgi için:*\n";
        $message .= "Panel: https://panel.yalihanemlak.com.tr";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Chat action gönder (typing indicator)
     */
    private function sendChatAction(string $chatId, string $action = 'typing'): void
    {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
            $telegramService->sendChatAction((int) $chatId, $action);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Chat action gönderme hatası', [
                'chat_id' => $chatId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mesaj gönder (TelegramService üzerinden)
     */
    private function sendMessage(string $chatId, string $text): void
    {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
            $telegramService->sendMessage((int) $chatId, $text);
        } catch (\Exception $e) {
            Log::error('TelegramBrain: Mesaj gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
