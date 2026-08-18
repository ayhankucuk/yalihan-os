<?php

namespace App\Services\Notification\Adapters;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * N1-B: Email Channel Adapter
 * Bridges the normalized flow to Laravel's Mail system.
 * @sab-ignore-catch
 */
class EmailAdapter
{
    /**
     * Send the email and update audit record.
     */
    public function send(NotificationContract $notification, int $auditId): bool
    {
        $audit = OutboundNotification::find($auditId);

        try {
            $data = $notification->getData();
            $recipient = $notification->getRecipient();
            
            // Resolve Mailable class if provided in payload
            $mailableClass = $data['mailable_class'] ?? null;
            $mailableArgs = $data['mailable_args'] ?? [];

            // Update Audit for start of attempt
            if ($audit) {
                $audit->update([
                    'son_deneme_tarihi' => now(),
                    'deneme_sayisi' => ($audit->deneme_sayisi ?? 0) + 1
                ]);
            }

            if ($mailableClass && class_exists($mailableClass)) {
                // Instantiate the mailable with provided arguments
                $mailable = new $mailableClass(...$mailableArgs);
                Mail::to($recipient)->send($mailable);
            } else {
                // W3-INV-1: Use getRenderedBody() for credential notifications.
                // The rendered body contains the plaintext credential but is NEVER stored
                // in OutboundNotification.payload_data. It is used only at send time.
                $subject = $data['subject'] ?? 'Yalihan Emlak — Giriş Bilgileriniz';
                $body = $notification->getRenderedBody() ?: ($data['body'] ?? 'Bildirim içeriği bulunamadı.');

                Mail::raw($body, function ($message) use ($recipient, $subject) {
                    $message->to($recipient)->subject($subject);
                });
            }

            return true;

        } catch (\Throwable $e) {
            Log::error("[EmailAdapter] Delivery failed: " . $e->getMessage(), [
                'recipient' => $notification->getRecipient(),
                'audit_id' => $auditId
            ]);

            return false;
        }
    }
}
