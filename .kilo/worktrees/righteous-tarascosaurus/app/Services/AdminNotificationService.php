<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Models\Deprecated\AdminNotification;
use App\Models\IlanReservation;
use App\Models\Ilan;
use App\Models\User;
use App\Services\AdminActivityEventService;
use App\Services\Logging\LogService;
use App\Services\TelegramService;
use Carbon\Carbon;

/**
 * Admin Notification Service
 *
 * Context7 Compliance: Rezervasyon bildirimleri için admin notification sistemi
 * Phase S: Rezervasyon Bildirimleri + Otomasyon
 *
 * - UPS SSOT korunur
 * - Cortex observer mode korunur
 * - LogService kullanılır (content_type YOK)
 */
class AdminNotificationService
{
    protected LogService $logService;
    protected TelegramService $telegramService;
    protected AdminActivityEventService $activityService;

    public function __construct(
        LogService $logService,
        TelegramService $telegramService,
        AdminActivityEventService $activityService
    ) {
        $this->logService = $logService;
        $this->telegramService = $telegramService;
        $this->activityService = $activityService;
    }

    /**
     * Rezervasyon oluşturuldu bildirimi
     */
    public function notifyReservationCreated(IlanReservation $reservation, string $source = 'admin'): void
    {
        $ilan = $reservation->ilan;
        $danismanId = $ilan->danisman_id;

        if (!$danismanId) {
            return;
        }

        $title = 'Yeni Rezervasyon';
        $message = sprintf(
            "İlan #%d için yeni rezervasyon oluşturuldu.\n\n" .
                "Tarih: %s - %s\n" .
                "Müşteri: %s\n" .
                "Kaynak: %s",
            $ilan->id,
            $reservation->starts_at->format('d.m.Y H:i'),
            $reservation->ends_at->format('d.m.Y H:i'),
            $reservation->customer_name ?? 'Belirtilmemiş',
            $source === 'telegram' ? 'Telegram Bot' : 'Admin Panel'
        );

        $payload = [
            'ilan_id' => $ilan->id,
            'reservation_id' => $reservation->id,
            'starts_at' => $reservation->starts_at->toIso8601String(),
            'ends_at' => $reservation->ends_at->toIso8601String(),
            'customer_name' => $reservation->customer_name,
            'customer_phone' => $reservation->customer_phone,
            'source' => $source,
        ];

        $notification = $this->createNotification(
            $danismanId,
            'reservation',
            'reservation_created',
            $title,
            $message,
            $payload
        );

        // Telegram bildirimi gönder
        $this->sendTelegramNotification($danismanId, $title, $message, $payload);

        LogService::info('admin_notification_reservation_created', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'user_id' => $danismanId,
            'source' => $source,
        ]);
    }

    /**
     * Rezervasyon iptal edildi bildirimi
     */
    public function notifyReservationCancelled(IlanReservation $reservation, ?int $userId = null, ?string $reason = null, string $source = 'admin'): void
    {
        $ilan = $reservation->ilan;
        $danismanId = $ilan->danisman_id;

        if (!$danismanId) {
            return;
        }

        $title = 'Rezervasyon İptal Edildi';
        $message = sprintf(
            "İlan #%d için rezervasyon iptal edildi.\n\n" .
                "Tarih: %s - %s\n" .
                "Müşteri: %s\n" .
                "Kaynak: %s",
            $ilan->id,
            $reservation->starts_at->format('d.m.Y H:i'),
            $reservation->ends_at->format('d.m.Y H:i'),
            $reservation->customer_name ?? 'Belirtilmemiş',
            $source === 'telegram' ? 'Telegram Bot' : 'Admin Panel'
        );

        if ($reason) {
            $message .= "\n\nİptal Nedeni: {$reason}";
        }

        $payload = [
            'ilan_id' => $ilan->id,
            'reservation_id' => $reservation->id,
            'starts_at' => $reservation->starts_at->toIso8601String(),
            'ends_at' => $reservation->ends_at->toIso8601String(),
            'customer_name' => $reservation->customer_name,
            'cancelled_by_user_id' => $userId,
            'cancel_reason' => $reason,
            'source' => $source,
        ];

        $notification = $this->createNotification(
            $danismanId,
            'reservation',
            'reservation_cancelled',
            $title,
            $message,
            $payload
        );

        // Telegram bildirimi gönder
        $this->sendTelegramNotification($danismanId, $title, $message, $payload);

        LogService::info('admin_notification_reservation_cancelled', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'user_id' => $danismanId,
            'cancelled_by_user_id' => $userId,
            'source' => $source,
        ]);
    }

    /**
     * Rezervasyon onaylandı bildirimi
     *
     * Phase T: Rezervasyon onaylama bildirimi
     */
    public function notifyReservationConfirmed(IlanReservation $reservation, string $source = 'admin'): void
    {
        $ilan = $reservation->ilan;
        $danismanId = $ilan->danisman_id;

        if (!$danismanId) {
            return;
        }

        $title = 'Rezervasyon Onaylandı';
        $message = sprintf(
            "İlan #%d için rezervasyon onaylandı.\n\n" .
                "Tarih: %s - %s\n" .
                "Müşteri: %s\n" .
                "Kaynak: %s",
            $ilan->id,
            $reservation->starts_at->format('d.m.Y H:i'),
            $reservation->ends_at->format('d.m.Y H:i'),
            $reservation->customer_name ?? 'Belirtilmemiş',
            $source === 'telegram' ? 'Telegram Bot' : 'Admin Panel'
        );

        $payload = [
            'ilan_id' => $ilan->id,
            'reservation_id' => $reservation->id,
            'starts_at' => $reservation->starts_at->toIso8601String(),
            'ends_at' => $reservation->ends_at->toIso8601String(),
            'customer_name' => $reservation->customer_name,
            'source' => $source,
        ];

        $this->createNotification(
            $danismanId,
            'reservation',
            'reservation_confirmed',
            $title,
            $message,
            $payload
        );

        // Telegram bildirimi gönder
        $this->sendTelegramNotification($danismanId, $title, $message, $payload);

        LogService::info('admin_notification_reservation_confirmed', [
            'reservation_id' => $reservation->id,
            'ilan_id' => $ilan->id,
            'user_id' => $danismanId,
            'source' => $source,
        ]);
    }

    /**
     * Takvim kapatıldı bildirimi
     */
    public function notifyCalendarClosed(Ilan $ilan, Carbon $from, Carbon $to, ?int $userId = null, ?string $reason = null, string $source = 'admin'): void
    {
        $danismanId = $ilan->danisman_id;

        if (!$danismanId) {
            return;
        }

        $title = 'Takvim Kapatıldı';
        $message = sprintf(
            "İlan #%d için takvim kapatıldı.\n\n" .
                "Tarih Aralığı: %s - %s\n" .
                "Kaynak: %s",
            $ilan->id,
            $from->format('d.m.Y H:i'),
            $to->format('d.m.Y H:i'),
            $source === 'telegram' ? 'Telegram Bot' : 'Admin Panel'
        );

        if ($reason) {
            $message .= "\n\nNeden: {$reason}";
        }

        $payload = [
            'ilan_id' => $ilan->id,
            'starts_at' => $from->toIso8601String(),
            'ends_at' => $to->toIso8601String(),
            'closed_by_user_id' => $userId,
            'reason' => $reason,
            'source' => $source,
        ];

        $notification = $this->createNotification(
            $danismanId,
            'calendar',
            'calendar_closed',
            $title,
            $message,
            $payload
        );

        // Telegram bildirimi gönder
        $this->sendTelegramNotification($danismanId, $title, $message, $payload);

        LogService::info('admin_notification_calendar_closed', [
            'ilan_id' => $ilan->id,
            'user_id' => $danismanId,
            'closed_by_user_id' => $userId,
            'source' => $source,
        ]);
    }

    /**
     * Bildirim oluştur
     */
    protected function createNotification(
        int $userId,
        string $channel,
        string $event,
        string $title,
        string $message,
        array $payload = []
    ): AdminNotification {
        return AdminNotification::create([
            'user_id' => $userId,
            'channel' => $channel,
            'event' => $event,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
            'is_read' => false,
        ]);
    }

    /**
     * Kullanıcının okunmamış bildirim sayısı
     */
    public function getUnreadCount(int $userId): int
    {
        return AdminNotification::forUser($userId)
            ->unread()
            ->count();
    }

    /**
     * Kullanıcının bildirimlerini listele
     */
    public function listForUser(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AdminNotification::forUser($userId)
            ->orderBy('created_at', 'desc') // context7-ignore
            ->limit($limit)
            ->get();
    }

    /**
     * Telegram bildirimi gönder
     *
     * Phase T: Inline keyboard desteği eklendi
     */
    protected function sendTelegramNotification(int $userId, string $title, string $message, array $payload = []): void
    {
        try {
            $user = User::find($userId);

            if (!$user || !$user->telegram_chat_id) {
                return;
            }

            $telegramMessage = "🔔 *{$title}*\n\n{$message}";

            // İlan linki ekle
            if (isset($payload['ilan_id'])) {
                $ilanUrl = route('admin.ilanlar.show', $payload['ilan_id']);
                $telegramMessage .= "\n\n📎 [İlanı Görüntüle]({$ilanUrl})";
            }

            // Phase T: Inline keyboard ekle (rezervasyon bildirimleri için)
            $replyMarkup = null;
            if (isset($payload['reservation_id']) && isset($payload['ilan_id'])) {
                $reservation = IlanReservation::find($payload['reservation_id']);
                if ($reservation && $reservation->islem_durumu === 'active') { // context7-ignore
                    $nonce = substr(md5($reservation->id . time()), 0, 8);
                    $reservationId = $reservation->id;
                    $replyMarkup = [
                        'inline_keyboard' => [
                            [
                                ['text' => '✅ Onayla', 'callback_data' => "resv:confirm:{$reservationId}:{$nonce}"],
                                ['text' => '❌ İptal', 'callback_data' => "resv:cancel:{$reservationId}:{$nonce}"],
                            ],
                            [
                                ['text' => '📄 Detay', 'callback_data' => "resv:detail:{$reservationId}:{$nonce}"],
                            ],
                        ],
                    ];
                }
            }

            $this->telegramService->sendMessage(
                (string) $user->telegram_chat_id,
                $telegramMessage,
                $replyMarkup
            );

            LogService::info('admin_notification_telegram_sent', [
                'user_id' => $userId,
                'telegram_chat_id' => $user->telegram_chat_id,
                'title' => $title,
                'has_keyboard' => $replyMarkup !== null,
            ]);
        } catch (\Exception $e) {
            // Telegram hatası bildirimi engellemez
            LogService::error('admin_notification_telegram_failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
