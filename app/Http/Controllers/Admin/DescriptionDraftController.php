<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AIDescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\AIDescriptionDraft;
use App\Models\Ilan;
use App\Services\AI\Description\DescriptionDraftService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Description Draft Controller
 *
 * Pipeline: Context Builder → LLM → Draft → Owner Review → Accept → Persist
 *
 * AI NEVER writes directly to ilan.aciklama.
 * AI produces Draft only.
 * Owner reviews and decides.
 */
class DescriptionDraftController extends Controller
{
    public function __construct(
        private readonly DescriptionDraftService $draftService
    ) {}

    /**
     * Generate AI Description Draft
     *
     * POST /admin/ilan-ai/draft/generate
     */
    public function generate(Request $request, Ilan $ilan): JsonResponse
    {
        $result = $this->draftService->generateDraft($ilan, auth()->id());

        if (! $result['success']) {
            return ResponseService::error($result['error'] ?? 'Taslak oluşturulamadı', 500);
        }

        return ResponseService::success([
            'draft_id' => $result['draft_id'],
            'provider' => $result['provider'] ?? 'cortex',
            'duration_ms' => $result['duration_ms'] ?? 0,
        ], 'AI taslak oluşturuldu. Lütfen inceleyip onaylayın veya reddedin.', 201);
    }

    /**
     * Get latest draft for review
     *
     * GET /admin/ilan-ai/draft/{ilan}
     */
    public function show(Ilan $ilan): JsonResponse
    {
        $draft = $this->draftService->getLatestDraft($ilan);

        if (! $draft) {
            return ResponseService::error('Henüz taslak oluşturulmamış', 404);
        }

        return ResponseService::success([
            'id' => $draft->id,
            'ilan_id' => $draft->ilan_id,
            'draft_content' => $draft->draft_content,
            'original_content' => $draft->original_content,
            'durum' => $draft->durum,
            'durum_label' => $draft->durum_enum?->label(),
            'durum_color' => $draft->durum_enum?->color(),
            'provider' => $draft->provider,
            'model' => $draft->model,
            'metadata' => $draft->metadata,
            'created_at' => $draft->created_at,
            'approved_at' => $draft->approved_at,
            'applied_at' => $draft->applied_at,
            'rejected_at' => $draft->rejected_at,
            'rejection_note' => $draft->rejection_note,
            'user' => $draft->user ? [
                'id' => $draft->user->id,
                'name' => $draft->user->name,
            ] : null,
        ]);
    }

    /**
     * Get draft history
     *
     * GET /admin/ilan-ai/draft/{ilan}/history
     */
    public function history(Ilan $ilan): JsonResponse
    {
        $history = $this->draftService->getDraftHistory($ilan, 10);

        return ResponseService::success([
            'drafts' => array_map(fn ($draft) => [
                'id' => $draft['id'],
                'durum' => $draft['durum'],
                'durum_label' => AIDescriptionStatus::tryFrom($draft['durum'])?->label(),
                'created_at' => $draft['created_at'],
                'user' => $draft['user'] ?? null,
                'approved_by' => $draft['approved_by'] ?? null,
                'rejected_by' => $draft['rejected_by'] ?? null,
            ], $history),
        ]);
    }

    /**
     * Approve draft and persist to ilan.aciklama
     *
     * POST /admin/ilan-ai/draft/{draft}/approve
     */
    public function approve(AIDescriptionDraft $draft): JsonResponse
    {
        if (! $draft->canApprove()) {
            return ResponseService::error(
                'Bu taslak onaylanamaz. Durum: '.$draft->durum_enum?->label(),
                422
            );
        }

        $result = $this->draftService->approveAndApply($draft, auth()->id());

        if (! $result['success']) {
            return ResponseService::error($result['error'] ?? 'Onaylama sırasında hata', 500);
        }

        return ResponseService::success([
            'ilan_id' => $draft->ilan_id,
            'aciklama' => $draft->fresh()->draft_content,
        ], 'Açıklama onaylandı ve uygulandı.');
    }

    /**
     * Reject draft
     *
     * POST /admin/ilan-ai/draft/{draft}/reject
     */
    public function reject(Request $request, AIDescriptionDraft $draft): JsonResponse
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        if (! $draft->canReject()) {
            return ResponseService::error(
                'Bu taslak reddedilemez. Durum: '.$draft->durum_enum?->label(),
                422
            );
        }

        $result = $this->draftService->reject($draft, auth()->id(), $request->input('note'));

        if (! $result['success']) {
            return ResponseService::error($result['error'] ?? 'Reddetme sırasında hata', 500);
        }

        return ResponseService::success([
            'ilan_id' => $draft->ilan_id,
            'original_content_preserved' => true,
        ], 'Taslak reddedildi. Orijinal açıklama korundu.');
    }
}
