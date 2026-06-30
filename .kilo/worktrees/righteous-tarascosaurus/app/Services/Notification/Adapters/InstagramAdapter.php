<?php

namespace App\Services\Notification\Adapters;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * N2: Instagram Channel Adapter (Graph API)
 * @sab-ignore-catch
 */
class InstagramAdapter
{
    /**
     * Send the Instagram message and update audit record.
     */
    public function send(NotificationContract $notification, int $auditId): bool
    {
        $audit = OutboundNotification::find($auditId);

        try {
            $data = $notification->getData();
            $recipientId = $notification->getRecipient();
            
            $accessToken = config('services.instagram.access_token');
            $apiVersion = config('services.instagram.api_version', 'v18.0');

            if (!$accessToken) {
                throw new \Exception("Instagram API access token missing");
            }

            $endpoint = "https://graph.facebook.com/{$apiVersion}/me/messages";

            $payload = [
                'recipient' => [
                    'id' => $recipientId,
                ],
                'message' => [
                    'text' => $data['body'] ?? $data['message'] ?? '',
                ],
                'messaging_type' => 'RESPONSE',
                'access_token' => $accessToken,
            ];

            if ($audit) {
                $audit->update([
                    'son_deneme_tarihi' => now(),
                    'deneme_sayisi' => ($audit->deneme_sayisi ?? 0) + 1
                ]);
            }

            $response = Http::post($endpoint, $payload);

            if ($audit) {
                $audit->update([
                    'provider_response' => $response->json()
            if ($response->successful()) {
                if ($audit) {
                    $audit->update([
                        'provider_response' => $response->json()
                    ]);
                }
                return true;
            }

            throw new \Exception("Instagram API error: " . $response->body());

        } catch (\Throwable $e) {
            if ($audit) {
                $audit->update([
                    'provider_response' => isset($response) ? $response->json() : null
                ]);
            }

            Log::error("[InstagramAdapter] Delivery failed: " . $e->getMessage(), [
                'recipient' => $notification->getRecipient(),
                'audit_id' => $auditId
            ]);

            return false;
        }
    }
}
