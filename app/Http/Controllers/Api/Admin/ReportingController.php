<?php

namespace App\Http\Controllers\Api\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingIdAliasRequest;
use App\Services\Admin\ReportingService;
use Illuminate\Support\Carbon;

class ReportingController extends Controller
{
    protected $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Get Reporting Metrics for a Listing.
     *
     * GET /api/v1/admin/reporting/metrics
     * Params:
     * - listing_id (required, exists:ilanlar,id) — Phase 17 Adapter aracılığıyla ilan_id'ye eşlenir
     * - start_date (required, date, Y-m-d)
     * - end_date (required, date, after_or_equal:start_date)
     *
     * Phase 17 Adapter: dış dünya `listing_id` gönderir,
     * ListingIdAliasRequest bunu canonical `ilan_id`'ye eşler.
     */
    public function getMetrics(ListingIdAliasRequest $request)
    {
        // Tarih validasyonu — adapter listing_id/ilan_id'yi zaten doğruladı
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $ilanId = $request->ilanId(); // canonical ilan_id
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate   = Carbon::parse($request->input('end_date'));

        try {
            $occupancy = $this->reportingService->calculateOccupancy($ilanId, $startDate, $endDate);
            $adr = $this->reportingService->calculateADR($ilanId, $startDate, $endDate);
            $revpar = $this->reportingService->calculateRevPAR($ilanId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'listing_id' => $ilanId, // Dış contract korunuyor — Phase 5 cleanup'a kadar
                    'period' => [
                        'start' => $startDate->toDateString(),
                        'end'   => $endDate->toDateString(),
                    ],
                    'metrics' => [
                        'occupancy_rate' => $occupancy,
                        'adr' => $adr,
                        'revpar' => $revpar,
                        'currency' => 'TL' // Default currency for now
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating metrics: ' . $e->getMessage()
            ], 500);
        }
    }
}
