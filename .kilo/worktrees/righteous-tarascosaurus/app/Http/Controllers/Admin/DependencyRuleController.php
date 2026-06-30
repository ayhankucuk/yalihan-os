<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DependencyRule\ClearDependencyRulesAction;
use App\Actions\Admin\DependencyRule\UpdateDependencyRulesAction;
use App\Http\Controllers\Admin\Traits\UPSHelperTrait;
use App\Models\FeatureAssignment;
use App\Models\YayinTipiSablonu;
use App\Models\IlanKategori;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;

/**
 * DependencyRuleController — Admin CRUD for feature assignment dependency rules.
 *
 * Manages visible_if_json, required_if_json, enabled_if_json on FeatureAssignment records.
 * All mutations are logged for audit trail.
 */
class DependencyRuleController extends AdminController
{
    use UPSHelperTrait;

    public function __construct(
        private readonly UpdateDependencyRulesAction $updateDependencyRulesAction,
        private readonly ClearDependencyRulesAction $clearDependencyRulesAction,
    )
    {
        $this->middleware('can:manage-settings');
    }

    /**
     * List all feature assignments with dependency rules.
     *
     * GET /admin/property-hub/dependency-rules
     */
    public function index(Request $request)
    {
        $query = FeatureAssignment::with('feature')
            ->whereNull('rolled_back_at')
            ->where(function ($q) {
                $q->whereNotNull('visible_if_json')
                    ->orWhereNotNull('required_if_json')
                    ->orWhereNotNull('enabled_if_json');
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('feature', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('listing_type_id')) {
            $query->where('listing_type_id', $request->input('listing_type_id'));
        }

        if ($request->filled('main_category_id')) {
            $query->where('main_category_id', $request->input('main_category_id'));
        }

        $assignments = $query->orderBy('listing_type_id')
            ->orderBy('display_order')
            ->paginate(30);

        // Get filter options
        $listingTypes = YayinTipiSablonu::select('id', 'ad')
            ->orderBy('ad')
            ->get();

        $categories = IlanKategori::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Also get all assignments for dropdown (for rule targets)
        $allAssignments = FeatureAssignment::with('feature')
            ->whereNull('rolled_back_at')
            ->select('id', 'feature_id', 'field_slug', 'listing_type_id')
            ->get();

        return view('admin.property-hub.dependency-rules.index', compact(
            'assignments',
            'listingTypes',
            'categories',
            'allAssignments'
        ));
    }

    /**
     * Update dependency rules for a feature assignment.
     *
     * PUT /admin/property-hub/dependency-rules/{assignmentId}
     */
    public function update(Request $request, $assignmentId)
    {
        $request->validate([
            'visible_if_json' => 'nullable|json',
            'required_if_json' => 'nullable|json',
            'enabled_if_json' => 'nullable|json',
        ]);

        try {
            $assignment = FeatureAssignment::findOrFail($assignmentId);

            $oldValues = [
                'visible_if_json' => $assignment->visible_if_json,
                'required_if_json' => $assignment->required_if_json,
                'enabled_if_json' => $assignment->enabled_if_json,
            ];

            $updates = [];

            if ($request->has('visible_if_json')) {
                $parsed = $request->input('visible_if_json')
                    ? json_decode($request->input('visible_if_json'), true)
                    : null;
                if ($request->input('visible_if_json') && $parsed === null) {
                    return $this->sendUPSError('visible_if_json geçersiz JSON formatı', [], 422);
                }
                $updates['visible_if_json'] = $parsed;
            }

            if ($request->has('required_if_json')) {
                $parsed = $request->input('required_if_json')
                    ? json_decode($request->input('required_if_json'), true)
                    : null;
                if ($request->input('required_if_json') && $parsed === null) {
                    return $this->sendUPSError('required_if_json geçersiz JSON formatı', [], 422);
                }
                $updates['required_if_json'] = $parsed;
            }

            if ($request->has('enabled_if_json')) {
                $parsed = $request->input('enabled_if_json')
                    ? json_decode($request->input('enabled_if_json'), true)
                    : null;
                if ($request->input('enabled_if_json') && $parsed === null) {
                    return $this->sendUPSError('enabled_if_json geçersiz JSON formatı', [], 422);
                }
                $updates['enabled_if_json'] = $parsed;
            }

            $assignment = $this->updateDependencyRulesAction->handle($assignment, $updates);

            LogService::info('Dependency rules updated', [
                'assignment_id' => $assignmentId,
                'feature_slug' => $assignment->feature?->slug,
                'old_values' => $oldValues,
                'new_values' => $updates,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Bağımlılık kuralları güncellendi', [
                'assignment_id' => $assignment->id,
                'visible_if_json' => $assignment->visible_if_json,
                'required_if_json' => $assignment->required_if_json,
                'enabled_if_json' => $assignment->enabled_if_json,
            ]);
        } catch (\Exception $e) {
            LogService::error('Dependency rule update failed', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendUPSError('Güncelleme hatası: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove all dependency rules from a feature assignment.
     *
     * DELETE /admin/property-hub/dependency-rules/{assignmentId}
     */
    public function destroy(Request $request, $assignmentId)
    {
        try {
            $assignment = FeatureAssignment::findOrFail($assignmentId);

            $oldValues = [
                'visible_if_json' => $assignment->visible_if_json,
                'required_if_json' => $assignment->required_if_json,
                'enabled_if_json' => $assignment->enabled_if_json,
            ];

            $assignment = $this->clearDependencyRulesAction->handle($assignment);

            LogService::info('Dependency rules cleared', [
                'assignment_id' => $assignmentId,
                'feature_slug' => $assignment->feature?->slug,
                'old_values' => $oldValues,
                'user_id' => auth()->id(),
            ]);

            return $this->sendUPSSuccess('Bağımlılık kuralları temizlendi');
        } catch (\Exception $e) {
            LogService::error('Dependency rule clear failed', [
                'assignment_id' => $assignmentId,
            ], $e);
            return $this->sendUPSError('Silme hatası: ' . $e->getMessage(), [], 500);
        }
    }
}
