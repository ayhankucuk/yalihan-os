<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Services\Export\ExportService;
use Illuminate\Http\Request;

class ReportingController extends AdminController
{
    protected $exportService;
    protected $reportingService;

    public function __construct(ExportService $exportService, \App\Services\Admin\ReportingService $reportingService)
    {
        $this->exportService = $exportService;
        $this->reportingService = $reportingService;
    }

    public function index(Request $request)
    {
        return view('admin.reports.index');
    }

    /**
     * Kişi (Müşteri) Reports
     * Context7: Method renamed but kept kisiRaporlari name for backward compatibility
     * View uses: admin/reports/kisiler.blade.php (renamed from musteriler.blade.php)
     */
    public function kisiRaporlari(Request $request)
    {
        // Context7: View renamed to kisiler.blade.php
        return view('admin.reports.kisiler');
    }

    /**
     * Performance Reports
     */
    public function performanceReports(Request $request)
    {
        return view('admin.reports.performance');
    }

    /**
     * Export to Excel
     * Context7: Unified export service implementation
     */
    public function exportExcel(Request $request)
    {
        try {
            $type = $request->input('type', 'ilan'); // Default: ilan // context7-ignore

            // Validate type
            if (! in_array($type, ['ilan', 'kisi', 'talep'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz export tipi',
                ], 400);
            }

            return $this->exportService->exportToExcel($type, $request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excel export hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export to PDF
     * Context7: Unified export service implementation
     */
    public function exportPdf(Request $request)
    {
        try {
            $type = $request->input('type', 'ilan'); // Default: ilan // context7-ignore

            // Validate type
            if (! in_array($type, ['ilan', 'kisi', 'talep'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz export tipi',
                ], 400);
            }

            return $this->exportService->exportToPdf($type, $request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PDF export hatası: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get metrics for a specific listing (IX-006).
     *
     * Params: start_date, end_date
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListingMetrics(Request $request, int $id)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = \Illuminate\Support\Carbon::parse($request->input('start_date'));
        $endDate = \Illuminate\Support\Carbon::parse($request->input('end_date'));

        $metrics = $this->reportingService->getListingMetrics($id, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);
    }
}
