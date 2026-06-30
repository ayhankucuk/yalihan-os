<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Modules\Finans\Models\FinansalIslem;
use Illuminate\Support\Facades\Log;

/**
 * FinanceAuditLog — R08 structured audit trail
 *
 * Logs structured audit events for every FinanceProcessor operation.
 * Fields: processor, tenant_id, model, provider, validation_status,
 *         failure_reason, fallback_used, ai_review_required
 */
class FinanceAuditLog
{
    private const LOG_CHANNEL = 'finance';

    /**
     * Log a completed FinanceProcessor operation
     */
    public function log(
        ?int $userId,
        ?int $tenantId,
        ?string $aiModel,
        ?string $aiProvider,
        FinanceValidationResult $result,
        bool $fallbackUsed,
        ?string $rawAiResponse,
        ?string $messagePreview,
    ): void {
        Log::channel(self::LOG_CHANNEL)->info('FinanceProcessor: AI Safety Audit', [
            'processor' => 'FinanceProcessor',
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'ai_model' => $aiModel,
            'ai_provider' => $aiProvider,
            'message_preview' => mb_substr($messagePreview ?? '', 0, 100),
            'raw_ai_response' => mb_substr($rawAiResponse ?? '', 0, 500),
            'validation_status' => $result->isValid ? 'VALID' : 'INVALID',
            'failure_reason' => $result->errorCode,
            'failure_message' => $result->errorMessage,
            'fallback_used' => $fallbackUsed,
            'ai_review_required' => $result->aiReviewRequired,
            'review_reason' => $result->reviewReason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log when AI response is skipped (not financial message)
     */
    public function logSkipped(?int $userId, ?string $messagePreview): void
    {
        Log::channel(self::LOG_CHANNEL)->info('FinanceProcessor: Skipped (not financial)', [
            'processor' => 'FinanceProcessor',
            'user_id' => $userId,
            'message_preview' => mb_substr($messagePreview ?? '', 0, 100),
            'validation_status' => 'SKIPPED',
            'ai_review_required' => false,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log when transaction creation succeeds
     */
    public function logTransactionCreated(
        FinansalIslem $islem,
        bool $aiReviewRequired,
        ?string $reviewReason,
    ): void {
        Log::channel(self::LOG_CHANNEL)->info('FinanceProcessor: Transaction created', [
            'processor' => 'FinanceProcessor',
            'islem_id' => $islem->id,
            'user_id' => $islem->user_id,
            'islem_tipi' => $islem->islem_tipi,
            'miktar' => $islem->miktar,
            'para_birimi' => $islem->para_birimi,
            'islem_durumu' => $islem->islem_statusu,
            'ai_review_required' => $aiReviewRequired,
            'review_reason' => $reviewReason,
            'ai_inceleme_gerekli' => $islem->ai_inceleme_gerekli,
            'ai_modeli' => $islem->ai_modeli,
            'ai_saglayici' => $islem->ai_saglayici,
            'ai_dogrulama_durumu' => $islem->ai_dogrulama_durumu,
            'ai_hata_sebebi' => $islem->ai_hata_sebebi,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
