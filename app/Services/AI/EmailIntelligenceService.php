<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EmailIntelligenceService
 *
 * Gmail mesajlarından LLM ile signal extraction yapar.
 *
 * SORUMLULUK SINIRI (SAAB WAVE1):
 *   LLM sadece SIGNAL Cikarir — intent, dil, konu, kaynak, yapilandirilmis alanlar.
 *   Severity/P0-P2 karari → CommunicationSeverityPolicy (PHP, deterministic).
 *   LLM'e operasyon yetkisi yoktur.
 *
 * Provider sirasi: DeepSeek → OpenAI (YalihanCortex provider chain).
 */
class EmailIntelligenceService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Sen bir villa kiralama operasyon asistanısın.
Verilen email mesajından asagidaki bilgileri cikar:

1. intent: Misafirin ne istedigini anla:
   - checkin_lockout: Giris yapilamiyor, anahtar/kapı sorunu
   - checkin_question: Giris bilgisi sorusu
   - checkout_confusion: Cikis ile ilgili karisiklik
   - complaint: Sikayet
   - maintenance_issue: Teknik sorun (isitma, su, elektrik)
   - pool_issue: Havuz sorunu
   - early_checkin_req: Erken giris talebi
   - late_checkout_req: Geç çikis talebi
   - extend_stay: Konaklamayı uzatma talebi
   - refund_request: İade talebi
   - damage_report: Hasar bildirimi
   - general_question: Genel soru
   - house_rules: Ev kurallari
   - wifi_info: WiFi bilgisi
   - area_question: Bölge sorusu
   - unknown: Belirsiz

2. language: Email dili (tr, en, de, ru, vb.)

3. source_platform: Gonderenin kaynagi:
   - airbnb: Airbnb email adresi veya Airbnb icerigi
   - booking.com: Booking.com email veya icerigi
   - direct: Dogrudan email (Airbnb/Booking yok)
   - unknown: Belirlenemedi

4. guest_name: Misafir adi (email adresinden veya icerikten cikarilmis)

5. reservation_ref: Airbnb/Booking rezervasyon referansi (varsa)

6. message_summary: Tek cumle email özeti

7. sentiment: overall duygu durumu:
   - positive: Memnun / tesekkur
   - neutral: Bilgi talebi / soru
   - negative: Sikayet / problem / upset

8. is_urgent: Aciliyet belirtisi var mi?
   - GIRIS GUNU ve problem belirtisi → true
   - Guvenlik/saglik sorunu → true
   - Genel soru → false

9. extracted_fields: Ek cikarilmis alanlar (bos olabilir):
   - checkin_date: Giris tarihi (varsa)
   - checkout_date: Cikis tarihi (varsa)
   - villa_name: Villa adi (varsa)
   - guest_count: Kisi sayisi (varsa)
   - specific_issue: Sikayetin spesifik konusu

YALIHIMIZ:
- Her zaman sadece JSON döndür.
- Bilmiyorsan null veya "unknown" kullan.
- Spekülatif bilgi ekleme.
PROMPT;

    public function __construct(
        private readonly DeepSeekProvider $deepSeekProvider,
    ) {}

    /**
     * Email mesajindan signal extraction yap.
     */
    public function extractSignals(
        string $email,
        string $subject,
        ?string $bodyText,
    ): EmailExtractionResult {
        $userPrompt = $this->buildUserPrompt($email, $subject, $bodyText ?? '');

        try {
            $raw = $this->callDeepSeek($userPrompt);
            $data = json_decode($raw, true);

            if (! is_array($data)) {
                Log::warning('[EmailIntelligence] JSON decode failed, using unknown', [
                    'raw' => substr($raw, 0, 100),
                ]);
                return $this->unknownResult();
            }

            return EmailExtractionResult::fromArray($data);
        } catch (\Throwable $e) {
            Log::error('[EmailIntelligence] LLM call failed', [
                'error'   => $e->getMessage(),
                'email'   => $email,
            ]);

            // Fail-open: unknown intent, P2 severity
            return $this->unknownResult();
        }
    }

    // ── Private ─────────────────────────────────────────────────────────────

    private function callDeepSeek(string $prompt): string
    {
        $response = Http::withToken(config('services.deepseek.api_key'))
            ->timeout(20)
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model'    => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens'  => 512,
                'temperature' => 0.2,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('DeepSeek API error: ' . $response->status());
        }

        $content = $response->json('choices.0.message.content', '');

        // Strip markdown code fences if present
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);

        return trim($content);
    }

    private function buildUserPrompt(string $email, string $subject, string $body): string
    {
        return <<<USER
Email gonderen: {$email}
Konu: {$subject}
Email icerigi:
{$body}

Yukaridaki email icerigini analiz et ve JSON döndür.
USER;
    }

    private function unknownResult(): EmailExtractionResult
    {
        return EmailExtractionResult::fromArray([
            'intent'            => 'unknown',
            'language'          => 'unknown',
            'source_platform'   => 'unknown',
            'guest_name'        => null,
            'reservation_ref'  => null,
            'message_summary'   => 'Email analiz edilemedi',
            'sentiment'         => 'neutral',
            'is_urgent'         => false,
            'extracted_fields'  => [],
        ]);
    }
}
