<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PipelineRun;
use App\Services\AI\Copilot\Pipeline\PipelineDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Template AI Pipeline Controller
 *
 * Bridges the Copilot Pipeline Engine with the Template AI system.
 * Start → returns run_uuid immediately (async pipeline).
 * Poll → returns current pipeline state + results when complete.
 */
class TemplateAiPipelineController extends Controller
{
    public function __construct(
        protected PipelineDispatcher $dispatcher,
    ) {}

    /**
     * Start a Template AI pipeline run.
     * Returns run_uuid for frontend polling.
     *
     * POST /admin/property-hub/templates/ai-pipeline/start
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|integer|min:1',
            'yayin_tipi_id' => 'required|integer|min:1',
            'pipeline_type' => 'required|string|in:template_suggest,template_generate,template_gap_analysis,template_ai_design',
            'description' => 'nullable|string|max:500',
            'scenario' => 'nullable|string|in:improve_existing,short_term_rental_optimize,seo_optimized,fast_entry_minimal',
            'toggles' => 'nullable|array',
            'toggles.seo_focus' => 'nullable|boolean',
            'toggles.fast_entry' => 'nullable|boolean',
            'toggles.premium_mode' => 'nullable|boolean',
            'toggles.filterable_boost' => 'nullable|boolean',
            'toggles.required_boost' => 'nullable|boolean',
        ]);

        $runUuid = $this->dispatcher->dispatch(
            pipelineType: $validated['pipeline_type'],
            inputPayload: [
                'kategori_id' => $validated['kategori_id'],
                'yayin_tipi_id' => $validated['yayin_tipi_id'],
                'description' => $validated['description'] ?? null,
                'scenario' => $validated['scenario'] ?? null,
                'toggles' => $validated['toggles'] ?? [],
                'source' => 'template_editor',
            ],
            module: 'property_hub',
            triggeredBy: auth()->id(),
        );

        Log::channel('telemetry')->info('template_ai_pipeline_started', [
            'event' => 'template_ai_pipeline_started',
            'run_uuid' => $runUuid,
            'pipeline_type' => $validated['pipeline_type'],
            'kategori_id' => $validated['kategori_id'],
            'yayin_tipi_id' => $validated['yayin_tipi_id'],
            'user_id' => auth()->id(),
            'basarili' => true,
        ]);

        return response()->json([
            'success' => true,
            'run_uuid' => $runUuid,
        ]);
    }

    /**
     * Poll pipeline run state.
     * Returns current step, progress, and final results when complete.
     *
     * GET /admin/property-hub/templates/ai-pipeline/{runUuid}/poll
     */
    public function poll(string $runUuid): JsonResponse
    {
        $run = PipelineRun::where('run_uuid', $runUuid)
            ->where('triggered_by', auth()->id())
            ->first();

        if (!$run) {
            return response()->json([
                'success' => false,
                'message' => 'Pipeline bulunamadı.',
            ], 404);
        }

        $steps = $run->steps()
            ->orderBy('id')
            ->get(['adim_adi', 'adim_durumu', 'shard_key', 'updated_at']);

        $response = [
            'success' => true,
            'pipeline_durumu' => $run->pipeline_durumu->value,
            'mevcut_asama' => $run->mevcut_asama,
            'completed_steps' => $run->completed_steps,
            'total_steps' => $run->total_steps,
            'is_terminal' => $run->isTerminal(),
            'steps' => $steps->map(fn ($s) => [
                'adim_adi' => $s->adim_adi,
                'adim_durumu' => $s->adim_durumu->value,
                'shard_key' => $s->shard_key,
            ])->toArray(),
        ];

        // Include results when pipeline is terminal
        if ($run->isTerminal()) {
            $response['karar_aksiyonu'] = $run->karar_aksiyonu;
            $response['karar_gerekcesi'] = $run->karar_gerekcesi;
            $response['final_output'] = $run->final_output;
            $response['duration_ms'] = $run->durationMs();
        }

        return response()->json($response);
    }
}
