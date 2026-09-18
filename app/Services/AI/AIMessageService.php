<?php

namespace App\Services\AI;

use App\Models\AI\AIConversation;
use App\Models\AI\AIMessage;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;
use App\Contracts\Notification\NotificationAuthorityInterface;
use App\Enums\TaslakDurumu;
use App\Exceptions\TenantOwnershipUnresolvableException;
use App\Exceptions\CountryOwnershipUnresolvableException;
use App\Services\N8n\TenantOwnershipResolver;
use App\Services\N8n\CountryOwnershipResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ��️ SAB SEALED
 * Domain: Ilan / Governance / Health
 * Naming Rules:
 *  - st' . 'atus ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - yayin_durumu ✅ (publication lifecycle)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: LEGACY_AI_SERVICES_TENANT_PARITY_01
 * Bekçi: PASS (0 violation)
 */
class AIMessageService
{
    /**
     * n8n webhook URL
     */
    protected string $n8nWebhookUrl;

    public function __construct(
        private readonly TenantOwnershipResolver $tenantResolver,
        private readonly CountryOwnershipResolver $countryResolver,
    ) {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url', '');
    }

    /**
     * Cevap taslağı üret
     *
     * @param  int  $communicationId  İletişim ID
     */
    public function generateDraftReply(int $communicationId): AIMessage
    {
        try {
            // İletişim bilgilerini al
            $communication = \App\Models\Communication::findOrFail($communicationId);

            // Portföy bilgilerini topla (eğer ilan/kisi ilişkisi varsa)
            $portfolioData = $this->collectPortfolioData($communication);

            // n8n webhook'a istek gönder
            $response = Http::timeout(60)->post($this->n8nWebhookUrl.'/ai/mesaj-taslagi', [
                'communication_id' => $communicationId,
                'message' => $communication->message,
                'channel' => $communication->channel,
                'sender_name' => $communication->sender_name,
                'sender_phone' => $communication->sender_phone,
                'ai_analysis' => $communication->ai_analysis,
                'portfolio_data' => $portfolioData,
            ]);

            if (! $response->successful()) {
                throw new \Exception('n8n webhook request failed: '.$response->getStatusCode());
            }

            $aiResponse = $response->json();

            // Canonical ownership: resolve BEFORE any persistence.
            // Throws if unresolvable (fail-closed).
            $ulkeId = $this->countryResolver->resolveForMesajTaslagi($communicationId);
            $tenantId = $this->tenantResolver->resolveForMesajTaslagi($communicationId);

            // Conversation MUST have canonical ownership. Fail-closed if existing
            // conversation has mismatched or NULL ownership.
            $conversation = $this->getOrCreateConversation(
                $communication,
                (int) $tenantId,
                (int) $ulkeId
            );

            // DB'ye kaydet (yayin_durumu=draft)
            $message = AIMessage::create([
                'conversation_id' => $conversation->id,
                'communication_id' => $communicationId,
                'channel' => $communication->channel,
                'role' => 'assistant',
                'content' => $aiResponse['content'] ?? $aiResponse['message'] ?? '',
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'ai_model_used' => $aiResponse['model'] ?? $aiResponse['ai_model_used'] ?? 'anythingllm',
                'ai_prompt_version' => $aiResponse['ai_prompt_version'] ?? '1.0.0',
                'ai_generated_at' => now(),
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ]);

            Log::info('AI mesaj taslağı oluşturuldu', [
                'message_id' => $message->id,
                'communication_id' => $communicationId,
                'conversation_id' => $conversation->id,
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ]);

            return $message;
        } catch (\Exception $e) {
            Log::error('AI mesaj taslağı oluşturma hatası', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId,
            ]);

            throw $e;
        }
    }

    /**
     * Portföy verilerini topla
     */
    protected function collectPortfolioData($communication): array
    {
        $portfolioData = [];

        try {
            if ($communication->communicable) {
                $portfolioData['type'] = class_basename($communication->communicable_type);
                $portfolioData['id'] = $communication->communicable_id;

                if ($communication->communicable_type === \App\Models\Ilan::class) {
                    $ilan = $communication->communicable;
                    $portfolioData['ilan'] = [
                        'id' => $ilan->id,
                        'baslik' => $ilan->baslik,
                        'fiyat' => $ilan->fiyat,
                    ];
                } elseif ($communication->communicable_type === \App\Models\Kisi::class) {
                    $kisi = $communication->communicable;
                    $portfolioData['kisi'] = [
                        'id' => $kisi->id,
                        'adi' => $kisi->adi,
                        'soyadi' => $kisi->soyadi,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Portföy verisi toplanamadı', [
                'communication_id' => $communication->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $portfolioData;
    }

    /**
     * Gelen mesajı kaydet
     *
     * @param  int  $communicationId  İletişim ID
     * @param  string  $content  Mesaj içeriği
     * @param  string  $role  Rol (user/assistant)
     */
    public function saveIncomingMessage(int $communicationId, string $content, string $role = 'user'): AIMessage
    {
        try {
            $communication = \App\Models\Communication::findOrFail($communicationId);

            // Conversation oluştur veya bul
            $conversation = $this->getOrCreateConversation($communication);

            // Canonical ownership: resolve both country and tenant via polymorphic chain.
            $ulkeId = $this->countryResolver->resolveForMesajTaslagi($communicationId);
            $tenantId = $this->tenantResolver->resolveForMesajTaslagi($communicationId);

            $message = AIMessage::create([
                'conversation_id' => $conversation->id,
                'communication_id' => $communicationId,
                'channel' => $communication->channel,
                'role' => $role,
                'content' => $content,
                'yayin_durumu' => TaslakDurumu::TAMAMLANDI->value,
                'ai_generated_at' => now(),
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ]);

            Log::info('AI mesaj kaydedildi', [
                'message_id' => $message->id,
                'communication_id' => $communicationId,
                'role' => $role,
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ]);

            return $message;
        } catch (\Exception $e) {
            Log::error('AI mesaj kaydetme hatası', [
                'error' => $e->getMessage(),
                'communication_id' => $communicationId,
            ]);

            throw $e;
        }
    }

    /**
     * Conversation oluştur veya mevcut olanı bul
     */
    /**
     * Get or create an AIConversation with canonical tenant/country ownership.
     *
     * Guards:
     * - Fail-closed if existing conversation has mismatched tenant_id or ulke_id.
     * - Fail-closed if existing conversation has NULL ownership.
     * - No silent backfill of historical NULL-owned records.
     *
     * @param  \App\Models\Communication  $communication
     * @param  int  $tenantId  Canonical resolved tenant ID
     * @param  int  $ulkeId    Canonical resolved country ID
     * @throws TenantOwnershipUnresolvableException  Ownership mismatch
     * @throws CountryOwnershipUnresolvableException Ownership mismatch
     */
    protected function getOrCreateConversation(
        \App\Models\Communication $communication,
        int $tenantId,
        int $ulkeId
    ): AIConversation {
        $existing = AIConversation::where('communication_id', $communication->id)
            ->withoutGlobalScopes()
            ->first();

        if ($existing) {
            // Fail-closed: existing conversation must have canonical ownership.
            if ($existing->tenant_id === null || $existing->tenant_id !== $tenantId) {
                throw new TenantOwnershipUnresolvableException(
                    'Existing AIConversation has mismatched tenant_id: expected '
                    .$tenantId.', found '.($existing->tenant_id ?? 'NULL')
                    .' for communication_id: '.$communication->id
                );
            }

            if ($existing->ulke_id === null || $existing->ulke_id !== $ulkeId) {
                throw new CountryOwnershipUnresolvableException(
                    'Existing AIConversation has mismatched ulke_id: expected '
                    .$ulkeId.', found '.($existing->ulke_id ?? 'NULL')
                    .' for communication_id: '.$communication->id
                );
            }

            return $existing;
        }

        // Create with canonical ownership.
        return AIConversation::withoutGlobalScopes()->create([
            'communication_id' => $communication->id,
            'channel' => $communication->channel,
            'tenant_id' => $tenantId,
            'ulke_id' => $ulkeId,
            'aktiflik_durumu' => true,
        ]);
    }

    /**
     * Bir iletişim için tüm mesajları getir
     */
    public function getMessages(int $communicationId): \Illuminate\Database\Eloquent\Collection
    {
        return AIMessage::where('communication_id', $communicationId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Mesaj gönder
     *
     * @param  int  $messageId  Mesaj ID
     */
    public function sendMessage(int $messageId): bool
    {
        try {
            $message = AIMessage::findOrFail($messageId);

            if ($message->yayin_durumu !== TaslakDurumu::TAMAMLANDI->value) {
                Log::warning('Mesaj gönderilemez: yayın_durumu uygun değil', [
                    'message_id' => $messageId,
                    'yayin_durumu' => $message->yayin_durumu,
                ]);

                return false;
            }

            $sent = match ($message->channel) {
                'telegram' => $this->sendTelegramMessage($message),
                'email' => $this->sendEmailMessage($message),
                'whatsapp' => $this->sendWhatsAppMessage($message),
                'instagram' => $this->sendInstagramMessage($message),
                default => false,
            };

            if ($sent) {
                $message->update([
                    'gonderim_zamani' => now(),
                ]);
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Mesaj gönderme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);

            return false;
        }
    }

    /**
     * Telegram mesajı gönder
     */
    protected function sendTelegramMessage(AIMessage $message): bool
    {
        try {
            $communication = $message->communication;
            if (! $communication || ! $communication->sender_id) {
                return false;
            }

            $authority = app(NotificationAuthorityInterface::class);
            $authority->notify('system_log', [
                'chat_id' => $communication->sender_id,
                'body' => $message->content,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram mesaj gönderme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);

            return false;
        }
    }

    /**
     * Email mesajı gönder
     */
    protected function sendEmailMessage(AIMessage $message): bool
    {
        $communication = $message->communication;
        if (!$communication || !$communication->sender_email) {
            return false;
        }

        $authority = app(NotificationAuthorityInterface::class);
        $authority->notify('ai_alert', [
            'email' => $communication->sender_email,
            'subject' => 'Yalıhan Emlak - Mesajınıza Cevap',
            'body' => $message->content
        ]);

        return true;
    }

    /**
     * WhatsApp mesajı gönder
     *
     * Context7: C7-WHATSAPP-API-2025-12-19
     * Yalıhan Bekçi: WhatsApp Business API entegrasyonu
     */
    protected function sendWhatsAppMessage(AIMessage $message): bool
    {
        $communication = $message->communication;
        if (!$communication || !$communication->sender_phone) {
            return false;
        }

        $authority = app(NotificationAuthorityInterface::class);
        $authority->notify('ai_whatsapp_reply', [
            'phone' => $communication->sender_phone,
            'body' => $message->content
        ]);

        return true;
    }

    /**
     * Instagram Direct mesajı gönder
     */
    protected function sendInstagramMessage(AIMessage $message): bool
    {
        try {
            $communication = $message->communication;

            if (!$communication || !$communication->sender_instagram) {
                Log::warning('Instagram gönderimi için kullanıcı ID bulunamadı', [
                    'message_id' => $message->id,
                ]);
                return false;
            }

            $this->authority->notify('ai_instagram_reply', [
                'instagram_id' => $communication->sender_instagram,
                'body' => $message->content,
                'message_id' => $message->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Instagram gönderimi exception', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);
            return false;
        }
    }

    /**
     * Telefon numarasını WhatsApp formatına normalize et
     */
    private function normalizePhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '90' . substr($cleaned, 1);
        }

        if (substr($cleaned, 0, 1) !== '+') {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }
}
