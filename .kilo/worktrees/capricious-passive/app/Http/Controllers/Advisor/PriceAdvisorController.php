<?php

namespace App\Http\Controllers\Advisor;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AI\PriceAdvisor\PriceAdvisorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

/**
 * 🛡️ SAB SEALED
 * Advisor Price Advisor Controller
 * Thin controller handling pricing decision requests.
 */
class PriceAdvisorController extends Controller
{
    protected PriceAdvisorService $advisorService;

    public function __construct(PriceAdvisorService $advisorService)
    {
        $this->advisorService = $advisorService;
    }

    /**
     * Web View Page
     */
    public function index(Ilan $ilan)
    {
        return view('advisor.listing_price', compact('ilan'));
    }

    /**
     * API: Get Price Analysis
     */
    public function analysis(int $id)
    {
        try {
            $ilan = Ilan::findOrFail($id);
            $result = $this->advisorService->analyze($ilan);

            return Response::json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('advisor_price_analysis_failed', [
                'ilan_id' => $id,
                'hata_mesaji' => $e->getMessage(),
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Fiyat danışmanı verisi alınamadı.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * API: Get Price Analysis for Wizard Data
     */
    public function wizardAnalysis(Request $request)
    {
        try {
            $data = $request->validate([
                'il_id' => 'required|integer',
                'ilce_id' => 'required|integer',
                'mahalle_id' => 'nullable|integer',
                'kategori_id' => 'required|integer',
                'fiyat' => 'required|numeric',
                'alan_m2' => 'required|numeric',
                'lat' => 'nullable|numeric',
                'lng' => 'nullable|numeric',
            ]);

            $result = $this->advisorService->analyzeWizardData($data);

            return Response::json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('advisor_wizard_price_analysis_failed', [
                'payload' => $request->only(['il_id', 'ilce_id', 'mahalle_id', 'kategori_id']),
                'hata_mesaji' => $e->getMessage(),
            ]);

            return Response::json([
                'success' => false,
                'message' => 'Wizard fiyat analizi başarısız oldu.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
