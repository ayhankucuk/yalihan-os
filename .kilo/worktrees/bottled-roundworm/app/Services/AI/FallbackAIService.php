<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * Fallback AI Service (Template Mode)
 *
 * Provides deterministic, safe responses when AI Hard Cap is reached.
 * Context7 Compliant: No forbidden words.
 */
class FallbackAIService
{
    /**
     * Generate a fallback response based on the request type/context.
     *
     * @param string $context Context of the request (e.g., 'listing_description', 'chat')
     * @return array Standardized response structure
     */
    public function generateResponse(string $context = 'generic'): array
    {
        Log::info("Generating Fallback AI Response for context: {$context}");

        $response = [
            'success' => true, // Technically a success in system terms, even if degraded
            'data' => $this->getTemplate($context),
            'meta' => [
                'fallback' => true,
                'neden' => 'HARD_CAP_LIMIT_ULASILDI',
                'timestamp' => now()->toIso8601String(),
            ]
        ];

        return $response;
    }

    private function getTemplate(string $context): array
    {
        return match ($context) {
            'listing_description' => [
                'content' => "Bu ilan için otomatik açıklama şu an oluşturulamıyor. Lütfen temel özellikleri kontrol ederek manuel bir açıklama giriniz.",
                'ozet' => "Sistem yoğunluğu nedeniyle otomatik özet oluşturulamadı.",
            ],
            'chat' => [
                'message' => "Şu anda size yardımcı olamıyorum. Lütfen daha sonra tekrar deneyiniz veya müşteri temsilcimizle iletişime geçiniz.",
                'action_suggested' => 'contact_agent',
            ],
            default => [
                'message' => "Servis geçici olarak tam kapasite çalışmıyor. Lütfen daha sonra tekrar deneyiniz.",
            ]
        };
    }
}
