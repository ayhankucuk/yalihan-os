<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\ConversationalAdvisorService;
use App\Services\AI\PricingIntelligenceSyncService;

class ConversationalAdvisorController extends Controller
{
    public function __construct(
        private ConversationalAdvisorService $advisorService,
        private PricingIntelligenceSyncService $syncService
    ) {}

    public function index()
    {
        return view('advisor.conversational-advisor');
    }

    public function query(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:1000'
        ]);

        $result = $this->advisorService->processQuery($validated['query']);

        // Post-process side effects using the sync service if valuation was done
        if ($result['is_success'] && $result['intent_detected'] === 'MARKET_VALUATION') {
            $this->syncService->recordPricingSignal($result['data_payload'], auth()->id());
        }

        return response()->json($result);
    }
}
