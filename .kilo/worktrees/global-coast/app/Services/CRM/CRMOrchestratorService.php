<?php

namespace App\Services\CRM;

use App\Models\Kisi;
use App\Repositories\KisiRepository;
use App\Repositories\KisiEtkilesimRepository;
use App\Services\CRM\KisiScoringService;
use Illuminate\Support\Collection;

/**
 * 🛰️ CRMOrchestrator
 *
 * @governance PHASE4B_SERVICE_GOVERNANCE
 * @governance AGGREGATION_BOUNDARY
 * @refactored 2026-05-12
 * @reason Dashboard aggregation service migrated to Repository Kernel with tenant-scoped aggregations
 */
class CRMOrchestratorService
{
    public function __construct(
        private readonly KisiRepository $kisiRepository,
        private readonly KisiEtkilesimRepository $etkilesimRepository,
        private readonly KisiScoringService $scoringService
    ) {}

    /**
     * Get dashboard statistics
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregations
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getDashboardStats(): array
    {
        $kisiStats = $this->kisiRepository->getDashboardStats(auth()->user());

        return [
            'total_customers'         => $kisiStats['total_customers'],
            'active_customers'        => $kisiStats['active_customers'],
            'pending_followups'       => $this->etkilesimRepository->getPendingFollowupsCount(30, auth()->user()),
            'today_activities'        => $this->etkilesimRepository->getTodayActivitiesCount(auth()->user()),
            'revenue_forecast'        => null,
            'high_priority_followups' => $this->etkilesimRepository->getHighPriorityFollowupsCount(7, auth()->user()),
        ];
    }

    /**
     * Get customer segments
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregations
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getCustomerSegments(): Collection
    {
        return $this->kisiRepository->getCustomerSegments(auth()->user());
    }

    /**
     * Get recent activities
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped queries
     */
    public function getRecentActivities(int $limit = 15): Collection
    {
        return $this->etkilesimRepository->getRecentActivities($limit, auth()->user())
            ->map(fn ($e) => [
                'kisi' => [
                    'id'    => $e->kisi->id ?? 0,
                    'ad'    => $e->kisi->ad ?? '',
                    'soyad' => $e->kisi->soyad ?? '',
                ],
                'aciklama'       => $e->notlar ?? '',
                'aktivite_tipi'  => $e->tip ?? 'Genel',
                'aktivite_tarihi' => $e->etkilesim_tarihi ?? $e->created_at,
                'status'          => 'Tamamlandı',
            ]);
    }

    /**
     * Get pipeline stages
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregations
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getPipelineStages(): array
    {
        return $this->kisiRepository->getPipelineStages(auth()->user());
    }

    /**
     * Get lost pipeline count
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregations
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getLostPipelineCount(): int
    {
        return $this->kisiRepository->getLostPipelineCount(auth()->user());
    }

    public function updatePipelineStage(Kisi $kisi, $stage): Kisi
    {
        return $this->scoringService->updatePipelineStage($kisi, $stage);
    }

    public function updateSegment(Kisi $kisi, string $segment): void
    {
        $this->scoringService->updateSegment($kisi, $segment);
    }

    public function recalculateAllScores(): void
    {
        $this->scoringService->recalculateAllScores();
    }

    /**
     * Get lead source analytics
     *
     * ✅ REFACTORED: Phase 4B - Service Governance Alignment
     * Now uses Repository Kernel with tenant-scoped aggregations
     *
     * @governance AGGREGATION_BOUNDARY
     */
    public function getLeadSourceAnalytics(): Collection
    {
        return $this->kisiRepository->getLeadSourceAnalytics(auth()->user());
    }
}
