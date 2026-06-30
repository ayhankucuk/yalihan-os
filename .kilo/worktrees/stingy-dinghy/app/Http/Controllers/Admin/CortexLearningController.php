<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\YalihanCortex;
use App\Services\Response\ResponseService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;

/**
 * Cortex Learning Controller
 *
 * Phase E: AI Learning Loop - Read-only analytics dashboard
 *
 * Sorumluluklar:
 * - Quality check outcomes analizi (read-only)
 * - Publish decision statistics (read-only)
 * - Advisory recommendations (not auto-applied)
 *
 * Kurallar (ZORUNLU):
 * - ❌ Hiçbir ayar otomatik uygulanmaz
 * - ❌ Toggle yok, auto-apply yok
 * - ✅ İnsan kararı şart
 * - ✅ Read-only endpoint
 * - ✅ Observer mode korunur
 *
 * Endpoint: GET /admin/ai/quality-learning
 * Middleware: web + auth + verified + can:view-admin-panel + throttle:10,1
 */
class CortexLearningController extends Controller
{
    public function __construct(
        private YalihanCortex $cortex
    ) {}

    /**
     * Get quality learning analytics
     *
     * Query params:
     * - kategori_slug (optional): Filter by category
     * - days (optional, default: 30): Time window for analysis
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "filters": {...},
     *     "stats": {
     *       "quality_checks": {...},
     *       "publish_decisions": {...}
     *     },
     *     "recommendations": [
     *       {
     *         "type": "threshold", // context7-ignore
     *         "code": "LOWER_MIN_SCORE",
     *         "message": "...",
     *         "suggested_value": 70,
     *         "confidence": 0.85
     *       }
     *     ],
     *     "meta": {...}
     *   }
     * }
     */
    public function qualityLearning(Request $request)
    {
        try {
            // Validation
            $validated = $request->validate([
                'kategori_slug' => 'nullable|string|max:255',
                'days' => 'nullable|integer|min:1|max:90',
            ]);

            $filters = [
                'kategori_slug' => $validated['kategori_slug'] ?? null,
                'days' => $validated['days'] ?? 30,
            ];

            LogService::info('Quality learning analysis requested', [
                'filters' => $filters,
                'user_id' => auth()->id(),
            ]);

            // Cortex learning analysis (read-only)
            $result = $this->cortex->analyzeQualityOutcomes($filters);

            if (!$result['success']) {
                return ResponseService::error(
                    message: $result['message'] ?? 'Learning analysis failed',
                    yanitKodu: 500,
                    errors: ['learning' => $result['data']['meta']['error'] ?? 'Unknown error']
                );
            }

            return ResponseService::success(
                data: $result['data'],
                message: 'Quality learning analytics retrieved successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseService::validationError(
                errors: $e->errors(),
                message: 'Validation error'
            );
        } catch (\Exception $e) {
            LogService::error('Quality learning analysis failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], $e);

            return ResponseService::serverError(
                message: 'Learning analysis request failed, please try again.',
                exception: $e
            );
        }
    }

    /**
     * Get global quality learning stats (no filters)
     *
     * Shortcut endpoint for global dashboard view
     */
    public function globalStats(Request $request)
    {
        try {
            $days = $request->integer('days', 30);

            $result = $this->cortex->analyzeQualityOutcomes(['days' => $days]);

            if (!$result['success']) {
                return ResponseService::error(
                    message: 'Global stats retrieval failed',
                    yanitKodu: 500
                );
            }

            return ResponseService::success(
                data: $result['data'],
                message: 'Global quality stats retrieved'
            );
        } catch (\Exception $e) {
            LogService::error('Global stats retrieval failed', [
                'error' => $e->getMessage(),
            ], $e);

            return ResponseService::serverError(
                message: 'Stats retrieval failed',
                exception: $e
            );
        }
    }
}
