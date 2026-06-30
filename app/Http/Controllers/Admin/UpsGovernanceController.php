<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Governance\GovernanceDashboardService;
use App\Services\Intelligence\CortexFindingService;
use App\Services\Intelligence\SabDecisionBridgeService;
use App\Services\Ups\UpsFeatureGovernanceService;
use Illuminate\Http\Request;

/**
 * UPS Feature Governance Controller — Özellik Sağlık Matrisi
 *
 * UPS = Template + Schema + Assignment Integrity System
 * Feature lifecycle, usage metrics, and health monitoring.
 *
 * Context7: Read-only reporting, no mutations
 * Governance: Integrated with SAB pipeline via GovernanceDashboardService
 */
class UpsGovernanceController extends Controller
{
    public function __construct(
        private UpsFeatureGovernanceService $governanceService,
        private GovernanceDashboardService $dashboardService,
        private CortexFindingService $findingService,
        private SabDecisionBridgeService $bridge,
    ) {}

    /**
     * Governance dashboard index
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $filters = $request->only(['lifecycle', 'aktiflik_durumu', 'orphaned', 'search']);

        $features = $this->governanceService->getUsageStats($filters);
        $summary = $this->governanceService->getSummaryReport();

        return view('admin.ups.governance.index', [
            'features' => $features,
            'summary' => $summary,
            'filters' => $filters,
            'runtimeStrip' => $this->dashboardService->getOverview(),
        ]);
    }

    /**
     * Generate SAB proposals from UPS health violations
     *
     * Scans feature matrix for: orphaned, archived-but-assigned, inactive-but-assigned
     * Creates one SAB proposal per violation category found.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateHealthProposals()
    {
        $summary = $this->governanceService->getSummaryReport();
        $created = [];

        // Orphaned features → propose cleanup review
        if ($summary['orphaned_count'] > 0) {
            $filename = $this->dashboardService->createProposal(
                'governance.feature_health',
                'update',
                ['action' => 'review_orphaned', 'count' => $summary['orphaned_count']],
                [
                    'reason' => "{$summary['orphaned_count']} feature hiçbir template'e atanmamış (orphan). İnceleme gerekli.",
                    'risk' => 'low',
                    'rule' => 'ups_orphan_check',
                ]
            );
            if ($filename) {
                $created[] = $filename;
            }
        }

        // Archived but assigned → propose deassignment
        if ($summary['archived_but_assigned'] > 0) {
            $filename = $this->dashboardService->createProposal(
                'governance.feature_health',
                'update',
                ['action' => 'deassign_archived', 'count' => $summary['archived_but_assigned']],
                [
                    'reason' => "{$summary['archived_but_assigned']} arşivlenmiş feature hala template'lere atanmış. Atama kaldırılmalı.",
                    'risk' => 'medium',
                    'rule' => 'ups_archived_assigned',
                ]
            );
            if ($filename) {
                $created[] = $filename;
            }
        }

        // Inactive but assigned → propose deactivation review
        if ($summary['inactive_but_assigned'] > 0) {
            $filename = $this->dashboardService->createProposal(
                'governance.feature_health',
                'update',
                ['action' => 'review_inactive_assigned', 'count' => $summary['inactive_but_assigned']],
                [
                    'reason' => "{$summary['inactive_but_assigned']} pasif feature hala template'lere atanmış. Durum kontrol edilmeli.",
                    'risk' => 'low',
                    'rule' => 'ups_inactive_assigned',
                ]
            );
            if ($filename) {
                $created[] = $filename;
            }
        }

        // Deprecated but assigned → propose migration plan
        if ($summary['deprecated_assigned'] > 0) {
            $filename = $this->dashboardService->createProposal(
                'governance.feature_health',
                'update',
                ['action' => 'migrate_deprecated', 'count' => $summary['deprecated_assigned']],
                [
                    'reason' => "{$summary['deprecated_assigned']} deprecated feature hala kullanımda. Migrasyon planı gerekli.",
                    'risk' => 'medium',
                    'rule' => 'ups_deprecated_assigned',
                ]
            );
            if ($filename) {
                $created[] = $filename;
            }
        }

        $message = count($created) > 0
            ? count($created) . ' SAB proposal oluşturuldu.'
            : 'Hiçbir sağlık ihlali bulunamadı — proposal oluşturulmadı.';

        // Also flow through Decision Engine pipeline for audit trail
        $findings = $this->findingService->collectFrom('ups_health');
        if (count($findings) > 0) {
            $engineResult = $this->bridge->processBatch($findings);
            $message .= sprintf(
                ' Karar Motoru: %d otomatik, %d inceleme, %d engellendi.',
                count($engineResult['auto_run']),
                $engineResult['queued'],
                $engineResult['blocked']
            );
        }

        return redirect()->route('admin.governance.feature-health')
            ->with('success', $message);
    }
}
