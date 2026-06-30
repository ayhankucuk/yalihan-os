<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\Copilot\CopilotOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CopilotController extends Controller
{
    public function __construct(
        protected CopilotOrchestrator $orchestrator
    ) {}

    public function insights(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route' => 'required|string|max:255',
            'entity_id' => 'nullable|integer|min:1',
        ]);

        $result = $this->orchestrator->analyze(
            $validated['route'],
            $validated['entity_id'] ?? null
        );

        return response()->json($result);
    }
}
