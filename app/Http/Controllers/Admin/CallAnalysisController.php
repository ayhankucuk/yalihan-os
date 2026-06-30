<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Services\AI\CallIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CallAnalysisController extends Controller
{
    protected CallIntelligenceService $intelligenceService;

    public function __construct(CallIntelligenceService $intelligenceService)
    {
        $this->intelligenceService = $intelligenceService;
        $this->middleware('can:manage-settings');
    }

    /**
     * Trigger analysis for a specific call activity.
     *
     * @param Request $request
     * @param int $activityId
     * @return JsonResponse
     */
    public function analyze(Request $request, int $activityId): JsonResponse
    {
        // 1. Validation
        // Ensure activity exists and is a call type
        $activity = LeadActivity::findOrFail($activityId);

        // 2. Get Input (Audio path or text)
        $audioPath = $request->input('audio_path');
        $text = $request->input('text');

        // 3. Service Call
        $result = $this->intelligenceService->analyzeCall($activity->id, $audioPath, $text);

        // 4. Response
        if (isset($result['success']) && $result['success'] === false) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
