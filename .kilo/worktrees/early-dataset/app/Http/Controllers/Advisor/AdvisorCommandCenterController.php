<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\AdvisorCommandCenterService;
use Illuminate\Http\Request;

/**
 * @sab-ignore-service
 */

/**
 * 🛡️ SAB SEALED
 * Advisor Command Center Web Controller
 * Thin controller serving the unified AI dashboard.
 */
class AdvisorCommandCenterController extends Controller
{
    public function __construct(
        private AdvisorCommandCenterService $commandCenterService
    ) {}

    /**
     * AI Advisor Command Center Dashboard Page
     */
    public function index()
    {
        return view('advisor.command-center');
    }

    /**
     * AI Advisor Command Center Data Fetch (JSON)
     */
    public function fetch(Request $request)
    {
        // Allowed filters: 'priority_filter' (etc.)
        $filters = $request->only(['priority_filter']);

        // Service orchestrates all logic; controller stays thin.
        $data = $this->commandCenterService->getCommandCenterData($filters);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
