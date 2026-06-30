<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\CortexPDFReportGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Yalıhan Cortex AI: Report Controller
 *
 * Context7 Standard: C7-REPORT-CONTROLLER-2025-12-23
 * Version: 1.0.0
 */
class CortexReportController extends Controller
{
    public function __construct(
        protected CortexPDFReportGenerator $reportGenerator
    ) {}

    /**
     * Generate investment report
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function generateReport(int $id, Request $request): JsonResponse
    {
        try {
            $ilan = Ilan::with([
                'turizmDetail',
                'arsaDetail',
                'il',
                'ilce',
                'mahalle',
                'anaKategori',
                'fotograflar',
            ])->findOrFail($id);

            $options = [
                'language' => $request->input('language', 'tr'),
                'include_photos' => $request->input('include_photos', true),
            ];

            $result = $this->reportGenerator->generateInvestmentReport($ilan, $options);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json($result);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'İlan bulunamadı',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Report generation controller error', [
                'ilan_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Report generation error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download report
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function downloadReport(int $id)
    {
        try {
            // Find latest report for this property
            $reportPath = $this->findLatestReport($id);

            if (!$reportPath || !Storage::exists($reportPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found. Generate report first.',
                ], 404);
            }

            return Storage::download($reportPath);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List reports for property
     *
     * @param int $id
     * @return JsonResponse
     */
    public function listReports(int $id): JsonResponse
    {
        try {
            $files = Storage::files('reports/investment');

            $propertyReports = array_filter($files, function($file) use ($id) {
                return str_contains($file, "investment-report-{$id}-");
            });

            $reports = array_map(function($file) {
                return [
                    'file_name' => basename($file),
                    'file_path' => $file,
                    'file_size_kb' => round(Storage::size($file) / 1024, 2),
                    'created_at' => Storage::lastModified($file),
                    'download_url' => Storage::url($file),
                ];
            }, $propertyReports);

            return response()->json([
                'success' => true,
                'data' => array_values($reports),
                'count' => count($reports),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'List reports error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report statistics
     *
     * @return JsonResponse
     */
    public function getReportStats(): JsonResponse
    {
        try {
            $files = Storage::files('reports/investment');

            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += Storage::size($file);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_reports' => count($files),
                    'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                    'latest_report' => count($files) > 0 ? [
                        'file_name' => basename($files[count($files) - 1]),
                        'created_at' => Storage::lastModified($files[count($files) - 1]),
                    ] : null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stats error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find latest report for property
     *
     * @param int $ilanId
     * @return string|null
     */
    private function findLatestReport(int $ilanId): ?string
    {
        $files = Storage::files('reports/investment');

        $propertyReports = array_filter($files, function($file) use ($ilanId) {
            return str_contains($file, "investment-report-{$ilanId}-");
        });

        if (empty($propertyReports)) {
            return null;
        }

        usort($propertyReports, function($a, $b) {
            return Storage::lastModified($b) <=> Storage::lastModified($a);
        });

        return $propertyReports[0];
    }
}
