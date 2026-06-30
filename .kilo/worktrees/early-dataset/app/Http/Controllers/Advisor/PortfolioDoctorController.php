<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Services\AI\PortfolioDoctorService;
use Illuminate\Http\Request;

/**
 * @sab-ignore-service
 */

/**
 * 🛡️ SAB SEALED
 * Advisor Portfolio Doctor Web Controller
 * Thin controller serving the AI Portfolio Doctor product surface.
 */
class PortfolioDoctorController extends Controller
{
    public function __construct(
        private PortfolioDoctorService $portfolioDoctorService
    ) {}

    /**
     * AI Portfolio Doctor Dashboard Page
     */
    public function index()
    {
        return view('advisor.portfolio-doctor');
    }

    /**
     * AI Portfolio Doctor Data Fetch (JSON)
     */
    public function fetch(Request $request)
    {
        $filters = $request->only(['problem_category']);

        $portfolioHealth = $this->portfolioDoctorService->analyzePortfolio($filters);

        return response()->json([
            'success' => true,
            'data' => [
                'portfolio_health' => $portfolioHealth
            ]
        ]);
    }

    /**
     * AI Portfolio Doctor Diagnostics for a single listing
     */
    public function diagnostics(int $ilan)
    {
        $diagnostics = $this->portfolioDoctorService->analyzePortfolio(['ilan_id' => $ilan]);

        return response()->json([
            'success' => true,
            'data' => $diagnostics,
        ]);
    }
}
