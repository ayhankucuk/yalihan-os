<?php

namespace App\Services\Notification\Adapters;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * N1-B: Telegram Channel Adapter
 * @sab-ignore-catch
 */
class TelegramAdapter
{
    /**
     * Send the Telegram message and update audit record.
     */
    public function send(NotificationContract $notification, int $auditId): bool
    {
        $audit = OutboundNotification::find($auditId);

        try {
            $data = $notification->getData();
            $recipient = $notification->getRecipient();
            
            // Resolve TelegramService (existing bot logic)
            $telegramService = app(TelegramService::class);
            
            // N1-B Bridge: Use existing sendMessage method
            // Standardizing text and reply_markup
            $message = $data['body'] ?? $data['message'] ?? '';
            $replyMarkup = $data['reply_markup'] ?? null;

            if ($audit) {
                $audit->update([
                    'son_deneme_tarihi' => now(),
                    'deneme_sayisi' => ($audit->deneme_sayisi ?? 0) + 1
                ]);
            }

            $response = $telegramService->sendMessage(
                $recipient,
                $message,
                $replyMarkup
            );

            if ($response->successful()) {
                if ($audit) {
                    $audit->update([
                        'provider_response' => $response->json()
                    ]);
                }
                return true;
            }

            throw new \Exception("Telegram API error: " . $response->body());

        } catch (\Throwable $e) {
            if ($audit) {
                $audit->update([
                    'provider_response' => isset($response) ? $response->json() : null
                ]);
            }

            Log::error("[TelegramAdapter] Delivery failed: " . $e->getMessage(), [
                'recipient' => $notification->getRecipient(),
                'audit_id' => $auditId
            ]);

            return false;
        }
    }
}
