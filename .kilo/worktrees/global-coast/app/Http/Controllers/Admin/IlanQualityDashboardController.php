<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;

/**
 * Ilan Quality Dashboard Controller
 *
 * Phase F-UI: AI Quality & Template Insights Dashboard
 *
 * Sorumluluklar:
 * - Quality learning analytics görüntüleme (read-only)
 * - Template advice görüntüleme (read-only)
 * - Passive dashboard - NO mutations
 *
 * Kurallar (ZORUNLU):
 * - ❌ UPS writes YOK
 * - ❌ Ilan updates YOK
 * - ❌ Policy logic YOK
 * - ✅ Read-only view
 * - ✅ Observer mode korunur
 * - ✅ Page view logging
 *
 * Endpoint: GET /admin/ilanlar/ai/dashboard
 * Middleware: web + auth + verified + can:view-admin-panel
 */
class IlanQualityDashboardController extends Controller
{
    public function __construct(
        private YalihanCortex $cortex
    ) {}

    /**
     * Show AI quality dashboard
     *
     * Displays:
     * - Quality overview (avg score, trends, distribution)
     * - Common issues by kategori + yayin_tipi
     * - Template advisor insights
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $startTime = LogService::startTimer('quality_dashboard_view');

        try {
            $days = $request->integer('days', 30);

            // 1. Get quality learning analytics
            $qualityResult = $this->cortex->analyzeQualityOutcomes(['days' => $days]);
            $qualityData = $qualityResult['success'] ? $qualityResult['data'] : [];

            // 2. Get template advice
            $templateResult = $this->cortex->getTemplateAdvice(['days' => $days]);
            $templateData = $templateResult['success'] ? $templateResult['data'] : [];

            $durationMs = LogService::stopTimer($startTime);

            // 3. Log page view (Context7/MCP)
            LogService::ai('quality_dashboard_view', 'YalihanCortex', [
                'days' => $days,
                'quality_success' => $qualityResult['success'] ?? false,
                'template_success' => $templateResult['success'] ?? false,
                'duration_ms' => $durationMs,
                'user_id' => auth()->id(),
            ]);

            // 4. Prepare dashboard data
            $dashboard = $this->prepareDashboardData($qualityData, $templateData);

            return view('admin.ilanlar.ai-quality-dashboard', [
                'dashboard' => $dashboard,
                'days' => $days,
                'quality_raw' => $qualityData,
                'template_raw' => $templateData,
            ]);
        } catch (\Exception $e) {
            $durationMs = LogService::stopTimer($startTime);

            LogService::error('Quality dashboard view failed', [
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'user_id' => auth()->id(),
            ], $e);

            return view('admin.ilanlar.ai-quality-dashboard', [
                'dashboard' => [
                    'error' => 'Dashboard yüklenemedi: ' . $e->getMessage(),
                ],
                'days' => 30,
                'quality_raw' => [],
                'template_raw' => [],
            ]);
        }
    }

    /**
     * Prepare dashboard data structure
     *
     * @param array $qualityData
     * @param array $templateData
     * @return array
     */
    private function prepareDashboardData(array $qualityData, array $templateData): array
    {
        // Quality overview
        $avgQualityScore = $qualityData['stats']['quality_checks']['avg_quality_score'] ?? 0;

        // Score distribution (0-49 low, 50-79 medium, 80+ high)
        $scoreDistribution = [
            'low' => 0,      // 0-49
            'medium' => 0,   // 50-79
            'high' => 0,     // 80+
        ];

        // Common issues from quality data
        $commonIssues = $templateData['common_mistakes'] ?? [];

        // Template insights
        $titlePatterns = $templateData['best_title_patterns'] ?? [];
        $descriptionStructure = $templateData['best_description_structure'] ?? [];
        $advice = $templateData['advice'] ?? [];

        return [
            'overview' => [
                'avg_quality_score' => $avgQualityScore,
                'total_checks' => $qualityData['stats']['quality_checks']['total'] ?? 0,
                'total_publishes' => $qualityData['stats']['publish_decisions']['total'] ?? 0,
                'success_rate' => $qualityData['stats']['publish_decisions']['success_rate'] ?? 0,
                'block_rate' => $qualityData['stats']['publish_decisions']['block_rate'] ?? 0,
                'override_rate' => $qualityData['stats']['publish_decisions']['override_rate'] ?? 0,
            ],
            'score_distribution' => $scoreDistribution,
            'common_issues' => array_slice($commonIssues, 0, 10), // Top 10
            'template_insights' => [
                'title_patterns' => $titlePatterns,
                'description_structure' => $descriptionStructure,
                'advice' => array_slice($advice, 0, 5), // Top 5
            ],
            'recommendations' => $qualityData['recommendations'] ?? [],
        ];
    }
}
