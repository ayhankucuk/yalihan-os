<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * TaskProcessor
 *
 * Context7 Standard: C7-TELEGRAM-TASKS-2025-12-01
 *
 * Telegram üzerinden görev yönetimi işlemlerini yönetir.
 */
class TaskProcessor
{
    /**
     * Günlük özet gönder
     *
     * @param User $user
     * @return void
     */
    public function dailySummary(User $user): void
    {
        try {
            // Typing indicator
            $this->sendChatAction($user->telegram_id, 'typing');
            $today = Carbon::today();

            // Bugünün randevuları (KisiNot veya Gorev)
            $todayTasks = Gorev::where('atanan_user_id', $user->id)
                ->whereDate('bitis_tarihi', $today)
                ->whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])
                ->orderBy('oncelik', 'desc') // context7-ignore
                ->get();

            // Acil işler (deadline yakın)
            $urgentTasks = Gorev::where('atanan_user_id', $user->id)
                ->whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])
                ->where('bitis_tarihi', '<=', $today->copy()->addDays(3))
                ->where('bitis_tarihi', '>=', $today)
                ->orderBy('bitis_tarihi') // context7-ignore
                ->get();

            $message = "📊 *Günlük Özet - " . $today->format('d.m.Y') . "*\n\n";

            // Bugünün işleri
            if ($todayTasks->isNotEmpty()) {
                $message .= "📅 *Bugünün İşleri:*\n";
                foreach ($todayTasks->take(5) as $task) {
                    $priority = $this->getPriorityEmoji($task->oncelik);
                    $message .= "{$priority} {$task->baslik}\n";
                }
                $message .= "\n";
            } else {
                $message .= "✅ Bugün için planlanmış iş yok.\n\n";
            }

            // Acil işler
            if ($urgentTasks->isNotEmpty()) {
                $message .= "⚠️ *Acil İşler (3 gün içinde):*\n";
                foreach ($urgentTasks->take(5) as $task) {
                    $daysLeft = $task->bitis_tarihi ? $today->diffInDays($task->bitis_tarihi, false) : 0;
                    $message .= "🔴 {$task->baslik} ({$daysLeft} gün kaldı)\n";
                }
            } else {
                $message .= "✅ Acil iş bulunmuyor.\n";
            }

            $this->sendMessage($user->telegram_id, $message);
        } catch (\Exception $e) {
            Log::error('TaskProcessor: Günlük özet hatası', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($user->telegram_id, "❌ Özet oluşturulurken hata oluştu.");
        }
    }

    /**
     * Bekleyen görevleri listele
     *
     * @param User $user
     * @return void
     */
    public function pendingTasks(User $user): void
    {
        try {
            // Typing indicator
            $this->sendChatAction($user->telegram_id, 'typing');
            $tasks = Gorev::where('atanan_user_id', $user->id)
                ->whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])
                ->orderBy('oncelik', 'desc') // context7-ignore
                ->orderBy('bitis_tarihi') // context7-ignore
                ->get();

            if ($tasks->isEmpty()) {
                $this->sendMessage($user->telegram_id, "✅ Bekleyen görev bulunmuyor.");
                return;
            }

            $message = "📋 *Bekleyen Görevleriniz:*\n\n";

            foreach ($tasks->take(10) as $task) {
                $priority = $this->getPriorityEmoji($task->oncelik);
                $durum = $this->getDurumEmoji($task->gorev_durumu); // ✅ SAB: 'status' logic
                $deadline = $task->bitis_tarihi ? $task->bitis_tarihi->format('d.m.Y') : 'Belirtilmemiş';

                $message .= "{$priority} {$durum} *{$task->baslik}*\n";
                $message .= "   📅 {$deadline}\n";
                if ($task->aciklama) {
                    $message .= "   📝 " . substr($task->aciklama, 0, 50) . "...\n";
                }
                $message .= "\n";
            }

            if ($tasks->count() > 10) {
                $message .= "\n💡 Toplam {$tasks->count()} görev var. İlk 10'u gösteriliyor.";
            }

            $this->sendMessage($user->telegram_id, $message);
        } catch (\Exception $e) {
            Log::error('TaskProcessor: Görev listesi hatası', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->sendMessage($user->telegram_id, "❌ Görevler yüklenirken hata oluştu.");
        }
    }

    /**
     * Öncelik emoji'si
     */
    private function getPriorityEmoji(?string $priority): string
    {
        return match ($priority) {
            'yuksek' => '🔴',
            'orta' => '🟡',
            'dusuk' => '🟢',
            default => '⚪'
        };
    }

    /**
     * Durum emoji'si
     */
    private function getDurumEmoji(string $durum): string
    {
        return match ($durum) {
            'beklemede' => '⏳',
            'devam_ediyor' => '🔄',
            'tamamlandi' => '✅',
            default => '❓'
        };
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
            Log::error('TaskProcessor: Chat action gönderme hatası', [
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
            Log::error('TaskProcessor: Mesaj gönderme hatası', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
