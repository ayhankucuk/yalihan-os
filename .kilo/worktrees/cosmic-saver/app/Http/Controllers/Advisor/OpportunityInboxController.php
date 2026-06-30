<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\OpportunityEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 🛡️ SAB SEALED
 * Domain: Advisor / AI Opportunity Inbox
 *
 * Rules:
 * - Thin Controller: No business or scoring logic.
 * - Delegates entirely to OpportunityEngineService.
 */
class OpportunityInboxController extends Controller
{
    protected OpportunityEngineService $opportunityEngine;

    public function __construct(OpportunityEngineService $opportunityEngine)
    {
        $this->opportunityEngine = $opportunityEngine;
    }

    /**
     * Display the Opportunity Inbox UI.
     */
    public function index(): View
    {
        return view('advisor.opportunity-inbox');
    }

    /**
     * Fetch AI Opportunities via API (CQRS Read Model).
     */
    public function fetch(Request $request): JsonResponse
    {
        $filters = $request->only(['opportunity_type']);

        $opportunities = $this->opportunityEngine->getOpportunities($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'total_opportunities' => $opportunities->count(),
                'average_score' => $opportunities->avg('opportunity_score') ? (int) round($opportunities->avg('opportunity_score')) : 0,
                'opportunities' => $opportunities,
            ]
        ]);
    }
}
