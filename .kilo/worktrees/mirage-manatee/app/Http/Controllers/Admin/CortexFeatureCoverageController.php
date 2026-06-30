<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\Analytics\CortexFeatureCoverageService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Phase I: Feature Coverage Analytics Controller (READ-ONLY)
 *
 * Provides insights into which UPS features are actually used by AI
 * Context7: Read-only analytics, observer mode, no mutations
 */
class CortexFeatureCoverageController extends Controller
{
    protected CortexFeatureCoverageService $coverageService;

    public function __construct(CortexFeatureCoverageService $coverageService)
    {
        $this->coverageService = $coverageService;
    }

    /**
     * Get feature coverage report for specific scope
     *
     * Query params:
     * - days: Analysis window (default 30)
     * - kategori_slug: Filter by category (optional)
     * - yayin_tipi_slug: Filter by publication type (optional)
     * - limit: Top features limit (default 20)
     */
    public function index(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'kategori_slug' => 'nullable|string|max:100',
            'yayin_tipi_slug' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $days = $request->input('days', 30);
        $kategoriSlug = $request->input('kategori_slug');
        $yayinTipiSlug = $request->input('yayin_tipi_slug');
        $limit = $request->input('limit', 20);

        $report = $this->coverageService->getCoverageReport(
            $kategoriSlug,
            $yayinTipiSlug,
            $days,
            $limit
        );

        return ResponseService::success(
            $report,
            'Feature coverage report generated'
        );
    }

    /**
     * Get global coverage summary across all categories
     */
    public function global(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);

        $summary = $this->coverageService->getGlobalSummary($days);

        return ResponseService::success(
            $summary,
            'Global coverage summary generated'
        );
    }
}
