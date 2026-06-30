<?php

namespace App\Services\Notification\Adapters;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * N1-B: Webhook Channel Adapter
 * @sab-ignore-catch
 */
class WebhookAdapter
{
    /**
     * Send the Webhook and update audit record.
     */
    public function send(NotificationContract $notification, int $auditId): bool
    {
        $audit = OutboundNotification::find($auditId);

        try {
            $data = $notification->getData();
            $url = $notification->getRecipient(); // For webhooks, recipient is the URL

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception("Invalid Webhook URL: {$url}");
            }

            if ($audit) {
                $audit->update([
                    'son_deneme_tarihi' => now(),
                    'deneme_sayisi' => ($audit->deneme_sayisi ?? 0) + 1
                ]);
            }

            // HTTP POST with timeout
            $response = Http::timeout(30)
                ->post($url, $data);

            if ($audit) {
                $audit->update([
                    'provider_response' => $response->json() ?? ['raw_body' => $response->body()],
                ]);
            }

            if ($response->successful()) {
                if ($audit) {
                    $audit->update([
                        'provider_response' => $response->json()
                    ]);
                }
                return true;
            }

            throw new \Exception("Webhook failed with HTTP code: " . $response->status()); // context7-ignore: framework API method

        } catch (\Throwable $e) {
            if ($audit) {
                $audit->update([
                    'provider_response' => isset($response) ? $response->json() : null
                ]);
            }

            Log::error("[WebhookAdapter] Delivery failed: " . $e->getMessage(), [
                'url' => $notification->getRecipient(),
                'audit_id' => $auditId
            ]);

            return false;
        }
    }
}
