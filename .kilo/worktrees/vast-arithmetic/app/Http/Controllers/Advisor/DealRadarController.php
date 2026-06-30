<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\DealRadarService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * 🏢 SAB SEALED
 * Controller for the AI Deal Radar Engine.
 * Strict Thin Controller architecture implementation.
 * Zero business logic, scoring, or query building.
 */
class DealRadarController extends Controller
{
    public function __construct(
        private DealRadarService $dealRadarService
    ) {}

    /**
     * Display the Deal Radar Dashboard.
     */
    public function index(): View
    {
        return view('advisor.deal-radar');
    }

    /**
     * Fetch Fast-Moving Listings via JSON payload.
     */
    public function fetch(): JsonResponse
    {
        $filters = request()->all();
        $data = $this->dealRadarService->getRadarListings($filters);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Display Deal Radar details for a single listing.
     */
    public function show(int $ilanId): View
    {
        $data = $this->dealRadarService->getRadarListings(['ilan_id' => $ilanId]);

        return view('advisor.deal-radar', ['data' => $data, 'ilanId' => $ilanId]);
    }
}
