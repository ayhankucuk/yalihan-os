<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Hermes\WorkforceService;
use Illuminate\Http\JsonResponse;

/**
 * WorkforceDashboardController
 *
 * Sprint 4.3 — AI Workforce Vertical Slice
 *
 * Provides AI Workforce metrics via REST API.
 * Read-only: no mutations, no external API calls.
 */
class WorkforceDashboardController extends Controller
{
    public function __construct(
        private WorkforceService $workforceService,
    ) {}

    /**
     * GET /api/workforce/dashboard
     *
     * Returns complete AI Workforce dashboard metrics:
     * - Events received/processed/failed/success_rate
     * - Workforce executions with success rate and avg duration
     * - Per-agent breakdown
     * - Chain completion metrics
     * - Queue size
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->workforceService->getDashboardMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /api/workforce/chains
     *
     * Returns recent workforce chain executions.
     */
    public function chains(): JsonResponse
    {
        $chains = $this->workforceService->getRecentChains(limit: 20);

        return response()->json([
            'success' => true,
            'data' => $chains,
        ]);
    }

    /**
     * GET /api/workforce/agents
     *
     * Returns workforce agent registry status.
     */
    public function agents(): JsonResponse
    {
        $status = $this->workforceService->getAgentRegistryStatus();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}
