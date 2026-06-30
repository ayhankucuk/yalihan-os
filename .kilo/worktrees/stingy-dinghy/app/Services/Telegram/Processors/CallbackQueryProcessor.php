<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use App\Services\Logging\LogService;
use App\Services\Telegram\AlertService;
use App\Enums\TalepDurumu;
use Illuminate\Support\Facades\Log;

/**
 * CallbackQueryProcessor
 *
 * Context7 Standard: C7-TELEGRAM-CALLBACK-PROCESSOR-2026-01-04
 *
 * Telegram inline button tıklamalarını işler:
 * - [Düzenle] → Admin paneline link
 * - [Yayınla] → Talep yayınla + matching trigger
 * - [TKGM Doldur] → TKGM verilerini otomatik doldur
 *
 * @package App\Services\Telegram\Processors
 * @version 1.0.0
 */
class CallbackQueryProcessor
{
    private AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Callback query'yi işle
     *
     * Telegram API'den gelen:
     * {
     *   "update_id": 123,
     *   "callback_query": {
     *     "id": "query_id",
     *     "from": { "id": 123, "username": "..." },
     *     "message": { "message_id": 456, "chat": { "id": 789 } },
     *     "data": "{\"action\": \"edit_draft\", \"talep_id\": 1}"
     *   }
     * }
     *
     * @param array $callbackQuery Telegram callback_query
     * @param User $user Danışman
     *
     * @return void
     */
    public function process(array $callbackQuery, User $user): void
    {
        $timerId = LogService::startTimer('telegram_callback');

        try {
            $data = json_decode($callbackQuery['data'] ?? '{}', true);
            $action = $data['action'] ?? null;
            $chatId = (int) ($callbackQuery['message']['chat']['id'] ?? 0);
            $messageId = (int) ($callbackQuery['message']['message_id'] ?? 0);
            $queryId = $callbackQuery['id'] ?? '';

            Log::info('CallbackQueryProcessor: Callback alındı', [
                'user_id' => $user->id,
                'action' => $action,
                'data' => $data,
            ]);

            match ($action) {
                'edit_draft' => $this->handleEditDraft($chatId, $messageId, $queryId, $data, $user),
                'publish' => $this->handlePublish($chatId, $messageId, $queryId, $data, $user),
                'tkgm_fill' => $this->handleTkgmFill($chatId, $messageId, $queryId, $data, $user),
                default => $this->sendAnswerCallback($queryId, "❌ Bilinmeyen işlem"),
            };

            LogService::stopTimer($timerId, ['action' => $action]);
        } catch (\Exception $e) {
            Log::error('CallbackQueryProcessor: Hata', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            LogService::stopTimer($timerId, ['error' => $e->getMessage()]);
        }
    }

    /**
     * [Düzenle] buttonu: Admin paneline yönlendir
     *
     * @param int $chatId Telegram chat ID
     * @param int $messageId Message ID
     * @param string $queryId Callback query ID
     * @param array $data {talep_id, type}
     * @param User $user Danışman
     */
    private function handleEditDraft(
        int $chatId,
        int $messageId,
        string $queryId,
        array $data,
        User $user
    ): void {
        try {
            $taleId = $data['talep_id'] ?? null;

            if (!$taleId) {
                $this->sendAnswerCallback($queryId, "❌ Talep ID bulunamadı");
                return;
            }

            // Admin panel URL'i oluştur
            $adminUrl = config('app.url') . "/admin/talepler/{$taleId}/edit";

            Log::info('CallbackQueryProcessor: Edit draft - Link gönderiliyor', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
                'admin_url' => $adminUrl,
            ]);

            $message = "✏️ *Düzenleme Sayfası*\n\n";
            $message .= "Admin paneline gitmek için linki tıklayın:\n";
            $message .= "[📋 Talep Detayları]({$adminUrl})\n\n";
            $message .= "Sayfada:\n";
            $message .= "• Talep detaylarını düzenleyebilir\n";
            $message .= "• İlgili müşteriler görebilir\n";
            $message .= "• TKGM verilerini doldurabilirsiniz";

            // Mesajı yanıt olarak gönder
            $this->sendAnswerCallback(
                $queryId,
                "✏️ Admin paneline yönlendiriliyorsunuz...",
                true
            );

            // Chat'e de yeni mesaj gönder
            try {
                $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
                $telegramService->sendMessage($chatId, $message, [
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Exception $e) {
                Log::warning('CallbackQueryProcessor: Edit draft mesaj gönderme hatası', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CallbackQueryProcessor: Edit draft hatası', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->sendAnswerCallback($queryId, "❌ Hata oluştu");
        }
    }

    /**
     * [Yayınla] buttonu: Talep yayınla + Alert
     *
     * @param int $chatId Telegram chat ID
     * @param int $messageId Message ID
     * @param string $queryId Callback query ID
     * @param array $data {talep_id}
     * @param User $user Danışman
     */
    private function handlePublish(
        int $chatId,
        int $messageId,
        string $queryId,
        array $data,
        User $user
    ): void {
        try {
            $taleId = $data['talep_id'] ?? null;

            if (!$taleId) {
                $this->sendAnswerCallback($queryId, "❌ Talep ID bulunamadı");
                return;
            }

            Log::info('CallbackQueryProcessor: Publish - Talep yayınlanıyor', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
            ]);

            // Talep model'ini al
            $talep = \App\Models\Talep::find($taleId);

            if (!$talep) {
                $this->sendAnswerCallback($queryId, "❌ Talep bulunamadı");
                return;
            }

            // Talep yayınla (talep_durumu: Beklemede → Aktif)
            $talep->update([
                'talep_durumu' => TalepDurumu::AKTIF->value, // Context7: talep_durumu (TalepDurumu enum)
            ]);

            // Matching trigger (ReverseMatchJob tetikle - queue'ye koy)
            try {
                dispatch(new \App\Jobs\ReverseMatchJob($taleId))->onQueue('high');
            } catch (\Exception $e) {
                Log::warning('CallbackQueryProcessor: ReverseMatchJob dispatch hatası', [
                    'talep_id' => $taleId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Alert gönder
            $this->alertService->sendPublishedAlert($chatId, [
                'id' => $talep->id,
                'baslik' => $talep->baslik,
            ]);

            // Callback response
            $this->sendAnswerCallback($queryId, "✅ Talep yayınlandı!", true);

            Log::info('CallbackQueryProcessor: Publish başarılı', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
            ]);
        } catch (\Exception $e) {
            Log::error('CallbackQueryProcessor: Publish hatası', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->sendAnswerCallback($queryId, "❌ Yayınlama başarısız");
        }
    }

    /**
     * [TKGM Doldur] buttonu: TKGM verilerini otomatik doldur
     *
     * @param int $chatId Telegram chat ID
     * @param int $messageId Message ID
     * @param string $queryId Callback query ID
     * @param array $data {talep_id}
     * @param User $user Danışman
     */
    private function handleTkgmFill(
        int $chatId,
        int $messageId,
        string $queryId,
        array $data,
        User $user
    ): void {
        try {
            $taleId = $data['talep_id'] ?? null;

            if (!$taleId) {
                $this->sendAnswerCallback($queryId, "❌ Talep ID bulunamadı");
                return;
            }

            Log::info('CallbackQueryProcessor: TKGM Fill - Başlanıyor', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
            ]);

            // TKGM auto-fill job dispatch et
            try {
                dispatch(new \App\Jobs\TKGMAutoFillJob($taleId, $user->id))->onQueue('high');
            } catch (\Exception $e) {
                Log::warning('CallbackQueryProcessor: TKGMAutoFillJob dispatch hatası', [
                    'talep_id' => $taleId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Callback response
            $this->sendAnswerCallback(
                $queryId,
                "📋 TKGM verileri işlenmeye başlandı...",
                true
            );

            // Chat'e bilgilendirme mesajı
            $message = "📋 *TKGM Veriler İşleniyor*\n\n";
            $message .= "Ada/parsel bilgilerini TKGM tabanından arıyoruz...\n";
            $message .= "Sonuç hazır olunca size haber vereceğiz.\n\n";
            $message .= "⏱️ Bu işlem 30 saniyeyi alabilir.";

            try {
                $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
                $telegramService->sendMessage($chatId, $message, [
                    'parse_mode' => 'Markdown',
                ]);
            } catch (\Exception $e) {
                Log::warning('CallbackQueryProcessor: TKGM mesaj gönderme hatası', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('CallbackQueryProcessor: TKGM Fill job dispatch başarılı', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
            ]);
        } catch (\Exception $e) {
            Log::error('CallbackQueryProcessor: TKGM Fill hatası', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->sendAnswerCallback($queryId, "❌ TKGM veriler yüklenemedi");
        }
    }

    /**
     * Callback query'ye cevap gönder
     * (Telegram'da popup/notification gösterir)
     *
     * @param string $callbackQueryId Callback query ID
     * @param string $text Mesaj (0-200 karakter)
     * @param bool $showAlert true ise alert box, false ise toast notification
     */
    private function sendAnswerCallback(
        string $callbackQueryId,
        string $text,
        bool $showAlert = false
    ): void {
        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);

            // Eğer TelegramBotService'in answerCallbackQuery methodu varsa çağır
            if (method_exists($telegramService, 'answerCallbackQuery')) {
                $telegramService->answerCallbackQuery($callbackQueryId, $text, $showAlert);
            } else {
                // Fallback: Log
                Log::info('CallbackQueryProcessor: Callback answer (fallback)', [
                    'callback_id' => $callbackQueryId,
                    'message' => $text,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('CallbackQueryProcessor: Callback answer hatası', [
                'callback_id' => $callbackQueryId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
