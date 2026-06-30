<?php

namespace App\Http\Controllers\Advisor;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\Copilot\BrokerCopilotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

/**
 * 🛡️ SAB SEALED
 * Advisor Broker Copilot Controller
 * Thin controller handling Copilot requests.
 */
class CopilotController extends Controller
{
    protected BrokerCopilotService $copilotService;

    public function __construct(BrokerCopilotService $copilotService)
    {
        $this->copilotService = $copilotService;
    }

    /**
     * Web View Page
     */
    public function index(Request $request)
    {
        // If a listing is pre-selected
        $ilan = null;
        if ($request->has('ilan_id')) {
            $ilan = Ilan::with(['il', 'ilce'])->find($request->ilan_id);
        }

        return view('advisor.copilot', compact('ilan'));
    }

    /**
     * API: Perform Analysis
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'ilan_id' => 'required|exists:ilanlar,id',
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $ilan = Ilan::findOrFail($request->ilan_id);
            $result = $this->copilotService->analyze($ilan, $request->question ?? '');

            return Response::json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('advisor_copilot_analysis_failed', [
                'ilan_id' => $request->ilan_id,
                'hata_mesaji' => $e->getMessage(),
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Copilot analizi sırasında bir hata oluştu.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
