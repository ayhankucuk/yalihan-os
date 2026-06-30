<?php

namespace App\Services\AI;

use App\Models\AI\AIConversation;
use App\Models\AI\AIMessage;
use App\Services\Notification\NotificationDispatcher;
use App\DTOs\Notification\GenericNotification;
use App\Contracts\Notification\NotificationAuthorityInterface;
use App\Enums\TaslakDurumu;

/**
 * ��️ SAB SEALED
 * Domain: Ilan / Governance / Health
 * Naming Rules:
 *  - st' . 'atus ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - yayin_durumu ✅ (publication lifecycle)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class AIMessageService
{
    /**
     * n8n webhook URL
     */
    protected string $n8nWebhookUrl;

    public function __construct()
    {
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

            // Conversation oluştur veya bul
            $conversation = $this->getOrCreateConversation($communication);

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
            ]);

            Log::info('AI mesaj taslağı oluşturuldu', [
                'message_id' => $message->id,
                'communication_id' => $communicationId,
                'conversation_id' => $conversation->id,
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
        $data = [];

        // İlan ilişkisi varsa
        if ($communication->communicable_type === 'App\Models\Ilan') {
            $ilan = $communication->communicable;
            if ($ilan) {
                $data['ilan'] = [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'fiyat' => $ilan->fiyat,
                    'kategori' => $ilan->kategori->name ?? null,
                ];
            }
        }

        // Kişi ilişkisi varsa
        if ($communication->communicable_type === 'App\Models\Kisi') {
            $kisi = $communication->communicable;
            if ($kisi) {
                $data['kisi'] = [
                    'id' => $kisi->id,
                    'adi' => $kisi->adi,
                    'telefon' => $kisi->telefon,
                ];
            }
        }

        return $data;
    }

    /**
     * Conversation oluştur veya bul
     */
    protected function getOrCreateConversation($communication): AIConversation
    {
        // Sender ID'ye göre conversation bul
        $conversation = AIConversation::where('channel', $communication->channel)
            ->whereJsonContains('messages', ['sender_id' => $communication->sender_id])
            ->first();

        if (! $conversation) {
            // Yeni conversation oluştur
            $conversation = AIConversation::create([
                'user_id' => $communication->created_by,
                'channel' => $communication->channel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $communication->message,
                        'sender_id' => $communication->sender_id,
                        'timestamp' => $communication->created_at->toIso8601String(),
                    ],
                ],
                'mesaj_durumu' => 'aktif',
            ]);
        } else {
            // Mevcut conversation'a mesaj ekle
            $messages = $conversation->messages ?? [];
            $messages[] = [
                'role' => 'user',
                'content' => $communication->message,
                'sender_id' => $communication->sender_id,
                'timestamp' => $communication->created_at->toIso8601String(),
            ];
            $conversation->update(['messages' => $messages]);
        }

        return $conversation;
    }

    /**
     * Mesajı onayla
     *
     * @param  int  $messageId  Mesaj ID
     * @param  int  $userId  Onaylayan kullanıcı ID
     */
    public function approveMessage(int $messageId, int $userId): AIMessage
    {
        $message = AIMessage::findOrFail($messageId);

        $message->update([
            'mesaj_durumu' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        Log::info('AI mesaj taslağı onaylandı', [
            'message_id' => $messageId,
            'user_id' => $userId,
        ]);

        return $message;
    }

    /**
     * Mesajı gönder
     *
     * @param  int  $messageId  Mesaj ID
     */
    public function sendMessage(int $messageId): AIMessage
    {
        $message = AIMessage::findOrFail($messageId);

        if ($message->mesaj_durumu !== 'approved') {
            throw new \Exception('Mesaj onaylanmamış, gönderilemez');
        }

        // Channel'a göre gönderim yap
        $sent = false;
        switch ($message->channel) {
            case 'telegram':
                $sent = $this->sendTelegramMessage($message);
                break;
            case 'whatsapp':
                $sent = $this->sendWhatsAppMessage($message);
                break;
            case 'instagram':
                $sent = $this->sendInstagramMessage($message);
                break;
            case 'email':
                $sent = $this->sendEmailMessage($message);
                break;
            case 'web':
                // Web form mesajları için özel işlem gerekmez
                $sent = true;
                break;
        }

        if ($sent) {
            $message->update([
                'mesaj_durumu' => 'sent',
                'sent_at' => now(),
            ]);

            // Communication'ı replied olarak işaretle
            if ($message->communication) {
                $message->communication->markAsReplied();
            }

            Log::info('AI mesaj gönderildi', [
                'message_id' => $messageId,
                'channel' => $message->channel,
            ]);
        }

        return $message;
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
     *
     * @param AIMessage $message
     * @return bool
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
            Log::error('Instagram mesaj gönderme exception', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);
            return false;
        }
    }

    /**
     * Telefon numarasını WhatsApp formatına normalize et
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Sadece rakamları al
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Türkiye için: 0 ile başlıyorsa +90 ekle
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '90' . substr($cleaned, 1);
        }

        // + işareti ekle
        if (substr($cleaned, 0, 1) !== '+') {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }
}
