<?php

namespace App\Http\Controllers\Admin\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\Monetization\AiUsageQueryService;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Http\Request;

/**
 * AI Usage Dashboard Controller
 * 🛡️ Phase 12 Sprint 3 + Sprint 12.5 Hotfix
 *
 * SAB §10.1: Thin Controller — Controller sadece validate + delegate
 * SAB §12.4: Tenant Isolation — All queries delegated to Service layer
 * Context7: Kanonik isimlendirme (aktiflik_kodu, snake_case)
 */
class AiUsageController extends Controller
{
    public function __construct(
        private readonly AiUsageQueryService $queryService,
        private readonly TenantContextResolver $tenantResolver
    ) {}

    /**
     * Display AI usage dashboard
     * Route: GET /admin/ai/usage
     *
     * SAB §10.1: Controller sadece delegate eder
     */
    public function index(Request $request)
    {
        // Controller sadece servise delege eder
        $data = $this->queryService->getDashboardData();

        return view('admin.ai.usage-dashboard', [
            'credit_balance' => $data['credit_balance'],
            'feature_breakdown' => $data['feature_breakdown'],
            'daily_trend' => $data['daily_trend'],
            'top_consumers' => $data['top_consumers'],
            'monthly_usage' => $data['monthly_usage'],
            'projected_monthly_usage' => $data['projected_monthly_usage'],
            'tenant' => $data['tenant'],
        ]);
    }

    /**
     * Export usage data to CSV
     * Route: GET /admin/ai/usage/export
     *
     * Context7: aktiflik_kodu (kanonik isim)
     */
    public function export(Request $request)
    {
        $tenant = $this->tenantResolver->getTenant();

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Delegate to service
        $logs = $this->queryService->getLogsForExport($tenant->id, $startDate, $endDate);

        $filename = "ai-usage-{$tenant->id}-{$startDate}-to-{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date',
                'Feature',
                'User',
                'Aktiflik Kodu', // Context7: Turkish canonical name
                'Duration (ms)',
                'Provider',
                'Model'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->feature_key ?? 'N/A',
                    $log->user->name ?? 'System',
                    $log->aktiflik_kodu ?? 'N/A', // Context7: aktiflik_kodu (kanonik isim)
                    $log->duration_ms ?? 0,
                    $log->provider ?? 'N/A',
                    $log->model ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get real-time usage stats (AJAX endpoint)
     * Route: GET /admin/ai/usage/stats
     *
     * SAB §10.1: Controller sadece delegate eder
     * Context7: snake_case field names
     */
    public function stats(Request $request)
    {
        // Controller sadece servise delege eder
        return response()->json(
            $this->queryService->getRealtimeStats()
        );
    }
}
