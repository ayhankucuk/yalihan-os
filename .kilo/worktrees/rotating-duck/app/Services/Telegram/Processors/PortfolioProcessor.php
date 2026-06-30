<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PortfolioProcessor
 *
 * Context7 Standard: C7-TELEGRAM-PORTFOLIO-2025-12-01
 *
 * Konum bazlı ilan arama işlemlerini yönetir.
 */
class PortfolioProcessor
{
    /**
     * Kullanıcının konumuna yakın ilanları bul
     *
     * @param User $user
     * @param float $latitude
     * @param float $longitude
     * @return void
     */
    public function findNearMe(User $user, float $latitude, float $longitude): void
    {
        try {
            // Typing indicator (find_location zaten TelegramBrain'de gönderildi)
            $this->sendChatAction($user->telegram_id, 'typing');
            // 2km çapında arama (basit mesafe hesaplama)
            // Gerçek uygulamada Haversine formülü kullanılmalı
            $radius = 0.02; // ~2km (yaklaşık)

            $ilans = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->whereNotNull('enlem')
                ->whereNotNull('boylam')
                ->whereBetween('enlem', [$latitude - $radius, $latitude + $radius])
                ->whereBetween('boylam', [$longitude - $radius, $longitude + $radius])
                ->with('kategori') // Eager load kategori ilişkisi
                ->limit(10)
                ->get();

            if ($ilans->isEmpty()) {
                $this->sendMessage($user->telegram_id, "📍 Yakınınızda aktif ilan bulunamadı.");
                return;
            }

            $message = "🏠 *Yakınınızdaki İlanlar* (2km çapında):\n\n";

            foreach ($ilans as $ilan) {
                $fiyat = $ilan->satis_fiyati ? number_format($ilan->satis_fiyati, 0, ',', '.') . ' TL' : 'Fiyat belirtilmemiş';
                $kategori = $ilan->kategori ? ($ilan->kategori->name ?? 'Genel') : 'Genel';

                $message .= "🏡 *{$ilan->baslik}*\n";
                $message .= "   💰 {$fiyat}\n";
                $message .= "   📂 {$kategori}\n";
                $message .= "   🔗 [Detayları Gör](https://panel.yalihanemlak.com.tr/admin/ilanlar/{$ilan->id})\n\n";
            }

            $message .= "💡 Toplam {$ilans->count()} ilan bulundu.";

            $this->sendMessage($user->telegram_id, $message);
        } catch (\Exception $e) {
            Log::error('PortfolioProcessor: Konum arama hatası', [
                'user_id' => $user->id,
                'lat' => $latitude,
                'lon' => $longitude,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($user->telegram_id, "❌ İlanlar yüklenirken hata oluştu.");
        }
    }

    /**
     * Chat action gönder
     */
    private function sendChatAction(?string $chatId, string $action = 'typing'): void
    {
        if (!$chatId) {
            return;
        }

        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
            $telegramService->sendChatAction((int) $chatId, $action);
        } catch (\Exception $e) {
            Log::error('PortfolioProcessor: Chat action gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mesaj gönder
     */
    private function sendMessage(?string $chatId, string $text): void
    {
        if (!$chatId) {
            return;
        }

        try {
            $telegramService = app(\App\Modules\TakimYonetimi\Services\TelegramBotService::class);
            $telegramService->sendMessage((int) $chatId, $text);
        } catch (\Exception $e) {
            Log::error('PortfolioProcessor: Mesaj gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
