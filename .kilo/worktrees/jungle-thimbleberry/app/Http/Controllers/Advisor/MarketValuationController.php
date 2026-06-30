<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\MarketValuationService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * AI Market Valuation Engine Controller
 *
 * SAB Thin Controller Prensibi (Sadece request, service, response)
 * Core logic MarketValuationService içindedir.
 */
class MarketValuationController extends Controller
{
    protected MarketValuationService $marketValuationService;

    public function __construct(MarketValuationService $marketValuationService)
    {
        $this->marketValuationService = $marketValuationService;
    }

    /**
     * Valuation Dashboard Sayfasını Render Eder
     */
    public function index()
    {
        return view('advisor.market-valuation');
    }

    /**
     * AI Market Valuation Query JSON
     */
    public function fetch(Request $request)
    {
        try {
            $validated = $request->validate([
                'il' => 'required|string',
                'ilce' => 'required|string',
                'mahalle' => 'required|string',
                'm2' => 'required|numeric|min:1',
                'asset_type' => 'nullable|string'
            ]);

            $result = $this->marketValuationService->evaluateQuery($validated);

            // Check if sufficient comparables were found
            if ($result['is_success'] === false) {
                return ResponseService::error($result['message'], 404);
            }

            return ResponseService::success($result['data'], "Market valuation generated successfully.");

        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), 500);
        }
    }
}
