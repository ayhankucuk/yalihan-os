<?php

namespace App\Http\Controllers\Api\V2;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Actions\Api\V2\Draft\ApproveDraftAction;
use App\Actions\Api\V2\Draft\DestroyDraftAction;
use App\Actions\Api\V2\Draft\PublishDraftAction;
use App\Actions\Api\V2\Draft\StoreDraftAction;
use App\Actions\Api\V2\Draft\UpdateDraftAction;
use App\Models\V2\AiIlanTaslagi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * V2 AI İlan Taslakları (Drafts) API Controller
 *
 * Context7: 100% Compliant
 * - Field names: taslak_durumu, ai_response, ai_model_used, ai_prompt_version
 * - No forbidden field patterns (using canonical names only)
 * - All endpoints require authentication (voice-to-draft workflow)
 * - Approval workflow with policies
 */
class DraftController extends Controller
{
    /**
     * Display a listing of drafts for authenticated user
     * GET /api/v1/drafts
     */
    public function index(): JsonResponse
    {
        $drafts = AiIlanTaslagi::where('kullanici_id', auth('sanctum')->id())
            ->select([
                'id', 'kullanici_id', 'ai_response', 'taslak_durumu',
                'ai_model_used', 'ai_prompt_version', 'ai_generated_at', 'created_at'
            ])
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $drafts->items(),
            'pagination' => [
                'total' => $drafts->total(),
                'per_page' => $drafts->perPage(),
                'current_page' => $drafts->currentPage(),
                'last_page' => $drafts->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created draft (voice-to-draft workflow)
     * POST /api/v1/drafts
     */
    public function store(Request $request, StoreDraftAction $action): JsonResponse
    {
        $validated = $request->validate([
            'ai_response' => 'required|string|min:20',
            'ai_model_used' => 'required|string|in:gpt4,gpt35,deepseek,gemini,llama2,ollama',
            'ai_prompt_version' => 'required|string',
            'metadata' => 'sometimes|array',
        ]);

        $draft = $action->handle($validated);

        return response()->json([
            'success' => true,
            'message' => 'Taslak başarıyla oluşturuldu',
            'data' => $draft,
        ], 201);
    }

    /**
     * Display the specified draft
     * GET /api/v1/drafts/{id}
     */
    public function show(AiIlanTaslagi $draft): JsonResponse
    {
        // Check authorization
        if ($draft->kullanici_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu taslağa erişim izniniz yok',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $draft,
        ]);
    }

    /**
     * Update the specified draft
     * PUT /api/v1/drafts/{id}
     */
    public function update(Request $request, AiIlanTaslagi $draft, UpdateDraftAction $action): JsonResponse
    {
        // Check authorization
        if ($draft->kullanici_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu taslağa erişim izniniz yok',
            ], 403);
        }

        $validated = $request->validate([
            'ai_response' => 'sometimes|string|min:20',
            'ai_model_used' => 'sometimes|string|in:gpt4,gpt35,deepseek,gemini,llama2,ollama',
            'ai_prompt_version' => 'sometimes|string',
            'metadata' => 'sometimes|array|nullable',
        ]);

        $action->handle($draft, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Taslak başarıyla güncellendi',
            'data' => $draft,
        ]);
    }

    /**
     * Delete the specified draft
     * DELETE /api/v1/drafts/{id}
     */
    public function destroy(AiIlanTaslagi $draft, DestroyDraftAction $action): JsonResponse
    {
        if ($draft->kullanici_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu taslağa erişim izniniz yok',
            ], 403);
        }

        $action->handle($draft);

        return response()->json([
            'success' => true,
            'message' => 'Taslak başarıyla silindi',
        ]);
    }

    /**
     * Publish draft as a listing
     * PATCH /api/v1/drafts/{id}/publish
     */
    public function publish(AiIlanTaslagi $draft, PublishDraftAction $action): JsonResponse
    {
        if ($draft->kullanici_id !== auth('sanctum')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu taslağa erişim izniniz yok',
            ], 403);
        }

        if ($draft->taslak_durumu !== 'Onaylı') {
            return response()->json([
                'success' => false,
                'message' => 'Sadece onaylı taslaklar yayınlanabilir',
            ], 422);
        }

        $action->handle($draft);

        return response()->json([
            'success' => true,
            'message' => 'Taslak başarıyla yayınlandı',
            'data' => $draft,
        ]);
    }

    /**
     * Approve draft for publishing (admin/moderator only)
     * PATCH /api/v1/drafts/{id}/approve
     *
     * Requires policy: can:approve-drafts
     */
    public function approve(AiIlanTaslagi $draft, ApproveDraftAction $action): JsonResponse
    {
        // Authorization is checked by middleware/policy
        $action->handle($draft, (int) auth('sanctum')->id());

        return response()->json([
            'success' => true,
            'message' => 'Taslak başarıyla onaylandı',
            'data' => $draft,
        ]);
    }
}
