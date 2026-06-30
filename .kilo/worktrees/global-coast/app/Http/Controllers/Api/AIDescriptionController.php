<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AI\AIDescriptionDraft;
use App\Models\Ilan;
use App\Services\AI\Description\AIDescriptionPipeline;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI Description Pipeline Controller
 *
 * Pipeline: ContextBuilder → LLM → Draft → Owner Review → Accept → Persist
 *
 * Endpoints:
 *  POST   /api/ai/description/generate     - Generate draft
 *  GET    /api/ai/description/ilan/{id}    - Get drafts for listing
 *  GET    /api/ai/description/ilan/{id}/latest - Get latest draft
 *  GET    /api/ai/description/ilan/{id}/history - Get draft history
 *  POST   /api/ai/description/{id}/approve - Owner approve + apply
 *  POST   /api/ai/description/{id}/reject  - Owner reject
 *  GET    /api/ai/description/{id}        - Get single draft
 */
class AIDescriptionController extends Controller
{
    use ValidatesApiRequests;

    public function __construct(
        private readonly AIDescriptionPipeline $pipeline
    ) {}

    /**
     * Generate AI description draft
     *
     * POST /api/v1/ai/description/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'ilan_id' => 'required|integer|exists:ilanlar,id',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($request->ilan_id);
            $userId = $request->user()?->id ?? 0;

            $result = $this->pipeline->generateDraft($ilan, $userId);

            if ($result['success']) {
                return ResponseService::success([
                    'draft_id' => $result['draft_id'],
                    'provider' => $result['provider'] ?? 'cortex',
                    'duration_ms' => $result['duration_ms'] ?? 0,
                ], 'AI açıklama taslağı oluşturuldu');
            }

            return ResponseService::error($result['error'] ?? 'Hata oluştu', 400);
        } catch (\Exception $e) {
            return ResponseService::serverError('Draft oluşturma hatası', $e);
        }
    }

    /**
     * Get latest draft for a listing
     *
     * GET /api/v1/ai/description/ilan/{id}/latest
     */
    public function getLatest(Request $request, int $ilanId): JsonResponse
    {
        try {
            $ilan = Ilan::findOrFail($ilanId);
            $draft = $this->pipeline->getLatestDraft($ilan);

            if (!$draft) {
                return ResponseService::success([
                    'draft' => null,
                    'message' => 'Henüz taslak yok',
                ]);
            }

            return ResponseService::success([
                'draft' => $draft->load(['ilan:id,baslik', 'user:id,name']),
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Draft bulunamadı', $e);
        }
    }

    /**
     * Get draft history for a listing
     *
     * GET /api/v1/ai/description/ilan/{id}/history
     */
    public function getHistory(Request $request, int $ilanId): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($ilanId);
            $limit = $request->input('limit', 10);

            $drafts = $this->pipeline->getDraftHistory($ilan, $limit);

            return ResponseService::success([
                'drafts' => $drafts,
                'count' => count($drafts),
            ]);
        } catch (\Exception $e) {
            return ResponseService::serverError('Tarihçe hatası', $e);
        }
    }

    /**
     * Get single draft details
     *
     * GET /api/v1/ai/description/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $draft = AIDescriptionDraft::with([
                'ilan:id,baslik',
                'user:id,name',
                'approvedByUser:id,name',
                'rejectedByUser:id,name',
            ])->findOrFail($id);

            return ResponseService::success($draft);
        } catch (\Exception $e) {
            return ResponseService::serverError('Draft bulunamadı', $e);
        }
    }

    /**
     * Owner approves and applies draft
     *
     * POST /api/v1/ai/description/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $draft = AIDescriptionDraft::findOrFail($id);
            $userId = $request->user()?->id ?? 0;

            $result = $this->pipeline->approveAndApply($draft, $userId);

            if ($result['success']) {
                return ResponseService::success([
                    'ilan_id' => $draft->ilan_id,
                ], 'Açıklama onaylandı ve ilana uygulandı');
            }

            return ResponseService::error($result['error'] ?? 'Onaylanamadı', 400);
        } catch (\Exception $e) {
            return ResponseService::serverError('Onay hatası', $e);
        }
    }

    /**
     * Owner rejects draft
     *
     * POST /api/v1/ai/description/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $this->validateRequestWithResponse($request, [
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $draft = AIDescriptionDraft::findOrFail($id);
            $userId = $request->user()?->id ?? 0;

            $result = $this->pipeline->reject($draft, $userId, $request->note);

            if ($result['success']) {
                return ResponseService::success([
                    'draft_id' => $draft->id,
                ], 'Draft reddedildi');
            }

            return ResponseService::error($result['error'] ?? 'Reddedilemedi', 400);
        } catch (\Exception $e) {
            return ResponseService::serverError('Reddetme hatası', $e);
        }
    }
}