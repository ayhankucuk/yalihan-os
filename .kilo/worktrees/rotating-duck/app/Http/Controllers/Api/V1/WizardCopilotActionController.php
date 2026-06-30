<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CopilotActionLog;
use App\Services\Wizard\CopilotListingGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WizardCopilotActionController extends Controller
{
    public function __construct(
        private readonly CopilotListingGenerator $generator,
    ) {}

    /**
     * POST /admin/copilot/actions
     *
     * Generate copilot actions for current wizard form state.
     * Returns executable action list with types, targets, values, and confidence.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'form_state' => 'required|array',
            'form_state.ana_kategori_id' => 'nullable|integer',
            'form_state.yayin_tipi_id' => 'nullable|integer',
            'form_state.il_id' => 'nullable|integer',
            'form_state.ilce_id' => 'nullable|integer',
            'form_state.mahalle_id' => 'nullable|integer',
            'form_state.baslik' => 'nullable|string|max:255',
            'form_state.aciklama' => 'nullable|string',
            'form_state.fiyat' => 'nullable|numeric',
            'form_state.fiyat_gosterim_modu' => 'nullable|string|in:exact,starting_from,on_request,hidden',
            'form_state.baslangic_fiyati' => 'nullable|numeric|min:0',
            'form_state.fiyat_notu' => 'nullable|string|max:255',
            'form_state.alan_m2' => 'nullable|numeric',
            'form_state.features' => 'nullable|array',
            'mode' => 'nullable|string|in:suggest,auto_run,full_generate',
            'ilan_id' => 'nullable|integer',
        ]);

        $formState = $validated['form_state'];
        $mode = $validated['mode'] ?? 'suggest';
        $ilanId = $validated['ilan_id'] ?? null;

        $result = $this->generator->generate($formState, $mode);

        // Log the action generation
        CopilotActionLog::create([
            'action_type' => $mode === 'full_generate' ? 'full_listing_generate' : 'multi_field_apply',
            'user_id' => Auth::id(),
            'ilan_id' => $ilanId,
            'main_category_id' => $formState['ana_kategori_id'] ?? null,
            'listing_type_id' => $formState['yayin_tipi_id'] ?? null,
            'request_payload' => $formState,
            'response_payload' => $result,
            'aksiyon_durumu' => 'preview',
            'confidence_score' => $result['confidence'] ?? null,
            'duration_ms' => $result['meta']['duration_ms'] ?? null,
        ]);

        return response()->json($result);
    }

    /**
     * POST /admin/copilot/actions/apply
     *
     * Mark an action as applied and record the diff.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id' => 'required|integer|exists:copilot_action_logs,id',
            'applied_fields' => 'required|array',
            'diff_snapshot' => 'nullable|array',
        ]);

        $log = CopilotActionLog::where('id', $validated['log_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($log->isApplied()) {
            return response()->json([
                'basarili' => false,
                'hata_mesaji' => 'Bu aksiyon zaten uygulanmış.',
            ], 422);
        }

        $log->markApplied($validated['applied_fields']);

        if (!empty($validated['diff_snapshot'])) {
            $log->update(['diff_snapshot' => $validated['diff_snapshot']]);
        }

        return response()->json([
            'basarili' => true,
            'log_id' => $log->id,
            'aksiyon_durumu' => 'applied',
        ]);
    }

    /**
     * POST /admin/copilot/actions/undo
     *
     * Undo a previously applied action.
     */
    public function undo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id' => 'required|integer|exists:copilot_action_logs,id',
        ]);

        $log = CopilotActionLog::where('id', $validated['log_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!$log->isApplied()) {
            return response()->json([
                'basarili' => false,
                'hata_mesaji' => 'Yalnızca uygulanmış aksiyonlar geri alınabilir.',
            ], 422);
        }

        $log->markUndone();

        return response()->json([
            'basarili' => true,
            'log_id' => $log->id,
            'aksiyon_durumu' => 'undone',
            'diff_snapshot' => $log->diff_snapshot,
        ]);
    }

    /**
     * POST /admin/copilot/actions/reject
     *
     * Reject an action suggestion.
     */
    public function reject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id' => 'required|integer|exists:copilot_action_logs,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $log = CopilotActionLog::where('id', $validated['log_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $log->markRejected($validated['reason'] ?? null);

        return response()->json([
            'basarili' => true,
            'log_id' => $log->id,
            'aksiyon_durumu' => 'rejected',
        ]);
    }
}
