<?php

namespace App\Services\ActionCenter;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Facades\Log;

/**
 * AIRecommendationManagementService — Sprint 16 Phase 1.
 *
 * Thin-service layer for AI recommendation CRUD operations.
 * Controller'dan taşınan logic burada yaşar — controller sadece
 * validate + delegate yapar.
 *
 * Exposes two operation groups:
 *  - explain: read-only Gorev explainability
 *  - approval: human-in-the-loop approve/reject state transitions
 */
class AIRecommendationManagementService
{
    public function __construct(
        private ActionExplainabilityService $explainability,
        private AIRecommendationRecorder   $recorder,
        private TenantContextService       $tenantService,
    ) {}

    /**
     * Full explainability payload for a Gorev.
     *
     * @return array{
     *   success: bool,
     *   data?: array,
     *   error?: string,
     *   http_status: int
     * }
     */
    public function explain(int $gorevId, int $tenantId): array
    {
        $gorev = Gorev::withoutTenant()
            ->where('id', $gorevId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$gorev) {
            return [
                'success'   => false,
                'error'     => 'Görev bulunamadı.',
                'http_status' => 404,
            ];
        }

        return [
            'success'   => true,
            'data'      => $this->explainability->explain($gorev),
            'http_status' => 200,
        ];
    }

    /**
     * Approve an AI recommendation Gorev.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: Gorev,
     *   error?: string,
     *   http_status: int
     * }
     */
    public function approve(int $gorevId, int $tenantId, int $approverUserId): array
    {
        $gorev = $this->findGorevOrFail($gorevId, $tenantId);
        if (!$gorev) {
            return ['success' => false, 'message' => 'Görev bulunamadı.', 'http_status' => 404];
        }

        if (!$gorev->onayBekliyorMu()) {
            return [
                'success'   => false,
                'message'   => 'Bu görev AI onayı beklemiyor durumunda değil.',
                'http_status' => 422,
            ];
        }

        $approved = $this->recorder->approveRecommendation($gorevId, $approverUserId);
        if (!$approved) {
            return [
                'success'   => false,
                'message'   => 'Onay işlemi başarısız oldu.',
                'http_status' => 500,
            ];
        }

        return [
            'success'   => true,
            'message'   => 'AI önerisi onaylandı. Görev bekleyenler listesine eklendi.',
            'data'      => Gorev::withoutTenant()->find($gorevId),
            'http_status' => 200,
        ];
    }

    /**
     * Reject an AI recommendation Gorev.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: Gorev,
     *   error?: string,
     *   http_status: int
     * }
     */
    public function reject(int $gorevId, int $tenantId, int $rejecterUserId, ?string $reason): array
    {
        $gorev = $this->findGorevOrFail($gorevId, $tenantId);
        if (!$gorev) {
            return ['success' => false, 'message' => 'Görev bulunamadı.', 'http_status' => 404];
        }

        if (!$gorev->onayBekliyorMu()) {
            return [
                'success'   => false,
                'message'   => 'Bu görev AI onayı beklemiyor durumunda değil.',
                'http_status' => 422,
            ];
        }

        $rejected = $this->recorder->rejectRecommendation($gorevId, $rejecterUserId, $reason);
        if (!$rejected) {
            return [
                'success'   => false,
                'message'   => 'Reddetme işlemi başarısız oldu.',
                'http_status' => 500,
            ];
        }

        return [
            'success'   => true,
            'message'   => 'AI önerisi reddedildi.',
            'data'      => Gorev::withoutTenant()->find($gorevId),
            'http_status' => 200,
        ];
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function findGorevOrFail(int $id, int $tenantId): ?Gorev
    {
        return Gorev::withoutTenant()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
