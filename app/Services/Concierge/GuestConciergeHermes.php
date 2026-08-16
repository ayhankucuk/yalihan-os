<?php

namespace App\Services\Concierge;

use App\Models\GuestMessage;

/**
 * GuestConciergeHermes — AI intent classifier for Guest Concierge.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Responsibilities:
 * ✓ Intent classification (LLM call)
 * ✓ Context enrichment
 * ✓ Response drafting
 *
 * NOT Responsibilities:
 * ✗ Database writes
 * ✗ Gorev creation
 * ✗ Tenant resolution
 * ✗ Authority enforcement
 * ✗ Outbound message sending
 *
 * GC-D10: Hermes is an AI Orchestrator, NOT an application service.
 */
class GuestConciergeHermes
{
    /**
     * Classify intent from a guest message.
     *
     * Hermes extracts the intent but does NOT make decisions.
     * The application layer (GuestConciergeAuthorityPolicy) enforces authority.
     *
     * @param string $message Guest's message text
     * @param PropertyFactSheet $facts Available property facts
     * @return IntentClassification
     */
    public function classifyIntent(string $message, PropertyFactSheet $facts): IntentClassification
    {
        $prompt = $this->buildClassificationPrompt($message, $facts);
        $response = $this->callLlm($prompt);

        // P07/P08: LLM unavailable → escalate (UNKNOWN intent)
        if ($response === null) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] LLM unavailable — escalating', [
                'message' => substr($message, 0, 50),
            ]);
            return IntentClassification::classify(
                intent: 'UNKNOWN',
                confidence: 0.0,
                requiredFactKeys: [],
                reasoning: 'LLM unavailable',
            );
        }

        return $this->parseClassificationResponse($response);
    }

    /**
     * Draft an answer for a guest question.
     *
     * Hermes drafts the response but the application layer validates
     * that the required facts exist before sending.
     *
     * @param string $message Guest's message text
     * @param PropertyFactSheet $facts Available property facts
     * @param IntentClassification $classification
     * @return string
     */
    public function draftAnswer(
        string $message,
        PropertyFactSheet $facts,
        IntentClassification $classification,
    ): string {
        $prompt = $this->buildAnswerPrompt($message, $facts, $classification);
        $response = $this->callLlm($prompt);

        // P07/P08: LLM unavailable → escalate via fallback
        if ($response === null) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] draftAnswer LLM unavailable');
            return 'Mesajınız alındı. Şu anda teknik bir sorun yaşıyoruz, danışmanınız en kısa sürede sizinle iletişime geçecektir.';
        }

        return $response;
    }

    /**
     * Draft a confirmation for a created task.
     */
    public function draftTaskConfirmation(string $taskType, string $ilanAdi): string
    {
        return "Talebiniz alındı. '{$taskType}' görevi oluşturuldu ve ekibimize iletildi. {$ilanAdi} için en kısa sürede işlem yapılacaktır.";
    }

    /**
     * Draft an escalation acknowledgment for the guest.
     */
    public function draftEscalationAcknowledgment(): string
    {
        return "Mesajınız alındı. Danışmanınız en kısa sürede sizinle iletişime geçecektir.";
    }

    // ── Prompt Building ────────────────────────────────────────────

    protected function buildClassificationPrompt(string $message, PropertyFactSheet $facts): string
    {
        return <<<PROMPT
Sen bir konaklama asistanısın. Misafirin mesajını analiz et ve niyetini sınıflandır.

MISAFIR MESAJI:
{$message}

MÜLK BILGILERI:
{$facts->toPromptContext()}

GÖREV:
1. Mesajın niyetini aşağıdaki listeden seç
2. Güven puanı ver (0.00 - 1.00)
3. Soruyu cevaplamak için gereken bilgi anahtarlarını belirt

NIYET LİSTESİ:
- WIFI_INFO: WiFi şifresi veya ağ bilgisi sorusu
- CHECK_IN_TIME: Giriş saati sorusu
- CHECK_OUT_TIME: Çıkış saati sorusu
- PARKING_INFO: Otopark bilgisi sorusu
- HOUSE_RULES: Ev kuralları sorusu
- TECHNICAL_ISSUE: Klima, su, elektrik, tesisat arızası bildirimi
- CLEANING_REQUEST: Temizlik talebi
- CREDENTIAL_REQUEST: Kapı kodu, anahtar kutusu, kilit kodu sorusu
- EARLY_CHECKIN: Erken giriş talebi
- LATE_CHECKOUT: Geç çıkış talebi
- EXTEND_STAY: Konaklama uzatma talebi
- REFUND_REQUEST: İade talebi
- DAMAGE_REPORT: Hasar bildirimi
- LEGAL_QUESTION: Hukuki soru
- UNKNOWN: Yukarıdakilerden hiçbiri değil

YANIT FORMATI (JSON):
{
  "intent": "NIYET",
  "confidence": 0.00,
  "reasoning": "kısa açıklama"
}
PROMPT;
    }

    protected function buildAnswerPrompt(
        string $message,
        PropertyFactSheet $facts,
        IntentClassification $classification,
    ): string {
        return <<<PROMPT
Sen bir konaklama asistanısın. Misafire FRIENDLY, KISACA ve DOĞRU cevap ver.

MISAFIR MESAJI:
{$message}

MÜLK BİLGİLERİ:
{$facts->toPromptContext()}

TESPİT EDİLEN NIYET:
{$classification->intent}

KURALLAR:
- YALNIZCA mülk bilgilerini kullan
- Bilmediğin bir şey varsa "Bu konuda danışmanınıza danışmanızı öneririm" de
- HİÇBİR ZAMAN kapı kodu, anahtar kutusu kodu, akıllı kilit kodu verme
- Kapı kodu sorulursa "Check-in bilgileriniz ayrıca gönderilecektir" de
- Kısa ve öz ol
- Türkçe yanıt ver
PROMPT;
    }

    // ── LLM Integration ────────────────────────────────────────────

    /**
     * Call the LLM provider.
     *
     * PILOT-GATE-02: Uses config('concierge.llm') provider settings.
     *
     * P07: If provider is not configured → returns null → Hermes escalates.
     * P08: On timeout → returns null → Hermes escalates.
     *
     * @param string $prompt
     * @return string|null null = LLM unavailable (escalation trigger)
     */
    protected function callLlm(string $prompt): ?string
    {
        $provider = config('concierge.llm.provider', 'ollama');
        $timeout = (int) config('concierge.llm.' . $provider . '.timeout', 30);

        try {
            return match ($provider) {
                'ollama' => $this->callOllama($prompt, $timeout),
                'deepseek' => $this->callDeepSeek($prompt, $timeout),
                'openai' => $this->callOpenAi($prompt, $timeout),
                default => null,  // Unknown provider → P07: escalate
            };
        } catch (\Illuminate\Http\Client\ConnectException $e) {
            // P08: Connection refused / timeout
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] LLM connection failed (P08)', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return null;  // Escalation trigger
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GuestConciergeHermes] LLM call failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return null;  // Escalation trigger
        }
    }

    /**
     * Ollama / local model.
     *
     * @return string|null
     */
    protected function callOllama(string $prompt, int $timeout): ?string
    {
        $cfg = config('concierge.llm.ollama', []);
        $baseUrl = $cfg['base_url'] ?? 'http://localhost:11434';
        $model = $cfg['model'] ?? 'llama3.2';

        // P07: Missing configuration
        if (empty($baseUrl)) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] Ollama not configured (P07)');
            return null;
        }

        $response = \Illuminate\Support\Facades\Http::timeout($timeout)
            ->post("{$baseUrl}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ]);

        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] Ollama call failed', [
                'status' => $response->status(),
            ]);
            return null;
        }

        $text = $response->json('response', '');
        return $text !== '' ? $text : null;
    }

    /**
     * DeepSeek API.
     *
     * @return string|null
     */
    protected function callDeepSeek(string $prompt, int $timeout): ?string
    {
        $cfg = config('concierge.llm.deepseek', []);
        $baseUrl = $cfg['base_url'] ?? 'https://api.deepseek.com';
        $model = $cfg['model'] ?? 'deepseek-chat';
        $apiKey = $cfg['api_key'] ?? '';

        // P07: Missing API key
        if (empty($apiKey)) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] DeepSeek API key not configured (P07)');
            return null;
        }

        $response = \Illuminate\Support\Facades\Http::timeout($timeout)
            ->withToken($apiKey)
            ->post("{$baseUrl}/v1/chat/completions", [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] DeepSeek call failed', [
                'status' => $response->status(),
            ]);
            return null;
        }

        $text = $response->json('choices.0.message.content', '');
        return $text !== '' ? $text : null;
    }

    /**
     * OpenAI-compatible API.
     *
     * @return string|null
     */
    protected function callOpenAi(string $prompt, int $timeout): ?string
    {
        $cfg = config('concierge.llm.openai', []);
        $baseUrl = $cfg['base_url'] ?? 'https://api.openai.com/v1';
        $model = $cfg['model'] ?? 'gpt-4o-mini';
        $apiKey = $cfg['api_key'] ?? '';

        // P07: Missing API key
        if (empty($apiKey)) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] OpenAI API key not configured (P07)');
            return null;
        }

        $response = \Illuminate\Support\Facades\Http::timeout($timeout)
            ->withToken($apiKey)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::warning('[GuestConciergeHermes] OpenAI call failed', [
                'status' => $response->status(),
            ]);
            return null;
        }

        $text = $response->json('choices.0.message.content', '');
        return $text !== '' ? $text : null;
    }

    // ── Response Parsing ──────────────────────────────────────────

    protected function parseClassificationResponse(string $response): IntentClassification
    {
        // Try to parse as JSON
        $data = json_decode($response, true);

        if ($data !== null && isset($data['intent'], $data['confidence'])) {
            $intent = $data['intent'];
            $confidence = (float) $data['confidence'];
            $reasoning = $data['reasoning'] ?? null;
        } else {
            // Fallback: parse text
            $intent = $this->extractIntentFromText($response);
            $confidence = 0.50;
            $reasoning = 'Fallback parsing';
        }

        // Map to P1 intents
        $intent = $this->normalizeIntent($intent);

        // Get required fact keys
        $requiredFactKeys = GuestMessage::INTENT_REQUIRED_FACTS[$intent] ?? [];

        return IntentClassification::classify(
            intent: $intent,
            confidence: $confidence,
            requiredFactKeys: $requiredFactKeys,
            reasoning: $reasoning,
        );
    }

    protected function extractIntentFromText(string $response): string
    {
        $response = strtoupper($response);

        $intents = [
            'WIFI_INFO', 'CHECK_IN_TIME', 'CHECK_OUT_TIME',
            'PARKING_INFO', 'HOUSE_RULES', 'TECHNICAL_ISSUE',
            'CLEANING_REQUEST', 'CREDENTIAL_REQUEST', 'EARLY_CHECKIN',
            'LATE_CHECKOUT', 'EXTEND_STAY', 'REFUND_REQUEST',
            'DAMAGE_REPORT', 'LEGAL_QUESTION',
        ];

        foreach ($intents as $intent) {
            if (str_contains($response, $intent)) {
                return $intent;
            }
        }

        return 'UNKNOWN';
    }

    protected function normalizeIntent(string $intent): string
    {
        $intent = strtoupper(trim($intent));

        $mapping = [
            'WIFI' => 'WIFI_INFO',
            'WI-FI' => 'WIFI_INFO',
            'WI FI' => 'WIFI_INFO',
            'INTERNET' => 'WIFI_INFO',
            'ŞİFRE' => 'WIFI_INFO',
            'KAPI SAATI' => 'CHECK_IN_TIME',
            'GIRIŞ SAAT' => 'CHECK_IN_TIME',
            'CHECK IN' => 'CHECK_IN_TIME',
            'ÇIKIŞ SAAT' => 'CHECK_OUT_TIME',
            'CHECK OUT' => 'CHECK_OUT_TIME',
            'OTOPARK' => 'PARKING_INFO',
            'PARKING' => 'PARKING_INFO',
            'EV KURALLAR' => 'HOUSE_RULES',
            'KURAL' => 'HOUSE_RULES',
            'KLIMA' => 'TECHNICAL_ISSUE',
            'ARIZA' => 'TECHNICAL_ISSUE',
            'SU' => 'TECHNICAL_ISSUE',
            'ELEKTRİK' => 'TECHNICAL_ISSUE',
            'TESISAT' => 'TECHNICAL_ISSUE',
            'TEMIZLIK' => 'CLEANING_REQUEST',
            'TEMIZ' => 'CLEANING_REQUEST',
            'KAPİ KOD' => 'CREDENTIAL_REQUEST',
            'KOD' => 'CREDENTIAL_REQUEST',
            'ANAHTAR' => 'CREDENTIAL_REQUEST',
        ];

        foreach ($mapping as $keyword => $canonical) {
            if (str_contains($intent, $keyword)) {
                return $canonical;
            }
        }

        return $intent;
    }
}
