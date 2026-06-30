<?php

namespace App\Services\Notification\Adapters;

use App\Contracts\Notification\NotificationContract;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * N1-B: WhatsApp Channel Adapter (Meta Business API)
 * @sab-ignore-catch
 */
class WhatsAppAdapter
{
    /**
     * Send the WhatsApp message and update audit record.
     */
    public function send(NotificationContract $notification, int $auditId): bool
    {
        $audit = OutboundNotification::find($auditId);

        try {
            $data = $notification->getData();
            $recipient = $this->normalizePhoneNumber($notification->getRecipient());
            
            // Meta API Credentials from config (SAB S1 hardening ensured these are safe)
            $accessToken = config('services.whatsapp.access_token');
            $phoneNumberId = config('services.whatsapp.phone_number_id');
            $apiVersion = config('services.whatsapp.api_version', 'v18.0');

            if (!$accessToken || !$phoneNumberId) {
                throw new \Exception("WhatsApp API credentials missing");
            }

            $endpoint = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            // N3: Support Meta Official Templates
            $providerTemplateId = $data['provider_template_id'] ?? null;
            
            if ($providerTemplateId) {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'template', // context7-ignore: Meta API payload field
                    'template' => [
                        'name' => $providerTemplateId,
                        'language' => [
                            'code' => $data['language'] ?? 'tr'
                        ]
                    ]
                ];
                
                // Note: Complex variable mapping for Meta templates (components) 
                // is reserved for a future iteration if needed.
            } else {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text', // context7-ignore: Meta API payload field
                    'text' => [
                        'preview_url' => false,
                        'body' => $data['body'] ?? $data['message'] ?? '',
                    ],
                ];
            }

            if ($audit) {
                $audit->update([
                    'son_deneme_tarihi' => now(),
                    'deneme_sayisi' => ($audit->deneme_sayisi ?? 0) + 1
                ]);
            }

            // HTTP Request with timeout and audit tracking
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($endpoint, $payload);

            if ($audit) {
                $audit->update([
                    'provider_response' => $response->json()
                ]);
            }

            if ($response->successful()) {
                if ($audit) {
                    $audit->update([
                        'payload_data' => array_merge($audit->payload_data ?? [], [
                            'whatsapp_message_id' => $response->json('messages.0.id')
                        ]),
                        'provider_response' => $response->json()
                    ]);
                }
                return true;
            }

            throw new \Exception("WhatsApp API error: " . $response->body());

        } catch (\Throwable $e) {
            if ($audit) {
                $audit->update([
                    'provider_response' => isset($response) ? $response->json() : null
                ]);
            }

            Log::error("[WhatsAppAdapter] Delivery failed: " . $e->getMessage(), [
                'recipient' => $notification->getRecipient(),
                'audit_id' => $auditId
            ]);

            return false;
        }
    }

    /**
     * Normalize phone number for WhatsApp (+90...)
     */
    private function normalizePhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '90' . substr($cleaned, 1);
        }

        return (str_starts_with($cleaned, '+')) ? $cleaned : '+' . $cleaned;
    }
}
