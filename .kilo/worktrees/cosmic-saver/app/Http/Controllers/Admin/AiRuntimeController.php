<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\AiRolloutService;
use Illuminate\Http\Request;

class AiRuntimeController extends Controller
{
    public function index(AiRolloutService $rolloutService)
    {
        $runtimeState = $rolloutService->getRuntimeState();
        
        return view('admin.ai.runtime', compact('runtimeState'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'ai_enabled' => 'required|boolean',
            'vision_enabled' => 'required|boolean',
            'suggestion_enabled' => 'required|boolean',
            'vision_percentage' => 'required|integer|min:0|max:100',
            'suggestion_percentage' => 'required|integer|min:0|max:100',
        ]);

        // Update config file (simplified - in production use database or cache)
        config([
            'ai-runtime.ai_enabled' => $validated['ai_enabled'],
            'ai-runtime.vision_enabled' => $validated['vision_enabled'],
            'ai-runtime.suggestion_enabled' => $validated['suggestion_enabled'],
            'ai-runtime.rollout.vision_percentage' => $validated['vision_percentage'],
            'ai-runtime.rollout.suggestion_percentage' => $validated['suggestion_percentage'],
        ]);

        return redirect()->route('admin.ai.runtime')->with('success', 'AI Runtime settings updated');
    }
}
