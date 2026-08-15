<?php

namespace App\Services\Concierge;

/**
 * WhatsAppOutboundService — Send WhatsApp messages via Business API.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Responsibilities:
 * - Send text messages via WhatsApp Business API
 * - Audit outbound messages
 */
class WhatsAppOutboundService
{
    /**
     * Send a WhatsApp message to a recipient.
     */
    public function send(string $to, string $message): bool
    {
        try {
            $phoneNumberId = config('services.whatsapp.phone_number_id');
            $accessToken = config('services.whatsapp.access_token');
            $apiVersion = config('services.whatsapp.api_version', 'v18.0');

            if (!$accessToken || !$phoneNumberId) {
                \Illuminate\Support\Facades\Log::error('[WhatsAppOutboundService] WhatsApp credentials not configured');
                return false;
            }

            $endpoint = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->timeout(15)
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('[WhatsAppOutboundService] Send failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return false;
            }

            \Illuminate\Support\Facades\Log::info('[WhatsAppOutboundService] Message sent', [
                'to' => $to,
                'message_length' => strlen($message),
                'message_id' => $response->json('messages.0.id'),
            ]);

            return true;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[WhatsAppOutboundService] Exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
