<?php

namespace App\Services\Notification;

use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Log;

/**
 * N2: Notification Retry & State Management Service
 * Responsible for handling delivery attempts, failures, and scheduling retries.
 */
class NotificationRetryService
{
    /**
     * Mark a notification as processing (locked for delivery).
     */
    public function markAsProcessing(OutboundNotification $notification): void
    {
        $notification->update([
            'gonderim_durumu' => OutboundNotification::STATE_PROCESSING,
            'son_deneme_tarihi' => now(),
        ]);
    }

    /**
     * Mark a notification as successfully sent.
     */
    public function markAsSent(OutboundNotification $notification, ?array $response = null): void
    {
        $notification->update([
            'gonderim_durumu' => OutboundNotification::STATE_SENT,
            'gonderim_tarihi' => now(),
            'provider_response' => $response,
            'hata_mesaji' => null,
        ]);
    }

    /**
     * Mark a notification as permanently failed.
     */
    public function markAsFailed(OutboundNotification $notification, string $reason): void
    {
        $notification->update([
            'gonderim_durumu' => OutboundNotification::STATE_FAILED,
            'hata_mesaji' => $reason,
            'basarisiz_olma_tarihi' => now(),
        ]);

        Log::error("[NotificationRetryService] Notification {$notification->id} permanently failed: {$reason}");
    }

    /**
     * Schedule a retry for a transient failure.
     */
    public function scheduleRetry(OutboundNotification $notification, string $reason): void
    {
        $notification->increment('deneme_sayisi');
        
        $notification->update([
            'gonderim_durumu' => OutboundNotification::STATE_RETRY_SCHEDULED,
            'hata_mesaji' => "Transient Failure: " . $reason,
        ]);

        Log::warning("[NotificationRetryService] Notification {$notification->id} scheduled for retry. Attempt: {$notification->deneme_sayisi}");
    }

    /**
     * Check if a notification can be retried.
     */
    public function canRetry(OutboundNotification $notification): bool
    {
        // P0: Do not retry if already sent or cancelled
        if (in_array($notification->gonderim_durumu, [
            OutboundNotification::STATE_SENT,
            OutboundNotification::STATE_CANCELLED
        ])) {
            return false;
        }

        return true;
    }

    /**
     * Reset a notification for manual retry.
     */
    public function resetForManualRetry(OutboundNotification $notification): void
    {
        $notification->update([
            'gonderim_durumu' => OutboundNotification::STATE_PENDING,
            'hata_mesaji' => "Manual retry initiated.",
            'basarisiz_olma_tarihi' => null,
        ]);
    }
}
