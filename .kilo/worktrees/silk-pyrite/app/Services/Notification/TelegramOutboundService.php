<?php

namespace App\Services\Notification;

use App\Models\TelegramNotification;
use App\Models\User;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;

class TelegramOutboundService
{
    /**
     * Tekil kullanıcıya mesaj gönderir.
     */
    public function sendMessageToUser(User $user, string $text, string $type = 'direct_message'): TelegramNotification
    {
        $notification = TelegramNotification::create([
            'ulke_id'       => $user->ulke_id ?? 1, // Default TR or User country
            'user_id'       => $user->id,
            'mesaj_tipi'    => $type,
            'mesaj_icerigi' => $text,
            'gonderim_durumu' => 0, // Beklemede
        ]);

        // Dispatch to Unified Dispatcher for Audit + Delivery
        $dispatcher = app(NotificationDispatcher::class);
        $notif = GenericNotification::make(
            'telegram',
            (string) $user->telegram_chat_id,
            $type,
            ['body' => $text]
        );
        $dispatcher->dispatch($notif);

        return $notification;
    }

    /**
     * Lead bildirimini gönderir.
     */
    public function sendLeadNotification(Lead $lead, string $text): TelegramNotification
    {
        $notification = TelegramNotification::create([
            'ulke_id'       => $lead->ulke_id ?? 1,
            'lead_id'       => $lead->id,
            'mesaj_tipi'    => 'lead_notification',
            'mesaj_icerigi' => $text,
            'gonderim_durumu' => 0,
        ]);

        return $notification;
    }

    /**
     * Gönderim durumunu günceller (SAB: Audit log üretir).
     */
    public function markAsSent(TelegramNotification $notification): void
    {
        $notification->update([
            'gonderim_durumu' => 1,
            'gonderim_zamani' => now(),
        ]);

        Log::info("Telegram Notification Sent: ID {$notification->id}");
    }

    /**
     * Hata durumunu günceller.
     */
    public function markAsFailed(TelegramNotification $notification, string $error): void
    {
        $notification->update([
            'gonderim_durumu' => 2,
            'hata_mesaji'     => $error,
            'deneme_sayisi'   => $notification->deneme_sayisi + 1,
        ]);

        Log::error("Telegram Notification Failed: ID {$notification->id} - Error: {$error}");
    }
}
