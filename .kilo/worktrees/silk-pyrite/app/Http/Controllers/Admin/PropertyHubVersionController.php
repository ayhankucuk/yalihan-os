<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\PropertyConfigVersion;
use App\Modules\GovernanceCore\Core\VersionActivationService;
use App\Modules\GovernanceCore\Core\VersionRollbackService;
use App\Modules\GovernanceCore\Services\RuleSetDiffService;
use App\Modules\GovernanceCore\Core\VersionStateMachine;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Actions\Admin\PropertyHub\SubmitVersionForReviewAction;
use App\Actions\Admin\PropertyHub\ApproveVersionAction;

/**
 * PropertyHub Version Controller
 *
 * Admin management for PropertyHub governance and rollback.
 */
class PropertyHubVersionController extends Controller
{
    public function __construct(
        private readonly VersionActivationService $activationService,
        private readonly VersionRollbackService $rollbackService,
        private readonly RuleSetDiffService $diffService,
        private readonly VersionStateMachine $stateMachine,
        private readonly \App\Domain\PropertyHub\Resolution\Registry\TenantConfigRegistry $tenantRegistry
    ) {}

    private function getTenantId(Request $request): string
    {
        return $request->header('X-Tenant-ID', 'SYSTEM');
    }

    /**
     * List all versions.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $tenantId = $this->getTenantId($request);
        $versions = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->withCount('rules')
            ->orderBy('created_at', 'desc') // context7-ignore
            ->paginate(15);

        $activeVersion = PropertyConfigVersion::where('tenant_id', $tenantId)
            ->where('yonetim_durumu', VersionStateMachine::DURUM_AKTIF)
            ->first();

        if (request()->wantsJson()) {
            return ResponseService::success($versions);
        }

        return view('admin.property-hub.versions.index', [
            'versions' => $versions,
            'activeVersion' => $activeVersion // context7-ignore
        ]);
    }

    /**
     * Submit version for review.
     */
    public function submit(Request $request, PropertyConfigVersion $version): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        if ($version->tenant_id !== $tenantId) { abort(403); }
        $this->checkCompromise($tenantId);
        try {
            $this->stateMachine->assertTransition($version, VersionStateMachine::DURUM_INCELEME); // context7-ignore
            app(SubmitVersionForReviewAction::class)->handle($version, VersionStateMachine::DURUM_INCELEME);

            $this->activationService->logAudit($version->id, 'submitted', Auth::id() ?? 0);

            return ResponseService::success($version, 'Versiyon incelemeye gönderildi');
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), 422);
        }
    }

    /**
     * Approve version.
     */
    public function approve(Request $request, PropertyConfigVersion $version): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        if ($version->tenant_id !== $tenantId) { abort(403); }
        $this->checkCompromise($tenantId);
        try {
            $this->stateMachine->assertTransition($version, VersionStateMachine::DURUM_ONAYLANDI); // context7-ignore
            app(ApproveVersionAction::class)->handle($version, VersionStateMachine::DURUM_ONAYLANDI);

            $this->activationService->logAudit($version->id, 'approved', Auth::id() ?? 0);

            return ResponseService::success($version, 'Versiyon onaylandı');
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), 422);
        }
    }

    /**
     * Activate version.
     */
    public function activate(Request $request, PropertyConfigVersion $version): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        if ($version->tenant_id !== $tenantId) { abort(403); }
        $this->checkCompromise($tenantId);
        try {
            $this->activationService->activate($version, Auth::id() ?? 0);
            return ResponseService::success($version, 'Versiyon başarıyla aktive edildi ve Circuit Breaker sıfırlandı');
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), 422);
        }
    }

    /**
     * Rollback to version.
     */
    public function rollback(Request $request, PropertyConfigVersion $version): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        if ($version->tenant_id !== $tenantId) { abort(403); }
        $this->checkCompromise($tenantId);
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->rollbackService->rollback($version, Auth::id() ?? 0, $request->reason);
            return ResponseService::success($version, 'Rollback başarıyla tamamlandı');
        } catch (\Exception $e) {
            return ResponseService::error($e->getMessage(), 422);
        }
    }

    /**
     * Check if system is compromised and throw exception.
     */
    private function checkCompromise(string $tenantId): void
    {
        if ($this->tenantRegistry->isSystemCompromised($tenantId)) {
            throw new \App\Exceptions\CriticalGovernanceException("CONTEXT7 HARD LOCK: System is compromised for tenant [{$tenantId}]. All configuration operations are disabled.");
        }
    }

    /**
     * Get diff between two versions.
     */
    public function diff(PropertyConfigVersion $version, PropertyConfigVersion $other): JsonResponse|\Illuminate\View\View
    {
        $diff = $this->diffService->compare($other, $version);

        if (request()->wantsJson()) {
            return ResponseService::success($diff);
        }

        return view('admin.property-hub.versions.diff', [
            'version' => $version,
            'other' => $other,
            'diff' => $diff
        ]);
    }
}
