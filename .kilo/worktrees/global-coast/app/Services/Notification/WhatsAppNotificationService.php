<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppNotificationService
 * Meta Business API üzerinden WhatsApp mesajlarını yönetir.
 * Context7 Uyumlu: Tüm operasyonel veriler mühürlü standartlara göredir.
 */
class WhatsAppNotificationService
{
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->apiVersion = config('services.whatsapp.api_version', 'v18.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}";
    }

    /**
     * Şablon tabanlı WhatsApp mesajı gönderir.
     */
    public function sendTemplateMessage(string $to, string $templateName, array $components, string $language = 'tr')
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::error("[WhatsAppService] API credentials missing in ENV.");
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template', // context7-ignore
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => $language],
                        'components' => $components
                    ]
                ]);

            if ($response->successful()) {
                Log::info("[WhatsAppService] Message sent successfully to {$to}. ID: " . $response->json('messages.0.id'));
                return $response->json();
            }

            Log::error("[WhatsAppService] API Error: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("[WhatsAppService] Connection Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Serbest metin mesajı gönderir (Müşteri ile 24 saatlik pencere açıksa).
     */
    public function sendTextMessage(string $to, string $text)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text', // context7-ignore
                    'text' => ['body' => $text]
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("[WhatsAppService] Text Message Exception: " . $e->getMessage());
            return false;
        }
    }
}
