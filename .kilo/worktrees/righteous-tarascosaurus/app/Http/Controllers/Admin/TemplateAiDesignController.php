<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PipelineRun;
use App\Models\TemplateDesignAudit;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use App\Services\PropertyHub\TemplateAiDesignMutationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Template AI Design Controller
 *
 * Dedicated controller for the "AI ile Tasarla" wizard.
 * Produces structural template design (add, make_required, keep_optional, remove_candidates).
 *
 * Routes:
 *   POST /admin/property-hub/templates/ai-design/start   → start()
 *   GET  /admin/property-hub/templates/ai-design/{run}/poll → poll()
 *   POST /admin/property-hub/templates/ai-design/apply    → apply()
 */
class TemplateAiDesignController extends Controller
{
    public function __construct(
        protected PipelineDispatcher $dispatcher,
        protected TemplateAiDesignMutationService $mutationService,
    ) {}

    /**
     * Start design pipeline.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|min:1',
            'yayin_tipi_id' => 'required|integer|min:1',
            'scope' => 'nullable|string|in:master,category',
            'scenario' => 'required|string|in:improve_existing,short_term_rental_optimize,seo_optimized,fast_entry_minimal',
            'user_prompt' => 'nullable|string|max:1000',
            'toggles' => 'nullable|array',
            'toggles.seo_focus' => 'nullable|boolean',
            'toggles.fast_entry' => 'nullable|boolean',
            'toggles.premium_mode' => 'nullable|boolean',
            'toggles.strong_required' => 'nullable|boolean',
        ]);

        $runUuid = $this->dispatcher->dispatch(
            pipelineType: 'template_ai_design',
            inputPayload: [
                'kategori_id' => $validated['kategori_id'],
                'yayin_tipi_id' => $validated['yayin_tipi_id'],
                'scope' => $validated['scope'] ?? 'master',
                'scenario' => $validated['scenario'],
                'user_prompt' => $validated['user_prompt'] ?? null,
                'toggles' => $validated['toggles'] ?? [],
                'source' => 'ai_design_wizard',
            ],
            module: 'property_hub',
            triggeredBy: auth()->id(),
        );

        Log::channel('telemetry')->info('template_ai_design_started', [
            'event' => 'template_ai_design_started',
            'run_uuid' => $runUuid,
            'scenario' => $validated['scenario'],
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $validated['yayin_tipi_id'],
            'user_id' => auth()->id(),
            'basarili' => true,
        ]);

        $run = PipelineRun::where('run_uuid', $runUuid)->first();

        return response()->json([
            'ok' => true,
            'run_id' => $run?->id ?? $runUuid,
            'run_uuid' => $runUuid,
        ]);
    }

    /**
     * Poll design pipeline state.
     * Returns frontend-friendly contract matching the AI Design JS stepper.
     */
    public function poll(string $run): JsonResponse
    {
        // Accept both numeric ID and UUID
        $pipelineRun = PipelineRun::where(function ($q) use ($run) {
            $q->where('run_uuid', $run)
                ->orWhere('id', is_numeric($run) ? (int) $run : 0);
        })
            ->where('triggered_by', auth()->id())
            ->first();

        if (! $pipelineRun) {
            return response()->json([
                'ok' => false,
                'message' => 'Pipeline bulunamadı.',
            ], 404);
        }

        $steps = $pipelineRun->steps()
            ->whereNull('shard_key')
            ->orderBy('id')
            ->get(['adim_adi', 'adim_durumu']);

        $isTerminal = $pipelineRun->isTerminal();

        // Map pipeline_durumu → user-facing durum
        $statusMap = [
            'queued' => 'queued',
            'normalizing' => 'running',
            'validated' => 'running',
            'audit_running' => 'running',
            'fix_running' => 'running',
            'execution_running' => 'running',
            'verification_running' => 'running',
            'governing' => 'running',
            'completed' => 'completed',
            'failed' => 'failed',
            'halted' => 'halted',
            'cancelled' => 'halted',
        ];

        $pipelineStatus = $pipelineRun->pipeline_durumu->value;

        // Map step durumu → user-facing durum
        $stepStatusMap = [
            'pending' => 'pending',
            'running' => 'running',
            'completed' => 'completed',
            'failed' => 'failed',
        ];

        $response = [
            'ok' => true,
            'run_id' => $pipelineRun->id,
            'durum' => $statusMap[$pipelineStatus] ?? 'running',
            'current_stage' => $pipelineRun->mevcut_asama,
            'steps' => $steps->map(fn ($s) => [
                'step_name' => $s->adim_adi,
                'durum' => $stepStatusMap[$s->adim_durumu->value] ?? $s->adim_durumu->value,
            ])->toArray(),
        ];

        if ($isTerminal) {
            $response['decision'] = $pipelineRun->karar_aksiyonu;
            $response['decision_reason'] = $pipelineRun->karar_gerekcesi;
            $response['result'] = $pipelineRun->final_output;
            $response['duration_ms'] = $pipelineRun->durationMs();
            $response['warnings'] = [];
        }

        return response()->json($response);
    }

    /**
     * Apply design result to template.
     * Preview-first: only applies after user explicitly confirms.
     * Wraps all mutations in a DB transaction with audit log.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|min:1',
            'yayin_tipi_id' => 'required|integer|min:1',
            'scope' => 'nullable|string|in:master,category',
            'apply_mode' => 'required|string|in:full,add_only,required_only',
            'design_payload' => 'required|array',
            'design_payload.summary' => 'nullable|string',
            'design_payload.design' => 'nullable|array',
            'design_payload.design.add' => 'nullable|array',
            'design_payload.design.make_required' => 'nullable|array',
            'design_payload.design.keep_optional' => 'nullable|array',
            'design_payload.design.remove_candidates' => 'nullable|array',
            'run_uuid' => 'nullable|string|max:36',
        ]);

        $userId = auth()->id();
        $result = $this->mutationService->applyDesign($validated, $userId);

        $changes = $result['changes'];
        $beforeSnapshot = $result['before_snapshot'];
        $mode = $result['mode'];
        $auditRecord = $result['audit'];
        $yayinTipiId = (int) $validated['yayin_tipi_id'];

        $totalApplied = count($changes['added']) + count($changes['made_required']);

        // Audit log — security channel for traceability
        Log::channel('security')->info('ai_design_applied', [
            'event' => 'ai_design_applied',
            'user_id' => $userId,
            'yayin_tipi_id' => $yayinTipiId,
            'kategori_id' => $validated['kategori_id'],
            'apply_mode' => $mode,
            'before_count' => count($beforeSnapshot),
            'added' => $changes['added'],
            'made_required' => $changes['made_required'],
            'skipped' => $changes['skipped'],
            'total_applied' => $totalApplied,
        ]);

        Log::channel('telemetry')->info('template_ai_design_applied', [
            'event' => 'template_ai_design_applied',
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $yayinTipiId,
            'apply_mode' => $mode,
            'applied_count' => $totalApplied,
            'skipped_count' => count($changes['skipped']),
            'user_id' => $userId,
            'basarili' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => "{$totalApplied} öneri uygulandı.",
            'applied_count' => $totalApplied,
            'audit_id' => $auditRecord?->id,
            'changes' => $changes,
        ]);
    }

    /**
     * Rollback a previously applied design.
     * Removes added features and reverts required flags using the stored audit snapshot.
     */
    public function rollback(Request $request, int $auditId): JsonResponse
    {
        $result = $this->mutationService->rollbackDesign($auditId, (int) auth()->id());
        $audit = $result['audit'];
        $reverted = $result['reverted'];

        $totalReverted = count($reverted['removed']) + count($reverted['reverted_required']);

        Log::channel('security')->info('ai_design_rolled_back', [
            'event' => 'ai_design_rolled_back',
            'audit_id' => $audit->id,
            'yayin_tipi_id' => $audit->yayin_tipi_id,
            'user_id' => auth()->id(),
            'reverted' => $reverted,
            'total_reverted' => $totalReverted,
        ]);

        return response()->json([
            'ok' => true,
            'message' => "{$totalReverted} değişiklik geri alındı.",
            'reverted_count' => $totalReverted,
            'reverted' => $reverted,
        ]);
    }

    /**
     * List audit history for a template.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yayin_tipi_id' => 'required|integer|min:1',
        ]);

        $audits = TemplateDesignAudit::forTemplate($validated['yayin_tipi_id'])
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($audit) => [
                'id' => $audit->id,
                'apply_mode' => $audit->apply_mode,
                'run_uuid' => $audit->run_uuid,
                'changes' => $audit->changes,
                'rolled_back' => $audit->rolled_back,
                'rolled_back_at' => $audit->rolled_back_at?->toIso8601String(),
                'user_name' => $audit->user?->name,
                'created_at' => $audit->created_at->toIso8601String(),
            ]);

        return response()->json([
            'ok' => true,
            'audits' => $audits,
        ]);
    }
}
