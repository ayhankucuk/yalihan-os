<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\ActionCenter\ActionAssignmentService;
use App\Services\ActionCenter\ActionCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * ActionCenterController — Sprint 15 Phase 2 API.
 *
 * Endpoints for the Action Center dashboard and task management.
 * All endpoints are tenant-scoped — requests without a valid tenant
 * session return 403.
 *
 * Routes are defined in routes/api/v1/action-center.php
 *
 * @see docs/architecture/sprint-15-action-center-architecture.md
 */
class ActionCenterController extends Controller
{
    public function __construct(
        private ActionCenterService $actionCenter,
        private ActionAssignmentService $assignmentService
    ) {}

    /**
     * GET /api/v1/action-center/dashboard
     *
     * Returns the priority action queue for the authenticated user.
     * Combines system-generated Gorev records from ActionCenterService
     * with AdvisorCommandCenter AI-generated actions.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Priority queue: open tasks sorted by priority score
        $queue = $this->actionCenter->getActionQueue($tenantId, [
            'assigned_to' => $user->id,
        ]);
        $overdue = $this->actionCenter->getOverdueActions($tenantId);

        // Unassigned tasks for this tenant
        $unassigned = $this->actionCenter->getActionQueue($tenantId, [
            'durum' => 'bekliyor',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'my_queue' => [
                    'items' => $queue->items(),
                    'total' => $queue->total(),
                    'per_page' => $queue->perPage(),
                    'current_page' => $queue->currentPage(),
                ],
                'overdue' => [
                    'items' => $overdue->toArray(),
                    'count' => $overdue->count(),
                ],
                'unassigned' => [
                    'items' => $unassigned->items(),
                    'count' => $unassigned->total(),
                ],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/action-center/tasks
     *
     * Paginated list of all Gorev records for the tenant.
     * Supports filtering by: durum, oncelik, gorev_tipi, assigned_to, overdue.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'durum' => ['nullable', Rule::in(['bekliyor', 'devam_ediyor', 'tamamlandi', 'iptal', 'beklemede'])],
            'oncelik' => ['nullable', Rule::in(['acil', 'yuksek', 'normal', 'dusuk'])],
            'gorev_tipi' => ['nullable', 'string', 'max:50'],
            'assigned_to' => ['nullable', 'integer'],
            'overdue' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = array_filter([
            'durum' => $validated['durum'] ?? null,
            'oncelik' => $validated['oncelik'] ?? null,
            'gorev_tipi' => $validated['gorev_tipi'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($validated['overdue'])) {
            $filters['overdue'] = true;
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $tasks = $this->actionCenter->getActionQueue($tenantId, $filters);

        return response()->json([
            'success' => true,
            'data' => $tasks->toArray(),
            'meta' => [
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/action-center/tasks/{id}
     *
     * Single Gorev detail. Must be tenant-scoped.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $task = Gorev::withoutTenant()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * PATCH /api/v1/action-center/tasks/{id}/assign
     *
     * Assign a Gorev to a user.
     * Body: { user_id: int }
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $task = Gorev::withoutTenant()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }

        // Verify target user belongs to same tenant
        $targetUser = User::where('id', $validated['user_id'])
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Atanacak kullanıcı bu işletmeye ait değil.',
            ], 422);
        }

        $updated = $this->actionCenter->assignAction($task, $targetUser->id);

        return response()->json([
            'success' => true,
            'message' => 'Görev atandı.',
            'data' => $updated,
        ]);
    }

    /**
     * PATCH /api/v1/action-center/tasks/{id}/status
     *
     * Update Gorev status (lifecycle transition).
     * Body: { durum: string, notlar?: string }
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'durum' => ['required', Rule::in(['bekliyor', 'devam_ediyor', 'tamamlandi', 'iptal', 'beklemede'])],
            'not' => ['nullable', 'string', 'max:5000'],
        ]);

        $task = Gorev::withoutTenant()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }

        // Lifecycle enforcement
        $validTransitions = $this->validTransitions($task->gorev_durumu);
        if (!in_array($validated['durum'], $validTransitions, true)) {
            return response()->json([
                'success' => false,
                'message' => "Durum geçişi izin verilmiyor: {$task->gorev_durumu} → {$validated['durum']}",
            ], 422);
        }

        $updateData = ['gorev_durumu' => $validated['durum']];

        if ($validated['durum'] === 'devam_ediyor' && !$task->started_at) {
            $updateData['started_at'] = now();
        }

        if ($validated['durum'] === 'tamamlandi') {
            $updateData['completed_at'] = now();
            $updateData['tamamlanma_yuzdesi'] = 100;
        }

        if (!empty($validated['not'])) {
            $existingNotes = $task->notlar ? json_decode($task->notlar, true) : [];
            $existingNotes[] = [
                'author_id' => $request->user()->id,
                'note' => $validated['not'],
                'created_at' => now()->toIso8601String(),
            ];
            $updateData['notlar'] = json_encode($existingNotes);
        }

        $task->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Durum güncellendi.',
            'data' => $task->fresh(),
        ]);
    }

    /**
     * GET /api/v1/action-center/stats
     *
     * Tenant-scoped statistics for the Action Center dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $query = Gorev::withoutTenant()->where('tenant_id', $tenantId);

        $total = (clone $query)->count();
        $open = (clone $query)->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor', 'beklemede'])->count();
        $completed = (clone $query)->where('gorev_durumu', 'tamamlandi')->count();
        $overdue = $this->actionCenter->getOverdueActions($tenantId)->count();

        // Priority breakdown
        $byPriority = (clone $query)
            ->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor'])
            ->selectRaw('oncelik, COUNT(*) as count')
            ->groupBy('oncelik')
            ->pluck('count', 'oncelik')
            ->toArray();

        // By type
        $byType = (clone $query)
            ->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor'])
            ->selectRaw('gorev_tipi, COUNT(*) as count')
            ->groupBy('gorev_tipi')
            ->pluck('count', 'gorev_tipi')
            ->toArray();

        // My tasks
        $userId = $request->user()->id;
        $myOpen = (clone $query)
            ->where('atanan_user_id', $userId)
            ->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'open' => $open,
                'completed' => $completed,
                'overdue' => $overdue,
                'my_open' => $myOpen,
                'by_priority' => $byPriority,
                'by_type' => $byType,
            ],
        ]);
    }

    /**
     * POST /api/v1/action-center/tasks/{id}/auto-assign
     *
     * Trigger auto-assignment for a specific Gorev using ActionAssignmentService.
     * Re-assigns based on the Gorev's action_type.
     */
    public function autoAssign(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $task = Gorev::withoutTenant()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Görev bulunamadı.'], 404);
        }

        if ($task->atanan_user_id && $task->atanan_user_id > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bu görev zaten atanmış. Önce mevcut atamayı kaldırın.',
            ], 422);
        }

        $updated = $this->assignmentService->autoAssign($task);

        return response()->json([
            'success' => true,
            'message' => $updated->atanan_user_id
                ? 'Otomatik atama başarılı.'
                : 'Otomatik atama için uygun kullanıcı bulunamadı.',
            'data' => $updated,
        ]);
    }

    /**
     * Allowed status transitions per lifecycle.
     *
     * @see docs/architecture/sprint-15-action-center-architecture.md §4
     */
    private function validTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'bekliyor' => ['devam_ediyor', 'iptal'],
            'devam_ediyor' => ['tamamlandi', 'iptal', 'beklemede'],
            'beklemede' => ['devam_ediyor', 'iptal'],
            'tamamlandi' => [],
            'iptal' => [],
            default => [],
        };
    }
}
