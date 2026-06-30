<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\Market\MarketIntelligenceService;

class MarketIntelligenceAdvisorController extends Controller
{
    public function __construct(
        private MarketIntelligenceService $intelligenceService
    ) {}

    /**
     * Display the Market Intelligence summary and insights for advisors.
     */
    public function index()
    {
        // Default context: Bodrum (common for testing/advisor)
        $locationData = ['il_id' => 48, 'ilce_id' => 1188]; // Muğla / Bodrum
        $stats = $this->intelligenceService->calculateMarketValue($locationData, 1); // 1 = Arsa/Tarla typical

        return view('advisor.market-intelligence', [
            'stats' => $stats
        ]);
    }
}
