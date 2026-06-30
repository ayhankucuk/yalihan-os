<?php

namespace App\Services\AI\Description;

use App\Models\AIDescriptionDraft;
use App\Models\Ilan;
use App\Services\AI\YalihanCortex;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Description Draft Service
 *
 * Pipeline: Context Builder → LLM → Draft → Owner Review → Accept → Persist
 *
 * AI NEVER writes directly to ilan.aciklama.
 * AI produces Draft only.
 * Owner reviews and decides.
 *
 * Target Pipeline:
 * Portfolio → Photos → Readiness → Recommendations → Context Builder → LLM → Draft
 * → Owner Review → Accept → Persist
 */
class DescriptionDraftService
{
    public function __construct(
        private readonly DescriptionContextBuilder $contextBuilder,
        private readonly YalihanCortex $cortex
    ) {}

    /**
     * Generate AI Description Draft for an Ilan
     *
     * @param  int  $userId  Owner who requested the draft
     * @return array{success: bool, draft_id?: int, error?: string}
     */
    public function generateDraft(Ilan $ilan, int $userId): array
    {
        $requestId = Str::uuid()->toString();
        $startTime = LogService::startTimer('description_draft_generation');

        try {
            // 1. Build context from Ilan
            $context = $this->contextBuilder->build($ilan);
            $formattedContext = $this->contextBuilder->formatForPrompt($context);

            // 2. Call AI via Cortex
            $response = $this->cortex->generateStructuredDescription($context);

            if (! $response['success']) {
                $this->logError('draft_generation_failed', $requestId, $response['error'] ?? 'Unknown', $ilan->id);

                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Açıklama üretilemedi',
                ];
            }

            $durationMs = LogService::stopTimer($startTime);

            // 3. Create draft record
            $draft = $this->createDraft($ilan, $userId, $response, $requestId, $durationMs);

            // 4. Log success
            $this->logSuccess('draft_generated', $requestId, $durationMs, $ilan->id, $response['provider'] ?? 'cortex');

            return [
                'success' => true,
                'draft_id' => $draft->id,
                'provider' => $response['provider'] ?? 'cortex',
                'duration_ms' => $durationMs,
            ];

        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);
            LogService::error('draft_generation_failed', [
                'ilan_id' => $ilan->id,
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            $this->logError('draft_generation_exception', $requestId, $e->getMessage(), $ilan->id, $e);

            return [
                'success' => false,
                'error' => 'Açıklama üretimi sırasında hata oluştu',
            ];
        }
    }

    /**
     * Approve draft and persist to ilan.aciklama
     *
     * @return array{success: bool, error?: string}
     */
    public function approveAndApply(AIDescriptionDraft $draft, int $userId): array
    {
        try {
            DB::transaction(function () use ($draft, $userId) {
                // 1. Approve
                $draft->approve($userId);

                // 2. Apply to ilan.aciklama
                $draft->apply();
            });

            return ['success' => true];

        } catch (\Exception $e) {
            LogService::error('description_approve_apply_failed', [
                'draft_id' => $draft->id,
                'ilan_id' => $draft->ilan_id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject draft
     *
     * @return array{success: bool, error?: string}
     */
    public function reject(AIDescriptionDraft $draft, int $userId, ?string $note = null): array
    {
        try {
            $draft->reject($userId, $note);

            return ['success' => true];

        } catch (\Exception $e) {
            LogService::error('description_reject_failed', [
                'draft_id' => $draft->id,
                'ilan_id' => $draft->ilan_id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get latest draft for an ilan
     */
    public function getLatestDraft(Ilan $ilan): ?AIDescriptionDraft
    {
        return AIDescriptionDraft::where('ilan_id', $ilan->id)
            ->latest('id')
            ->first();
    }

    /**
     * Get draft history for an ilan
     */
    public function getDraftHistory(Ilan $ilan, int $limit = 10): array
    {
        return AIDescriptionDraft::where('ilan_id', $ilan->id)
            ->withUser()
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ========================================================================
    // PRIVATE HELPERS
    // ========================================================================

    private function createDraft(
        Ilan $ilan,
        int $userId,
        array $aiResponse,
        string $requestId,
        int $durationMs
    ): AIDescriptionDraft {
        return AIDescriptionDraft::create([
            'ilan_id' => $ilan->id,
            'user_id' => $userId,
            'draft_content' => $aiResponse['data']['aciklama'] ?? $aiResponse['data']['content'] ?? '',
            'original_content' => $ilan->aciklama, // Keep original for rollback
            'durum' => 'taslak',
            'provider' => $aiResponse['provider'] ?? 'cortex',
            'model' => $aiResponse['model'] ?? null,
            'metadata' => [
                'request_id' => $requestId,
                'duration_ms' => $durationMs,
                'tokens' => $aiResponse['metadata']['tokens'] ?? null,
            ],
        ]);
    }

    private function logSuccess(string $action, string $requestId, int $durationMs, int $ilanId, string $provider): void
    {
        LogService::ai("description_draft_{$action}", 'DescriptionDraftService', [
            'request_id' => $requestId,
            'ilan_id' => $ilanId,
            'duration_ms' => $durationMs,
            'provider' => $provider,
        ]);
    }

    private function logError(string $action, string $requestId, string $error, int $ilanId, ?\Exception $e = null): void
    {
        LogService::error("DescriptionDraftService {$action}", [
            'request_id' => $requestId,
            'ilan_id' => $ilanId,
            'error' => $error,
        ], $e);
    }
}
