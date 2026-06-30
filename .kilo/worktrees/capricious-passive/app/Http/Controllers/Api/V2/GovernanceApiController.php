<?php

namespace App\Http\Controllers\Api\V2;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Modules\GovernanceCore\Contracts\GovernanceEngineInterface;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Governance API Controller (V2)
 *
 * Sprint 20: Enterprise-ready governance endpoints.
 *
 * Principles:
 * - Tenant-first (X-Tenant-ID header mandatory)
 * - Read-only + DRAFT-only operations
 * - ACTIVE version immutable via API
 * - Deterministic responses (cacheable)
 * - p95 < 200ms for all endpoints
 *
 * Auth: sanctum + tenant.validate middleware
 */
class GovernanceApiController extends Controller
{
    public function __construct(
        private readonly GovernanceEngineInterface $governance
    ) {}

    /**
     * GET /api/v2/governance/health
     *
     * Returns active version + health score for tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function health(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        try {
            $activeVersion = $this->governance->getActiveVersion($tenantId);
            $healthScore = $this->calculateHealthScore($tenantId);
            $driftSummary = $this->governance->detectDrift($tenantId);

            return ResponseService::success([
                'tenant_id' => $tenantId,
                'active_version' => $activeVersion, // context7-ignore
                'health_score' => $healthScore,
                'drift_count' => count($driftSummary['drifts'] ?? []),
                'last_incident' => $this->getLastIncident($tenantId),
            ]);

        } catch (\App\Exceptions\CriticalGovernanceException $e) {
            return ResponseService::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/governance/risk
     *
     * Returns tenant risk analytics (heatmap + maturity).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function riskAnalytics(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        try {
            $analytics = $this->governance->getTenantRiskAnalytics($tenantId);
            $maturity = $this->governance->getMaturityScore($tenantId);

            return ResponseService::success([
                'tenant_id' => $tenantId,
                'risk_analytics' => $analytics,
                'maturity_score' => $maturity,
                'generated_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return ResponseService::error('Risk analytics unavailable: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/governance/drift/detect
     *
     * Triggers drift detection for tenant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detectDrift(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        try {
            $driftResult = $this->governance->detectDrift($tenantId);

            return ResponseService::success([
                'tenant_id' => $tenantId,
                'drift_summary' => [
                    'value_drifts' => count($driftResult['drifts'] ?? []),
                    'shadow_missing' => count($driftResult['shadow_missing'] ?? []),
                    'ungoverned_records' => count($driftResult['ungoverned'] ?? []),
                ],
                'details' => $driftResult,
                'detected_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return ResponseService::error('Drift detection failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/governance/simulate
     *
     * Simulates activation of DRAFT version (dry-run).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function simulateActivation(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        $validator = Validator::make($request->all(), [
            'version_hash' => 'required|string|size:64', // SHA256 hash
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors()->first(), 422);
        }

        try {
            $impact = $this->governance->simulateActivation($tenantId, $request->input('version_hash'));

            return ResponseService::success([
                'tenant_id' => $tenantId,
                'simulation_result' => $impact,
                'simulated_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return ResponseService::error('Simulation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/governance/export
     *
     * Exports governance timeline as JSON with hash chain.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $tenantId = $this->extractTenantId($request);

        $validator = Validator::make($request->all(), [
            'since' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors()->first(), 422);
        }

        try {
            $since = $request->input('since') ? new \DateTime($request->input('since')) : null;
            $filePath = $this->governance->exportTimeline($tenantId, $since);

            return ResponseService::success([
                'tenant_id' => $tenantId,
                'export_file' => basename($filePath),
                'download_url' => route('governance.download', ['file' => basename($filePath)]),
                'exported_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return ResponseService::error('Export failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/governance/verify
     *
     * Verifies integrity of exported governance timeline.
     * Public endpoint (no auth required).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyExport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors()->first(), 422);
        }

        try {
            $filePath = $request->file('file')->getRealPath();
            $isValid = $this->governance->verifyExport($filePath);

            return ResponseService::success([
                'verification_result' => $isValid ? 'VALID' : 'INVALID',
                'verified_at' => now()->toIso8601String(),
            ]);

        } catch (\InvalidArgumentException $e) {
            return ResponseService::error('Invalid export format: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            return ResponseService::error('Verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Extract tenant ID from request header.
     *
     * @param Request $request
     * @return string
     * @throws \App\Exceptions\CriticalGovernanceException
     */
    private function extractTenantId(Request $request): string
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            abort(403, 'X-Tenant-ID header is required');
        }

        // Validate tenant matches authenticated user's tenant
        if (auth()->check() && auth()->user()->tenant_id !== $tenantId) {
            abort(403, 'Tenant ID mismatch');
        }

        return $tenantId;
    }

    /**
     * Calculate health score for tenant.
     *
     * @param string $tenantId
     * @return int Health score (0-100)
     */
    private function calculateHealthScore(string $tenantId): int
    {
        $maturity = $this->governance->getMaturityScore($tenantId);
        $driftHistory = $this->governance->getDriftHistory($tenantId, 10);

        $recentDriftCount = count(array_filter($driftHistory, function ($incident) {
            return $incident['detected_at'] >= now()->subDays(7)->toIso8601String();
        }));

        // Health = Maturity - Recent Drift Penalty
        $healthScore = $maturity['maturity_skoru'] - ($recentDriftCount * 3);

        return max(0, min(100, $healthScore));
    }

    /**
     * Get last incident for tenant.
     *
     * @param string $tenantId
     * @return array|null
     */
    private function getLastIncident(string $tenantId): ?array
    {
        $driftHistory = $this->governance->getDriftHistory($tenantId, 1);
        return $driftHistory[0] ?? null;
    }
}
