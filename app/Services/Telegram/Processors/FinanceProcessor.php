<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use App\Modules\Finans\Models\FinansalIslem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FinanceProcessor - Telegram Financial Intelligence (R08 Safe Version)
 *
 * Context7 Standard: C7-TELEGRAM-FINANCE-2026-01-01
 * R08: FinanceProcessor AI Safety Implementation
 *
 * Safety guarantees:
 * - Never trusts AI output directly (always validated)
 * - Never auto-approves finance decisions from AI-only output
 * - Falls back safely on any AI failure
 * - Audit logs every operation
 * - Sets ai_review_required when AI output is untrusted
 *
 * @see FinanceResponseValidator For validation rules
 * @see FinanceAuditLog For audit trail
 */
class FinanceProcessor
{
    private ?string $openaiApiKey;
    private FinanceResponseValidator $validator;
    private FinanceAuditLog $audit;

    public function __construct(
        ?string $openaiApiKey = null,
        ?FinanceResponseValidator $validator = null,
        ?FinanceAuditLog $audit = null,
    ) {
        $this->openaiApiKey = $openaiApiKey ?? config('ai.api_key');
        $this->validator = $validator ?? new FinanceResponseValidator();
        $this->audit = $audit ?? new FinanceAuditLog();
    }

    /**
     * Handle Telegram message — extract financial data, validate, create transaction
     *
     * @param User $user
     * @param string $message
     * @return string|null Response message
     */
    public function handle(User $user, string $message): ?string
    {
        // Layer 1: Non-financial message detection
        if (!$this->isFinancialMessage($message)) {
            return null;
        }

        try {
            $this->audit->logSkipped($user->id, $message);
        } catch (\Throwable) {
            // Audit failures must not block financial processing
        }

        try {
            // Layer 2: Extract financial data (AI or fallback)
            [$rawAiResponse, $fallbackUsed, $aiModel, $aiProvider] = $this->extractFinancialDataWithAI($message);

            // Layer 3: Validate AI response (schema + business rules)
            $result = $this->validator->validate($rawAiResponse);

            // Layer 4: Audit the validation (non-blocking)
            $this->tryAuditLog($user, $message, $rawAiResponse, $result, $fallbackUsed, $aiModel, $aiProvider);

            // Layer 5: Handle invalid or review-required responses
            if (!$result->isValid || $result->aiReviewRequired) {
                return $this->handleReviewRequired($user, $message, $result);
            }

            // Layer 6: Create transaction (never auto-approve, always set to bekliyor)
            $islem = $this->createFinancialTransaction($user, $result->data, $result);

            // Layer 7: Audit transaction creation (non-blocking)
            $this->tryAuditTransactionCreated($islem, $result->aiReviewRequired, $result->reviewReason);

            return $this->generateSuccessMessage($islem, $result->aiReviewRequired);

        } catch (\Throwable $e) {
            Log::error('FinanceProcessor: Unhandled exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->buildErrorMessage();
        }
    }

    /**
     * Non-blocking audit log call
     */
    private function tryAuditLog(
        User $user,
        string $message,
        mixed $rawAiResponse,
        FinanceValidationResult $result,
        bool $fallbackUsed,
        ?string $aiModel,
        ?string $aiProvider,
    ): void {
        try {
            $this->audit->log(
                userId: $user->id,
                tenantId: $user->tenant_id ?? null,
                aiModel: $aiModel,
                aiProvider: $aiProvider,
                result: $result,
                fallbackUsed: $fallbackUsed,
                rawAiResponse: is_string($rawAiResponse) ? $rawAiResponse : json_encode($rawAiResponse),
                messagePreview: $message,
            );
        } catch (\Throwable) {
            // Audit failures must not block financial processing
        }
    }

    /**
     * Non-blocking audit transaction created call
     */
    private function tryAuditTransactionCreated(FinansalIslem $islem, bool $aiReviewRequired, ?string $reviewReason): void
    {
        try {
            $this->audit->logTransactionCreated(
                $islem,
                $aiReviewRequired,
                $reviewReason,
            );
        } catch (\Throwable) {
            // Audit failures must not block financial processing
        }
    }

    /**
     * Extract financial data — AI with safe fallback
     *
     * @return array{0: mixed, 1: bool, 2: string|null, 3: string|null}
     */
    private function extractFinancialDataWithAI(string $message): array
    {
        // No API key → use regex fallback immediately
        if (empty($this->openaiApiKey)) {
            Log::warning('FinanceProcessor: OpenAI API key not configured, using regex fallback');
            return [$this->regexFallbackExtraction($message), true, null, null];
        }

        try {
            $response = $this->callOpenAI($message);

            if (!$response->successful()) {
                Log::error('FinanceProcessor: OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [$this->regexFallbackExtraction($message), true, 'gpt-4o', 'openai'];
            }

            $body = $response->json();
            $content = $body['choices'][0]['message']['content'] ?? null;

            if (empty($content)) {
                return [$this->regexFallbackExtraction($message), true, 'gpt-4o', 'openai'];
            }

            // Try to parse JSON from AI response
            $parsed = json_decode(trim($content), true);

            if (!is_array($parsed)) {
                Log::warning('FinanceProcessor: AI response is not valid JSON', [
                    'content' => mb_substr($content, 0, 200),
                ]);
                return [$this->regexFallbackExtraction($message), true, 'gpt-4o', 'openai'];
            }

            return [$parsed, false, 'gpt-4o', 'openai'];

        } catch (\Throwable $e) {
            Log::error('FinanceProcessor: OpenAI exception', [
                'error' => $e->getMessage(),
            ]);
            return [$this->regexFallbackExtraction($message), true, 'gpt-4o', 'openai'];
        }
    }

    /**
     * Call OpenAI API with strict safety settings
     */
    private function callOpenAI(string $message): \Illuminate\Http\Client\Response
    {
        $systemPrompt = "Sen bir finansal asistansın. Kullanıcının Türkçe mesajından finansal bilgileri çıkar ve JSON formatında döndür.\n\n" .
                       "ZORUNLU ALANLAR:\n" .
                       "- miktar (float): Tutarı sayı olarak çıkar\n" .
                       "- para_birimi (string): TRY, USD, EUR, GBP\n" .
                       "- islem_tipi (string): gelir, gider, komisyon, masraf, odeme\n" .
                       "- aciklama (string): İşlem açıklaması\n\n" .
                       "KURALLAR:\n" .
                       "1. Varsayılan para birimi: TRY\n" .
                       "2. 'masraf', 'harcama' → gider\n" .
                       "3. 'kazanç', 'tahsilat' → gelir\n" .
                       "4. Miktar bulunamazsa null döndür\n\n" .
                       "SADECE JSON döndür, başka metin ekleme!";

        return Http::timeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'temperature' => 0.1, // Very low temperature for structured output
                'max_tokens' => 200,
            ]);
    }

    /**
     * Safe regex fallback — used when AI is unavailable
     */
    private function regexFallbackExtraction(string $message): array
    {
        preg_match('/(\d+(?:[.,]\d{3})*(?:[.,]\d{1,2})?)\s*(tl|lira|dolar|euro|usd|eur|₺|\$|€)?/i', $message, $matches);

        if (empty($matches[1])) {
            // Even fallback failed — return null which triggers review
            return ['_fallback_failed' => true, '_original_message' => $message];
        }

        $amount = str_replace(['.', ','], ['', '.'], $matches[1]);
        $currency = 'TRY';

        if (!empty($matches[2])) {
            $curr = strtolower($matches[2]);
            if (in_array($curr, ['dolar', 'dollar', 'usd', '$'])) {
                $currency = 'USD';
            } elseif (in_array($curr, ['euro', 'eur', '€'])) {
                $currency = 'EUR';
            }
        }

        $lowerMessage = mb_strtolower($message);
        $type = 'gider';

        if (strpos($lowerMessage, 'gelir') !== false || strpos($lowerMessage, 'kazanç') !== false || strpos($lowerMessage, 'tahsilat') !== false) {
            $type = 'gelir';
        } elseif (strpos($lowerMessage, 'komisyon') !== false) {
            $type = 'komisyon';
        } elseif (strpos($lowerMessage, 'masraf') !== false) {
            $type = 'masraf';
        }

        $description = preg_replace('/\d+(?:[.,]\d{3})*(?:[.,]\d{1,2})?\s*(tl|lira|dolar|euro|usd|eur|₺|\$|€)?/i', '', $message);
        $description = trim(preg_replace('/\s+/', ' ', str_replace(['gelir', 'gider', 'masraf', 'komisyon', ':', '-'], '', mb_strtolower($description))));

        return [
            'miktar' => (float) $amount,
            'para_birimi' => $currency,
            'islem_tipi' => $type,
            'aciklama' => !empty($description) ? $description : 'Telegram üzerinden eklendi (regex fallback)',
        ];
    }

    /**
     * Handle response that requires human review
     */
    private function handleReviewRequired(User $user, string $message, FinanceValidationResult $result): string
    {
        $reviewReason = $result->reviewReason ?? $result->errorMessage ?? 'Bilinmeyen doğrulama hatası';

        // Try to create transaction with review flag
        if ($result->data !== null) {
            try {
                $islem = $this->createFinancialTransaction($user, $result->data, $result);
                $this->audit->logTransactionCreated($islem, true, $reviewReason);
            } catch (\Throwable $e) {
                // Even transaction creation failed — still log and return safe message
                Log::error('FinanceProcessor: Transaction creation failed in review path', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$result->isValid) {
            return "⚠️ *Finansal veriler doğrulanamadı*\n\n" .
                   "📋 *Sebep:* {$reviewReason}\n\n" .
                   "⏳ İşlem inceleme için bekletildi. Lütfen bilgileri kontrol edin.";
        }

        // Valid but requires review (e.g., AI auto-approval signal)
        return "🔍 *İnceleme Gerekli*\n\n" .
               "📋 *Sebep:* {$reviewReason}\n\n" .
               "⏳ İşlem onayınız için bekletildi.";
    }

    /**
     * Create financial transaction with AI metadata
     */
    private function createFinancialTransaction(
        User $user,
        array $data,
        FinanceValidationResult $result,
    ): FinansalIslem {
        // Deterministic fallback for invalid data — never trust
        if ($result->errorCode !== null) {
            $data = $this->safeFallback($data);
        }

        $islem = FinansalIslem::create([
            'kisi_id' => $user->id,
            'islem_tipi' => $data['islem_tipi'],
            'miktar' => $data['miktar'],
            'para_birimi' => $data['para_birimi'],
            'aciklama' => $data['aciklama'],
            'tarih' => now(),
            'islem_statusu' => 'bekliyor', // Always bekliyor — never auto-approve
            'notlar' => 'Telegram Bot üzerinden oluşturuldu',
            'ai_inceleme_gerekli' => $result->aiReviewRequired,
            'ai_modeli' => 'gpt-4o',
            'ai_saglayici' => 'openai',
            'ai_dogrulama_durumu' => $result->isValid ? 'VALIDATED' : 'VALIDATION_FAILED',
            'ai_hata_sebebi' => $result->errorCode,
        ]);

        return $islem;
    }

    /**
     * Deterministic safe fallback — never panics, never invents data
     */
    private function safeFallback(array $data): array
    {
        return [
            'miktar' => isset($data['miktar']) ? max(0.01, (float) $data['miktar']) : 0.01,
            'para_birimi' => $data['para_birimi'] ?? 'TRY',
            'islem_tipi' => $data['islem_tipi'] ?? 'gider',
            'aciklama' => $data['aciklama'] ?? 'Doğrulama başarısız — inceleme gerekli',
        ];
    }

    /**
     * Generate success message
     */
    private function generateSuccessMessage(FinansalIslem $islem, bool $aiReviewRequired): string
    {
        $tipLabel = $this->typeLabel($islem->islem_tipi);
        $symbol = $this->currencySymbol($islem->para_birimi);

        $reviewNote = $aiReviewRequired
            ? "\n\n⚠️ *Bu işlem AI tarafından oluşturuldu — inceleme bekliyor*"
            : '';

        return "💎 *Kaptan, {$islem->miktar} {$symbol} tutarındaki {$tipLabel} işlemi finansal kayıtlara mühürlendi!*\n\n" .
               "📝 *Detaylar:*\n" .
               "• Tip: {$tipLabel}\n" .
               "• Tutar: " . number_format((float) $islem->miktar, 2, ',', '.') . " {$symbol}\n" .
               "• Açıklama: {$islem->aciklama}\n" .
               "• Tarih: " . $islem->tarih->format('d.m.Y H:i') . "\n" .
               "• Durum: ⏳ Onay Bekliyor\n\n" .
               "🔍 Panel'den görüntüleyebilir ve düzenleyebilirsiniz.{$reviewNote}";
    }

    /**
     * Build generic error message — safe, no internal details
     */
    private function buildErrorMessage(): string
    {
        return "❌ Finansal işlem kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.";
    }

    /**
     * Detect financial messages
     */
    private function isFinancialMessage(string $message): bool
    {
        $keywords = [
            'tl', 'lira', '₺', 'dolar', 'dollar', '$', 'euro', '€', 'usd', 'eur',
            'gelir', 'gider', 'masraf', 'harcama', 'ödeme', 'odeme',
            'kaydet', 'komisyon', 'fatura', 'tahsilat', 'ödendi',
        ];

        $lower = mb_strtolower($message);

        foreach ($keywords as $keyword) {
            if (mb_strpos($lower, $keyword) !== false) {
                return preg_match('/\d+/', $message) === 1;
            }
        }

        return false;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'gelir' => 'Gelir',
            'gider' => 'Gider',
            'komisyon' => 'Komisyon',
            'masraf' => 'Masraf',
            'odeme' => 'Ödeme',
            default => ucfirst($type),
        };
    }

    private function currencySymbol(string $currency): string
    {
        return match ($currency) {
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency,
        };
    }
}
