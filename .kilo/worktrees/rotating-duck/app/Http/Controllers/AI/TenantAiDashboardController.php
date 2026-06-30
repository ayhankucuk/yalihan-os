<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\Reporting\AiUsageReportService;
use App\Application\Shared\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;

/**
 * 🛡️ SaaS Dashboard Controller
 * Provides usage analytics for the authenticated tenant.
 */
class TenantAiDashboardController extends Controller
{
    public function __construct(
        protected AiUsageReportService $reportService,
        protected TenantContextResolver $contextResolver
    ) {}

    /**
     * Get a summary of AI usage for the current tenant.
     */
    public function index(): JsonResponse
    {
        $context = $this->contextResolver->resolve();
        $tenantId = $context->tenantId;

        return response()->json([
            'daily_usage' => $this->reportService->getDailyUsage($tenantId),
            'features' => $this->reportService->getFeatureUsageBreakdown($tenantId),
            'providers' => $this->reportService->getProviderStats($tenantId),
        ]);
    }

    /**
     * Get detailed logs for the current tenant (paginated).
     */
    public function logs(): JsonResponse
    {
        $context = $this->contextResolver->resolve();
        
        $logs = \App\Models\AiLog::where('tenant_id', $context->tenantId)
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
