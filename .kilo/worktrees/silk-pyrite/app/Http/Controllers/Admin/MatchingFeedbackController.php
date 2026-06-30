<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\MatchingFeedback;
use App\Services\Matching\MatchingFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Matching Feedback Controller
 *
 * 🎯 PHASE 8 - SPRINT 3: UI Feedback Loop
 *
 * Danışmanların eşleşmeleri değerlendirmesini sağlar.
 * Bu feedback'ler MatchingWeightsOptimizer'ın öğrenmesine katkı sağlar.
 */
class MatchingFeedbackController extends AdminController
{
    protected $feedbackService;

    public function __construct(MatchingFeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }
    /**
     * Feedback kaydet
     *
     * POST /api/admin/matching/feedback
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'talep_id' => 'required|exists:talepler,id',
            'ilan_id' => 'required|exists:ilanlar,id',
            'feedback_tipi' => 'required|in:thumbs_up,thumbs_down,perfect_match,not_relevant',
            'cortex_score_at_time' => 'required|integer|min:0|max:100',
            'match_breakdown' => 'nullable|array',
            'danisman_notu' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $feedback = $this->feedbackService->storeFeedback($request->all(), auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Feedback başarıyla kaydedildi',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'feedback_tipi' => $feedback->feedback_tipi,
                    'yayin_durumu_log' => $feedback->yayin_durumu_log, // Context7
                    'learning_triggered' => true,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback kaydedilemedi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Danışmanın feedback geçmişi
     *
     * GET /api/admin/matching/feedback/history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            // Authority Guard: non-admin sadece kendi history'sini görebilir.
            $currentUser = auth()->user();
            $isAdmin = $currentUser && (
                (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) ||
                (method_exists($currentUser, 'hasRole') && $currentUser->hasRole(['admin', 'super-admin']))
            );
            $danismanId = ($isAdmin && $request->has('danisman_id'))
                ? (int) $request->input('danisman_id')
                : auth()->id();
            $limit = (int) $request->input('limit', 20);

            $feedbacks = $this->feedbackService->getHistory($danismanId, $limit);

            $formatted = $feedbacks->map(function ($fb) {
                return [
                    'id' => $fb->id,
                    'feedback_tipi' => $fb->feedback_tipi,
                    'cortex_score' => $fb->cortex_score_at_time,
                    'talep_id' => $fb->talep_id,
                    'ilan_id' => $fb->ilan_id,
                    'created_at' => $fb->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'count' => $formatted->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Geçmiş yüklenemedi',
            ], 500);
        }
    }

    /**
     * Feedback istatistikleri
     *
     * GET /api/admin/matching/feedback/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Authority Guard: non-admin sadece kendi istatistiklerini görebilir.
            $currentUser = auth()->user();
            $isAdmin = $currentUser && (
                (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) ||
                (method_exists($currentUser, 'hasRole') && $currentUser->hasRole(['admin', 'super-admin']))
            );
            $danismanId = ($isAdmin && $request->has('danisman_id'))
                ? (int) $request->input('danisman_id')
                : auth()->id();
            $stats = $this->feedbackService->getStats($danismanId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İstatistikler yüklenemedi',
            ], 500);
        }
    }

    /**
     * Sonuç işaretle (Görüşme/Tıklama oldu)
     *
     * POST /api/admin/matching/feedback/{id}/mark-result
     */
    public function markResult(Request $request, int $id): JsonResponse
    {
        try {
            $this->feedbackService->markResultCreated($id, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Sonuç başarıyla işaretlendi',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
