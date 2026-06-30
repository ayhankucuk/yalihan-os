<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\RentalEvKarti;
use App\Models\RentalGelirKalemi;
use App\Models\RentalGiderKalemi;
use App\Services\Rental\RentalFinanceService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SAB Phase 17C: Rental Management API
 *
 * Single API surface for ev kartı financial operations.
 * Controller = thin orchestration. Service = calculation.
 */
class RentalController extends Controller
{
    public function __construct(
        private readonly RentalFinanceService $financeService
    ) {}

    /**
     * GET /api/v1/rental/ev-kartlari/{id}/ozet
     *
     * Params: yil (required), ay (optional — if omitted, returns yearly summary)
     */
    public function ozet(int $id, Request $request): JsonResponse
    {
        $evKarti = RentalEvKarti::findOrFail($id);

        $yil = (int) $request->input('yil', now()->year);
        $ay  = $request->input('ay');

        if ($ay !== null) {
            $ozet = $this->financeService->calculateMonthlySummary($evKarti, $yil, (int) $ay);
        } else {
            $ozet = $this->financeService->calculateYearSummary($evKarti, $yil);
        }

        return ResponseService::success($ozet);
    }

    /**
     * POST /api/v1/rental/gelir
     */
    public function storeIncome(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ev_karti_id' => 'required|exists:rental_ev_kartlari,id',
            'kalem_turu'  => 'required|integer|in:0,1,2',
            'tutar'       => 'required|numeric|min:0.01',
            'donem_yil'   => 'required|integer|min:2020|max:2099',
            'donem_ay'    => 'required|integer|min:1|max:12',
            'odeme_tarihi'=> 'nullable|date',
            'aciklama'    => 'nullable|string|max:500',
        ]);

        $gelir = RentalGelirKalemi::create($validated);

        return ResponseService::success([
            'id'    => $gelir->id,
            'tutar' => $gelir->tutar,
        ], 'Gelir kalemi eklendi.', 201);
    }

    /**
     * POST /api/v1/rental/gider
     */
    public function storeExpense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ev_karti_id' => 'required|exists:rental_ev_kartlari,id',
            'kalem_turu'  => 'required|integer|in:0,1,2,3,4,5,6',
            'tutar'       => 'required|numeric|min:0.01',
            'donem_yil'   => 'required|integer|min:2020|max:2099',
            'donem_ay'    => 'required|integer|min:1|max:12',
            'odeme_tarihi'=> 'nullable|date',
            'tedarikci'   => 'nullable|string|max:255',
            'aciklama'    => 'nullable|string|max:500',
        ]);

        $gider = RentalGiderKalemi::create($validated);

        return ResponseService::success([
            'id'    => $gider->id,
            'tutar' => $gider->tutar,
        ], 'Gider kalemi eklendi.', 201);
    }
}
