<?php

namespace App\Services\ActionCenter;

use App\Models\Ilan;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\SaaS\TenantContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ActionAssignmentService — Sprint 15 Phase 2 Auto-Assignment Engine.
 *
 * Implements three assignment strategies for Action Center Gorev records:
 *  1. Owner-assigned: listing owner (for ilan-specific tasks)
 *  2. Round-robin: cycle through available danisman (for lead/SLA tasks)
 *  3. Workload-balanced: least-loaded agent (for reservation tasks)
 *
 * Each strategy is tenant-scoped — no cross-tenant assignment possible.
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §3.2
 *
 * Usage:
 *   $service = app(ActionAssignmentService::class);
 *   $gorev = $service->autoAssign($gorev);
 */
class ActionAssignmentService
{
    /**
     * Round-robin cache TTL (seconds).
     * On each assignment, the last assigned user ID is incremented.
     * We don't need persistent ordering — just "spread evenly".
     */
    private const ROUND_ROBIN_TTL = 86400; // 24 hours

    /**
     * Cache key prefix for round-robin state.
     */
    private const RR_KEY_PREFIX = 'action_assignment.rr';

    public function __construct(
        private TenantContextService $tenantService
    ) {}

    /**
     * Auto-assign a Gorev based on its action_type.
     *
     * Strategy selection:
     *  - ilan_* actions   → owner-assigned (ilan sahibi)
     *  - contact_lead_*   → round-robin (available danisman)
     *  - hazirlik/temizlik/kontrol/havuz/bahce → workload-balanced (cleaner role)
     *  - process_payout   → workload-balanced (admin/finance role)
     *  - *_matching_check, re_evaluate  → system (no assignment)
     *  - post_stay_inspection → workload-balanced (cleaner)
     *  - review_publication → owner-assigned (ilan sahibi)
     *
     * @param Gorev $gorev
     * @return Gorev Updated Gorev (assigned or unchanged)
     */
    public function autoAssign(Gorev $gorev): Gorev
    {
        $tenantId = $gorev->tenant_id ?? $this->tenantService->getTenantId();
        $actionType = $gorev->gorev_tipi ?? $this->extractActionType($gorev);

        $assigneeId = match (true) {
            // Owner-assigned: ilan-specific tasks
            $this->isIlanOwnerTask($actionType) => $this->resolveIlanOwnerId($gorev),

            // Round-robin: lead contact SLA tasks
            $this->isLeadContactTask($actionType) => $this->roundRobinAssign($tenantId, $actionType),

            // Workload-balanced: operational/reservation tasks
            $this->isOperationalTask($actionType) => $this->assignByWorkload($tenantId, $this->resolveRoleForTask($actionType)),

            // Review tasks: ilan owner
            $this->isReviewTask($actionType) => $this->resolveIlanOwnerId($gorev),

            // System tasks (matching, evaluation): no auto-assignment
            default => null,
        };

        if ($assigneeId === null) {
            Log::debug('ActionAssignmentService: no assignee found', [
                'gorev_id' => $gorev->id,
                'action_type' => $actionType,
            ]);
            return $gorev;
        }

        Log::info('ActionAssignmentService: auto-assigning', [
            'gorev_id' => $gorev->id,
            'assignee_id' => $assigneeId,
            'strategy' => $this->resolveStrategy($actionType),
            'action_type' => $actionType,
        ]);

        return $this->assignToUser($gorev, $assigneeId);
    }

    /**
     * Round-robin assignment — cycles through available agents for a tenant.
     *
     * Uses a cache key per tenant+actionType to track the last assigned user ID,
     * then selects the next eligible agent in rotation.
     *
     * @param int $tenantId
     * @param string $actionType
     * @return int|null User ID of next agent, or null if no agents available
     */
    public function roundRobinAssign(int $tenantId, string $actionType): ?int
    {
        $agents = $this->getAvailableAgents($tenantId, 'danisman');
        if ($agents->isEmpty()) {
            return null;
        }

        $cacheKey = self::RR_KEY_PREFIX . ".{$tenantId}.{$actionType}";
        $lastAssignedId = (int) Cache::get($cacheKey, 0);

        // Find next agent after lastAssignedId
        $sorted = $agents->sortBy('id')->values();
        $next = $sorted->firstWhere('id', '>', $lastAssignedId)
            ?? $sorted->first(); // wrap around to first

        if ($next) {
            Cache::put($cacheKey, $next->id, self::ROUND_ROBIN_TTL);
            return $next->id;
        }

        return null;
    }

    /**
     * Workload-balanced assignment — assigns to the agent with the fewest open Gorev.
     *
     * @param int $tenantId
     * @param string $role Role slug (danisman, temizlik, admin)
     * @return int|null User ID of least-loaded agent, or null if none available
     */
    public function assignByWorkload(int $tenantId, string $role): ?int
    {
        $agents = $this->getAvailableAgents($tenantId, $role);
        if ($agents->isEmpty()) {
            return null;
        }

        // Count open Gorev per agent
        $openCounts = Gorev::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('gorev_durumu', ['tamamlandi', 'iptal'])
            ->whereNotNull('atanan_user_id')
            ->where('atanan_user_id', '>', 0)
            ->select('atanan_user_id', DB::raw('COUNT(*) as open_count'))
            ->groupBy('atanan_user_id')
            ->pluck('open_count', 'atanan_user_id'); // id => count

        // Find agent with minimum open tasks
        $candidate = $agents->sortBy(function (User $agent) use ($openCounts) {
            return $openCounts->get($agent->id, 0);
        })->first();

        return $candidate?->id;
    }

    /**
     * Get available agents for a tenant and role.
     *
     * Filters to active, non-deleted users within the tenant.
     * Uses both Spatie Permission system and legacy role_id FK.
     *
     * @param int $tenantId
     * @param string $role Role slug: danisman | temizlik | admin
     * @return Collection<User>
     */
    public function getAvailableAgents(int $tenantId, string $role): Collection
    {
        $query = User::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->filter(fn(User $user) => $user->hasRole($role));
    }

    /**
     * Assign a Gorev to a specific user and update lifecycle timestamp.
     *
     * @param Gorev $gorev
     * @param int $userId
     * @return Gorev
     */
    public function assignToUser(Gorev $gorev, int $userId): Gorev
    {
        $gorev->update([
            'atanan_user_id' => $userId,
            'assigned_at' => now(),
        ]);

        return $gorev->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────

    private function extractActionType(Gorev $gorev): ?string
    {
        return $gorev->source_event
            ? class_basename($gorev->source_event)
            : $gorev->gorev_tipi;
    }

    private function isIlanOwnerTask(string $actionType): bool
    {
        return in_array($actionType, [
            'ilan_foto_yukle',
            'ilan_aciklama_yaz',
            'ilan_fiyatlandir',
            'ilan_hazirlama',
            'review_publication_decision',
            'review_ai_description',
        ], true);
    }

    private function isLeadContactTask(string $actionType): bool
    {
        return in_array($actionType, [
            'contact_lead_sla',
            'contact_lead',
            'contact_assigned_lead',
        ], true);
    }

    private function isOperationalTask(string $actionType): bool
    {
        return in_array($actionType, [
            'hazirlik',
            'temizlik',
            'kontrol',
            'havuz',
            'bahce',
            'cancel_readiness',
            'post_stay_inspection',
            'process_payout',
            'musteri_takibi',
        ], true);
    }

    private function isReviewTask(string $actionType): bool
    {
        return in_array($actionType, [
            'review_publication_decision',
            'review_ai_description',
        ], true);
    }

    private function resolveIlanOwnerId(Gorev $gorev): ?int
    {
        if (!$gorev->ilan_id) {
            return null;
        }

        $ilan = Ilan::withoutGlobalScopes()
            ->where('id', $gorev->ilan_id)
            ->where('tenant_id', $gorev->tenant_id)
            ->first(['danisman_id', 'tenant_id']);

        if ($ilan && $ilan->danisman_id > 0) {
            return (int) $ilan->danisman_id;
        }

        // Fallback: find admin in same tenant
        $query = User::query()
            ->where('tenant_id', $ilan?->tenant_id ?? $gorev->tenant_id)
            ->whereNull('deleted_at');

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->filter(fn(User $u) => $u->hasRole('admin'))
            ->sortBy('id')
            ->first()?->id;
    }

    private function resolveRoleForTask(string $actionType): string
    {
        return match ($actionType) {
            'hazirlik', 'temizlik', 'kontrol', 'havuz', 'bahce', 'cancel_readiness', 'post_stay_inspection'
                => 'temizlik',
            'process_payout'
                => 'admin',
            default => 'danisman',
        };
    }

    private function resolveStrategy(string $actionType): string
    {
        return match (true) {
            $this->isIlanOwnerTask($actionType) => 'owner',
            $this->isLeadContactTask($actionType) => 'round-robin',
            $this->isOperationalTask($actionType) => 'workload',
            default => 'none',
        };
    }
}
