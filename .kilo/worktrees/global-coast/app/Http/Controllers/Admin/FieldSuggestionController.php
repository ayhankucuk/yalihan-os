<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\FieldSuggestion\SetSuggestionStatusAction;
use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
use App\Models\AiFieldSuggestion;
use App\Models\AiSuggestionAction;
use App\Services\Logging\LogService;
use App\Services\Wizard\AiFieldSuggestionEngine;
use Illuminate\Http\Request;

/**
 * FieldSuggestionController — Admin governance panel for AI field suggestions.
 *
 * Governance rules:
 * - AI NEVER auto-applies. Admin always approves.
 * - Every action is logged to ai_suggestion_actions.
 * - Rollback always possible.
 * - No silent mutation.
 */
class FieldSuggestionController extends AdminController
{
    use UPSHelperTrait;

    public function __construct(
        private readonly AiFieldSuggestionEngine $engine,
        private readonly SetSuggestionStatusAction $setSuggestionStatusAction,
    ) {
        $this->middleware('can:manage-settings');
    }

    /**
     * List all AI field suggestions with filters.
     *
     * GET /admin/property-hub/field-suggestions
     */
    public function index(Request $request)
    {
        $query = AiFieldSuggestion::query()
            ->with('feature');

        if ($request->filled('oneri_durumu')) {
            $query->where('oneri_durumu', $request->input('oneri_durumu'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('main_category_id')) {
            $query->where('main_category_id', $request->input('main_category_id'));
        }

        if ($request->filled('listing_type_id')) {
            $query->where('listing_type_id', $request->input('listing_type_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        $suggestions = $query->orderByDesc('total_score')
            ->orderByDesc('created_at')
            ->paginate(30);

        // Stats
        $stats = [
            'pending' => AiFieldSuggestion::where('oneri_durumu', 'pending')->count(),
            'approved' => AiFieldSuggestion::where('oneri_durumu', 'approved')->count(),
            'applied' => AiFieldSuggestion::where('oneri_durumu', 'applied')->count(),
            'rejected' => AiFieldSuggestion::where('oneri_durumu', 'rejected')->count(),
            'rolled_back' => AiFieldSuggestion::where('oneri_durumu', 'rolled_back')->count(),
        ];

        // Filter options — Eloquent modelleri üzerinden (DB::table controller yasağı)
        $categories = \App\Models\IlanKategori::select('id', 'name')
            ->orderBy('name') // context7-ignore
            ->get();

        $listingTypes = \App\Models\YayinTipiSablonu::select('id', 'ad')
            ->orderBy('ad') // context7-ignore
            ->get();

        return view('admin.property-hub.field-suggestions.index', compact(
            'suggestions',
            'stats',
            'categories',
            'listingTypes'
        ));
    }

    /**
     * Show suggestion detail with scoring breakdown and action history.
     *
     * GET /admin/property-hub/field-suggestions/{suggestion}
     */
    public function show(AiFieldSuggestion $suggestion)
    {
        $suggestion->load(['actions.user', 'feature', 'appliedAssignment']);

        return view('admin.property-hub.field-suggestions.show', compact('suggestion'));
    }

    /**
     * Generate new suggestions for a category/listing type and persist to DB.
     *
     * POST /admin/property-hub/field-suggestions/generate
     */
    public function generate(Request $request)
    {
        $request->validate([
            'main_category_id' => 'required|integer|exists:ilan_kategorileri,id',
            'sub_category_id' => 'nullable|integer',
            'listing_type_id' => 'required|integer|exists:yayin_tipi_sablonlari,id',
        ]);

        try {
            $result = $this->engine->suggest(
                $request->input('main_category_id'),
                $request->input('sub_category_id'),
                $request->input('listing_type_id'),
                ['max_suggestions' => 20, 'min_score' => 15]
            );

            $persisted = 0;
            foreach ($result['suggestions'] ?? [] as $suggestion) {
                // Skip if already exists with same slug + scope
                $exists = AiFieldSuggestion::where('slug', $suggestion['slug'])
                    ->where('main_category_id', $request->input('main_category_id'))
                    ->where('listing_type_id', $request->input('listing_type_id'))
                    ->whereIn('oneri_durumu', ['pending', 'approved', 'applied'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                AiFieldSuggestion::create([
                    'slug' => $suggestion['slug'],
                    'label' => $suggestion['name'] ?? $suggestion['slug'],
                    'field_type' => $suggestion['type'] ?? null,
                    'group_name' => $suggestion['group'] ?? null,
                    'main_category_id' => $request->input('main_category_id'),
                    'sub_category_id' => $request->input('sub_category_id'),
                    'listing_type_id' => $request->input('listing_type_id'),
                    'reason' => $suggestion['source'] ?? 'ai_analysis',
                    'score_json' => $suggestion['dimensions'] ?? null,
                    'total_score' => $suggestion['total_score'] ?? 0,
                    'priority' => $suggestion['priority'] ?? 'medium',
                    'source' => 'ai_engine',
                    'oneri_durumu' => 'pending',
                    'feature_id' => $suggestion['feature_id'] ?? null,
                ]);

                $persisted++;
            }

            LogService::info('AI field suggestions generated', [
                'main_category_id' => $request->input('main_category_id'),
                'listing_type_id' => $request->input('listing_type_id'),
                'total_from_engine' => count($result['suggestions'] ?? []),
                'persisted' => $persisted,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess(
                "{$persisted} yeni öneri oluşturuldu",
                [
                    'persisted' => $persisted,
                    'total_analyzed' => $result['summary']['total_candidates_analyzed'] ?? 0,
                ]
            );
        } catch (\Exception $e) {
            LogService::error('AI suggestion generation failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->sendUPSError('Öneri oluşturma hatası: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Approve a pending suggestion.
     *
     * POST /admin/property-hub/field-suggestions/{suggestion}/approve
     */
    public function approve(Request $request, AiFieldSuggestion $suggestion)
    {
        if (!$suggestion->isPending()) {
            return $this->sendUPSError('Bu öneri zaten işlem görmüş', [], 422);
        }

        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $suggestion = $this->setSuggestionStatusAction->handle($suggestion, 'approved');

            AiSuggestionAction::create([
                'suggestion_id' => $suggestion->id,
                'action' => 'approve',
                'user_id' => auth()->id(),
                'note' => $request->input('note'),
                'snapshot_json' => $suggestion->toArray(),
            ]);

            LogService::info('AI suggestion approved', [
                'suggestion_id' => $suggestion->id,
                'slug' => $suggestion->slug,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Öneri onaylandı', [
                'suggestion_id' => $suggestion->id,
                'oneri_durumu' => 'approved',
            ]);
        } catch (\Exception $e) {
            LogService::error('FieldSuggestion: approval failed', [
                'suggestion_id' => $suggestion->id,
            ], $e);
            return $this->sendUPSError('Onay hatası: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Reject a pending suggestion.
     *
     * POST /admin/property-hub/field-suggestions/{suggestion}/reject
     */
    public function reject(Request $request, AiFieldSuggestion $suggestion)
    {
        if (!$suggestion->isPending()) {
            return $this->sendUPSError('Bu öneri zaten işlem görmüş', [], 422);
        }

        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $suggestion = $this->setSuggestionStatusAction->handle($suggestion, 'rejected');

            AiSuggestionAction::create([
                'suggestion_id' => $suggestion->id,
                'action' => 'reject',
                'user_id' => auth()->id(),
                'note' => $request->input('note'),
                'snapshot_json' => $suggestion->toArray(),
            ]);

            LogService::info('AI suggestion rejected', [
                'suggestion_id' => $suggestion->id,
                'slug' => $suggestion->slug,
                'reason' => $request->input('note'),
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Öneri reddedildi', [
                'suggestion_id' => $suggestion->id,
                'oneri_durumu' => 'rejected',
            ]);
        } catch (\Exception $e) {
            LogService::error('FieldSuggestion: rejection failed', [
                'suggestion_id' => $suggestion->id,
            ], $e);
            return $this->sendUPSError('Ret hatası: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Apply an approved suggestion — creates actual FeatureAssignment.
     *
     * POST /admin/property-hub/field-suggestions/{suggestion}/apply
     */
    public function apply(Request $request, AiFieldSuggestion $suggestion)
    {
        if (!$suggestion->isApproved()) {
            return $this->sendUPSError('Önce onaylamanız gerekiyor', [], 422);
        }

        $request->validate([
            'note' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'label_override' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:100',
        ]);

        try {
            $result = $this->engine->approveSuggestion(
                $suggestion->feature_id,
                $suggestion->main_category_id,
                $suggestion->sub_category_id,
                $suggestion->listing_type_id,
                [
                    'is_required' => $request->boolean('is_required', false),
                    'label_override' => $request->input('label_override'),
                    'field_type' => $suggestion->field_type,
                    'group_name' => $request->input('group_name', $suggestion->group_name),
                ]
            );

            if (!($result['basarili'] ?? false)) {
                return $this->sendUPSError(
                    $result['hata_mesaji'] ?? 'Uygulama başarısız',
                    $result,
                    422
                );
            }

            $suggestion = $this->setSuggestionStatusAction->handle(
                $suggestion,
                'applied',
                (int) $result['assignment_id']
            );

            AiSuggestionAction::create([
                'suggestion_id' => $suggestion->id,
                'action' => 'apply',
                'user_id' => auth()->id(),
                'note' => $request->input('note'),
                'snapshot_json' => [
                    'assignment_id' => $result['assignment_id'],
                    'feature_slug' => $result['feature_slug'] ?? $suggestion->slug,
                ],
            ]);

            LogService::info('AI suggestion applied', [
                'suggestion_id' => $suggestion->id,
                'assignment_id' => $result['assignment_id'],
                'slug' => $suggestion->slug,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Öneri uygulandı', [
                'suggestion_id' => $suggestion->id,
                'assignment_id' => $result['assignment_id'],
                'oneri_durumu' => 'applied',
            ]);
        } catch (\Exception $e) {
            LogService::error('AI suggestion apply failed', [
                'suggestion_id' => $suggestion->id,
            ], $e);

            return $this->sendUPSError('Uygulama hatası: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Rollback an applied suggestion — soft-deletes the FeatureAssignment.
     *
     * POST /admin/property-hub/field-suggestions/{suggestion}/rollback
     */
    public function rollback(Request $request, AiFieldSuggestion $suggestion)
    {
        if (!$suggestion->isApplied()) {
            return $this->sendUPSError('Sadece uygulanmış öneriler geri alınabilir', [], 422);
        }

        if (!$suggestion->applied_assignment_id) {
            return $this->sendUPSError('Uygulanmış atama bulunamadı', [], 422);
        }

        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->engine->rollbackSuggestion($suggestion->applied_assignment_id);

            if (!($result['basarili'] ?? false)) {
                return $this->sendUPSError(
                    $result['hata_mesaji'] ?? 'Geri alma başarısız',
                    $result,
                    422
                );
            }

            $suggestion = $this->setSuggestionStatusAction->handle($suggestion, 'rolled_back');

            AiSuggestionAction::create([
                'suggestion_id' => $suggestion->id,
                'action' => 'rollback',
                'user_id' => auth()->id(),
                'note' => $request->input('note'),
                'snapshot_json' => [
                    'assignment_id' => $suggestion->applied_assignment_id,
                    'rolled_back_at' => $result['rolled_back_at'] ?? now()->toIso8601String(),
                ],
            ]);

            LogService::info('AI suggestion rolled back', [
                'suggestion_id' => $suggestion->id,
                'assignment_id' => $suggestion->applied_assignment_id,
                'slug' => $suggestion->slug,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Öneri geri alındı', [
                'suggestion_id' => $suggestion->id,
                'oneri_durumu' => 'rolled_back',
            ]);
        } catch (\Exception $e) {
            LogService::error('AI suggestion rollback failed', [
                'suggestion_id' => $suggestion->id,
            ], $e);

            return $this->sendUPSError('Geri alma hatası: ' . $e->getMessage(), [], 500);
        }
    }
}
